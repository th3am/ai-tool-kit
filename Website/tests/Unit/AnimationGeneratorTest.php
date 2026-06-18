<?php

namespace Tests\Unit;

use App\Services\Ai\AnimationGenerator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnimationGeneratorTest extends TestCase
{
    public function test_chatgpt_animation_response_is_cleaned_to_valid_svg(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.animation_retry_attempts' => 1,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::response([
                'response' => "```svg\n<svg viewBox=\"0 0 100 100\"><circle cx=\"50\" cy=\"50\" r=\"10\" /></svg>\n```",
            ]),
        ]);

        $svg = (new AnimationGenerator)->generate('bouncing ball');

        $this->assertStringStartsWith('<svg ', $svg);
        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $svg);
        $this->assertStringContainsString('<circle', $svg);
        $this->assertStringNotContainsString('```', $svg);
    }

    public function test_animation_generator_retries_transient_llm_failures(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.animation_retry_attempts' => 2,
            'services.ai.animation_retry_delay_ms' => 0,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::sequence()
                ->push('<html><h1>504 Gateway Time-out</h1></html>', 504)
                ->push([
                    'response' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="orange" /></svg>',
                ]),
        ]);

        $svg = (new AnimationGenerator)->generate('orange square');

        $this->assertStringContainsString('<rect', $svg);
        Http::assertSentCount(2);
    }

    public function test_animation_generator_rejects_invalid_svg(): void
    {
        config([
            'services.ai.provider' => 'chatgpt',
            'services.ai.chatgpt_endpoint' => 'https://gpt-api.metaphilia.com/chat',
            'services.ai.chatgpt_mode' => 'proxy',
            'services.ai.animation_retry_attempts' => 1,
        ]);

        Http::fake([
            'https://gpt-api.metaphilia.com/chat' => Http::response([
                'response' => 'Here is an animation idea, but not SVG.',
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LLM did not return valid SVG animation code.');

        (new AnimationGenerator)->generate('bad output');
    }
}
