<?php

namespace App\Jobs;

use App\Models\ToolJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConvertAnimationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    protected $jobId;

    public function __construct($jobId)
    {
        $this->jobId = $jobId;
    }

    public function handle()
    {
        $toolJob = ToolJob::find($this->jobId);

        if (! $toolJob || $toolJob->isCancelled() || ! isset($toolJob->results['svg_path'])) {
            Log::error("ConvertAnimationJob: Job or SVG not found for ID {$this->jobId}");

            return;
        }

        $svgPath = $toolJob->results['svg_path'];
        $inputPath = Storage::path('public/'.$svgPath);

        // Output filenames
        $mp4Filename = 'animations/anim-'.$toolJob->id.'.mp4';
        $gifFilename = 'animations/anim-'.$toolJob->id.'.gif';

        $mp4Path = Storage::path('public/'.$mp4Filename);
        $gifPath = Storage::path('public/'.$gifFilename);

        // Temp PNG for conversion (using ImageMagick)
        $tempPng = Storage::path('public/animations/temp-'.$toolJob->id.'.png');

        // 1. Convert SVG to high-res PNG
        if (! file_exists($inputPath) || filesize($inputPath) < 10) {
            Log::error('ConvertAnimationJob: Input SVG is invalid or empty: '.$inputPath);

            return;
        }

        // Correct order: magick [input-options] input [operation-options] output
        $magickCommand = "magick -density 150 -background white \"{$inputPath}\" -flatten \"{$tempPng}\" 2>&1";
        exec($magickCommand, $magickOutput, $magickReturn);

        $toolJob->refresh();
        if ($toolJob->isCancelled()) {
            @unlink($tempPng);

            return;
        }

        if ($magickReturn !== 0 || ! file_exists($tempPng)) {
            Log::error('ConvertAnimationJob: ImageMagick Failed: '.implode("\n", $magickOutput));

            return;
        }

        // 2. Create MP4 (Loop 5s, enforce even dimensions)
        // scale=trunc(iw/2)*2:trunc(ih/2)*2 ensures width/height are divisible by 2 for libx264
        $ffmpegMp4 = "ffmpeg -y -loop 1 -i \"{$tempPng}\" -c:v libx264 -t 5 -pix_fmt yuv420p -vf \"scale=trunc(iw/2)*2:trunc(ih/2)*2\" \"{$mp4Path}\" 2>&1";
        exec($ffmpegMp4, $mp4Output, $mp4Return);

        $toolJob->refresh();
        if ($toolJob->isCancelled()) {
            @unlink($tempPng);
            @unlink($mp4Path);

            return;
        }

        if ($mp4Return !== 0) {
            Log::error('ConvertAnimationJob: MP4 Conversion Failed: '.implode("\n", $mp4Output));
        }

        // 3. Create GIF (Loop 5s, optimized palette)
        $ffmpegGif = "ffmpeg -y -loop 1 -i \"{$tempPng}\" -t 5 -vf \"scale=512:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse\" \"{$gifPath}\" 2>&1";
        exec($ffmpegGif, $gifOutput, $gifReturn);

        $toolJob->refresh();
        if ($toolJob->isCancelled()) {
            @unlink($tempPng);
            @unlink($mp4Path);
            @unlink($gifPath);

            return;
        }

        if ($gifReturn !== 0) {
            Log::error('ConvertAnimationJob: GIF Conversion Failed: '.implode("\n", $gifOutput));
        }

        // Cleanup
        @unlink($tempPng);

        // Update Job Results
        $results = $toolJob->results;
        if (file_exists($mp4Path)) {
            $results['mp4_path'] = $mp4Filename;
        }
        if (file_exists($gifPath)) {
            $results['gif_path'] = $gifFilename;
        }

        $toolJob->results = $results;
        $toolJob->save();

        Log::info("ConvertAnimationJob: Conversion completed for Job ID {$this->jobId}");
    }
}
