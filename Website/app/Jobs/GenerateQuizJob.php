<?php

namespace App\Jobs;

use App\Models\Quiz;
use App\Models\ChatMessage;
use App\Models\ToolJob;
use App\Services\QuestgenService;
use App\Services\QuizService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // Allow up to 5 minutes for AI generation
    public int $tries   = 2;

    public function __construct(
        protected Quiz $quiz,
        protected ?int $toolJobId = null,
        protected int|string|null $sessionId = null
    ) {}

    public function handle(QuestgenService $questgen, QuizService $quizService): void
    {
        $toolJob = $this->toolJobId ? ToolJob::find($this->toolJobId) : null;

        // Mark as processing
        $this->quiz->update(['status' => 'processing']);
        $toolJob?->update(['status' => 'running']);

        try {
            // Call the Questgen API
            $questions = $questgen->generate(
                $this->quiz->source_text,
                $this->quiz->max_questions
            );

            // Save questions to database
            $quizService->saveQuestions($this->quiz, $questions);

            // Mark as done
            $this->quiz->update(['status' => 'done']);
            $toolJob?->update([
                'status' => 'succeeded',
                'results' => ['quiz_id' => $this->quiz->id],
            ]);

            if ($this->sessionId) {
                ChatMessage::create([
                    'session_id' => $this->sessionId,
                    'role' => 'assistant',
                    'content' => "Your quiz **{$this->quiz->title}** is ready.",
                    'tool_job_id' => $toolJob?->id,
                ]);
            }

            Log::info("Quiz #{$this->quiz->id} generated successfully with " . count($questions) . " questions.");

        } catch (\Exception $e) {
            Log::error("Quiz #{$this->quiz->id} generation failed: " . $e->getMessage());

            $this->quiz->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $toolJob?->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            if ($this->sessionId) {
                ChatMessage::create([
                    'session_id' => $this->sessionId,
                    'role' => 'system',
                    'content' => 'Quiz generation failed: '.$e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->quiz->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        if ($this->toolJobId) {
            ToolJob::whereKey($this->toolJobId)->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
