<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaphiliaClient
{
    public function sendMessage(string $number, string $message): bool
    {
        return $this->post((string) config('services.metaphilia.send_message_url'), [
            'number' => $this->normalizeNumber($number),
            'message' => $message,
        ]);
    }

    public function sendMedia(string $number, string $mediaType, string $url, string $caption = ''): bool
    {
        return $this->post((string) config('services.metaphilia.send_media_url'), [
            'number' => $this->normalizeNumber($number),
            'media_type' => $mediaType,
            'url' => $url,
            'caption' => $caption,
        ]);
    }

    public function sendButton(string $number, string $message, array $buttons, string $footer = '', ?string $url = null): bool
    {
        return $this->post((string) config('services.metaphilia.send_button_url'), [
            'number' => $this->normalizeNumber($number),
            'message' => $message,
            'button' => array_values(array_slice($buttons, 0, 5)),
            'footer' => $footer,
            'url' => $url,
        ]);
    }

    private function post(string $endpoint, array $payload): bool
    {
        $apiKey = trim((string) config('services.metaphilia.api_key'));
        $sender = trim((string) config('services.metaphilia.sender'));

        if ($apiKey === '' || $sender === '') {
            Log::warning('MetaphiliaClient: missing METAPHILIA_API_KEY or METAPHILIA_SENDER.');
            return false;
        }

        $payload = array_filter([
            ...$payload,
            'api_key' => $apiKey,
            'sender' => $this->normalizeNumber($sender),
        ], fn ($value) => $value !== null);

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(max(10, (int) config('services.metaphilia.timeout', 30)))
            ->withoutVerifying()
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error('MetaphiliaClient: send failed.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function normalizeNumber(?string $number): string
    {
        return preg_replace('/\D+/', '', (string) $number) ?: '';
    }
}
