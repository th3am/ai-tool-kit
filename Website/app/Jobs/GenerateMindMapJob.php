<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Models\ToolJob;
use App\Services\Ai\MindMapGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMindMapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        protected int|string $sessionId,
        protected int $toolJobId,
        protected string $topic
    ) {}

    public function handle(MindMapGenerator $generator): void
    {
        $job = ToolJob::find($this->toolJobId);

        if (! $job || $job->isCancelled()) {
            return;
        }

        $job->update(['status' => 'running']);

        try {
            $markdown = $generator->generate($this->topic);

            $job->refresh();
            if ($job->isCancelled()) {
                return;
            }

            $job->update([
                'status' => 'succeeded',
                'results' => ['raw_markdown' => $markdown],
            ]);

            ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'assistant',
                'content' => "Your mind map for **{$this->topic}** is ready.",
                'tool_job_id' => $job->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('GenerateMindMapJob failed.', ['message' => $e->getMessage()]);

            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
