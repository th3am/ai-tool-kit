<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuestgenService
{
    protected string $apiUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl  = config('services.questgen.url', 'https://quest-gen.eduvoo.com/generate');
        $this->timeout = (int) config('services.questgen.timeout', 180);
    }

    /**
     * Generate MCQ questions from a given text.
     *
     * @param string $text         The source text to generate questions from.
     * @param int    $maxQuestions Maximum number of questions to generate.
     * @return array               Parsed array of question objects.
     * @throws \Exception
     */
    public function generate(string $text, int $maxQuestions = 5): array
    {
        $provider = AppSetting::getValue('quiz_ai_provider', 'questgen');

        return match ($provider) {
            'gemini' => $this->generateWithGemini($text, $maxQuestions),
            'chatgpt' => $this->generateWithChatGpt($text, $maxQuestions),
            default => $this->generateWithQuestgen($text, $maxQuestions),
        };
    }

    private function generateWithQuestgen(string $text, int $maxQuestions): array
    {
        $response = Http::timeout($this->timeout)
            ->post($this->apiUrl, [
                'input_text'    => $text,
                'max_questions' => $maxQuestions,
            ]);

        if ($response->failed()) {
            $detail = $response->json('detail', 'Unknown error from Questgen API');
            throw new \Exception("Questgen API error: {$detail}");
        }

        $data = $response->json();

        if (empty($data['questions'])) {
            throw new \Exception('Questgen API returned no questions. Try using a longer or more detailed text.');
        }

        return $data['questions'];
    }

    private function generateWithGemini(string $text, int $maxQuestions): array
    {
        $apiKey = config('services.ai.gemini_key') ?: env('GEMINI_API_KEY');
        if (! $apiKey) {
            throw new \Exception('Gemini API key is missing.');
        }

        $model = AppSetting::getValue('quiz_ai_model', 'gemini-2.5-flash');
        $prompt = $this->buildAiPrompt($text, $maxQuestions);

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(180)
            ->withoutVerifying()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [[
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        if ($response->failed()) {
            Log::error('Quiz Gemini API error: '.$response->body());
            throw new \Exception('Gemini could not generate quiz questions.');
        }

        $content = $response->json('candidates.0.content.parts.0.text', '');
        return $this->extractQuestions($content, 'Gemini');
    }

    private function generateWithChatGpt(string $text, int $maxQuestions): array
    {
        $endpoint = config('services.ai.chatgpt_endpoint') ?: env('CHATGPT_API_ENDPOINT');
        $apiKey = config('services.ai.chatgpt_key') ?: env('CHATGPT_API_KEY');

        if (! $endpoint || ! $apiKey) {
            throw new \Exception('ChatGPT endpoint or API key is missing.');
        }

        $response = Http::withToken($apiKey)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(180)
            ->withoutVerifying()
            ->post($endpoint, [
                'message' => $this->buildAiPrompt($text, $maxQuestions),
                'model' => AppSetting::getValue('quiz_ai_model', config('services.ai.chatgpt_model', 'gpt-4.1-mini')),
            ]);

        if ($response->failed()) {
            Log::error('Quiz ChatGPT API error: '.$response->body());
            throw new \Exception('ChatGPT could not generate quiz questions.');
        }

        $content = $response->json('response')
            ?? $response->json('choices.0.message.content')
            ?? $response->body();
        return $this->extractQuestions($content, 'ChatGPT');
    }

    private function buildAiPrompt(string $text, int $maxQuestions): string
    {
        return "Create exactly {$maxQuestions} multiple-choice quiz questions from the source text.
Return ONLY valid JSON in this exact shape:
{
  \"questions\": [
    {
      \"question_statement\": \"Question text\",
      \"answer\": \"Correct answer text\",
      \"options\": [\"Wrong option 1\", \"Wrong option 2\", \"Wrong option 3\"]
    }
  ]
}
Rules:
- Every question must have one correct answer and exactly three wrong options.
- Do not repeat the correct answer inside options.
- Keep answers short and clear.
- Use the same language as the source text when possible.

Source text:
{$text}";
    }

    private function extractQuestions(string $content, string $provider): array
    {
        $clean = trim(str_replace(['```json', '```'], '', $content));
        $json = json_decode($clean, true);

        if (! is_array($json) && preg_match('/\{.*\}/s', $clean, $match)) {
            $json = json_decode($match[0], true);
        }

        $questions = $json['questions'] ?? (is_array($json) ? $json : []);

        if (empty($questions) || ! is_array($questions)) {
            Log::error("Quiz {$provider} returned invalid JSON.", ['content' => $content]);
            throw new \Exception("{$provider} returned no valid quiz questions.");
        }

        return $questions;
    }
}
