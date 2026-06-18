<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Services\Ai\VideoExplainerGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * VideoExplainerService
 * ──────────────────────────────────────────────────────────────────────────
 * Full pipeline:
 *   1. Configured AI provider → structured slides + narration text
 *   2. For each slide:
 *      a. Save HTML to temp file
 *      b. Screenshot it at 1920×1080 with wkhtmltoimage / Chromium (Browsershot)
 *      c. Generate narration audio with the Edge TTS API, CLI, or Gemini TTS
 *   3. FFmpeg concat: image + audio per scene → MP4 clip
 *   4. FFmpeg concat all clips → final MP4
 *   5. (Optional) Burn phrase-level ASS subtitles timed to the narration
 *   6. Return relative storage path to the final MP4
 */
class VideoExplainerService
{
    protected VideoExplainerGenerator $generator;

    public function __construct(VideoExplainerGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return string Relative path inside storage/app/public, e.g. "videos/explainer-xxx.mp4"
     */
    public function generate(
        string $topic,
        string $style,
        int $slideCount,
        string $instructions,
        string $language,
        bool $enableCaptions,
        ?callable $shouldCancel = null
    ): string {
        if (
            $slideCount < VideoExplainerGenerator::MIN_SCENES
            || $slideCount > VideoExplainerGenerator::MAX_SCENES
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Video explainer scene count must be between %d and %d.',
                VideoExplainerGenerator::MIN_SCENES,
                VideoExplainerGenerator::MAX_SCENES
            ));
        }

        Log::info("VideoExplainerService: Starting for topic='{$topic}', lang={$language}, slides={$slideCount}");
        $this->throwIfCancelled($shouldCancel);

        // ── 1. Generate slide data ─────────────────────────────────────────
        $data = $this->generator->generate($topic, $style, $slideCount, $instructions, $language);
        $this->throwIfCancelled($shouldCancel);
        $slides = $data['slides'] ?? [];

        if (empty($slides)) {
            throw new \Exception('No slides returned from AI generator.');
        }

        $jobId = Str::uuid();
        $tmpDir = storage_path("app/tmp/explainer-{$jobId}");
        mkdir($tmpDir, 0777, true);

        try {
            $clipPaths = [];
            $voice = VideoExplainerGenerator::$voiceMap[$language] ?? 'en-US-JennyNeural';

            foreach ($slides as $index => $slide) {
                $this->throwIfCancelled($shouldCancel);
                $n = $index + 1;
                $htmlPath = "{$tmpDir}/slide-{$n}.html";
                $imgPath = "{$tmpDir}/slide-{$n}.png";
                $audioPath = "{$tmpDir}/slide-{$n}.mp3";
                $clipPath = "{$tmpDir}/clip-{$n}.mp4";
                $subtitlePath = "{$tmpDir}/slide-{$n}.ass";

                // ── a. Write HTML ──────────────────────────────────────────
                $html = $this->renderSlideHtml($slide, $style, $language, $n, count($slides));
                file_put_contents($htmlPath, $html);
                Log::info("VideoExplainerService: Wrote HTML slide {$n}");

                // ── b. Screenshot HTML → PNG ───────────────────────────────
                $this->htmlToImage($htmlPath, $imgPath);
                $this->throwIfCancelled($shouldCancel);

                // ── c. Generate narration audio ────────────────────────────
                $narration = $slide['narration'] ?? '';
                $this->textToSpeech($narration, $audioPath, $voice);
                $this->throwIfCancelled($shouldCancel);

                // ── d. Get audio duration ──────────────────────────────────
                $duration = $this->getAudioDuration($audioPath);
                if ($duration <= 0) {
                    $duration = 5.0;
                } // Fallback 5 s

                if ($enableCaptions && trim($narration) !== '') {
                    $this->writeSubtitleTrack($subtitlePath, $narration, $duration, $language);
                }

                // ── e. Merge image + audio into clip MP4 ───────────────────
                $this->buildClip(
                    $imgPath,
                    $audioPath,
                    $clipPath,
                    $duration,
                    $enableCaptions ? $subtitlePath : null
                );
                $this->throwIfCancelled($shouldCancel);

                $clipPaths[] = $clipPath;
            }

            // ── 3. Concatenate clips ───────────────────────────────────────
            $this->throwIfCancelled($shouldCancel);
            $finalPath = $this->concatClips($clipPaths, $tmpDir, $jobId);
            $this->throwIfCancelled($shouldCancel);

            // ── 4. Move to public storage ──────────────────────────────────
            $relativePath = "videos/explainer-{$jobId}.mp4";
            Storage::disk('public')->put($relativePath, file_get_contents($finalPath));

            Log::info("VideoExplainerService: Done → {$relativePath}");

            return $relativePath;

        } finally {
            // Clean up tmp directory
            $this->rrmdir($tmpDir);
        }
    }

    private function throwIfCancelled(?callable $shouldCancel): void
    {
        if ($shouldCancel && $shouldCancel()) {
            throw new \RuntimeException('Generation cancelled by the user.');
        }
    }

    // ─── HTML → PNG Screenshot ────────────────────────────────────────────────

    private function renderSlideHtml(
        array $slide,
        string $style,
        string $language,
        int $number,
        int $total
    ): string {
        $isRtl = in_array($language, ['ar', 'ar-sa'], true);
        $direction = $isRtl ? 'rtl' : 'ltr';
        $title = $this->escapeHtml((string) ($slide['title'] ?? ''));
        $subtitle = $this->escapeHtml((string) ($slide['subtitle'] ?? ''));
        $bullets = is_array($slide['bullets'] ?? null) ? array_slice($slide['bullets'], 0, 4) : [];
        $bulletHtml = '';

        foreach ($bullets as $bullet) {
            $bulletHtml .= '<li><span class="bullet-dot"></span><span>'
                .$this->escapeHtml((string) $bullet)
                .'</span></li>';
        }

        $palette = match ($style) {
            'Professional' => ['#071a33', '#0f2f57', '#38bdf8', '#e0f2fe', '#f8fafc'],
            'Creative' => ['#2e1065', '#7e22ce', '#fb7185', '#fdf2f8', '#fff7ed'],
            'Minimalist' => ['#f8fafc', '#e2e8f0', '#2563eb', '#0f172a', '#ffffff'],
            default => ['#0f172a', '#312e81', '#22d3ee', '#eef2ff', '#ffffff'],
        };
        [$background, $backgroundEnd, $accent, $muted, $foreground] = $palette;
        $darkText = $style === 'Minimalist';
        $textColor = $darkText ? '#0f172a' : $foreground;
        $secondaryColor = $darkText ? '#475569' : '#cbd5e1';
        $cardColor = $darkText ? 'rgba(255,255,255,.84)' : 'rgba(255,255,255,.09)';
        $cardBorder = $darkText ? 'rgba(15,23,42,.10)' : 'rgba(255,255,255,.16)';
        $visual = $this->renderVisualSvg(
            is_array($slide['visual'] ?? null) ? $slide['visual'] : [],
            (string) ($slide['title'] ?? ''),
            $accent,
            $textColor,
            $isRtl
        );
        $sceneLabel = $isRtl ? "المشهد {$number} من {$total}" : "SCENE {$number} OF {$total}";
        $brand = $isRtl ? 'شرح مرئي من EduAI' : 'EduAI VISUAL EXPLAINER';

        return <<<HTML
<!DOCTYPE html>
<html lang="{$language}" dir="{$direction}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=1920,height=1080,initial-scale=1">
<style>
*{box-sizing:border-box}
html,body{width:1920px;height:1080px;margin:0;overflow:hidden}
body{font-family:"DejaVu Sans","Noto Sans Arabic","Arial",sans-serif;background:linear-gradient(135deg,{$background} 0%,{$backgroundEnd} 100%);color:{$textColor}}
.slide{position:relative;width:1920px;height:1080px;padding:70px 84px 52px;overflow:hidden}
.glow{position:absolute;width:620px;height:620px;border-radius:999px;background:{$accent};filter:blur(130px);opacity:.16;top:-280px;right:-180px}
.grid{position:absolute;inset:0;opacity:.07;background-image:linear-gradient({$textColor} 1px,transparent 1px),linear-gradient(90deg,{$textColor} 1px,transparent 1px);background-size:64px 64px}
.topline{position:relative;display:flex;justify-content:space-between;align-items:center;font-size:22px;font-weight:800;letter-spacing:2px;color:{$secondaryColor}}
.brand{display:flex;align-items:center;gap:14px}.brand-mark{width:18px;height:18px;border-radius:5px;background:{$accent};box-shadow:0 0 24px {$accent}}
.content{position:relative;display:grid;grid-template-columns:1.02fr .98fr;gap:64px;align-items:center;height:850px}
.copy{padding:24px 0}.eyebrow{display:inline-flex;padding:10px 18px;border-radius:999px;background:{$cardColor};border:1px solid {$cardBorder};font-size:22px;font-weight:800;color:{$accent};margin-bottom:28px}
h1{font-size:76px;line-height:1.08;letter-spacing:-2px;margin:0 0 26px;font-weight:900;max-width:900px}
.subtitle{font-size:32px;line-height:1.5;color:{$secondaryColor};margin:0 0 34px;max-width:850px}
ul{display:grid;gap:17px;list-style:none;margin:0;padding:0}
li{display:flex;align-items:flex-start;gap:18px;font-size:28px;line-height:1.4;font-weight:650}
.bullet-dot{width:14px;height:14px;margin-top:13px;border-radius:4px;flex:0 0 auto;background:{$accent};box-shadow:0 0 18px {$accent}}
.visual-card{height:650px;padding:36px;border-radius:42px;background:{$cardColor};border:1px solid {$cardBorder};box-shadow:0 30px 90px rgba(0,0,0,.24);display:flex;align-items:center;justify-content:center}
.visual-card svg{width:100%;height:100%;overflow:visible}
.footer{position:absolute;left:84px;right:84px;bottom:36px;display:flex;align-items:center;gap:18px;color:{$secondaryColor};font-size:19px}
.footer-line{height:2px;flex:1;background:linear-gradient(90deg,{$accent},transparent);opacity:.65}
</style>
</head>
<body>
<main class="slide">
  <div class="grid"></div><div class="glow"></div>
  <div class="topline">
    <div class="brand"><span class="brand-mark"></span><span>{$brand}</span></div>
    <span>{$sceneLabel}</span>
  </div>
  <section class="content">
    <div class="copy">
      <div class="eyebrow">{$sceneLabel}</div>
      <h1>{$title}</h1>
      <p class="subtitle">{$subtitle}</p>
      <ul>{$bulletHtml}</ul>
    </div>
    <div class="visual-card">{$visual}</div>
  </section>
  <div class="footer"><span>EDUAI</span><div class="footer-line"></div><span>{$number}/{$total}</span></div>
</main>
</body>
</html>
HTML;
    }

    private function renderVisualSvg(
        array $visual,
        string $title,
        string $accent,
        string $textColor,
        bool $isRtl
    ): string {
        $type = (string) ($visual['type'] ?? 'concept_map');
        $labels = is_array($visual['labels'] ?? null)
            ? array_slice(array_values($visual['labels']), 0, 5)
            : [];
        $values = is_array($visual['values'] ?? null)
            ? array_slice(array_values($visual['values']), 0, 5)
            : [];

        if ($labels === []) {
            $labels = $isRtl ? ['الفكرة', 'التطبيق', 'النتيجة'] : ['Idea', 'Application', 'Outcome'];
        }

        return match ($type) {
            'bar_chart' => $this->renderBarChart($labels, $values, $accent, $textColor),
            'process' => $this->renderProcess($labels, $accent, $textColor),
            'timeline' => $this->renderTimeline($labels, $accent, $textColor),
            'comparison' => $this->renderComparison($labels, $accent, $textColor),
            default => $this->renderConceptMap($title, $labels, $accent, $textColor),
        };
    }

    private function renderBarChart(array $labels, array $values, string $accent, string $textColor): string
    {
        $bars = '';
        $count = max(1, count($labels));
        $barWidth = 580 / $count;

        foreach ($labels as $index => $label) {
            $value = max(12, min(100, (float) ($values[$index] ?? (35 + ($index * 18)))));
            $height = $value * 4.1;
            $x = 55 + ($index * $barWidth);
            $y = 500 - $height;
            $bars .= '<rect x="'.$x.'" y="'.$y.'" width="'.($barWidth - 28)
                .'" height="'.$height.'" rx="18" fill="'.$accent.'" opacity="'
                .(0.55 + ($index * 0.09)).'"/>';
            $bars .= '<text x="'.($x + (($barWidth - 28) / 2)).'" y="545" text-anchor="middle" fill="'
                .$textColor.'" font-size="22">'.$this->escapeHtml((string) $label).'</text>';
            $bars .= '<text x="'.($x + (($barWidth - 28) / 2)).'" y="'.($y - 18)
                .'" text-anchor="middle" fill="'.$textColor.'" font-size="24" font-weight="800">'
                .round($value).'%</text>';
        }

        return '<svg viewBox="0 0 700 600" role="img"><line x1="40" y1="500" x2="670" y2="500" stroke="'
            .$textColor.'" opacity=".25" stroke-width="3"/>'.$bars.'</svg>';
    }

    private function renderProcess(array $labels, string $accent, string $textColor): string
    {
        $items = '';
        $count = max(1, count($labels));
        $step = 560 / max(1, $count - 1);

        foreach ($labels as $index => $label) {
            $x = 70 + ($index * $step);
            if ($index > 0) {
                $items .= '<path d="M'.($x - $step + 64).' 300 H'.($x - 64)
                    .'" stroke="'.$accent.'" stroke-width="8" stroke-linecap="round" opacity=".55"/>';
            }
            $items .= '<circle cx="'.$x.'" cy="300" r="62" fill="'.$accent.'" opacity=".88"/>'
                .'<text x="'.$x.'" y="310" text-anchor="middle" fill="#fff" font-size="30" font-weight="900">'
                .($index + 1).'</text>'
                .'<text x="'.$x.'" y="405" text-anchor="middle" fill="'.$textColor
                .'" font-size="23">'.$this->escapeHtml((string) $label).'</text>';
        }

        return '<svg viewBox="0 0 700 600" role="img">'.$items.'</svg>';
    }

    private function renderTimeline(array $labels, string $accent, string $textColor): string
    {
        $items = '<line x1="350" y1="70" x2="350" y2="530" stroke="'.$accent
            .'" stroke-width="9" opacity=".55"/>';
        $step = 440 / max(1, count($labels) - 1);

        foreach ($labels as $index => $label) {
            $y = 80 + ($index * $step);
            $right = $index % 2 === 0;
            $x = $right ? 410 : 290;
            $anchor = $right ? 'start' : 'end';
            $items .= '<circle cx="350" cy="'.$y.'" r="21" fill="'.$accent.'"/>'
                .'<line x1="'.($right ? 371 : 329).'" y1="'.$y.'" x2="'.$x
                .'" y2="'.$y.'" stroke="'.$accent.'" stroke-width="5"/>'
                .'<text x="'.$x.'" y="'.($y + 8).'" text-anchor="'.$anchor
                .'" fill="'.$textColor.'" font-size="25" font-weight="700">'
                .$this->escapeHtml((string) $label).'</text>';
        }

        return '<svg viewBox="0 0 700 600" role="img">'.$items.'</svg>';
    }

    private function renderComparison(array $labels, string $accent, string $textColor): string
    {
        $left = $this->escapeHtml((string) ($labels[0] ?? 'Before'));
        $right = $this->escapeHtml((string) ($labels[1] ?? 'After'));

        return '<svg viewBox="0 0 700 600" role="img">'
            .'<rect x="45" y="100" width="285" height="360" rx="36" fill="'.$accent.'" opacity=".22"/>'
            .'<rect x="370" y="100" width="285" height="360" rx="36" fill="'.$accent.'" opacity=".55"/>'
            .'<text x="187" y="220" text-anchor="middle" fill="'.$textColor.'" font-size="38" font-weight="900">'
            .$left.'</text><text x="512" y="220" text-anchor="middle" fill="'.$textColor
            .'" font-size="38" font-weight="900">'.$right.'</text>'
            .'<path d="M305 280 H395" stroke="'.$accent.'" stroke-width="12" stroke-linecap="round"/>'
            .'<path d="M380 255 L410 280 L380 305" fill="none" stroke="'.$accent
            .'" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    private function renderConceptMap(
        string $title,
        array $labels,
        string $accent,
        string $textColor
    ): string {
        $positions = [[350, 90], [590, 235], [500, 485], [200, 485], [110, 235]];
        $items = '';

        foreach ($labels as $index => $label) {
            [$x, $y] = $positions[$index] ?? $positions[$index % count($positions)];
            $items .= '<line x1="350" y1="300" x2="'.$x.'" y2="'.$y.'" stroke="'
                .$accent.'" stroke-width="5" opacity=".45"/>'
                .'<rect x="'.($x - 90).'" y="'.($y - 42).'" width="180" height="84" rx="24" fill="'
                .$accent.'" opacity=".82"/>'
                .'<text x="'.$x.'" y="'.($y + 8).'" text-anchor="middle" fill="#fff" font-size="22" font-weight="800">'
                .$this->escapeHtml((string) $label).'</text>';
        }

        $center = mb_strlen($title) > 28 ? mb_substr($title, 0, 26).'...' : $title;

        return '<svg viewBox="0 0 700 600" role="img">'.$items
            .'<circle cx="350" cy="300" r="112" fill="'.$accent.'" opacity=".28"/>'
            .'<circle cx="350" cy="300" r="88" fill="'.$accent.'"/>'
            .'<text x="350" y="292" text-anchor="middle" fill="#fff" font-size="25" font-weight="900">'
            .$this->escapeHtml($center).'</text>'
            .'<text x="350" y="328" text-anchor="middle" fill="#fff" font-size="18" opacity=".8">CORE IDEA</text>'
            .'</svg>';
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function htmlToImage(string $htmlPath, string $outputPng): void
    {
        $browsershotError = null;

        // The slide is self-contained, so Chromium does not depend on a CDN.
        try {
            $browsershot = \Spatie\Browsershot\Browsershot::html(
                (string) file_get_contents($htmlPath)
            )
                ->windowSize(1920, 1080)
                ->setOption('deviceScaleFactor', 1)
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--font-render-hinting=none',
                ])
                ->setDelay(350)
                ->noSandbox()
                ->dismissDialogs();

            foreach (['node' => 'setNodeBinary', 'npm' => 'setNpmBinary', 'chrome' => 'setChromePath'] as $key => $method) {
                $path = trim((string) config("services.video_explainer.{$key}", ''));
                if ($path !== '') {
                    $browsershot->{$method}($path);
                }
            }

            $browsershot->save($outputPng);

            Log::info("VideoExplainerService: Browsershot OK → {$outputPng}");

            return;
        } catch (\Exception $e) {
            $browsershotError = $e->getMessage();
            Log::warning("VideoExplainerService: Browsershot failed ({$e->getMessage()}), trying wkhtmltoimage");
        }

        // Fallback: wkhtmltoimage
        $out = [];
        $cmd = sprintf(
            '%s --width 1920 --height 1080 --quality 90 %s %s 2>&1',
            $this->binary('wkhtmltoimage'),
            escapeshellarg($htmlPath),
            escapeshellarg($outputPng)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || ! file_exists($outputPng)) {
            $wkhtmltoimageError = trim(implode(PHP_EOL, $out));

            throw new \RuntimeException(sprintf(
                'Unable to render explainer slide. Browsershot failed: %s. wkhtmltoimage failed (exit %d): %s',
                $browsershotError ?: 'unknown error',
                $code,
                $wkhtmltoimageError !== '' ? $wkhtmltoimageError : 'no output'
            ));
        }

        Log::info("VideoExplainerService: wkhtmltoimage OK → {$outputPng}");
    }

    // ─── Text → Speech ────────────────────────────────────────────────────────

    private function textToSpeech(string $text, string $outputMp3, string $voice): void
    {
        if (empty(trim($text))) {
            // Generate 1 s of silence
            $this->generateSilence($outputMp3, 1.0);

            return;
        }

        if (AppSetting::getValue('tts_provider', 'edge') === 'gemini') {
            if ($this->textToSpeechWithGemini($text, $outputMp3)) {
                return;
            }

            Log::warning('VideoExplainerService: Gemini TTS failed. Trying Edge TTS.');
        }

        // Strategy 1: remote Edge TTS API
        if ($this->textToSpeechWithApi($text, $outputMp3, $voice)) {
            return;
        }

        // Strategy 2: local edge-tts CLI (pip install edge-tts)
        $txtFile = str_replace('.mp3', '.txt', $outputMp3);
        file_put_contents($txtFile, $text);

        $cmd = sprintf(
            '%s --voice %s --file %s --write-media %s 2>&1',
            $this->binary('edge_tts'),
            escapeshellarg($voice),
            escapeshellarg($txtFile),
            escapeshellarg($outputMp3)
        );
        exec($cmd, $out, $code);

        if ($code === 0 && file_exists($outputMp3) && filesize($outputMp3) > 0) {
            Log::info('VideoExplainerService: local edge-tts OK');

            return;
        }

        Log::warning("VideoExplainerService: local edge-tts failed (exit {$code}). Trying Gemini TTS.");

        // Strategy 3: Gemini TTS (our existing service)
        try {
            /** @var \App\Services\GeminiTtsService $geminiTts */
            $geminiTts = app(\App\Services\GeminiTtsService::class);
            $relPath = $geminiTts->generateAudio($text);

            if ($relPath) {
                $absPath = Storage::disk('public')->path($relPath);
                copy($absPath, $outputMp3);
                Storage::disk('public')->delete($relPath); // Clean up
                Log::info('VideoExplainerService: Gemini TTS OK');

                return;
            }
        } catch (\Exception $e) {
            Log::warning('VideoExplainerService: Gemini TTS exception – '.$e->getMessage());
        }

        // Strategy 4: silence fallback
        Log::warning('VideoExplainerService: All TTS strategies failed, using silence.');
        $this->generateSilence($outputMp3, max(3.0, str_word_count($text) / 2.5));
    }

    private function textToSpeechWithGemini(string $text, string $outputMp3): bool
    {
        try {
            /** @var \App\Services\GeminiTtsService $geminiTts */
            $geminiTts = app(\App\Services\GeminiTtsService::class);
            $relPath = $geminiTts->generateAudio($text);

            if ($relPath) {
                $absPath = Storage::disk('public')->path($relPath);
                copy($absPath, $outputMp3);
                Storage::disk('public')->delete($relPath);
                Log::info('VideoExplainerService: Gemini TTS OK');

                return true;
            }
        } catch (\Exception $e) {
            Log::warning('VideoExplainerService: Gemini TTS exception - '.$e->getMessage());
        }

        return false;
    }

    private function textToSpeechWithApi(string $text, string $outputMp3, string $voice): bool
    {
        $url = trim((string) config('services.video_explainer.tts_api_url'));
        if ($url === '') {
            return false;
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

            $audio = $response->body();
            if ($response->successful() && $this->isMp3($audio)) {
                file_put_contents($outputMp3, $audio);
                Log::info('VideoExplainerService: Edge TTS API OK', [
                    'voice' => $voice,
                    'bytes' => strlen($audio),
                ]);

                return true;
            }

            $detail = $response->json('detail');
            Log::warning('VideoExplainerService: Edge TTS API failed', [
                'status' => $response->status(),
                'voice' => $voice,
                'detail' => is_string($detail) ? $detail : 'Response was not a valid MP3 file.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('VideoExplainerService: Edge TTS API exception - '.$e->getMessage());
        }

        return false;
    }

    private function isMp3(string $audio): bool
    {
        if (strlen($audio) < 100) {
            return false;
        }

        if (str_starts_with($audio, 'ID3')) {
            return true;
        }

        $limit = min(strlen($audio) - 1, 4096);
        for ($i = 0; $i < $limit; $i++) {
            if (ord($audio[$i]) === 0xFF && (ord($audio[$i + 1]) & 0xE0) === 0xE0) {
                return true;
            }
        }

        return false;
    }

    private function generateSilence(string $outputMp3, float $seconds): void
    {
        $cmd = sprintf(
            '%s -f lavfi -i anullsrc=r=44100:cl=mono -t %.2f -q:a 9 -acodec libmp3lame %s -y 2>&1',
            $this->binary('ffmpeg'),
            $seconds,
            escapeshellarg($outputMp3)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || ! file_exists($outputMp3)) {
            // Create a valid minimal MP3 so the pipeline doesn't crash
            file_put_contents($outputMp3, $this->minimalMp3Bytes());
        }
    }

    /** Returns the bytes of a tiny valid MP3 frame (silence) */
    private function minimalMp3Bytes(): string
    {
        return str_repeat("\xFF\xFB\x90\x00".str_repeat("\x00", 413), 10);
    }

    // ─── FFmpeg helpers ───────────────────────────────────────────────────────

    private function getAudioDuration(string $audioPath): float
    {
        $cmd = sprintf(
            '%s -v error -show_entries format=duration -of csv=p=0 %s 2>&1',
            $this->binary('ffprobe'),
            escapeshellarg($audioPath)
        );
        exec($cmd, $out, $code);

        return (float) ($out[0] ?? 5.0);
    }

    private function writeSubtitleTrack(
        string $subtitlePath,
        string $narration,
        float $duration,
        string $language
    ): void {
        $phrases = $this->splitCaptionPhrases($narration, $language);
        if ($phrases === []) {
            return;
        }

        $font = str_replace(',', '', (string) config(
            'services.video_explainer.subtitle_font',
            'DejaVu Sans'
        ));
        $header = <<<ASS
[Script Info]
ScriptType: v4.00+
PlayResX: 1920
PlayResY: 1080
WrapStyle: 2
ScaledBorderAndShadow: yes

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Default,{$font},50,&H00FFFFFF,&H00FFFFFF,&H00111111,&H90000000,-1,0,0,0,100,100,0,0,3,2,0,2,120,120,52,1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
ASS;

        $weights = array_map(fn (string $phrase) => max(1, mb_strlen($phrase)), $phrases);
        $totalWeight = array_sum($weights);
        $elapsed = 0.0;
        $events = [];

        foreach ($phrases as $index => $phrase) {
            $start = $elapsed;
            $elapsed = $index === array_key_last($phrases)
                ? $duration
                : min($duration, $elapsed + ($duration * ($weights[$index] / $totalWeight)));
            $events[] = sprintf(
                'Dialogue: 0,%s,%s,Default,,0,0,0,,{\\fad(90,90)}%s',
                $this->assTimestamp($start),
                $this->assTimestamp(max($start + 0.1, $elapsed)),
                $this->escapeAssText($phrase)
            );
        }

        file_put_contents(
            $subtitlePath,
            "\xEF\xBB\xBF".$header."\n".implode("\n", $events)."\n"
        );
    }

    private function splitCaptionPhrases(string $narration, string $language): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $narration) ?? '');
        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        if (count($words) === 1 && mb_strlen($text) > 18) {
            return array_values(array_filter(preg_split('/(?<=.{14})/u', $text) ?: []));
        }

        $maxWords = in_array($language, ['ar', 'ar-sa'], true) ? 7 : 8;
        $phrases = [];
        $current = [];

        foreach ($words as $word) {
            $current[] = $word;
            $hasNaturalBreak = preg_match('/[.!?؟،,:;؛]$/u', $word) === 1;
            if (count($current) >= $maxWords || ($hasNaturalBreak && count($current) >= 4)) {
                $phrases[] = implode(' ', $current);
                $current = [];
            }
        }

        if ($current !== []) {
            $phrases[] = implode(' ', $current);
        }

        return $phrases;
    }

    private function assTimestamp(float $seconds): string
    {
        $centiseconds = max(0, (int) round($seconds * 100));
        $hours = intdiv($centiseconds, 360000);
        $centiseconds %= 360000;
        $minutes = intdiv($centiseconds, 6000);
        $centiseconds %= 6000;
        $wholeSeconds = intdiv($centiseconds, 100);
        $fraction = $centiseconds % 100;

        return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $wholeSeconds, $fraction);
    }

    private function escapeAssText(string $text): string
    {
        return str_replace(
            ['\\', '{', '}', "\r", "\n"],
            ['\\\\', '\\{', '\\}', '', '\\N'],
            $text
        );
    }

    private function buildClip(
        string $imgPath,
        string $audioPath,
        string $outputClip,
        float $duration,
        ?string $subtitlePath
    ): void {
        // Base: static image + audio → mp4
        $subtitleFilter = '';
        if ($subtitlePath && file_exists($subtitlePath)) {
            $subtitleFilter = ",ass='".$this->escapeFilterPath($subtitlePath)."'";
        }

        $vf = "scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2{$subtitleFilter}";

        $cmd = sprintf(
            '%s -loop 1 -i %s -i %s -c:v libx264 -tune stillimage -c:a aac -b:a 128k -vf %s -pix_fmt yuv420p -t %.2f %s -y 2>&1',
            $this->binary('ffmpeg'),
            escapeshellarg($imgPath),
            escapeshellarg($audioPath),
            escapeshellarg($vf),
            $duration + 0.1,
            escapeshellarg($outputClip)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || ! file_exists($outputClip)) {
            $outLog = implode("\n", array_slice($out, -20));
            throw new \Exception("FFmpeg buildClip failed (exit {$code}): {$outLog}");
        }

        Log::info("VideoExplainerService: Built clip → {$outputClip}");
    }

    private function concatClips(array $clipPaths, string $tmpDir, string $jobId): string
    {
        $listFile = "{$tmpDir}/concat.txt";
        $lines = array_map(fn ($p) => "file '".str_replace("'", "'\\''", $p)."'", $clipPaths);
        file_put_contents($listFile, implode("\n", $lines));

        $outputMp4 = "{$tmpDir}/final-{$jobId}.mp4";

        $cmd = sprintf(
            '%s -f concat -safe 0 -i %s -c copy %s -y 2>&1',
            $this->binary('ffmpeg'),
            escapeshellarg($listFile),
            escapeshellarg($outputMp4)
        );
        exec($cmd, $out, $code);

        if ($code !== 0 || ! file_exists($outputMp4)) {
            $outLog = implode("\n", array_slice($out, -20));
            throw new \Exception("FFmpeg concat failed (exit {$code}): {$outLog}");
        }

        return $outputMp4;
    }

    // ─── Utilities ────────────────────────────────────────────────────────────

    private function binary(string $name): string
    {
        $defaults = [
            'ffmpeg' => 'ffmpeg',
            'ffprobe' => 'ffprobe',
            'edge_tts' => 'edge-tts',
            'wkhtmltoimage' => 'wkhtmltoimage',
        ];
        $default = $defaults[$name] ?? $name;
        $binary = trim((string) config("services.video_explainer.{$name}", $default));

        return escapeshellarg($binary !== '' ? $binary : $default);
    }

    private function escapeFilterPath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return str_replace(
            [':', "'", ',', '[', ']'],
            ['\\:', "\\'", '\\,', '\\[', '\\]'],
            $path
        );
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
