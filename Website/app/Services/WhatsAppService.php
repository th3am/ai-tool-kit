<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiKey;
    protected string $senderNumber;
    protected string $baseUrl = 'https://wz.metaphilia.com';

    public function __construct()
    {
        // In a real app, these should be in .env. Hardcoding for now as per prompt request structure
        // But better to use config variables if possible.
        // I'll stick to what was provided in the prompt or use placeholders.
        $this->apiKey = env('WHATSAPP_API_KEY', 'iJCe1ONFkuoEcuv5NcUuYgVRdkhE6s');
        $this->senderNumber = env('WHATSAPP_SENDER_NUMBER', '201276178032');
    }

    /**
     * Send an OTP message via WhatsApp.
     *
     * @param string $toNumber User's phone number
     * @param string $otp The 6-digit OTP code
     * @return bool True if successful, False otherwise
     */
    public function sendOtp(string $toNumber, string $otp): bool
    {
        $message = "Your EduMorph verification code is: {$otp}. This code expires in 5 minutes.";
        
        return $this->sendMessage($toNumber, $message);
    }

    /**
     * Send a raw message via WhatsApp.
     *
     * @param string $toNumber
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $toNumber, string $message): bool
    {
        $endpoint = "{$this->baseUrl}/send-message";

        try {
            $response = Http::post($endpoint, [
                'api_key' => $this->apiKey,
                'sender' => $this->senderNumber,
                'number' => $toNumber,
                'message' => $message
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp sent successfully to {$toNumber}");
                return true;
            }

            // Retry once logic (Simple implementation)
            Log::warning("WhatsApp send failed. Retrying... Response: " . $response->body());
            $response = Http::post($endpoint, [
                'api_key' => $this->apiKey,
                'sender' => $this->senderNumber,
                'number' => $toNumber,
                'message' => $message
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("WhatsApp send failed after retry to {$toNumber}. Response: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WhatsApp Service Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a welcome message to the user.
     *
     * @param string $toNumber
     * @param string $name
     * @return bool
     */
    public function sendWelcomeMessage(string $toNumber, string $name): bool
    {
        $message = "Welcome to EduMorph, {$name}! 🚀\nWe are excited to have you on board.\nYour number {$toNumber} is now verified.";
        return $this->sendMessage($toNumber, $message);
    }

    /**
     * Check if a number exists on WhatsApp.
     *
     * @param string $number
     * @return bool
     */
    public function checkNumber(string $number): bool
    {
        $endpoint = "{$this->baseUrl}/check-number";

        try {
            $response = Http::post($endpoint, [
                'api_key' => $this->apiKey,
                'sender' => $this->senderNumber,
                'number' => $number
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // API returns { "status": true, "msg": { "exists": true, ... } }
                if (isset($data['status']) && $data['status'] === true) {
                    return $data['msg']['exists'] ?? false;
                }
            }

            Log::warning("WhatsApp Number Check Failed: " . $response->body());
            return false; // Treat API failure as "not found" or "error" depending on policy. Conservative: false.

        } catch (\Exception $e) {
            Log::error("WhatsApp Service Check Number Exception: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Send media via WhatsApp.
     *
     * @param string $toNumber
     * @param string $mediaType (image, video, audio, document)
     * @param string $url Direct URL to media
     * @param string $caption Optional caption
     * @return bool
     */
    public function sendMedia(string $toNumber, string $mediaType, string $url, string $caption = ''): bool
    {
        $endpoint = "{$this->baseUrl}/send-media";

        try {
            $payload = [
                'api_key' => $this->apiKey,
                'sender' => $this->senderNumber,
                'number' => $toNumber,
                'media_type' => $mediaType,
                'url' => $url,
                'caption' => $caption
            ];

            Log::info("WhatsApp Media Payload", $payload);

            $response = Http::post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp media sent successfully to {$toNumber}");
                return true;
            }
            
            Log::warning("WhatsApp media send failed. Response: " . $response->body() . " Payload: " . json_encode($payload));
            return false;

        } catch (\Exception $e) {
            Log::error("WhatsApp Service Media Exception: " . $e->getMessage());
            return false;
        }
    }
}
