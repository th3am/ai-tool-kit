<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappIntentDetector
{
    /**
     * @return array{intent:string, tool:?string, topic:string, language:?string, slide_count:?int, question_count:?int, reply:string}
     */
    public function detect(string $message): array
    {
        $message = trim($message);
        $fallback = $this->heuristic($message);
        $endpoint = trim((string) config('services.ai.chatgpt_endpoint'));

        if ($message === '' || $endpoint === '') {
            return $fallback;
        }

        try {
            $request = Http::asJson()
                ->acceptJson()
                ->timeout(45)
                ->withoutVerifying();

            $apiKey = trim((string) config('services.ai.chatgpt_key'));
            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post($endpoint, [
                'message' => $this->prompt($message),
                'model' => (string) config('services.ai.chatgpt_model', 'gpt-4.1-mini'),
            ]);

            if ($response->failed()) {
                Log::warning('WhatsappIntentDetector: ChatGPT proxy failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $fallback;
            }

            $content = $response->json('response')
                ?? $response->json('choices.0.message.content')
                ?? $response->body();

            return $this->normalize($content, $fallback);
        } catch (\Throwable $e) {
            Log::warning('WhatsappIntentDetector: detection failed.', ['message' => $e->getMessage()]);
            return $fallback;
        }
    }

    private function prompt(string $message): string
    {
        return <<<PROMPT
You route WhatsApp messages for an AI education platform.
Return ONLY JSON. No markdown.

Allowed intents:
- tool_request: user asks to create one tool result
- info: user asks about account, credits, plan, pricing, help, supported tools
- chat: normal conversation or unclear message

Allowed tools:
- presentation
- mindmap
- video-explainer
- audio
- video-animation
- quiz

JSON schema:
{
  "intent": "tool_request|info|chat",
  "tool": "presentation|mindmap|video-explainer|audio|video-animation|quiz|null",
  "topic": "clean topic or source text",
  "language": "ar|en|null",
  "slide_count": 5,
  "question_count": 5,
  "reply": "short helpful WhatsApp reply"
}

Rules:
- If the user says presentation, powerpoint, slides: tool is presentation.
- If mind map, map, markmap: tool is mindmap.
- If video explainer, explain video, lecture video: tool is video-explainer.
- If voice, audio, narration, recorder: tool is audio.
- If animation, 2d animation: tool is video-animation.
- If quiz, questions, exam, MCQ: tool is quiz.
- Extract the requested topic/source text.
- Use Arabic reply if the user wrote Arabic.

User message:
{$message}
PROMPT;
    }

    private function normalize(string $content, array $fallback): array
    {
        $clean = trim(str_replace(['```json', '```'], '', $content));
        $json = json_decode($clean, true);

        if (! is_array($json) && preg_match('/\{.*\}/s', $clean, $match)) {
            $json = json_decode($match[0], true);
        }

        if (! is_array($json)) {
            return $fallback;
        }

        $intent = in_array($json['intent'] ?? '', ['tool_request', 'info', 'chat'], true)
            ? $json['intent']
            : $fallback['intent'];
        $tool = $this->validTool($json['tool'] ?? null) ?: null;

        return [
            'intent' => $intent,
            'tool' => $tool,
            'topic' => trim((string) ($json['topic'] ?? $fallback['topic'])),
            'language' => $json['language'] ?? null,
            'slide_count' => $this->boundedInt($json['slide_count'] ?? null, 3, 15),
            'question_count' => $this->boundedInt($json['question_count'] ?? null, 3, 20),
            'reply' => trim((string) ($json['reply'] ?? $fallback['reply'])),
        ];
    }

    private function heuristic(string $message): array
    {
        $lower = mb_strtolower($message);
        $tool = null;

        foreach ([
            'presentation' => ['presentation', 'powerpoint', 'slides', 'ppt', 'عرض', 'بوربوينت'],
            'mindmap' => ['mindmap', 'mind map', 'markmap', 'خريطة ذهنية', 'mind'],
            'video-explainer' => ['video explainer', 'explainer', 'lecture video', 'شرح فيديو', 'فيديو'],
            'audio' => ['audio', 'voice', 'narration', 'tts', 'صوت', 'تعليق صوتي'],
            'video-animation' => ['animation', '2d', 'animated', 'انيميشن', 'رسوم'],
            'quiz' => ['quiz', 'mcq', 'questions', 'exam', 'اختبار', 'اسئلة', 'أسئلة'],
        ] as $candidate => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($lower, $needle)) {
                    $tool = $candidate;
                    break 2;
                }
            }
        }

        $info = str_contains($lower, 'credit') || str_contains($lower, 'plan') || str_contains($lower, 'help')
            || str_contains($lower, 'رصيد') || str_contains($lower, 'اشتراك') || str_contains($lower, 'مساعدة');

        return [
            'intent' => $tool ? 'tool_request' : ($info ? 'info' : 'chat'),
            'tool' => $tool,
            'topic' => $message,
            'language' => preg_match('/\p{Arabic}/u', $message) ? 'ar' : 'en',
            'slide_count' => 5,
            'question_count' => 5,
            'reply' => $tool ? 'I will start your request now.' : 'How can I help you with EduAI tools?',
        ];
    }

    private function validTool(mixed $tool): ?string
    {
        return in_array($tool, ['presentation', 'mindmap', 'video-explainer', 'audio', 'video-animation', 'quiz'], true)
            ? $tool
            : null;
    }

    private function boundedInt(mixed $value, int $min, int $max): int
    {
        $value = (int) $value;
        return max($min, min($max, $value ?: $min));
    }
}
