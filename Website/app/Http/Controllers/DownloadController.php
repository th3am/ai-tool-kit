<?php

namespace App\Http\Controllers;

use App\Models\Presentation;
use App\Models\ToolJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Browsershot\Browsershot;

class DownloadController extends Controller
{
    // ─── Presentation PDF ────────────────────────────────────────────────────

    public function downloadPresentation(Request $request, Presentation $presentation, \App\Services\PdfGeneratorService $pdfService)
    {
        $this->authorizeDownload($request, $presentation->user_id);

        $path = $presentation->pdf_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            try {
                $path = $pdfService->generate($presentation);
            } catch (\Exception $e) {
                abort(500, 'PDF generation failed: ' . $e->getMessage());
            }
        }

        $fullPath = Storage::disk('public')->path($path);

        return response()->download(
            $fullPath,
            'presentation-' . $presentation->id . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    // ─── Presentation PowerPoint ──────────────────────────────────────────────

    public function downloadPowerPoint(Request $request, Presentation $presentation, \App\Services\PowerPointGeneratorService $pptService)
    {
        $this->authorizeDownload($request, $presentation->user_id);

        $path = $presentation->ppt_path;

        if (!$path || !Storage::disk('public')->exists($path)) {
            try {
                $path = $pptService->generate($presentation);
            } catch (\Exception $e) {
                abort(500, 'PowerPoint generation failed: ' . $e->getMessage());
            }
        }

        $fullPath = Storage::disk('public')->path($path);

        return response()->download(
            $fullPath,
            'presentation-' . $presentation->id . '.pptx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation']
        );
    }

    // ─── Mind Map PNG ─────────────────────────────────────────────────────────

    public function downloadMindMapPng(Request $request, int $job)
    {
        $toolJob = ToolJob::findOrFail($job);

        $this->authorizeDownload($request, $toolJob->user_id);

        $path = $toolJob->results['png_path'] ?? null;

        if (!$path || !Storage::disk('public')->exists($path)) {
            $path = $this->generateMindMapPng($toolJob);
        }

        return response()->download(
            Storage::disk('public')->path($path),
            'mindmap-' . $job . '.png',
            ['Content-Type' => 'image/png']
        );
    }

    // ─── Animation ────────────────────────────────────────────────────────────

    public function downloadMindMapSvg(Request $request, int $job)
    {
        $toolJob = ToolJob::findOrFail($job);
        $this->authorizeDownload($request, $toolJob->user_id);

        $markdown = $toolJob->results['raw_markdown'] ?? null;
        abort_if(!$markdown, 404, 'Mind map content not found.');

        try {
            $svg = $this->configureBrowsershot(Browsershot::html($this->mindMapExportHtml($markdown)))
                ->windowSize(1600, 1000)
                ->setDelay(900)
                ->evaluate("() => {
                    const svg = document.querySelector('#mindmap');
                    if (!svg) return '';
                    svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
                    return svg.outerHTML;
                }");
        } catch (\Throwable $e) {
            Log::error('Mind map SVG generation failed: '.$e->getMessage());
            abort(500, 'Mind map SVG generation failed.');
        }

        abort_if(!is_string($svg) || trim($svg) === '', 500, 'Mind map SVG generation failed.');

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mindmap-' . $job . '.svg"',
        ]);
    }

    private function generateMindMapPng(ToolJob $toolJob): string
    {
        $markdown = $toolJob->results['raw_markdown'] ?? null;
        abort_if(!$markdown, 404, 'Mind map content not found.');

        $fileName = 'mindmap-' . $toolJob->id . '.png';
        $relativePath = 'mindmaps/' . $fileName;
        $fullPath = Storage::disk('public')->path($relativePath);

        Storage::disk('public')->makeDirectory('mindmaps');

        try {
            $this->configureBrowsershot(Browsershot::html($this->mindMapExportHtml($markdown)))
                ->windowSize(1600, 1000)
                ->setDelay(900)
                ->save($fullPath);

            $results = $toolJob->results ?? [];
            $results['png_path'] = $relativePath;
            $toolJob->update(['results' => $results]);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::error('Mind map PNG generation failed: '.$e->getMessage());
            abort(500, 'Mind map PNG generation failed.');
        }
    }

    private function mindMapExportHtml(string $markdown): string
    {
        return view('downloads.mindmap', ['markdown' => $markdown])->render();
    }

    private function configureBrowsershot(Browsershot $browsershot): Browsershot
    {
        $browsershot->setOption('args', [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
        ])->waitUntilNetworkIdle(false);

        $node = config('services.video_explainer.node');
        $npm = config('services.video_explainer.npm');
        $chrome = config('services.video_explainer.chrome');

        if ($node) {
            $browsershot->setNodeBinary($node);
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $browsershot->setNodeBinary('/usr/bin/node');
        }

        if ($npm) {
            $browsershot->setNpmBinary($npm);
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $browsershot->setNpmBinary('/usr/bin/npm');
        }

        if ($chrome) {
            $browsershot->setChromePath($chrome);
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $browsershot->setChromePath('/usr/bin/google-chrome');
        }

        return $browsershot;
    }

    private function authorizeDownload(Request $request, ?int $ownerUserId): void
    {
        if ($request->hasValidSignature()) {
            return;
        }

        $userId = $this->downloadUserId($request);

        abort_if(!$userId || !$ownerUserId || (int) $userId !== (int) $ownerUserId, 403, 'Invalid or missing download token.');
    }

    private function downloadUserId(Request $request): ?int
    {
        if (auth()->id()) {
            return (int) auth()->id();
        }

        $token = $request->bearerToken() ?: $request->query('token');

        if (!is_string($token) || trim($token) === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $user = $accessToken?->tokenable;

        return $user?->id ? (int) $user->id : null;
    }

    public function downloadAudio(Request $request, int $job)
    {
        $toolJob = ToolJob::findOrFail($job);
        $this->authorizeDownload($request, $toolJob->user_id);

        $path = $toolJob->results['audio_path'] ?? null;
        abort_if(!$path || !Storage::disk('public')->exists($path), 404, 'Audio file not found.');

        return response()->download(
            Storage::disk('public')->path($path),
            'audio-' . $job . '.mp3',
            ['Content-Type' => 'audio/mpeg']
        );
    }

    public function downloadAnimation(Request $request, int $job, string $format)
    {
        $toolJob = ToolJob::findOrFail($job);
        $this->authorizeDownload($request, $toolJob->user_id);

        $results = $toolJob->results ?? [];

        switch ($format) {
            case 'svg':
                $path = $results['svg_path'] ?? null;
                if ($path && Storage::disk('public')->exists($path)) {
                    return response()->download(
                        Storage::disk('public')->path($path),
                        'animation-' . $job . '.svg',
                        ['Content-Type' => 'image/svg+xml']
                    );
                }
                // Inline SVG fallback
                $svgContent = $results['svg_content'] ?? null;
                if ($svgContent) {
                    return response($svgContent, 200, [
                        'Content-Type'        => 'image/svg+xml',
                        'Content-Disposition' => 'attachment; filename="animation-' . $job . '.svg"',
                    ]);
                }
                abort(404, 'SVG file not found.');

            case 'mp4':
                $path = $results['mp4_path'] ?? null;
                if ($path && Storage::disk('public')->exists($path)) {
                    return response()->download(
                        Storage::disk('public')->path($path),
                        'animation-' . $job . '.mp4',
                        ['Content-Type' => 'video/mp4']
                    );
                }
                abort(404, 'MP4 not ready yet. Check back shortly.');

            case 'gif':
                $path = $results['gif_path'] ?? null;
                if ($path && Storage::disk('public')->exists($path)) {
                    return response()->download(
                        Storage::disk('public')->path($path),
                        'animation-' . $job . '.gif',
                        ['Content-Type' => 'image/gif']
                    );
                }
                abort(404, 'GIF not ready yet.');

            default:
                abort(400, 'Invalid format.');
        }
    }

    // ─── Video Explainer MP4 ─────────────────────────────────────────────────

    public function downloadVideoExplainer(Request $request, int $job)
    {
        $toolJob = ToolJob::findOrFail($job);
        $this->authorizeDownload($request, $toolJob->user_id);

        if ($toolJob->status !== 'succeeded') {
            abort(425, 'Video is still being generated. Please wait.');
        }

        $path = $toolJob->results['video_path'] ?? null;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Video file not found.');
        }

        $topic    = $toolJob->params['topic'] ?? 'explainer';
        $filename = 'video-explainer-' . \Illuminate\Support\Str::slug(substr($topic, 0, 40)) . '.mp4';

        return response()->download(
            Storage::disk('public')->path($path),
            $filename,
            ['Content-Type' => 'video/mp4']
        );
    }
}
