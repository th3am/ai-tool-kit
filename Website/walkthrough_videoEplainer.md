# Video Explainer Pipeline Walkthrough

This document serves as a comprehensive recap of the architecture and code implemented to bring the **Video Explainer Tool** to life in the EduAI Platform.

## 🏗️ Architecture Overview

The pipeline takes a user's prompt (topic) and converts it into a full MP4 video with custom slides and audio narration. 

### The 5-Step Rendering Pipeline
1. **AI Generation**: The `VideoExplainerGenerator` uses the configured ChatGPT/OpenAI-compatible API to produce distinct structured educational scenes containing a title, subtitle, bullet points, visual type/data, and narration.
2. **Screenshot Capture**: Laravel renders each scene as self-contained 1920x1080 HTML with embedded CSS and inline SVG diagrams/charts. Browsershot (Chromium) captures the slide without depending on the Tailwind CDN.
3. **Audio Synthesis**: The service calls the Eduvoo Edge TTS API to synthesize narration into an `.mp3` file. It falls back to the local `edge-tts` CLI, Gemini TTS, and finally silence if the remote service fails.
4. **Clip Assembly**: FFmpeg merges the `.png` and narration audio into a mini `.mp4` clip. Phrase-level ASS subtitles are timed proportionally across the measured audio duration and rendered with libass.
5. **Final Concatenation**: FFmpeg concatenates all the individual slide clips into one cohesive `final.mp4` video, moving it to the public storage directory.

---

## 📂 Core Files Updated & Created

### 1. Frontend & State Controllers
- **`app/Livewire/Dashboard.php`**
  - Updated the unified `generate()` method to dispatch the `video-explainer` and `lecture` tools.
  - Implemented Livewire polling to check on the background job's status every 3 seconds.
- **`resources/views/livewire/dashboard.blade.php`**
  - Added the wizard UI for the Video Explainer configuration.
  - Created a real-time progress overlay displaying the current processing stage (e.g., "AI Slides & Scripts", "Screenshots", "Voice Narration").

### 2. AI Generators & Services
- **`app/Services/Ai/VideoExplainerGenerator.php`** [NEW]
  - Responsible for prompt engineering and enforcing the required JSON schema.
  - Adapted to communicate with the `https://gpt-api.metaphilia.com/chat` proxy API, including formatting workarounds for markdown.
- **`app/Services/VideoExplainerService.php`** [NEW]
  - Orchestrates the 5-step rendering pipeline.
  - Handles the temporary workspace (`storage/app/tmp/explainer-...`) and self-destructs the raw assets after assembly to prevent disk bloat.
  - *Fix:* Removed the unsupported `wrap_unicode=1` FFmpeg flag for Ubuntu server compatibility.

### 3. Background Workers
- **`app/Jobs/GenerateVideoExplainerJob.php`** [NEW]
  - Dispatches the generation pipeline to Laravel's queue workers, keeping the web server free and the UI responsive.
  - Includes a 10-minute timeout limit since video rendering is heavily CPU-bound.
  - *Fix:* Updated the constructor to support `string|int` for UUID compatibility in the `ChatSession` model.

### 4. Downloading & Delivery
- **`app/Http/Controllers/DownloadController.php`**
  - Created a secure endpoint (`downloadVideoExplainer`) to serve the `.mp4` file, verifying the ownership via the `user_id` on the `ToolJob` model.
- **`routes/web.php`**
  - Registered the secure `video-explainer/{job}/download` route under the `auth` middleware.

---

## 🛠️ Server Requirements Reminder

To ensure the pipeline runs smoothly on your Ubuntu server, please verify the following are installed:
1. `ffmpeg` (Required for video assembly and subtitles).
2. `wkhtmltoimage` or `chromium-browser` (Required for HTML-to-PNG screenshots).
3. `edge-tts` (Optional local fallback if the remote TTS API is unavailable: `pip install edge-tts`).
4. `libass` support in FFmpeg and Arabic-capable fonts such as DejaVu Sans or Noto Sans Arabic.

Make sure your background queue workers are running:
```bash
php artisan queue:restart
php artisan queue:work --timeout=1800
```

On Ubuntu, set the installed command paths in `.env`:
```dotenv
AI_PROVIDER=chatgpt
CHATGPT_API_ENDPOINT=https://gpt-api.metaphilia.com/chat
CHATGPT_API_KEY=
CHATGPT_API_MODE=proxy
CHATGPT_MODEL=gpt-4.1-mini
CHATGPT_MAX_OUTPUT_TOKENS=16000

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

Confirm the actual locations with:
```bash
command -v ffmpeg
command -v ffprobe
command -v edge-tts
command -v wkhtmltoimage
command -v node
command -v npm
command -v google-chrome || command -v chromium || command -v chromium-browser
```

After changing `.env` or deploying this service, clear cached configuration and
restart queue workers:
```bash
php artisan optimize:clear
php artisan queue:restart
```
