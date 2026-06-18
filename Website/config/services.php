<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'questgen' => [
        'url' => env('QUESTGEN_URL', 'https://quest-gen.eduvoo.com/generate'),
        'timeout' => env('QUESTGEN_TIMEOUT', 180),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'chatgpt'),
        'gemini_key' => env('GEMINI_API_KEY'),
        'chatgpt_endpoint' => env('CHATGPT_API_ENDPOINT', 'https://gpt-api.metaphilia.com/chat'),
        'chatgpt_key' => env('CHATGPT_API_KEY'),
        'chatgpt_mode' => env('CHATGPT_API_MODE', 'auto'),
        'chatgpt_model' => env('CHATGPT_MODEL', 'gpt-4.1-mini'),
        'chatgpt_max_output_tokens' => (int) env('CHATGPT_MAX_OUTPUT_TOKENS', 16000),
        'chatgpt_timeout' => (int) env('CHATGPT_TIMEOUT', 120),
        'chatgpt_connect_timeout' => (int) env('CHATGPT_CONNECT_TIMEOUT', 15),
        'chatgpt_retry_attempts' => (int) env('CHATGPT_RETRY_ATTEMPTS', 3),
        'chatgpt_retry_delay_ms' => (int) env('CHATGPT_RETRY_DELAY_MS', 1500),
        'chatgpt_scene_batch_size' => (int) env('CHATGPT_SCENE_BATCH_SIZE', 2),
        'animation_timeout' => (int) env('AI_ANIMATION_TIMEOUT', env('CHATGPT_TIMEOUT', 120)),
        'animation_retry_attempts' => (int) env('AI_ANIMATION_RETRY_ATTEMPTS', env('CHATGPT_RETRY_ATTEMPTS', 3)),
        'animation_retry_delay_ms' => (int) env('AI_ANIMATION_RETRY_DELAY_MS', env('CHATGPT_RETRY_DELAY_MS', 1500)),
    ],

    'video_explainer' => [
        'ffmpeg' => env('FFMPEG_PATH', 'ffmpeg'),
        'ffprobe' => env('FFPROBE_PATH', 'ffprobe'),
        'edge_tts' => env('EDGE_TTS_PATH', 'edge-tts'),
        'wkhtmltoimage' => env('WKHTMLTOIMAGE_PATH', 'wkhtmltoimage'),
        'node' => env('NODE_BINARY'),
        'npm' => env('NPM_BINARY'),
        'chrome' => env('CHROME_PATH'),
        'tts_api_url' => env('EDGE_TTS_API_URL', 'https://tts-api.eduvoo.com/generate'),
        'tts_api_timeout' => (int) env('EDGE_TTS_API_TIMEOUT', 120),
        'tts_rate' => env('EDGE_TTS_RATE', '+0%'),
        'tts_pitch' => env('EDGE_TTS_PITCH', '+0Hz'),
        'subtitle_font' => env('VIDEO_SUBTITLE_FONT', 'DejaVu Sans'),
    ],

    'metaphilia' => [
        'api_key' => env('METAPHILIA_API_KEY'),
        'sender' => env('METAPHILIA_SENDER'),
        'webhook_secret' => env('METAPHILIA_WEBHOOK_SECRET'),
        'send_message_url' => env('METAPHILIA_SEND_MESSAGE_URL', 'https://metaphilia.com/send-message'),
        'send_media_url' => env('METAPHILIA_SEND_MEDIA_URL', 'https://metaphilia.com/send-media'),
        'send_button_url' => env('METAPHILIA_SEND_BUTTON_URL', 'https://metaphilia.com/send-button'),
        'timeout' => (int) env('METAPHILIA_TIMEOUT', 30),
    ],

];
