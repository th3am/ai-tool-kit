<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Models\ToolJob;
use App\Models\User;
use App\Services\CreditService;
use App\Services\VideoExplainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateVideoExplainerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Up to 30 scenes can require substantial screenshot, TTS, and FFmpeg time.
     */
    public int $timeout = 1800;

    protected int $userId;

    protected string|int $sessionId;

    protected string|int $toolJobId;

    protected string $topic;

    protected string $style;

    protected int $slideCount;

    protected string $instructions;

    protected string $language;

    protected bool $enableCaptions;

    public function __construct(
        int|string $userId,
        int|string $sessionId,
        int|string $toolJobId,
        string $topic,
        string $style = 'Modern',
        int $slideCount = 5,
        string $instructions = '',
        string $language = 'ar',
        bool $enableCaptions = true
    ) {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->toolJobId = $toolJobId;
        $this->topic = $topic;
        $this->style = $style;
        $this->slideCount = max(3, min(30, $slideCount));
        $this->instructions = $instructions;
        $this->language = $language;
        $this->enableCaptions = $enableCaptions;
    }

    /**
     * Execute the job.
     */
    public function handle(VideoExplainerService $service, CreditService $creditService): void
    {
        $job = ToolJob::find($this->toolJobId);

        if (! $job) {
            Log::error("GenerateVideoExplainerJob: ToolJob #{$this->toolJobId} not found.");

            return;
        }

        if ($job->isCancelled()) {
            Log::info("GenerateVideoExplainerJob: Job #{$job->id} was cancelled before starting.");

            return;
        }

        $job->update(['status' => 'running']);
        $startTime = microtime(true);

        try {
            Log::info("GenerateVideoExplainerJob: Starting – topic='{$this->topic}', lang={$this->language}, slides={$this->slideCount}");

            $videoPath = $service->generate(
                $this->topic,
                $this->style,
                $this->slideCount,
                $this->instructions,
                $this->language,
                $this->enableCaptions,
                fn (): bool => ToolJob::whereKey($job->id)
                    ->where('status', ToolJob::STATUS_CANCELLED)
                    ->exists()
            );

            $job->refresh();
            if ($job->isCancelled()) {
                Storage::disk('public')->delete($videoPath);
                Log::info("GenerateVideoExplainerJob: Job #{$job->id} was cancelled before completion.");

                return;
            }

            $duration = round(microtime(true) - $startTime, 1);

            $job->update([
                'status' => 'succeeded',
                'results' => ['video_path' => $videoPath],
            ]);

            Log::info("GenerateVideoExplainerJob: Completed in {$duration}s → {$videoPath}");

            ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'assistant',
                'content' => "✅ Your video explainer on **{$this->topic}** is ready! (took {$duration}s)",
                'tool_job_id' => $job->id,
            ]);

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 1);

            $job->refresh();
            if ($job->isCancelled()) {
                Log::info("GenerateVideoExplainerJob: Cancelled after {$duration}s.");

                return;
            }

            Log::error("GenerateVideoExplainerJob: Failed after {$duration}s – ".$e->getMessage());

            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'results' => $this->refundCreditsOnce($job, $creditService),
            ]);

            ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'system',
                'content' => '❌ Video Explainer generation failed: '.$e->getMessage(),
            ]);
        }
    }

    private function refundCreditsOnce(ToolJob $job, CreditService $creditService): array
    {
        $results = is_array($job->results) ? $job->results : [];

        if (! empty($results['credits_refunded'])) {
            return $results;
        }

        $toolType = (string) $job->tool_type;
        if (! in_array($toolType, ['video-explainer', 'lecture'], true)) {
            return $results;
        }

        $user = User::find($this->userId);
        if (! $user) {
            Log::warning("GenerateVideoExplainerJob: Could not refund credits; User#{$this->userId} not found.");

            return $results;
        }

        $creditService->refund($user, $toolType);
        $results['credits_refunded'] = true;

        return $results;
    }
}
