<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TtsService
{
    public function generateAudio(string $text, string $voice = 'en-US-AriaNeural'): ?string
    {
        $provider = AppSetting::getValue('tts_provider', 'edge');

        if ($provider === 'gemini') {
            return app(GeminiTtsService::class)->generateAudio($text) ?: $this->generateWithEdge($text, $voice);
        }

        return $this->generateWithEdge($text, $voice) ?: app(GeminiTtsService::class)->generateAudio($text);
    }

    private function generateWithEdge(string $text, string $voice): ?string
    {
        $url = trim((string) config('services.video_explainer.tts_api_url'));
        if ($url === '') {
            return null;
        }

        try {
            $response = Http::accept('audio/mpeg')
                ->asJson()
                ->timeout((int) config('services.video_explainer.tts_api_timeout', 120))
                ->post($url, [
                    'text' => $text,
                    'voice' => $voice,
                    'rate' => (string) config('services.video_explainer.tts_rate', '+0%'),
                    'pitch' => (string) config('services.video_explainer.tts_pitch', '+0Hz'),
                ]);

            if (! $response->successful() || $response->body() === '') {
                Log::warning('TtsService: Edge TTS API failed.', ['status' => $response->status()]);
                return null;
            }

            $path = 'audio/'.Str::uuid().'.mp3';
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable $e) {
            Log::warning('TtsService: Edge TTS exception - '.$e->getMessage());
            return null;
        }
    }
}
