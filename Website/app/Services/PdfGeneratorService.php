<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class PdfGeneratorService
{
    /**
     * Generate PDF from presentation and update database.
     *
     * @param Presentation $presentation
     * @return string Path to the PDF
     */
    public function generate(Presentation $presentation): string
    {
        // Fetch Tailwind Script (Full Build)
        // We use the CDN for simplicity. In production, consider a local build or proper asset pipeline.
        $tailwindScript = '<script src="https://cdn.tailwindcss.com"></script>';

        // Additional Custom CSS for slides specifically for PDF sizing (1920x1080)
        $customCss = "
            <style>
                @page { margin: 0px; size: 1920px 1080px; } /* Force 16:9 aspect ratio */
                body { margin: 0; padding: 0; width: 1920px; height: 1080px; overflow: hidden; -webkit-print-color-adjust: exact; font-size: 24px; line-height: 1.6; }
                .slide-container { width: 100%; height: 100%; }
                .slide { 
                    width: 1920px; 
                    height: 1080px; 
                    position: relative; 
                    overflow: hidden; 
                    page-break-after: always;
                    display: flex;
                    flex-direction: column;
                    background-color: white;
                }
            </style>
        ";
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            ' . $tailwindScript . '
            ' . $customCss . '
        </head>
        <body>';
        
        if (is_array($presentation->content)) {
            foreach ($presentation->content as $slide) {
                $html .= '<div class="slide">' . $slide . '</div>';
            }
        }
        $html .= '</body></html>';

        $fileName = 'presentation-' . $presentation->id . '.pdf';
        $directory = 'public/presentations';
        $fullPath = storage_path('app/' . $directory . '/' . $fileName);

        // Ensure directory exists
        Storage::makeDirectory($directory);

        // Generate PDF using Browsershot with correct dimensions
        // 1920x1080 pixels at 96 DPI is approx 508mm x 285.75mm
        try {
            $browsershot = Browsershot::html($html)
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--disable-features=VizDisplayCompositor'])
                ->setOption('landscape', true)
                ->setOption('printBackground', true)
                ->windowSize(1920, 1080)
                ->paperSize(13.3333, 7.5, 'in') // Standard PPT 16:9 (13.333 x 7.5 inches)
                ->margins(0, 0, 0, 0)
                ->waitUntilNetworkIdle();

            // Explicitly set Node/Npm paths for Ubuntu if needed (common fix)
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $browsershot->setNodeBinary('/usr/bin/node');
                $browsershot->setNpmBinary('/usr/bin/npm');
                $browsershot->setChromePath('/usr/bin/google-chrome');
            }

            $browsershot->save($fullPath);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Browsershot Error in Service: " . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("Browsershot Output: " . $e->getTraceAsString());
            throw $e;
        }

        // Save relative path to DB
        $relativePath = 'presentations/' . $fileName;
        $presentation->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }
}
