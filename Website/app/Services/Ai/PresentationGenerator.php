<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PresentationGenerator
{
    public function generate(string $topic, string $style = 'Modern', int $slideCount = 5, string $instructions = ''): array
    {
        $provider = strtolower((string) env('PRESENTATION_AI_PROVIDER', 'chatgpt'));
        $fastMode = $this->isFastMode($instructions);
        $prompt = $fastMode
            ? $this->buildFastPrompt($topic, $style, $slideCount, $instructions)
            : $this->buildPrompt($topic, $style, $slideCount, $instructions);
        $text = '';

        try {
            if ($provider === 'gemini') {
                $apiKey = config('services.ai.gemini_key') ?: env('GEMINI_API_KEY');

                if (! $apiKey) {
                    Log::warning('PresentationGenerator: PRESENTATION_AI_PROVIDER=gemini but GEMINI_API_KEY is missing.');
                    return $this->getMockData($topic, $style, $slideCount);
                }

                [$response, $text] = $this->requestGemini($prompt, $apiKey);

                if (! $response->successful()) {
                    Log::error('PresentationGenerator: Gemini API Error: '.$response->body());
                }
            } else {
                [$response, $text] = $this->requestChatGpt($prompt, $slideCount, $fastMode);

                if (! $response->successful()) {
                    Log::error('PresentationGenerator: ChatGPT API Error: '.$response->body());
                }
            }

            $json = $this->decodeJsonResponse($text);

            if (isset($json['slides']) && is_array($json['slides'])) {
                foreach ($json['slides'] as &$slide) {
                    if (! is_string($slide)) {
                        $slide = '';
                        continue;
                    }

                    $watermark = "<div style='position:absolute;bottom:30px;right:30px;font-size:24px;font-weight:700;opacity:.45;font-family:sans-serif;pointer-events:none;z-index:50;color:currentColor;'>EduAI</div>";

                    if (str_ends_with(trim($slide), '</div>')) {
                        $slide = preg_replace('/<\/div>\s*$/', $watermark.'</div>', trim($slide), 1);
                    } else {
                        $slide .= $watermark;
                    }
                }
                unset($slide);

                return $json;
            }

            Log::error('PresentationGenerator: Invalid JSON format received.', ['text' => $text]);
        } catch (\Throwable $e) {
            Log::error('PresentationGenerator: Exception: '.$e->getMessage());
        }

        return $this->getMockData($topic, $style, $slideCount);
    }

    /**
     * @return array{0: Response, 1: string}
     */
    private function requestChatGpt(string $prompt, int $slideCount, bool $fastMode = false): array
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
            throw new \RuntimeException('CHATGPT_API_ENDPOINT is empty.');
        }

        if ($isOpenAi && $apiKey === '') {
            throw new \RuntimeException('CHATGPT_API_KEY is required for the OpenAI API.');
        }

        $attempts = $fastMode ? 1 : max(1, (int) config('services.ai.chatgpt_retry_attempts', 3));
        $delayMs = max(0, (int) config('services.ai.chatgpt_retry_delay_ms', 1500));
        $response = null;

        Log::info('PresentationGenerator: Sending ChatGPT request.', [
            'fast_mode' => $fastMode,
            'slide_count' => $slideCount,
            'endpoint_host' => parse_url($endpoint, PHP_URL_HOST),
        ]);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $request = Http::asJson()
                ->acceptJson()
                ->connectTimeout(max(1, (int) config('services.ai.chatgpt_connect_timeout', 15)))
                ->timeout($fastMode
                    ? max(20, (int) env('CHATGPT_FAST_TIMEOUT', 45))
                    : max(30, (int) config('services.ai.chatgpt_timeout', 120)))
                ->withoutVerifying();

            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            if ($isOpenAi) {
                $response = $request->post($endpoint, [
                    'model' => (string) config('services.ai.chatgpt_model', 'gpt-4.1-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert presentation designer. Return only valid JSON.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_completion_tokens' => min(
                        (int) config('services.ai.chatgpt_max_output_tokens', 16000),
                        $fastMode ? max(1800, $slideCount * 650) : max(3500, $slideCount * 1200)
                    ),
                    'store' => false,
                    'response_format' => ['type' => 'json_object'],
                ]);
            } else {
                $response = $request->post($endpoint, [
                    'message' => $prompt."\nReturn only the JSON object. Do not use code fences.",
                ]);
            }

            if (
                $response->successful()
                || ! in_array($response->status(), [408, 425, 429, 500, 502, 503, 504], true)
                || $attempt === $attempts
            ) {
                break;
            }

            Log::warning('PresentationGenerator: Retrying ChatGPT request.', [
                'attempt' => $attempt,
                'max_attempts' => $attempts,
                'status' => $response->status(),
            ]);

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return [$response, $this->extractResponseText($response->json())];
    }

    /**
     * @return array{0: Response, 1: string}
     */
    private function requestGemini(string $prompt, string $apiKey): array
    {
        $response = Http::asJson()
            ->acceptJson()
            ->timeout(300)
            ->withoutVerifying()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        $data = $response->json();

        return [$response, $data['candidates'][0]['content']['parts'][0]['text'] ?? ''];
    }

    private function extractResponseText(mixed $data): string
    {
        if (! is_array($data)) {
            return '';
        }

        if (isset($data['slides']) && is_array($data['slides'])) {
            return (string) json_encode($data, JSON_UNESCAPED_UNICODE);
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

    private function decodeJsonResponse(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/```\s*$/', '', (string) $clean);
        $clean = trim((string) $clean);

        $json = json_decode($clean, true);
        if (is_array($json)) {
            return $json;
        }

        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $json = json_decode(substr($clean, $start, $end - $start + 1), true);
            if (is_array($json)) {
                return $json;
            }
        }

        return null;
    }

    private function buildPrompt(string $topic, string $style, int $count, string $instructions): string
    {
        $stylePrompts = [
            'Modern' => 'Use a sleek, clean design with gradients (indigo/blue), rounded corners, and sans-serif fonts (Inter/Tajawal). Backgrounds should be white or light gray with colorful accents.',
            'Professional' => 'Use a corporate design with navy blue/gray color scheme, serif headings, and sharp corners. Professional and trustworthy look.',
            'Creative' => 'Use vibrant colors (purple/pink/orange), dynamic shapes, and bold typography. Artistic and engaging.',
            'Minimalist' => 'Use a monochromatic scheme with plenty of whitespace, simple typography, and subtle shadows. Clean and elegant.',
        ];

        $selectedStyle = $stylePrompts[$style] ?? $stylePrompts['Modern'];
        $extra = trim($instructions) !== '' ? "Additional instructions: {$instructions}" : 'No additional instructions.';

        return <<<PROMPT
Role: You are an expert presentation designer and frontend developer.
Task: Create a professional HTML presentation about: "{$topic}".
{$extra}

Return ONLY valid JSON:
{"slides":["<div class='slide w-full h-full overflow-hidden'>...</div>"]}

Requirements:
1. Generate exactly {$count} slides.
2. Each slide must be one HTML string using Tailwind CSS classes.
3. Root div of every slide must include: w-full h-full overflow-hidden relative.
4. Use presentation-size typography:
   - H1 titles: text-6xl or larger.
   - H2 subtitles: text-5xl or larger.
   - Body text: text-3xl or text-4xl.
   - Spacious padding, usually p-12 or larger.
5. Slide plan:
   - Slide 1: title slide with title, subtitle, and "EduAI".
   - Middle slides: key concepts with bullets, visual hierarchy, icons, or simple diagrams.
   - Final slide: conclusion or thank you.
6. Visuals:
   - Include at least one relevant inline SVG illustration in every slide.
   - Do not use external image URLs or img tags.
   - Do not use markdown code fences.
7. Style guide: {$selectedStyle}

Output strictly JSON and nothing else.
PROMPT;
    }

    private function buildFastPrompt(string $topic, string $style, int $count, string $instructions): string
    {
        $count = max(3, min(6, $count));
        $extra = trim(str_replace('[WHATSAPP_FAST]', '', $instructions));
        $extra = $extra !== '' ? "Extra context: {$extra}" : 'No extra context.';

        return <<<PROMPT
Create exactly {$count} concise presentation slides about "{$topic}".
{$extra}

Return ONLY valid JSON in this shape:
{"slides":["<div class='w-full h-full overflow-hidden relative p-12 bg-white text-slate-900'>...</div>"]}

Rules:
1. Keep each slide HTML under 900 characters.
2. Use Tailwind CSS classes only.
3. Do not use external images, img tags, or inline SVG.
4. Use a title slide, simple content slides, and a final summary slide.
5. Body text must be readable for presentation export: headings text-5xl, body text-2xl or text-3xl.
6. Output JSON only. No markdown, no code fences, no explanation.
PROMPT;
    }

    private function isFastMode(string $instructions): bool
    {
        return str_contains($instructions, '[WHATSAPP_FAST]');
    }

    private function getMockData(string $topic, string $style, int $slideCount = 5): array
    {
        $colors = match ($style) {
            'Creative' => 'from-pink-500 to-orange-500',
            'Professional' => 'from-gray-700 to-gray-900',
            'Minimalist' => 'from-gray-100 to-gray-200 text-gray-800',
            default => 'from-blue-600 to-indigo-800',
        };

        $safeTopic = htmlspecialchars($topic, ENT_QUOTES, 'UTF-8');
        $count = max(3, min(5, $slideCount));
        $slides = [
            "<div class='w-full h-full overflow-hidden relative flex flex-col items-center justify-center p-12 text-center bg-gradient-to-br {$colors} text-white rounded-xl shadow-2xl'><h1 class='text-6xl font-bold mb-6'>{$safeTopic}</h1><p class='text-3xl opacity-90'>Generated with EduAI</p></div>",
            "<div class='w-full h-full overflow-hidden relative p-12 bg-white text-slate-900 flex flex-col justify-center rounded-xl'><p class='text-lg uppercase tracking-widest text-indigo-600 font-bold mb-4'>Overview</p><h2 class='text-5xl font-bold mb-8'>What this presentation covers</h2><ul class='space-y-5 text-3xl text-slate-700'><li>Core idea and purpose</li><li>Important concepts to understand</li><li>Practical example and use cases</li></ul></div>",
            "<div class='w-full h-full overflow-hidden relative p-12 bg-slate-950 text-white rounded-xl'><p class='text-lg uppercase tracking-widest text-cyan-300 font-bold mb-4'>Key Ideas</p><h2 class='text-5xl font-bold mb-8'>{$safeTopic}</h2><div class='grid grid-cols-2 gap-6 text-2xl'><div class='rounded-2xl bg-white/10 p-6'>Break the topic into clear parts.</div><div class='rounded-2xl bg-white/10 p-6'>Focus on the most useful details.</div><div class='rounded-2xl bg-white/10 p-6'>Use examples to make it memorable.</div><div class='rounded-2xl bg-white/10 p-6'>Connect the lesson to real usage.</div></div></div>",
            "<div class='w-full h-full overflow-hidden relative p-12 bg-indigo-50 text-slate-900 rounded-xl flex flex-col justify-center'><p class='text-lg uppercase tracking-widest text-indigo-700 font-bold mb-4'>Example</p><h2 class='text-5xl font-bold mb-8'>How to apply it</h2><p class='text-3xl leading-relaxed text-slate-700'>Start with the main question, identify the important information, then turn each part into a clear step or decision.</p></div>",
            "<div class='w-full h-full overflow-hidden relative p-12 bg-gradient-to-br from-emerald-500 to-teal-700 text-white rounded-xl flex flex-col justify-center'><p class='text-lg uppercase tracking-widest text-white/80 font-bold mb-4'>Summary</p><h2 class='text-5xl font-bold mb-8'>Main Takeaway</h2><p class='text-3xl leading-relaxed'>A strong presentation explains {$safeTopic} with clear structure, useful examples, and simple next steps.</p></div>",
        ];

        return [
            'slides' => array_slice($slides, 0, $count),
        ];
    }
}
