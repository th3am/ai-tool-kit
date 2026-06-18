# Video Explainer Server Deployment

Upload the files in this archive to the Laravel project root while preserving
their directory structure.

## Ubuntu requirements

Install or confirm:

- PHP extensions required by the Laravel application
- FFmpeg with the `libx264`, AAC, and `ass` filters
- Node.js and npm
- Google Chrome or Chromium
- Arabic-capable fonts such as DejaVu Sans or Noto Sans Arabic

Install project dependencies from the existing project manifests:

```bash
composer install --no-dev --optimize-autoloader
npm ci
```

Find the actual executable paths:

```bash
command -v ffmpeg
command -v ffprobe
command -v edge-tts
command -v wkhtmltoimage
command -v node
command -v npm
command -v google-chrome || command -v chromium || command -v chromium-browser
ffmpeg -filters | grep -E '(^| )ass( |$)'
```

Add or update these values in the server `.env`. Do not replace the server
`.env` with `.env.example`.

```dotenv
APP_URL=https://your-domain.example

AI_PROVIDER=chatgpt
CHATGPT_API_ENDPOINT=https://gpt-api.metaphilia.com/chat
CHATGPT_API_KEY=your_api_key_if_required
CHATGPT_API_MODE=proxy
CHATGPT_MODEL=gpt-4.1-mini
CHATGPT_MAX_OUTPUT_TOKENS=16000
CHATGPT_TIMEOUT=120
CHATGPT_CONNECT_TIMEOUT=15
CHATGPT_RETRY_ATTEMPTS=3
CHATGPT_RETRY_DELAY_MS=1500
CHATGPT_SCENE_BATCH_SIZE=4

FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe
EDGE_TTS_PATH=/usr/local/bin/edge-tts
WKHTMLTOIMAGE_PATH=/usr/bin/wkhtmltoimage
NODE_BINARY=/usr/bin/node
NPM_BINARY=/usr/bin/npm
CHROME_PATH=/usr/bin/google-chrome

EDGE_TTS_API_URL=https://tts-api.eduvoo.com/generate
EDGE_TTS_API_TIMEOUT=120
EDGE_TTS_RATE="+0%"
EDGE_TTS_PITCH="+0Hz"
VIDEO_SUBTITLE_FONT="DejaVu Sans"
QUEUE_RETRY_AFTER=2100
QUEUE_STALE_AFTER_MINUTES=40
```

Use the paths returned by `command -v`. If Chrome is installed as Chromium,
set `CHROME_PATH` to that returned path. `APP_URL` must be the exact public
Laravel URL. Include the subdirectory when the application is not hosted at
the domain root.

Video scenes are requested from ChatGPT in batches of four. This avoids the
common 60-second nginx gateway timeout from proxy APIs when requesting 8 to 30
scenes in one response. HTTP 408, 429, 500, 502, 503, and 504 responses are
retried automatically.

After uploading:

```bash
php artisan optimize:clear
php artisan queue:restart
sudo supervisorctl restart all
```

The Supervisor worker command must use `--timeout=1800`, and its
`stopwaitsecs` should be at least `2100`. The queue `retry_after` value must
remain greater than the worker timeout to prevent a long video job from being
processed twice.

The dashboard Cancel button marks the active tool job as `cancelled`.
Queued workers exit when they pick up a cancelled job, while a running video
stops at the next scene-generation checkpoint. Jobs left in `queued` or
`running` for more than `QUEUE_STALE_AFTER_MINUTES` are treated as failed
instead of showing "Resuming job" forever.

The dashboard layout now builds both Livewire endpoints from the current
Laravel URL, so tool selection works at the domain root and in subdirectory
deployments. The dashboard Blade file must remain UTF-8 without BOM; otherwise
Livewire can attach its component snapshot to the stepper instead of the page
root and all tool buttons will stop responding.

Reload the installed PHP-FPM service when applicable:

```bash
sudo systemctl reload php8.2-fpm
```

Use the server's installed PHP version in the service name.

Run the focused verification:

```bash
php artisan test tests/Unit/VideoExplainerServiceTest.php
php artisan test tests/Feature/DashboardJobCancellationTest.php
```

The archive intentionally excludes `.env`, `vendor`, `node_modules`, storage
output, and temporary files.
