<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiTtsService
{
    protected string $apiKey;
    // The specific model and endpoint for TTS from the user snippet
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        // Using the same key as standard Gemini, or we could fallback to the hardcoded one from the snippet if env is missing
        // But better to use env for security.
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Generate speech from text using Gemini TTS.
     *
     * @param string $text
     * @param string $voiceName 'Kore' by default
     * @return string|null Relative path to the audio file or null on failure.
     */
    public function generateAudio(string $text, string $voiceName = 'Kore'): ?string
    {
        if (!$this->apiKey) {
            Log::error("Gemini API Key is missing for TTS.");
            return null;
        }

        // Endpoint: .../gemini-2.5-flash-preview-tts:generateContent
        $url = "{$this->baseUrl}/gemini-2.5-flash-preview-tts:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseModalities' => ["AUDIO"],
                    'speechConfig' => [
                        'voiceConfig' => [
                            'prebuiltVoiceConfig' => [
                                'voiceName' => $voiceName
                            ]
                        ]
                    ]
                ],
                'model' => 'gemini-2.5-flash-preview-tts' // Redundant if in URL, but harmless? Actually usually not needed in body if in URL path.
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $part = $data['candidates'][0]['content']['parts'][0]['inlineData'] ?? null;
                $audioContent = $part['data'] ?? null;
                $mimeType = $part['mimeType'] ?? 'audio/mp3';

                if (!$audioContent) {
                     Log::error("Gemini TTS: No audio content found. " . json_encode($data));
                     return null;
                }

                $audioData = base64_decode($audioContent);
                $size = strlen($audioData);
                Log::info("Gemini TTS: Decoded size: $size bytes. Mime: $mimeType");

                // If raw PCM, wrap in WAV header
                // Gemini usually returns 24kHz, 1 channel, 16-bit PCM for "audio/L16;codec=pcm;rate=24000"
                if (str_contains($mimeType, 'pcm') || str_contains($mimeType, 'L16')) {
                    $audioData = $this->addWavHeader($audioData, 24000, 1, 16);
                    $extension = 'wav';
                } else {
                    $extension = 'mp3';
                }

                $filename = 'audio/' . Str::uuid() . '.' . $extension;
                
                Storage::disk('public')->put($filename, $audioData);
                return $filename;
            }

            Log::error("Gemini TTS API Error: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("Gemini TTS Service Exception: " . $e->getMessage());
            return null;
        }
    }

    private function addWavHeader($pcmData, $sampleRate, $channels, $bitsPerSample)
    {
        $dataLen = strlen($pcmData);
        $blockAlign = $channels * ($bitsPerSample / 8);
        $byteRate = $sampleRate * $blockAlign;
        // ChunkSize = 36 + SubChunk2Size
        $fileSize = 36 + $dataLen;

        // pack format:
        // A4 = "RIFF"
        // V = ChunkSize (little endian 32-bit)
        // a4 = "WAVE"
        // a4 = "fmt "
        // V = Subchunk1Size (16)
        // v = AudioFormat (1)
        // v = NumChannels
        // V = SampleRate
        // V = ByteRate
        // v = BlockAlign
        // v = BitsPerSample
        // a4 = "data"
        // V = Subchunk2Size

        $header = pack('A4Va4a4VvvVVvva4V',
            'RIFF',
            $fileSize,
            'WAVE',
            'fmt ',
            16,
            1,
            $channels,
            $sampleRate,
            $byteRate,
            $blockAlign,
            $bitsPerSample,
            'data',
            $dataLen
        );

        return $header . $pcmData;
    }
}
