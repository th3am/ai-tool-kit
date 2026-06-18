<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePresentationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;

    public $sessionId;

    public $toolJobId;

    public $topic;

    public $style;

    public $slideCount;

    public $instructions;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $sessionId, $toolJobId, $topic, $style, $slideCount, $instructions)
    {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->toolJobId = $toolJobId;
        $this->topic = $topic;
        $this->style = $style;
        $this->slideCount = $slideCount;
        $this->instructions = $instructions;
    }

    /**
     * Execute the job.
     */
    public function handle(
        \App\Services\Ai\PresentationGenerator $presentationService,
        \App\Services\PdfGeneratorService $pdfService
    ): void {
        try {
            $job = \App\Models\ToolJob::find($this->toolJobId);
            if (! $job) {
                return;
            }

            if ($job->isCancelled()) {
                return;
            }

            $job->update(['status' => 'running']);

            // 1. Generate Content
            $result = $presentationService->generate(
                $this->topic,
                $this->style,
                $this->slideCount,
                $this->instructions
            );

            $job->refresh();
            if ($job->isCancelled()) {
                return;
            }

            // 2. Save Presentation DB
            $presentation = \App\Models\Presentation::create([
                'user_id' => $this->userId,
                'topic' => $this->topic,
                'content' => $result['slides'],
            ]);

            // 3. Generate PDF (Optional but recommended to preload)
            try {
                $pdfService->generate($presentation);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Background PDF Generation Failed (Non-fatal): '.$e->getMessage());
            }

            // 4. Update Job Success
            $job->update([
                'status' => 'succeeded',
                'results' => ['presentation_id' => $presentation->id],
            ]);

            // 5. Create Assistant Response
            \App\Models\ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'assistant',
                'content' => "I've generated a presentation on **{$this->topic}** for you.",
                'tool_job_id' => $job->id,
            ]);

        } catch (\Exception $e) {
            if (isset($job)) {
                $job->refresh();
                if ($job->isCancelled()) {
                    return;
                }
            }

            \Illuminate\Support\Facades\Log::error('Presentation Job Failed: '.$e->getMessage());

            if (isset($job)) {
                $job->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }
}
