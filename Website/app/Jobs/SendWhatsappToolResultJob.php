<?php

namespace App\Jobs;

use App\Models\Presentation;
use App\Models\Quiz;
use App\Models\ToolJob;
use App\Services\Whatsapp\MetaphiliaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SendWhatsappToolResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 80;
    public int $timeout = 120;

    public function __construct(
        protected int $toolJobId,
        protected string $recipientNumber
    ) {}

    public function handle(MetaphiliaClient $client): void
    {
        $job = ToolJob::find($this->toolJobId);

        if (! $job) {
            return;
        }

        if (in_array($job->status, ['queued', 'pending', 'running'], true)) {
            $this->release(30);
            return;
        }

        if ($job->status === 'failed') {
            $client->sendMessage(
                $this->recipientNumber,
                'Sorry, your EduAI job failed: '.($job->error_message ?: 'Unknown error.')
            );
            return;
        }

        if ($job->status !== 'succeeded') {
            return;
        }

        try {
            match ($job->tool_type) {
                'presentation' => $this->sendPresentation($client, $job),
                'mindmap' => $this->sendMindmap($client, $job),
                'audio' => $this->sendAudio($client, $job),
                'video-animation' => $this->sendAnimation($client, $job),
                'video-explainer', 'lecture' => $this->sendVideoExplainer($client, $job),
                'quiz' => $this->sendQuiz($client, $job),
                default => $client->sendMessage($this->recipientNumber, 'Your EduAI job is ready.'),
            };
        } catch (\Throwable $e) {
            Log::error('SendWhatsappToolResultJob failed.', [
                'job_id' => $job->id,
                'message' => $e->getMessage(),
            ]);

            $client->sendMessage($this->recipientNumber, 'Your EduAI result is ready, but I could not send the file automatically.');
        }
    }

    private function sendPresentation(MetaphiliaClient $client, ToolJob $job): void
    {
        $presentationId = $job->results['presentation_id'] ?? null;
        $presentation = $presentationId ? Presentation::find($presentationId) : null;

        if (! $presentation) {
            $client->sendMessage($this->recipientNumber, 'Your presentation is ready, but the file record was not found.');
            return;
        }

        $topic = $job->params['topic'] ?? 'presentation';
        $pdfUrl = $this->signedRoute('api.download.presentation.pdf', ['presentation' => $presentation->id]);
        $pptUrl = $this->signedRoute('api.download.presentation.ppt', ['presentation' => $presentation->id]);

        $client->sendMedia($this->recipientNumber, 'document', $pdfUrl, "Your presentation PDF is ready: {$topic}");
        $client->sendMedia($this->recipientNumber, 'document', $pptUrl, 'PowerPoint version.');
    }

    private function sendMindmap(MetaphiliaClient $client, ToolJob $job): void
    {
        $topic = $job->params['topic'] ?? 'mind map';
        $pngUrl = $this->signedRoute('api.download.mindmap.png', ['job' => $job->id]);
        $svgUrl = $this->signedRoute('api.download.mindmap.svg', ['job' => $job->id]);

        $client->sendMedia($this->recipientNumber, 'image', $pngUrl, "Your mind map is ready: {$topic}");
        $client->sendMedia($this->recipientNumber, 'document', $svgUrl, 'Interactive SVG version.');
    }

    private function sendAudio(MetaphiliaClient $client, ToolJob $job): void
    {
        $url = $this->signedRoute('api.download.audio', ['job' => $job->id]);
        $client->sendMedia($this->recipientNumber, 'audio', $url, 'Your audio narration is ready.');
    }

    private function sendAnimation(MetaphiliaClient $client, ToolJob $job): void
    {
        $url = $this->signedRoute('api.download.animation', ['job' => $job->id, 'format' => 'svg']);
        $client->sendMedia($this->recipientNumber, 'document', $url, 'Your 2D animation SVG is ready.');
    }

    private function sendVideoExplainer(MetaphiliaClient $client, ToolJob $job): void
    {
        $topic = $job->params['topic'] ?? 'video explainer';
        $url = $this->signedRoute('api.download.video-explainer', ['job' => $job->id]);

        $client->sendMedia($this->recipientNumber, 'video', $url, "Your video explainer is ready: {$topic}");
    }

    private function sendQuiz(MetaphiliaClient $client, ToolJob $job): void
    {
        $quizId = $job->results['quiz_id'] ?? null;
        $quiz = $quizId ? Quiz::withCount('questions')->find($quizId) : null;

        if (! $quiz) {
            $client->sendMessage($this->recipientNumber, 'Your quiz is ready, but I could not find the quiz link.');
            return;
        }

        if (! $quiz->is_public) {
            $quiz->update(['is_public' => true]);
        }

        $client->sendMessage(
            $this->recipientNumber,
            "Your quiz is ready.\nTitle: {$quiz->title}\nQuestions: {$quiz->questions_count}\nPublic link: {$quiz->share_url}"
        );
    }

    private function signedRoute(string $name, array $parameters): string
    {
        return URL::temporarySignedRoute($name, now()->addDay(), $parameters);
    }
}
