<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoExplainerGenerator
{
    public const MIN_SCENES = 3;

    public const MAX_SCENES = 30;

    public static array $voiceMap = [
        'ar' => 'ar-EG-SalmaNeural',
        'ar-sa' => 'ar-SA-ZariyahNeural',
        'en' => 'en-US-JennyNeural',
        'en-gb' => 'en-GB-SoniaNeural',
        'fr' => 'fr-FR-DeniseNeural',
        'es' => 'es-ES-ElviraNeural',
        'de' => 'de-DE-KatjaNeural',
        'it' => 'it-IT-ElsaNeural',
        'pt' => 'pt-BR-FranciscaNeural',
        'tr' => 'tr-TR-EmelNeural',
        'zh' => 'zh-CN-XiaoxiaoNeural',
        'ja' => 'ja-JP-NanamiNeural',
        'ko' => 'ko-KR-SunHiNeural',
        'ru' => 'ru-RU-SvetlanaNeural',
    ];

    public static array $languageNames = [
        'ar' => 'Arabic (Egypt)',
        'ar-sa' => 'Arabic (Saudi)',
        'en' => 'English (US)',
        'en-gb' => 'English (UK)',
        'fr' => 'French',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese (BR)',
        'tr' => 'Turkish',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ru' => 'Russian',
    ];

    /**
     * @return array{slides: array<int, array<string, mixed>>}
     */
    public function generate(
        string $topic,
        string $style = 'Modern',
        int $slideCount = 5,
        string $instructions = '',
        string $language = 'ar'
    ): array {
        if ($slideCount < self::MIN_SCENES || $slideCount > self::MAX_SCENES) {
            throw new \InvalidArgumentException(sprintf(
                'Video explainer scene count must be between %d and %d.',
                self::MIN_SCENES,
                self::MAX_SCENES
            ));
        }

        $apiKey = trim((string) config('services.ai.gemini_key', ''));
        $provider = strtolower(trim((string) config('services.ai.provider', 'chatgpt')));

        if ($provider === 'gemini' && $apiKey === '') {
            Log::warning('VideoExplainerGenerator: No Gemini API key in Laravel configuration.');

            return $this->getMockData($topic, $slideCount, $language);
        }

        $prompt = $this->buildPrompt($topic, $style, $slideCount, $instructions, $language);

        try {
            if ($provider === 'chatgpt') {
                return $this->generateWithChatGpt(
                    $topic,
                    $style,
                    $slideCount,
                    $instructions,
                    $language
                );
            }

            $response = Http::asJson()
                ->timeout(300)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature' => 0.65,
                            'responseMimeType' => 'application/json',
                        ],
                    ]
                );

            $text = $response->successful()
                ? (string) ($response->json('candidates.0.content.parts.0.text') ?? '')
                : '';

            if (! $response->successful()) {
                Log::error('VideoExplainerGenerator: AI request failed.', [
                    'provider' => $provider,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 1000),
                ]);
            } else {
                $json = $this->decodeJson($text);
                if (isset($json['slides']) && is_array($json['slides'])) {
                    $result = $this->normalizeSlides(
                        $json['slides'],
                        $topic,
                        $slideCount,
                        $language
                    );

                    return $result;
                }

                Log::error('VideoExplainerGenerator: AI returned invalid slide JSON.', [
                    'provider' => $provider,
                    'text' => mb_substr($text, 0, 1000),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('VideoExplainerGenerator: Exception - '.$e->getMessage());

            if ($provider === 'chatgpt') {
                throw new \RuntimeException(
                    'LLM could not generate valid, distinct video scenes: '.$e->getMessage(),
                    0,
                    $e
                );
            }
        }

        if ($provider === 'chatgpt') {
            throw new \RuntimeException(
                'LLM did not return valid scene JSON. Check the LLM endpoint, mode, model, '
                .'API key, and laravel.log.'
            );
        }

        return $this->getMockData($topic, $slideCount, $language);
    }

    /**
     * @return array{slides: array<int, array<string, mixed>>}
     */
    private function generateWithChatGpt(
        string $topic,
        string $style,
        int $slideCount,
        string $instructions,
        string $language
    ): array {
        $batchSize = max(1, min(
            6,
            (int) config('services.ai.chatgpt_scene_batch_size', 2)
        ));
        $slides = [];

        for ($offset = 0; $offset < $slideCount; $offset += $batchSize) {
            $count = min($batchSize, $slideCount - $offset);
            $start = $offset + 1;
            $batchSlides = $this->generateChatGptBatch(
                $topic,
                $style,
                $slideCount,
                $start,
                $count,
                $instructions,
                $language
            );

            array_push($slides, ...$batchSlides);
        }

        return $this->normalizeSlides($slides, $topic, $slideCount, $language);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function generateChatGptBatch(
        string $topic,
        string $style,
        int $totalCount,
        int $start,
        int $count,
        string $instructions,
        string $language
    ): array {
        $end = $start + $count - 1;
        $batchInstructions = trim($instructions."\n\n"
            ."This is one batch of a {$totalCount}-scene video. Generate overall scene "
            ."positions {$start} through {$end}. Make them continue the topic logically "
            .'and do not repeat definitions, examples, titles, or narration from other positions.');
        $prompt = $this->buildPrompt(
            $topic,
            $style,
            $count,
            $batchInstructions,
            $language
        );

        try {
            [$response, $text] = $this->requestChatGpt($prompt, $count);
            $json = $this->decodeJson($text);

            if (
                ! $response->successful()
                || ! isset($json['slides'])
                || ! is_array($json['slides'])
            ) {
                throw new \RuntimeException(sprintf(
                    'LLM scene batch %d-%d failed with HTTP %d or invalid JSON.',
                    $start,
                    $end,
                    $response->status()
                ));
            }

            $batchSlides = array_slice($json['slides'], 0, $count);

            if (
                $this->isArabicLanguage($language)
                && ! $this->hasSufficientArabicDiacritics($batchSlides)
            ) {
                Log::warning(
                    'VideoExplainerGenerator: Arabic narration lacks sufficient diacritics; retrying batch.',
                    ['start_scene' => $start, 'end_scene' => $end]
                );

                [$retryResponse, $retryText] = $this->requestChatGpt(
                    $prompt."\n\nCRITICAL RETRY: The previous narration was not fully vocalized. "
                    .'Regenerate this batch and add Arabic diacritics (tashkeel) to every '
                    .'Arabic word in every narration field for accurate text-to-speech.',
                    $count
                );
                $retryJson = $this->decodeJson($retryText);

                if (
                    ! $retryResponse->successful()
                    || ! isset($retryJson['slides'])
                    || ! is_array($retryJson['slides'])
                ) {
                    throw new \RuntimeException(
                        "LLM failed to regenerate vocalized Arabic scenes {$start}-{$end}."
                    );
                }

                $batchSlides = array_slice($retryJson['slides'], 0, $count);
                if (! $this->hasSufficientArabicDiacritics($batchSlides)) {
                    throw new \RuntimeException(
                        "LLM returned Arabic scenes {$start}-{$end} without enough diacritics for TTS."
                    );
                }
            }

            return $batchSlides;
        } catch (\RuntimeException $e) {
            if ($count <= 1) {
                throw $e;
            }

            $leftCount = (int) ceil($count / 2);
            $rightCount = $count - $leftCount;

            Log::warning('VideoExplainerGenerator: Splitting failed LLM scene batch into smaller batches.', [
                'start_scene' => $start,
                'end_scene' => $end,
                'left_count' => $leftCount,
                'right_count' => $rightCount,
                'error' => $e->getMessage(),
            ]);

            $slides = $this->generateChatGptBatch(
                $topic,
                $style,
                $totalCount,
                $start,
                $leftCount,
                $instructions,
                $language
            );

            if ($rightCount > 0) {
                array_push(
                    $slides,
                    ...$this->generateChatGptBatch(
                        $topic,
                        $style,
                        $totalCount,
                        $start + $leftCount,
                        $rightCount,
                        $instructions,
                        $language
                    )
                );
            }

            return $slides;
        }
    }

    /**
     * @return array{0: \Illuminate\Http\Client\Response, 1: string}
     */
    private function requestChatGpt(string $prompt, int $slideCount): array
    {
        $endpoint = trim((string) config(
            'services.ai.chatgpt_endpoint',
            'https://gpt-api.metaphilia.com/chat'
        ));
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

        $attempts = max(1, (int) config('services.ai.chatgpt_retry_attempts', 3));
        $delayMs = max(0, (int) config('services.ai.chatgpt_retry_delay_ms', 1500));
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $request = Http::asJson()
                ->acceptJson()
                ->connectTimeout(max(
                    1,
                    (int) config('services.ai.chatgpt_connect_timeout', 15)
                ))
                ->timeout(max(30, (int) config('services.ai.chatgpt_timeout', 120)));
            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            if ($isOpenAi) {
                $configuredTokens = (int) config(
                    'services.ai.chatgpt_max_output_tokens',
                    16000
                );
                $response = $request->post($endpoint, [
                    'model' => (string) config('services.ai.chatgpt_model', 'gpt-4.1-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You create accurate visual educational video scenes. '
                                .'Return only JSON that follows the requested schema.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_completion_tokens' => min(
                        $configuredTokens,
                        max(2500, $slideCount * 900)
                    ),
                    'store' => false,
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'video_explainer_scenes',
                            'strict' => true,
                            'schema' => $this->slideJsonSchema($slideCount),
                        ],
                    ],
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

            Log::warning('VideoExplainerGenerator: Retrying LLM request.', [
                'attempt' => $attempt,
                'max_attempts' => $attempts,
                'status' => $response->status(),
                'scene_count' => $slideCount,
            ]);

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return [$response, $this->extractResponseText($response->json())];
    }

    private function slideJsonSchema(int $slideCount): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'slides' => [
                    'type' => 'array',
                    'minItems' => $slideCount,
                    'maxItems' => $slideCount,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'subtitle' => ['type' => 'string'],
                            'bullets' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'minItems' => 2,
                                'maxItems' => 4,
                            ],
                            'visual' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => [
                                        'type' => 'string',
                                        'enum' => [
                                            'bar_chart',
                                            'process',
                                            'timeline',
                                            'comparison',
                                            'concept_map',
                                        ],
                                    ],
                                    'labels' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                        'minItems' => 2,
                                        'maxItems' => 5,
                                    ],
                                    'values' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'number'],
                                        'maxItems' => 5,
                                    ],
                                ],
                                'required' => ['type', 'labels', 'values'],
                                'additionalProperties' => false,
                            ],
                            'narration' => ['type' => 'string'],
                        ],
                        'required' => [
                            'title',
                            'subtitle',
                            'bullets',
                            'visual',
                            'narration',
                        ],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['slides'],
            'additionalProperties' => false,
        ];
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

            if (is_array($candidate)) {
                $parts = [];
                foreach ($candidate as $part) {
                    if (is_array($part) && is_string($part['text'] ?? null)) {
                        $parts[] = $part['text'];
                    }
                }

                if ($parts !== []) {
                    return implode('', $parts);
                }
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

    private function buildPrompt(
        string $topic,
        string $style,
        int $count,
        string $instructions,
        string $language
    ): string {
        $langName = self::$languageNames[$language] ?? 'English';
        $isArabic = $this->isArabicLanguage($language);
        $direction = $isArabic ? 'right-to-left' : 'left-to-right';
        $extra = trim($instructions) !== '' ? "Additional instructions: {$instructions}" : '';
        $ttsRequirements = $isArabic
            ? <<<'ARABIC'
- The "narration" field is sent directly to an Arabic neural TTS voice.
- Write narration in clear Modern Standard Arabic, not dialect.
- FULLY VOCALIZE every Arabic word in narration with accurate tashkeel: fatha, damma, kasra, sukun, shadda, and tanween where grammatically appropriate.
- Vocalize grammatical endings when they improve pronunciation, and use punctuation to create natural pauses.
- Spell out numbers and abbreviations as Arabic words when that produces clearer speech.
- Keep title, subtitle, bullets, and visual labels concise; full tashkeel is mandatory specifically for narration.
- Before returning JSON, review every narration sentence for pronunciation ambiguity and correct its diacritics.
ARABIC
            : '- Write narration for natural neural text-to-speech, with punctuation that creates clear pauses.';

        return <<<PROMPT
You create visual educational explainers similar in pacing and clarity to a research-notebook video overview.

Create exactly {$count} scenes about "{$topic}".
Language: {$langName}. Text direction: {$direction}.
Visual style: {$style}.
{$extra}

Return only valid JSON using this schema:
{
  "slides": [
    {
      "title": "short scene heading",
      "subtitle": "one concise supporting sentence",
      "bullets": ["2 to 4 short facts"],
      "visual": {
        "type": "bar_chart | process | timeline | comparison | concept_map",
        "labels": ["2 to 5 short labels"],
        "values": [20, 45, 80]
      },
      "narration": "conversational narration"
    }
  ]
}

Requirements:
- Every scene must teach a different part of the topic.
- All title, subtitle, bullet, label, and narration text must be in {$langName}.
- Narration should be 35 to 65 words per scene and naturally explain the visible content.
{$ttsRequirements}
- Keep on-screen text brief enough for a 1920x1080 presentation.
- Choose visual data that can be rendered as an inline SVG diagram or chart.
- For non-numeric visuals, values may be omitted.
- Do not return HTML, markdown, URLs, image tags, or code fences.
PROMPT;
    }

    private function decodeJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? '';
        $text = preg_replace('/```\s*$/', '', $text) ?? '';

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace >= $firstBrace) {
            $text = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<int, mixed>  $slides
     * @return array{slides: array<int, array<string, mixed>>}
     */
    private function normalizeSlides(
        array $slides,
        string $topic,
        int $count,
        string $language
    ): array {
        $normalized = [];
        $seenTitles = [];
        $seenNarrations = [];

        foreach (array_slice($slides, 0, $count) as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $narration = trim((string) ($slide['narration'] ?? ''));
            $bullets = array_values(array_filter(
                array_map('strval', is_array($slide['bullets'] ?? null) ? $slide['bullets'] : []),
                fn (string $value) => trim($value) !== ''
            ));
            $visual = is_array($slide['visual'] ?? null) ? $slide['visual'] : [];
            $labels = array_values(array_filter(
                array_map('strval', is_array($visual['labels'] ?? null) ? $visual['labels'] : []),
                fn (string $value) => trim($value) !== ''
            ));
            $values = array_values(array_map(
                fn ($value) => max(0, min(100, (float) $value)),
                is_array($visual['values'] ?? null) ? $visual['values'] : []
            ));

            $title = trim((string) ($slide['title'] ?? '')) ?: $topic;
            $titleKey = $this->comparisonKey($title);
            $narrationKey = $this->comparisonKey($narration);

            if (
                ($titleKey !== '' && isset($seenTitles[$titleKey]))
                || ($narrationKey !== '' && isset($seenNarrations[$narrationKey]))
            ) {
                Log::warning('VideoExplainerGenerator: Replaced duplicate AI scene.', [
                    'scene' => $index + 1,
                    'title' => $title,
                ]);
                $normalized[] = $this->fallbackSlide($topic, $index + 1, $count, $language);

                continue;
            }

            $normalized[] = [
                'title' => $title,
                'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
                'bullets' => array_slice($bullets, 0, 4),
                'visual' => [
                    'type' => in_array(
                        $visual['type'] ?? '',
                        ['bar_chart', 'process', 'timeline', 'comparison', 'concept_map'],
                        true
                    ) ? $visual['type'] : 'concept_map',
                    'labels' => array_slice($labels, 0, 5),
                    'values' => array_slice($values, 0, 5),
                ],
                'narration' => $narration !== ''
                    ? $narration
                    : $this->fallbackNarration($topic, $index + 1, $count, $language),
            ];

            $seenTitles[$titleKey] = true;
            if ($narrationKey !== '') {
                $seenNarrations[$narrationKey] = true;
            }
        }

        while (count($normalized) < $count) {
            $index = count($normalized) + 1;
            $normalized[] = $this->fallbackSlide($topic, $index, $count, $language);
        }

        return ['slides' => $normalized];
    }

    private function comparisonKey(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/[\s\p{P}\p{S}]+/u', ' ', $value) ?? $value;
    }

    private function isArabicLanguage(string $language): bool
    {
        return in_array($language, ['ar', 'ar-sa'], true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $slides
     */
    private function hasSufficientArabicDiacritics(array $slides): bool
    {
        foreach ($slides as $slide) {
            $narration = (string) ($slide['narration'] ?? '');
            preg_match_all('/[\x{0621}-\x{064A}\x{066E}-\x{06D3}]/u', $narration, $letters);
            preg_match_all('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', $narration, $marks);

            $letterCount = count($letters[0]);
            if ($letterCount === 0 || count($marks[0]) / $letterCount < 0.25) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{slides: array<int, array<string, mixed>>}
     */
    private function getMockData(string $topic, int $count, string $language): array
    {
        $slides = [];
        for ($index = 1; $index <= $count; $index++) {
            $slides[] = $this->fallbackSlide($topic, $index, $count, $language);
        }

        return ['slides' => $slides];
    }

    private function fallbackSlide(string $topic, int $index, int $count, string $language): array
    {
        $isArabic = $this->isArabicLanguage($language);
        $sceneIndex = ($index - 1) % 8;
        $arabicScenes = [
            ['مدخل إلى الموضوع', 'نبدأ بتعريف واضح يضع أساس الفهم.', ['المعنى الأساسي', 'لماذا يهم', 'الصورة العامة'], ['التعريف', 'الأهمية', 'الهدف'], 'concept_map'],
            ['كيف تعمل الفكرة', 'نقسم الآلية إلى خطوات مترابطة وسهلة.', ['المدخلات', 'المعالجة', 'المخرجات'], ['مدخلات', 'معالجة', 'نتائج'], 'process'],
            ['العناصر الرئيسية', 'نتعرف على الأجزاء التي تكوّن الموضوع.', ['العنصر الأول', 'العنصر الثاني', 'العلاقة بينهما'], ['عنصر أ', 'عنصر ب', 'الترابط'], 'concept_map'],
            ['مثال من الواقع', 'نربط المفهوم بموقف عملي قريب من الحياة.', ['المشكلة', 'طريقة الاستخدام', 'النتيجة'], ['مشكلة', 'تطبيق', 'نتيجة'], 'timeline'],
            ['الفوائد الأساسية', 'نقارن الأثر قبل استخدام الفكرة وبعدها.', ['توفير الوقت', 'تحسين الدقة', 'دعم القرار'], ['الوقت', 'الدقة', 'القرار'], 'bar_chart'],
            ['التحديات والحدود', 'نوضح ما يحتاج إلى انتباه عند التطبيق.', ['جودة البيانات', 'المراجعة البشرية', 'الاستخدام المسؤول'], ['بيانات', 'مراجعة', 'مسؤولية'], 'comparison'],
            ['أفضل طريقة للتطبيق', 'نرتب خطوات عملية للوصول إلى نتيجة جيدة.', ['ابدأ بهدف واضح', 'اختبر النتيجة', 'حسّن تدريجيا'], ['تخطيط', 'اختبار', 'تحسين'], 'process'],
            ['الخلاصة والنظرة القادمة', 'نجمع أهم الأفكار ونحدد الخطوة التالية.', ['الفكرة الأساسية', 'أهم تطبيق', 'ما يمكن استكشافه لاحقا'], ['فهم', 'تطبيق', 'تطوير'], 'timeline'],
        ];
        $englishScenes = [
            ['Introduction', 'Start with a clear definition and the big picture.', ['Core meaning', 'Why it matters', 'Overall goal'], ['Definition', 'Importance', 'Goal'], 'concept_map'],
            ['How it works', 'Break the mechanism into a simple connected flow.', ['Inputs', 'Processing', 'Outputs'], ['Inputs', 'Process', 'Results'], 'process'],
            ['Main components', 'Identify the parts that make the topic work.', ['First component', 'Second component', 'How they connect'], ['Part A', 'Part B', 'Connection'], 'concept_map'],
            ['Real-world example', 'Connect the concept to a practical situation.', ['The problem', 'How it is used', 'The result'], ['Problem', 'Use', 'Result'], 'timeline'],
            ['Key benefits', 'Compare the impact before and after adoption.', ['Save time', 'Improve accuracy', 'Support decisions'], ['Time', 'Accuracy', 'Decisions'], 'bar_chart'],
            ['Challenges and limits', 'Highlight what requires care during use.', ['Data quality', 'Human review', 'Responsible use'], ['Data', 'Review', 'Responsibility'], 'comparison'],
            ['Practical approach', 'Follow a reliable sequence for better results.', ['Set a clear goal', 'Test the result', 'Improve gradually'], ['Plan', 'Test', 'Improve'], 'process'],
            ['Summary and next steps', 'Bring the ideas together and look forward.', ['Core lesson', 'Strongest use case', 'What to explore next'], ['Understand', 'Apply', 'Develop'], 'timeline'],
        ];
        [$sceneTitle, $subtitle, $bullets, $labels, $visualType] =
            ($isArabic ? $arabicScenes : $englishScenes)[$sceneIndex];
        $title = $index === 1
            ? $topic
            : ($isArabic
                ? "الْمَشْهَدُ التَّعْلِيمِيُّ {$index}: {$sceneTitle}"
                : "Learning scene {$index}: {$sceneTitle}");

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'bullets' => $bullets,
            'visual' => [
                'type' => $visualType,
                'labels' => $labels,
                'values' => $visualType === 'bar_chart' ? [45, 70, 88] : [],
            ],
            'narration' => $this->fallbackNarration($topic, $index, $count, $language),
        ];
    }

    private function fallbackNarration(string $topic, int $index, int $count, string $language): string
    {
        if ($this->isArabicLanguage($language)) {
            $scripts = [
                'نَبْدَأُ بِتَحْدِيدِ الْمَعْنَى الْأَسَاسِيِّ لِلْمَوْضُوعِ، وَنُوَضِّحُ لِمَاذَا يَسْتَحِقُّ الدِّرَاسَةَ. يُقَدِّمُ هٰذَا الْمَدْخَلُ الصُّورَةَ الْعَامَّةَ، وَيُهَيِّئُنَا لِفَهْمِ التَّفَاصِيلِ دُونَ خَلْطٍ بَيْنَ الْمَفْهُومِ وَتَطْبِيقَاتِهِ.',
                'الْآنَ نُوَضِّحُ كَيْفَ تَعْمَلُ الْفِكْرَةُ. نُتَابِعُ انْتِقَالَهَا مِنَ الْمُدْخَلَاتِ، إِلَى خُطُوَاتِ الْمُعَالَجَةِ، ثُمَّ إِلَى النَّتِيجَةِ النِّهَائِيَّةِ، حَتَّى تُصْبِحَ الْآلِيَّةُ مُتَرَابِطَةً وَسَهْلَةَ التَّذَكُّرِ.',
                'يَتَكَوَّنُ الْمَوْضُوعُ مِنْ عَنَاصِرَ رَئِيسِيَّةٍ، يُؤَدِّي كُلُّ عُنْصُرٍ مِنْهَا وَظِيفَةً مُحَدَّدَةً. وَيُسَاعِدُنَا فَهْمُ هٰذِهِ الْعَنَاصِرِ وَالْعَلَاقَةِ بَيْنَهَا عَلَى تَفْسِيرِ السُّلُوكِ الْكَامِلِ.',
                'لِنَرْبِطِ الْمَفْهُومَ بِمِثَالٍ وَاقِعِيٍّ. نَبْدَأُ بِمُشْكِلَةٍ وَاضِحَةٍ، ثُمَّ نَرَى كَيْفَ تُسْتَخْدَمُ الْفِكْرَةُ فِي مُعَالَجَتِهَا، وَأَخِيرًا نُقَارِنُ النَّتِيجَةَ بِمَا كَانَ يَحْدُثُ قَبْلَ التَّطْبِيقِ.',
                'مِنْ أَهَمِّ الْفَوَائِدِ تَحْسِينُ الْوَقْتِ وَالدِّقَّةِ وَدَعْمُ الْقَرَارَاتِ. وَتَخْتَلِفُ قِيمَةُ هٰذِهِ الْفَوَائِدِ بِحَسَبِ طَرِيقَةِ الِاسْتِخْدَامِ، لِذٰلِكَ يَجِبُ قِيَاسُ الْأَثَرِ الْفِعْلِيِّ.',
                'رَغْمَ الْفَوَائِدِ، تُوجَدُ تَحَدِّيَاتٌ مُهِمَّةٌ، مِثْلُ جَوْدَةِ الْبَيَانَاتِ، وَالْحَاجَةِ إِلَى الْمُرَاجَعَةِ الْبَشَرِيَّةِ، وَالِاسْتِخْدَامِ الْمَسْؤُولِ. وَتَمْنَعُ مَعْرِفَةُ هٰذِهِ الْحُدُودِ الثِّقَةَ الزَّائِدَةَ.',
                'لِلتَّطْبِيقِ بِصُورَةٍ جَيِّدَةٍ، نَبْدَأُ بِهَدَفٍ مُحَدَّدٍ، ثُمَّ نُجَرِّبُ عَلَى نِطَاقٍ صَغِيرٍ، وَنُرَاجِعُ النَّتِيجَةَ، وَبَعْدَ ذٰلِكَ نُحَسِّنُ الْخُطُوَاتِ تَدْرِيجِيًّا بِنَاءً عَلَى مَا تَعَلَّمْنَاهُ.',
                'فِي الْخِتَامِ، نَجْمَعُ بَيْنَ الْفِكْرَةِ الْأَسَاسِيَّةِ، وَالْآلِيَّةِ الْعَمَلِيَّةِ، وَالتَّطْبِيقَاتِ الْمُتَعَدِّدَةِ. وَتَكُونُ الْخُطْوَةُ التَّالِيَةُ اخْتِيَارَ مِثَالٍ مُنَاسِبٍ وَمُتَابَعَةَ النَّتَائِجِ.',
            ];

            return "فِي الْمَشْهَدِ رَقْمَ {$index} مِنْ أَصْلِ {$count} مَشْهَدًا، "
                .$scripts[($index - 1) % 8];
        }

        $scripts = [
            "We begin {$topic} by defining its central idea and explaining why it matters. This overview creates a clear foundation for the mechanisms, examples, benefits, and limitations covered in the following scenes.",
            "Now we examine how {$topic} works. We follow the flow from inputs through the main processing steps to the final result, making the mechanism connected and easier to remember.",
            "{$topic} depends on several main components, each with a specific role. Understanding these parts and their relationships explains the complete behavior better than memorizing isolated facts.",
            "A real-world example makes {$topic} concrete. We identify a practical problem, show how the concept is applied, and compare the resulting outcome with the original situation.",
            "The major benefits of {$topic} can include saving time, improving accuracy, and supporting decisions. These gains should be measured in practice because their value depends on implementation.",
            "{$topic} also has limitations. Data quality, human review, and responsible use all affect the outcome, so recognizing these constraints prevents overconfidence and reduces avoidable mistakes.",
            "A practical implementation of {$topic} starts with a clear goal, continues with a small test, and uses the observed result to improve the process gradually.",
            "To summarize, {$topic} combines a central idea, a working mechanism, and several applications. The next step is to test it on a suitable example and learn from the results.",
        ];

        return "In scene {$index} of {$count}, ".$scripts[($index - 1) % 8];
    }
}
