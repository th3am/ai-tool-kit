<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Models\ToolJob;
use App\Services\TtsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    protected $sessionId;

    protected $toolJobId;

    protected $inputContent; // Text or file path

    protected $inputType;    // 'text' or 'file'

    /**
     * Create a new job instance.
     *
     * @param  int  $userId
     * @param  int  $sessionId
     * @param  int  $toolJobId
     * @param  string  $inputContent  Text prompt or absolute path to file
     * @param  string  $inputType  'text' or 'file'
     */
    public function __construct($userId, $sessionId, $toolJobId, $inputContent, $inputType = 'text')
    {
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->toolJobId = $toolJobId;
        $this->inputContent = $inputContent;
        $this->inputType = $inputType;
    }

    /**
     * Execute the job.
     */
    public function handle(TtsService $ttsService): void
    {
        $job = ToolJob::find($this->toolJobId);
        if (! $job) {
            Log::error("GenerateAudioJob: ToolJob not found ID {$this->toolJobId}");

            return;
        }

        if ($job->isCancelled()) {
            return;
        }

        $job->update(['status' => 'running']);

        try {
            $narrationScript = '';
            $startTime = microtime(true);

            // Step 1: Generate Script via Gemini (Only if file or long text)
            // Optimization: Skip Gemini for short text (< 1000 chars) to speed up process
            if ($this->inputType === 'file' || strlen($this->inputContent) > 1000) {
                Log::info("GenerateAudioJob: Starting Gemini summarization for Job {$this->toolJobId}");
                $narrationScript = $this->generateScriptWithGemini($this->inputContent, $this->inputType);
            } else {
                Log::info("GenerateAudioJob: Skipping Gemini (Short Text) for Job {$this->toolJobId}");
                $narrationScript = $this->inputContent;
            }

            if (! $narrationScript) {
                throw new \Exception('Failed to generate narration script.');
            }

            // Step 2: Generate Audio
            Log::info("GenerateAudioJob: Starting TTS generation for Job {$this->toolJobId}");
            $audioPath = $ttsService->generateAudio($narrationScript);

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("GenerateAudioJob: Completed in {$duration}s. Audio Path: {$audioPath}");

            if (! $audioPath) {
                throw new \Exception('Failed to generate audio file.');
            }

            $job->refresh();
            if ($job->isCancelled()) {
                return;
            }

            // Step 3: Update Job & Notify
            $job->update([
                'status' => 'succeeded',
                'results' => [
                    'audio_path' => $audioPath,
                    'script' => $narrationScript,
                ],
            ]);

            ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'assistant',
                'content' => "I've generated the audio narration for you (took {$duration}s).",
                'tool_job_id' => $job->id,
            ]);

        } catch (\Exception $e) {
            $job->refresh();
            if ($job->isCancelled()) {
                return;
            }

            Log::error('GenerateAudioJob Error: '.$e->getMessage());
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            ChatMessage::create([
                'session_id' => $this->sessionId,
                'role' => 'system',
                'content' => 'Audio Generation Failed: '.$e->getMessage(),
            ]);
        }
    }

    private function generateScriptWithGemini($content, $type)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (! $apiKey) {
            return null;
        }

        $prompt = 'Summarize this content into an engaging, clear, and concise narration script suitable for speech explanation. Start directly with the narration, no intro text.';

        $parts = [
            ['text' => $type === 'text' ? $prompt."\n\nContent:\n".$content : $prompt],
        ];

        if ($type === 'file') {
            // Expecting $content to be an absolute path
            if (! file_exists($content)) {
                throw new \Exception('File not found for processing.');
            }

            $mimeType = mime_content_type($content);
            $data = file_get_contents($content);
            $base64Data = base64_encode($data);

            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $base64Data,
                ],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $body = [
            'contents' => [['parts' => $parts]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1000],
        ];

        $response = \Illuminate\Support\Facades\Http::post($url, $body);

        if ($response->successful()) {
            $data = $response->json();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        Log::error('Gemini Script Gen Error: '.$response->body());

        return null;
    }
}
