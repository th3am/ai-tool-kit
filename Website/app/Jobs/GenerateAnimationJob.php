<?php

namespace App\Jobs;

use App\Models\ToolJob;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Ai\AnimationGenerator;
use App\Services\CreditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateAnimationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;

    protected $prompt;

    protected $userId;

    protected $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $sessionId, $jobId, $prompt)
    {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->jobId = $jobId;
        $this->prompt = $prompt;
    }

    /**
     * Execute the job.
     */
    public function handle(AnimationGenerator $generator, CreditService $creditService)
    {
        $toolJob = ToolJob::find($this->jobId);

        if (! $toolJob) {
            Log::error("GenerateAnimationJob: ToolJob not found ID {$this->jobId}");

            return;
        }

        if ($toolJob->isCancelled()) {
            return;
        }

        $toolJob->update(['status' => 'running']);

        try {
            // 1. Generate SVG
            $svgContent = $generator->generate($this->prompt);

            $toolJob->refresh();
            if ($toolJob->isCancelled()) {
                return;
            }

            // 2. Save SVG to public storage
            $fileName = 'animations/anim-'.$this->jobId.'-'.time().'.svg';
            Storage::put('public/'.$fileName, $svgContent);

            // 3. Update Job
            $toolJob->update([
                'status' => 'succeeded',
                'results' => [
                    'svg_path' => $fileName,
                    'svg_content' => $svgContent,
                ],
            ]);

            ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'assistant',
                'content' => "Your 2D animation for **{$this->prompt}** is ready.",
                'tool_job_id' => $toolJob->id,
            ]);

            // 4. Trigger Video Conversion (Background)
            \App\Jobs\ConvertAnimationJob::dispatch($this->jobId);

        } catch (\Exception $e) {
            $toolJob->refresh();
            if ($toolJob->isCancelled()) {
                return;
            }

            Log::error('GenerateAnimationJob Failed: '.$e->getMessage());
            $toolJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'results' => $this->refundCreditsOnce($toolJob, $creditService),
            ]);
        }
    }

    private function refundCreditsOnce(ToolJob $toolJob, CreditService $creditService): array
    {
        $results = is_array($toolJob->results) ? $toolJob->results : [];

        if (! empty($results['credits_refunded'])) {
            return $results;
        }

        if (empty($toolJob->params['credits_charged'])) {
            return $results;
        }

        $user = User::find($this->userId);
        if (! $user) {
            Log::warning("GenerateAnimationJob: Could not refund credits; User#{$this->userId} not found.");

            return $results;
        }

        $creditService->refund($user, 'video-animation');
        $results['credits_refunded'] = true;

        return $results;
    }
}
