<?php

namespace Tests\Unit;

use App\Jobs\GenerateVideoExplainerJob;
use App\Services\Ai\VideoExplainerGenerator;
use App\Services\VideoExplainerService;
use Illuminate\Support\Facades\Http;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class VideoExplainerServiceTest extends TestCase
{
    public function test_edge_tts_api_writes_returned_mp3_audio(): void
    {
        config([
            'services.video_explainer.tts_api_url' => 'https://tts-api.eduvoo.com/generate',
            'services.video_explainer.tts_api_timeout' => 120,
            'services.video_explainer.tts_rate' => '+0%',
            'services.video_explainer.tts_pitch' => '+0Hz',
        ]);

        $audio = 'ID3'.str_repeat("\0", 200);
        Http::fake([
            'https://tts-api.eduvoo.com/generate' => Http::response(
                $audio,
                200,
                ['Content-Type' => 'audio/mpeg']
            ),
        ]);

        $service = new VideoExplainerService(Mockery::mock(VideoExplainerGenerator::class));
        $method = new ReflectionMethod($service, 'textToSpeechWithApi');
        $method->setAccessible(true);
        $output = tempnam(sys_get_temp_dir(), 'edge-tts-');

        try {
            $result = $method->invoke(
                $service,
                'السلام عليكم ورحمة الله وبركاته',
                $output,
                'ar-EG-SalmaNeural'
            );

            $this->assertTrue($result);
            $this->assertSame($audio, file_get_contents($output));

            Http::assertSent(fn ($request) => $request->url() === 'https://tts-api.eduvoo.com/generate'
                && $request['voice'] === 'ar-EG-SalmaNeural'
                && $request['rate'] === '+0%'
                && $request['pitch'] === '+0Hz'
            );
        } finally {
            @unlink($output);
        }
    }

    public function test_edge_tts_api_rejects_json_error_responses(): void
    {
        config([
            'services.video_explainer.tts_api_url' => 'https://tts-api.eduvoo.com/generate',
            'services.video_explainer.tts_api_timeout' => 120,
        ]);

        Http::fake([
            'https://tts-api.eduvoo.com/generate' => Http::response([
                'detail' => 'No audio received.',
            ], 500),
        ]);

        $service = new VideoExplainerService(Mockery::mock(VideoExplainerGenerator::class));
        $method = new ReflectionMethod($service, 'textToSpeechWithApi');
        $method->setAccessible(true);
        $output = tempnam(sys_get_temp_dir(), 'edge-tts-');

        try {
            $this->assertFalse($method->invoke(
                $service,
                'السلام عليكم',
                $output,
                'ar-EG-SalmaNeural'
            ));
            $this->assertSame('', file_get_contents($output));
        } finally {
            @unlink($output);
        }
    }

    public function test_slide_renderer_is_self_contained_and_includes_text_and_svg(): void
    {
        $service = new VideoExplainerService(Mockery::mock(VideoExplainerGenerator::class));
        $method = new ReflectionMethod($service, 'renderSlideHtml');
        $method->setAccessible(true);

        $html = $method->invoke($service, [
            'title' => 'How neural networks learn',
            'subtitle' => 'Data moves through connected layers.',
            'bullets' => ['Input data', 'Pattern detection', 'Useful prediction'],
            'visual' => [
                'type' => 'process',
                'labels' => ['Input', 'Layers', 'Output'],
                'values' => [],
            ],
        ], 'Modern', 'en', 1, 5);

        $this->assertStringContainsString('How neural networks learn', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('Input data', $html);
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html);
        $this->assertStringContainsString('width:1920px', $html);
    }

    public function test_generator_fallback_returns_structured_visual_scenes(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.ai.gemini_key' => '',
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            3,
            '',
            'en'
        );

        $this->assertCount(3, $result['slides']);
        $this->assertArrayHasKey('title', $result['slides'][0]);
        $this->assertArrayHasKey('visual', $result['slides'][0]);
        $this->assertArrayHasKey('narration', $result['slides'][0]);
        $this->assertArrayNotHasKey('html', $result['slides'][0]);
    }

    public function test_chatgpt_proxy_returns_distinct_scenes(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_key' => 'test-key',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_scene_batch_size' => 3,
        ]);

        $slides = $this->sampleDistinctSlides();
        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::response([
                'response' => json_encode(['slides' => $slides], JSON_UNESCAPED_UNICODE),
            ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            3,
            '',
            'en'
        );

        $this->assertCount(3, $result['slides']);
        $this->assertCount(3, array_unique(array_column($result['slides'], 'title')));
        $this->assertCount(3, array_unique(array_column($result['slides'], 'narration')));
        Http::assertSent(fn ($request) => $request->url() === 'https://gpt-api.metaphilia.com/chat'
            && is_string($request['message'])
            && str_contains($request['message'], 'Create exactly 3 scenes')
        );
    }

    public function test_openai_chat_completions_response_and_payload_are_supported(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://api.openai.com/v1/chat/completions',
            'services.ai.chatgpt_key' => 'test-key',
            'services.ai.chatgpt_mode' => 'openai',
            'services.ai.chatgpt_model' => 'gpt-4.1-mini',
            'services.ai.chatgpt_scene_batch_size' => 3,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'slides' => $this->sampleDistinctSlides(),
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            3,
            '',
            'en'
        );

        $this->assertCount(3, $result['slides']);
        Http::assertSent(fn ($request) => $request['model'] === 'gpt-4.1-mini'
            && $request['response_format']['type'] === 'json_schema'
            && $request['response_format']['json_schema']['schema']['properties']['slides']['minItems'] === 3
        );
    }

    public function test_chatgpt_splits_eight_scenes_into_small_batches(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_scene_batch_size' => 4,
            'services.ai.chatgpt_retry_attempts' => 1,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::sequence()
                ->push([
                    'response' => json_encode([
                        'slides' => $this->numberedSlides(1, 4),
                    ], JSON_UNESCAPED_UNICODE),
                ])
                ->push([
                    'response' => json_encode([
                        'slides' => $this->numberedSlides(5, 4),
                    ], JSON_UNESCAPED_UNICODE),
                ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            8,
            '',
            'en'
        );

        $this->assertCount(8, $result['slides']);
        $this->assertCount(8, array_unique(array_column($result['slides'], 'title')));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains(
            $request['message'],
            'positions 1 through 4'
        ));
        Http::assertSent(fn ($request) => str_contains(
            $request['message'],
            'positions 5 through 8'
        ));
    }

    public function test_chatgpt_retries_a_gateway_timeout_response(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_scene_batch_size' => 4,
            'services.ai.chatgpt_retry_attempts' => 2,
            'services.ai.chatgpt_retry_delay_ms' => 0,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::sequence()
                ->push('<html><h1>504 Gateway Time-out</h1></html>', 504)
                ->push([
                    'response' => json_encode([
                        'slides' => $this->numberedSlides(1, 3),
                    ], JSON_UNESCAPED_UNICODE),
                ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            3,
            '',
            'en'
        );

        $this->assertCount(3, $result['slides']);
        Http::assertSentCount(2);
    }

    public function test_chatgpt_splits_failed_large_scene_batch_into_smaller_batches(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_scene_batch_size' => 4,
            'services.ai.chatgpt_retry_attempts' => 1,
            'services.ai.chatgpt_retry_delay_ms' => 0,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::sequence()
                ->push('<html><h1>504 Gateway Time-out</h1></html>', 504)
                ->push([
                    'response' => json_encode([
                        'slides' => $this->numberedSlides(1, 2),
                    ], JSON_UNESCAPED_UNICODE),
                ])
                ->push([
                    'response' => json_encode([
                        'slides' => $this->numberedSlides(3, 2),
                    ], JSON_UNESCAPED_UNICODE),
                ])
                ->push([
                    'response' => json_encode([
                        'slides' => $this->numberedSlides(5, 1),
                    ], JSON_UNESCAPED_UNICODE),
                ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            5,
            '',
            'en'
        );

        $this->assertCount(5, $result['slides']);
        $this->assertSame(['Scene 1', 'Scene 2', 'Scene 3', 'Scene 4', 'Scene 5'], array_column($result['slides'], 'title'));
        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => str_contains($request['message'], 'positions 1 through 4'));
        Http::assertSent(fn ($request) => str_contains($request['message'], 'positions 1 through 2'));
        Http::assertSent(fn ($request) => str_contains($request['message'], 'positions 3 through 4'));
        Http::assertSent(fn ($request) => str_contains($request['message'], 'positions 5 through 5'));
    }

    public function test_duplicate_ai_narration_is_replaced_with_distinct_scene_content(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_scene_batch_size' => 3,
            'services.ai.chatgpt_retry_attempts' => 2,
            'services.ai.chatgpt_retry_delay_ms' => 0,
        ]);

        $slides = $this->sampleDistinctArabicSlides();
        $slides[1]['narration'] = $slides[0]['narration'];

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::response([
                'response' => json_encode(['slides' => $slides], JSON_UNESCAPED_UNICODE),
            ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'الذكاء الاصطناعي',
            'Modern',
            3,
            '',
            'ar'
        );

        $this->assertCount(3, array_unique(array_column($result['slides'], 'narration')));
        $this->assertStringContainsString(
            'كيف تعمل الفكرة',
            $result['slides'][1]['title']
        );
    }

    public function test_arabic_prompt_requires_fully_vocalized_tts_narration(): void
    {
        $generator = new VideoExplainerGenerator;
        $method = new ReflectionMethod($generator, 'buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke(
            $generator,
            'الذكاء الاصطناعي',
            'Modern',
            10,
            '',
            'ar'
        );

        $this->assertStringContainsString('FULLY VOCALIZE every Arabic word', $prompt);
        $this->assertStringContainsString('Modern Standard Arabic', $prompt);
        $this->assertStringContainsString('mandatory specifically for narration', $prompt);
    }

    public function test_generator_supports_thirty_distinct_vocalized_arabic_scenes(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.ai.gemini_key' => '',
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'الذكاء الاصطناعي',
            'Modern',
            30,
            '',
            'ar'
        );

        $narrations = array_column($result['slides'], 'narration');
        $this->assertCount(30, $result['slides']);
        $this->assertCount(30, array_unique(array_column($result['slides'], 'title')));
        $this->assertCount(30, array_unique($narrations));

        foreach ($narrations as $narration) {
            $this->assertMatchesRegularExpression(
                '/[\x{064B}-\x{065F}\x{0670}]/u',
                $narration
            );
        }
    }

    public function test_unvocalized_arabic_narration_is_regenerated_before_tts(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_scene_batch_size' => 3,
            'services.ai.chatgpt_retry_attempts' => 2,
            'services.ai.chatgpt_retry_delay_ms' => 0,
        ]);

        $vocalized = $this->sampleDistinctArabicSlides();
        $unvocalized = array_map(function (array $slide): array {
            $slide['narration'] = preg_replace(
                '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u',
                '',
                $slide['narration']
            );

            return $slide;
        }, $vocalized);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::sequence()
                ->push([
                    'response' => json_encode(['slides' => $unvocalized], JSON_UNESCAPED_UNICODE),
                ])
                ->push([
                    'response' => json_encode(['slides' => $vocalized], JSON_UNESCAPED_UNICODE),
                ]),
        ]);

        $result = (new VideoExplainerGenerator)->generate(
            'الذكاء الاصطناعي',
            'Modern',
            3,
            '',
            'ar'
        );

        Http::assertSentCount(2);
        foreach (array_column($result['slides'], 'narration') as $narration) {
            $this->assertMatchesRegularExpression(
                '/[\x{064B}-\x{065F}\x{0670}]/u',
                $narration
            );
        }
    }

    public function test_generator_rejects_more_than_thirty_scenes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('between 3 and 30');

        (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            31,
            '',
            'en'
        );
    }

    public function test_long_video_queue_settings_allow_thirty_scenes(): void
    {
        $job = new GenerateVideoExplainerJob(1, 1, 1, 'Topic', 'Modern', 30);

        $this->assertSame(1800, $job->timeout);
        $this->assertGreaterThan($job->timeout, config('queue.connections.database.retry_after'));
    }

    public function test_chatgpt_failure_does_not_create_repeated_fake_video_scenes(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.chatgpt_retry_attempts' => 2,
            'services.ai.chatgpt_retry_delay_ms' => 0,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::response([
                'error' => 'API unavailable',
            ], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LLM scene batch 1-1 failed with HTTP 500');

        (new VideoExplainerGenerator)->generate(
            'Artificial intelligence',
            'Modern',
            3,
            '',
            'en'
        );
    }

    public function test_subtitle_track_changes_phrase_during_audio(): void
    {
        config(['services.video_explainer.subtitle_font' => 'DejaVu Sans']);

        $service = new VideoExplainerService(Mockery::mock(VideoExplainerGenerator::class));
        $method = new ReflectionMethod($service, 'writeSubtitleTrack');
        $method->setAccessible(true);
        $output = tempnam(sys_get_temp_dir(), 'captions-');

        try {
            $method->invoke(
                $service,
                $output,
                'This first phrase introduces the idea. The second phrase explains the process. The final phrase gives the result.',
                12.0,
                'en'
            );

            $ass = file_get_contents($output);
            $this->assertGreaterThan(2, substr_count($ass, 'Dialogue:'));
            $this->assertStringContainsString('0:00:00.00', $ass);
            $this->assertStringContainsString('0:00:12.00', $ass);
            $this->assertStringContainsString('This first phrase', $ass);
            $this->assertStringContainsString('The final phrase', $ass);
        } finally {
            @unlink($output);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleDistinctSlides(): array
    {
        return [
            [
                'title' => 'Introduction',
                'subtitle' => 'Define the topic.',
                'bullets' => ['Meaning', 'Importance'],
                'visual' => [
                    'type' => 'concept_map',
                    'labels' => ['Idea', 'Purpose'],
                    'values' => [],
                ],
                'narration' => 'The first scene defines artificial intelligence and explains why the topic matters.',
            ],
            [
                'title' => 'How it works',
                'subtitle' => 'Follow the process.',
                'bullets' => ['Input', 'Model', 'Output'],
                'visual' => [
                    'type' => 'process',
                    'labels' => ['Input', 'Model', 'Output'],
                    'values' => [],
                ],
                'narration' => 'The second scene follows information through a model to produce a useful output.',
            ],
            [
                'title' => 'Applications',
                'subtitle' => 'See practical uses.',
                'bullets' => ['Health', 'Education', 'Business'],
                'visual' => [
                    'type' => 'bar_chart',
                    'labels' => ['Health', 'Education', 'Business'],
                    'values' => [60, 75, 85],
                ],
                'narration' => 'The final scene compares practical applications across health, education, and business.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function numberedSlides(int $start, int $count): array
    {
        $slides = [];

        for ($index = $start; $index < $start + $count; $index++) {
            $slides[] = [
                'title' => "Scene {$index}",
                'subtitle' => "Distinct educational point {$index}.",
                'bullets' => ["Fact {$index}A", "Fact {$index}B"],
                'visual' => [
                    'type' => 'process',
                    'labels' => ["Input {$index}", "Output {$index}"],
                    'values' => [],
                ],
                'narration' => "Scene {$index} explains a unique part of the topic with enough detail for clear narration.",
            ];
        }

        return $slides;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleDistinctArabicSlides(): array
    {
        $slides = $this->sampleDistinctSlides();
        $slides[0]['title'] = 'مُقَدِّمَةٌ';
        $slides[0]['narration'] = 'يُعَرِّفُ الْمَشْهَدُ الْأَوَّلُ الذَّكَاءَ الِاصْطِنَاعِيَّ، وَيُوَضِّحُ أَهَمِّيَّتَهُ فِي حَيَاتِنَا.';
        $slides[1]['title'] = 'كَيْفَ يَعْمَلُ';
        $slides[1]['narration'] = 'يَشْرَحُ الْمَشْهَدُ الثَّانِي كَيْفَ تَنْتَقِلُ الْبَيَانَاتُ خِلَالَ النَّمُوذَجِ لِإِنْتَاجِ نَتِيجَةٍ مُفِيدَةٍ.';
        $slides[2]['title'] = 'التَّطْبِيقَاتُ';
        $slides[2]['narration'] = 'يُقَارِنُ الْمَشْهَدُ الْأَخِيرُ بَيْنَ التَّطْبِيقَاتِ الْعَمَلِيَّةِ فِي الصِّحَّةِ وَالتَّعْلِيمِ وَالْأَعْمَالِ.';

        return $slides;
    }
}
