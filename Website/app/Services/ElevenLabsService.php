<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ElevenLabsService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.elevenlabs.io/v1';

    public function __construct()
    {
        $this->apiKey = env('ELEVENLABS_API_KEY');
    }

    /**
     * Generate audio from text.
     *
     * @param string $text
     * @param string $voiceId Default '21m00Tcm4TlvDq8ikWAM' (Rachel)
     * @return string|null Relative path to the audio file or null on failure.
     */
    public function generateAudio(string $text, string $voiceId = 'JBFqnCBsd6RMkjVDRZzb'): ?string
    {
        if (!$this->apiKey) {
            Log::error("ElevenLabs API Key is missing.");
            return null;
        }

        $endpoint = "{$this->baseUrl}/text-to-speech/{$voiceId}";

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg',
            ])->post($endpoint, [
                'text' => $text,
                'model_id' => 'eleven_multilingual_v2',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.5,
                ],
            ]);

            if ($response->successful()) {
                $filename = 'audio/' . Str::uuid() . '.mp3';
                Storage::disk('public')->put($filename, $response->body());
                return $filename;
            }

            Log::error("ElevenLabs API Error: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("ElevenLabs Service Exception: " . $e->getMessage());
            return null;
        }
    }
}
