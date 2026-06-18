<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnimationGenerator
{
    /**
     * Generate animated SVG content using Gemini AI.
     *
     * @param string $prompt
     * @return string SVG content
     */
    public function generate(string $prompt): string
    {
        $systemPrompt = "You are an expert SVG animator. Your task is to generate a fully self-contained, high-quality, 2D animated SVG based on the user's description.

Rules:
1. Return ONLY the valid XML SVG code. No markdown code blocks, no explanation.
2. Use <animate> or CSS @keyframes inside the <style> tag for animation.
3. Make sure the animation loops (repeatCount=\"indefinite\") so it looks good.
4. The SVG should be scalable (viewBox).
5. Ensure vivid colors and smooth movements.
6. The animation should last between 3-5 seconds per cycle.
7. Use a 16:9 composition with viewBox=\"0 0 800 450\".
8. Always include a visible background and visible foreground objects with strong contrast.
9. Do not return a blank, all-black, or nearly all-dark canvas. If using a dark background, add large bright animated elements.
10. Keep all important animated objects inside the viewBox from the first frame.

Example Structure:
<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 800 450\">
  <rect width=\"800\" height=\"450\" fill=\"#f8fafc\" />
  <circle cx=\"120\" cy=\"225\" r=\"35\" fill=\"#ef4444\">
    <animate attributeName=\"cx\" from=\"120\" to=\"680\" dur=\"3s\" repeatCount=\"indefinite\" />
  </circle>
</svg>
";

        $fullPrompt = "Make a 2D animation of: " . $prompt;
        $provider = strtolower((string) config('services.ai.provider', env('AI_PROVIDER', 'gemini')));

        if ($provider === 'chatgpt') {
            $svg = $this->requestChatGpt($systemPrompt, $fullPrompt);
        } else {
            $svg = $this->requestGemini($systemPrompt, $fullPrompt);
        }

        return $this->sanitizeSvg($svg);
    }

    private function requestChatGpt(string $systemPrompt, string $fullPrompt): string
    {
        $endpoint = trim((string) config('services.ai.chatgpt_endpoint', 'https://gpt-api.metaphilia.com/chat'));
        $apiKey = trim((string) config('services.ai.chatgpt_key', ''));
        $mode = strtolower(trim((string) config('services.ai.chatgpt_mode', 'auto')));
        $isOpenAi = $mode === 'openai'
            || ($mode === 'auto' && (
                str_contains($endpoint, 'api.openai.com')
                || str_contains($endpoint, '/chat/completions')
            ));

        if ($endpoint === '') {
            throw new \RuntimeException('LLM endpoint is empty.');
        }

        if ($isOpenAi && $apiKey === '') {
            throw new \RuntimeException('LLM API key is required for the OpenAI API.');
        }

        Log::info('AnimationGenerator: Requesting LLM for SVG animation.');

        $requestFactory = function () use ($apiKey) {
            $request = Http::asJson()
                ->acceptJson()
                ->connectTimeout(max(1, (int) config('services.ai.chatgpt_connect_timeout', 15)))
                ->timeout(max(30, (int) config('services.ai.animation_timeout', config('services.ai.chatgpt_timeout', 120))))
                ->withoutVerifying();

            return $apiKey !== '' ? $request->withToken($apiKey) : $request;
        };

        $response = $this->sendWithRetries(function () use (
            $requestFactory,
            $endpoint,
            $isOpenAi,
            $systemPrompt,
            $fullPrompt
        ) {
            if ($isOpenAi) {
                return $requestFactory()->post($endpoint, [
                    'model' => (string) config('services.ai.chatgpt_model', 'gpt-4.1-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $fullPrompt],
                    ],
                    'temperature' => 0.45,
                    'max_completion_tokens' => min(
                        (int) config('services.ai.chatgpt_max_output_tokens', 16000),
                        6000
                    ),
                    'store' => false,
                ]);
            }

            return $requestFactory()->post($endpoint, [
                'message' => $systemPrompt . "\n\nUser Prompt: " . $fullPrompt,
            ]);
        });

        $this->throwIfFailed($response, 'LLM');

        return $this->extractResponseText($response->json());
    }

    private function requestGemini(string $systemPrompt, string $fullPrompt): string
    {
        $apiKey = trim((string) config('services.ai.gemini_key', env('GEMINI_API_KEY', '')));
        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is missing.');
        }

        Log::info('AnimationGenerator: Requesting Gemini for SVG animation.');

        $response = $this->sendWithRetries(fn () => Http::asJson()
            ->acceptJson()
            ->timeout(max(30, (int) config('services.ai.animation_timeout', 120)))
            ->withoutVerifying()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nUser Prompt: " . $fullPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.45,
                    'maxOutputTokens' => 6000,
                ],
            ]));

        $this->throwIfFailed($response, 'Gemini');

        return (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
    }

    private function sendWithRetries(callable $send): Response
    {
        $attempts = max(1, (int) config('services.ai.animation_retry_attempts', config('services.ai.chatgpt_retry_attempts', 3)));
        $delayMs = max(0, (int) config('services.ai.animation_retry_delay_ms', config('services.ai.chatgpt_retry_delay_ms', 1500)));
        $lastException = null;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $response = $send();

                if (
                    $response->successful()
                    || ! in_array($response->status(), [408, 425, 429, 500, 502, 503, 504], true)
                    || $attempt === $attempts
                ) {
                    return $response;
                }

                Log::warning('AnimationGenerator: Retrying LLM animation request.', [
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                    'status' => $response->status(),
                ]);
            } catch (ConnectionException $e) {
                $lastException = $e;
                if ($attempt === $attempts) {
                    break;
                }

                Log::warning('AnimationGenerator: Retrying LLM animation request after connection error.', [
                    'attempt' => $attempt,
                    'max_attempts' => $attempts,
                    'message' => $e->getMessage(),
                ]);
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        if ($lastException) {
            throw new \RuntimeException('LLM animation request failed: '.$lastException->getMessage(), 0, $lastException);
        }

        throw new \RuntimeException('LLM animation request failed without a response.');
    }

    private function throwIfFailed(Response $response, string $provider): void
    {
        if ($response->successful()) {
            return;
        }

        Log::error("AnimationGenerator: {$provider} API error.", [
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 1000),
        ]);

        throw new \RuntimeException("{$provider} API returned HTTP ".$response->status().'.');
    }

    private function extractResponseText(mixed $data): string
    {
        if (! is_array($data)) {
            return '';
        }

        foreach ([
            $data['response'] ?? null,
            $data['message'] ?? null,
            $data['output_text'] ?? null,
            $data['choices'][0]['message']['content'] ?? null,
            $data['data']['response'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        foreach ($data['output'] ?? [] as $output) {
            foreach (is_array($output['content'] ?? null) ? $output['content'] : [] as $content) {
                if (is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        return '';
    }

    private function sanitizeSvg(string $svg): string
    {
        $svg = trim($svg);
        $svg = preg_replace('/^```(?:xml|svg)?\s*/i', '', (string) $svg);
        $svg = preg_replace('/\s*```$/', '', (string) $svg);
        $svg = trim((string) $svg);

        if (preg_match('/<svg\b[\s\S]*<\/svg>/i', $svg, $matches)) {
            $svg = trim($matches[0]);
        }

        if (! preg_match('/^<svg\b/i', $svg) || ! preg_match('/<\/svg>\s*$/i', $svg)) {
            Log::error('AnimationGenerator: LLM did not return valid SVG.', [
                'text' => mb_substr($svg, 0, 1000),
            ]);

            throw new \RuntimeException('LLM did not return valid SVG animation code.');
        }

        $svg = preg_replace('/<script\b[\s\S]*?<\/script>/i', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*([\'"]).*?\1/i', '', (string) $svg);
        $svg = preg_replace('/\s(?:href|xlink:href)\s*=\s*([\'"])\s*javascript:[\s\S]*?\1/i', '', (string) $svg);

        if (! str_contains($svg, 'xmlns=')) {
            $svg = preg_replace('/<svg\b/i', '<svg xmlns="http://www.w3.org/2000/svg"', $svg, 1);
        }

        return trim((string) $svg);
    }
}
