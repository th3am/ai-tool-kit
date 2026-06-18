<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MindMapGenerator
{
    /**
     * Generate mind map content as Markmap-compatible Markdown.
     *
     * @param string $topic
     * @param mixed $file (Optional uploaded file)
     */
    public function generate(string $topic, $file = null): string
    {
        $provider = strtolower((string) config('services.ai.provider', 'chatgpt'));
        $prompt = $this->buildPrompt($topic, (bool) $file);

        try {
            if ($provider === 'gemini') {
                $markdown = $this->requestGemini($prompt, $file);
            } else {
                $markdown = $this->requestChatGpt($prompt);
            }
        } catch (\Throwable $e) {
            Log::error('MindMapGenerator: AI request failed.', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            return "# Error\n- Failed to connect to AI\n- ".$e->getMessage();
        }

        $markdown = $this->cleanMarkdown($markdown);

        if ($markdown === '') {
            return "# Error\n- No content generated";
        }

        Log::info('MindMapGenerator: cleaned markdown generated.', ['markdown' => $markdown]);

        return $markdown;
    }

    private function buildPrompt(string $topic, bool $hasFile): string
    {
        $fileNote = $hasFile
            ? 'The user uploaded a file. Use the topic and any extracted context available from the request.'
            : 'No file was uploaded.';

        return <<<PROMPT
You are an expert at creating interactive mind maps.

Return ONLY valid JSON between the exact markers MINDMAP_JSON_START and MINDMAP_JSON_END.
Do not include explanations, thinking text, markdown, or code fences.

JSON schema:
{
  "title": "Main Topic",
  "branches": [
    {
      "label": "Branch name",
      "children": [
        "Short detail",
        {
          "label": "Nested branch",
          "children": ["Nested detail"]
        }
      ]
    }
  ]
}

Rules:
1. Use 3 to 6 main branches.
2. Keep labels short and readable.
3. Include useful children under each main branch.
4. The output must be parseable JSON.

Topic/context: {$topic}
File context: {$fileNote}

Example:
MINDMAP_JSON_START
{"title":"Binary Search","branches":[{"label":"Definition","children":["Fast search algorithm","Works on sorted lists"]},{"label":"How It Works","children":["Check middle element","Choose left or right half","Repeat until found"]}]}
MINDMAP_JSON_END
PROMPT;
    }

    private function requestChatGpt(string $prompt): string
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

        $request = Http::asJson()
            ->acceptJson()
            ->connectTimeout(max(1, (int) config('services.ai.chatgpt_connect_timeout', 15)))
            ->timeout(max(30, (int) config('services.ai.chatgpt_timeout', 120)))
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
                        'content' => 'Return only valid JSON for a mind map. Do not include markdown or explanations.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.35,
                'max_completion_tokens' => 3000,
                'store' => false,
            ]);
        } else {
            $response = $request->post($endpoint, [
                'message' => $prompt,
            ]);
        }

        $this->throwIfFailed($response, 'ChatGPT');

        return $this->extractResponseText($response->json());
    }

    private function requestGemini(string $prompt, mixed $file = null): string
    {
        $apiKey = trim((string) config('services.ai.gemini_key', ''));

        if ($apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY is missing.');
        }

        $parts = [
            ['text' => $prompt],
        ];

        if ($file) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $file->getMimeType(),
                    'data' => base64_encode((string) file_get_contents($file->getRealPath())),
                ],
            ];
        }

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(120)
            ->withoutVerifying()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => $parts],
                ],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'maxOutputTokens' => 3000,
                ],
            ]);

        $this->throwIfFailed($response, 'Gemini');

        $data = $response->json();

        return (string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    private function throwIfFailed(Response $response, string $provider): void
    {
        if (! $response->successful()) {
            Log::error("MindMapGenerator: {$provider} API request failed.", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("{$provider} API returned HTTP ".$response->status().'.');
        }
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

    private function cleanMarkdown(string $markdown): string
    {
        $markdown = $this->isolateUsefulResponse($markdown);

        $jsonMarkdown = $this->jsonResponseToMarkdown($markdown);
        if ($jsonMarkdown !== null) {
            return $jsonMarkdown;
        }

        $markdown = trim($markdown);
        $markdown = preg_replace('/^```(?:markdown|md)?\s*/i', '', (string) $markdown);
        $markdown = preg_replace('/```\s*$/', '', (string) $markdown);
        $markdown = str_ireplace('Markdown#', '#', (string) $markdown);
        $markdown = trim((string) $markdown);

        if (substr_count($markdown, "\n") < 3) {
            $markdown = preg_replace('/(?<!\n)(\s*#\s+)/', "\n$1", $markdown);
            $markdown = preg_replace('/(?<!\n)(\s*-\s+)/', "\n$1", (string) $markdown);
            $markdown = trim((string) $markdown);
        }

        if ($markdown !== '' && ! str_starts_with(ltrim($markdown), '#')) {
            $markdown = "# Mind Map\n".$markdown;
        }

        if ($this->looksLikeFlatText($markdown)) {
            $markdown = $this->flatTextToMarkdown($markdown);
        }

        return $markdown;
    }

    private function isolateUsefulResponse(string $text): string
    {
        $text = trim($text);

        if (preg_match('/MINDMAP_JSON_START\s*(.*?)\s*MINDMAP_JSON_END/is', $text, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/Thought\s+for\s+\d+s\s*(.*)$/is', $text, $matches)) {
            return trim($matches[1]);
        }

        return $text;
    }

    private function jsonResponseToMarkdown(string $text): ?string
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', (string) $clean);
        $clean = preg_replace('/```\s*$/', '', (string) $clean);
        $clean = trim((string) $clean);

        $json = json_decode($clean, true);

        if (! is_array($json)) {
            $start = strpos($clean, '{');
            $end = strrpos($clean, '}');

            if ($start !== false && $end !== false && $end > $start) {
                $json = json_decode(substr($clean, $start, $end - $start + 1), true);
            }
        }

        if (! is_array($json) || ! isset($json['branches']) || ! is_array($json['branches'])) {
            return null;
        }

        $title = trim((string) ($json['title'] ?? 'Mind Map')) ?: 'Mind Map';
        $lines = ['# '.$title];

        foreach ($json['branches'] as $branch) {
            $this->appendJsonBranch($lines, $branch, 0);
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, string> $lines
     */
    private function appendJsonBranch(array &$lines, mixed $branch, int $depth): void
    {
        $indent = str_repeat('  ', max(0, $depth));

        if (is_string($branch)) {
            $label = trim($branch);
            if ($label !== '') {
                $lines[] = $indent.'- '.$label;
            }

            return;
        }

        if (! is_array($branch)) {
            return;
        }

        $label = trim((string) ($branch['label'] ?? $branch['title'] ?? $branch['name'] ?? ''));
        if ($label !== '') {
            $lines[] = $indent.'- '.$label;
        }

        $children = $branch['children'] ?? $branch['items'] ?? [];
        if (! is_array($children)) {
            return;
        }

        foreach ($children as $child) {
            $this->appendJsonBranch($lines, $child, $depth + 1);
        }
    }

    private function looksLikeFlatText(string $markdown): bool
    {
        $lines = collect(preg_split('/\R/', $markdown) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        if ($lines->count() < 4) {
            return false;
        }

        $structured = $lines->filter(
            fn ($line) => preg_match('/^(#{1,6}\s+|[-*+]\s+|\d+\.\s+)/', $line)
        )->count();

        return $structured < max(2, (int) ceil($lines->count() * 0.35));
    }

    private function flatTextToMarkdown(string $text): string
    {
        $lines = collect(preg_split('/\R/', $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return "# Mind Map\n- No content generated";
        }

        $first = preg_replace('/^#{1,6}\s+/', '', (string) $lines->first());
        $genericHeading = preg_match('/^(mind\s*map|result)$/i', (string) $first);
        $title = $genericHeading && $lines->get(1)
            ? preg_replace('/^#{1,6}\s+/', '', (string) $lines->get(1))
            : $first;
        $body = $lines->slice($genericHeading && $lines->get(1) ? 2 : 1)->values();
        $output = ['# '.($title ?: 'Mind Map')];
        $hasBranch = false;
        $childCount = 0;

        foreach ($body as $index => $line) {
            $line = preg_replace('/^[-*+]\s+/', '', (string) $line);
            $next = (string) ($body->get($index + 1) ?? '');

            if (! $hasBranch || $this->looksLikeSectionHeading($line, $childCount, $next)) {
                $output[] = '- '.$line;
                $hasBranch = true;
                $childCount = 0;
            } else {
                $output[] = '  - '.$line;
                $childCount++;
            }
        }

        return implode("\n", $output);
    }

    private function looksLikeSectionHeading(string $line, int $currentChildCount, string $nextLine): bool
    {
        if ($currentChildCount < 2) {
            return false;
        }

        $words = preg_split('/\s+/', trim($line)) ?: [];
        $normalized = strtolower(trim($line));
        $sectionWords = [
            'definition',
            'overview',
            'how',
            'works',
            'process',
            'steps',
            'example',
            'speed',
            'performance',
            'complexity',
            'conditions',
            'uses',
            'applications',
            'advantages',
            'benefits',
            'disadvantages',
            'limitations',
            'features',
            'types',
            'components',
            'summary',
            'key concepts',
        ];

        foreach ($sectionWords as $word) {
            if (str_contains($normalized, $word)) {
                return true;
            }
        }

        return count(array_filter($words)) <= 4
            && trim($nextLine) !== ''
            && (bool) preg_match('/^[A-Z0-9][A-Za-z0-9 &\/()+-]{2,55}$/', $line);
    }
}
