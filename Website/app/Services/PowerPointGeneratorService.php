<?php

namespace App\Services;

use App\Models\Presentation;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Alignment;

class PowerPointGeneratorService
{
    public function generate(Presentation $presentation): string
    {
        $fileName = 'presentation-' . $presentation->id . '.pptx';
        $directory = 'public/presentations';
        $fullPath = storage_path('app/' . $directory . '/' . $fileName);

        // Ensure directory exists
        Storage::makeDirectory($directory);

        // Initialize PHP Presentation
        $objPHPPresentation = new PhpPresentation();
        
        // Set Layout to 16:9
        $objPHPPresentation->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);
        
        // Remove default first slide
        $objPHPPresentation->removeSlideByIndex(0);

        // Prepare CSS for individual slide screenshot
        // We render one slide at a time to capture high-res image
        
        $tailwindScript = '<script src="https://cdn.tailwindcss.com"></script>';
        $customCss = "
            <style>
                @page { margin: 0px; }
                body { margin: 0; padding: 0; background-color: white; overflow: hidden; font-size: 24px; line-height: 1.6; }
                .slide { width: 1920px; height: 1080px; position: relative; overflow: hidden; display: flex; flex-direction: column; }
            </style>
        ";
        
        // Loop through content and generate images
        if (is_array($presentation->content)) {
            foreach ($presentation->content as $index => $slideContent) {
                // Create temporary HTML for this single slide
                $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">' . $tailwindScript . $customCss . '</head><body>';
                $html .= '<div class="slide">' . $slideContent . '</div>';
                $html .= '</body></html>';
                
                $imagePath = storage_path('app/' . $directory . '/temp_slide_' . $presentation->id . '_' . $index . '.png');
                
                // Screenshot using Browsershot
                $browsershot = Browsershot::html($html)
                    ->windowSize(1920, 1080)
                    ->setOption('deviceScaleFactor', 2) // High resolution
                    ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--disable-features=VizDisplayCompositor'])
                    ->waitUntilNetworkIdle();

                if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                    $browsershot->setNodeBinary('/usr/bin/node');
                    $browsershot->setNpmBinary('/usr/bin/npm');
                    $browsershot->setChromePath('/usr/bin/google-chrome');
                }

                $browsershot->save($imagePath);
                
                // Create PPT Slide
                $currentSlide = $objPHPPresentation->createSlide();
                
                // Add Image to Slide
                $shape = $currentSlide->createDrawingShape();
                $shape->setName('Slide ' . ($index + 1));
                $shape->setDescription('Slide ' . ($index + 1));
                $shape->setPath($imagePath);
                $shape->setWidth(960); // Standard PPT width in points (approx 13.33 inches)
                $shape->setHeight(540); // Standard PPT height in points (approx 7.5 inches)
                $shape->setOffsetX(0);
                $shape->setOffsetY(0);
                
                // Clean up temp image immediately to save space? 
                // Alternatively, keep them for debugging or delete after loop. We'll delete after loop.
            }
        }

        // Save generated PPTX
        $oWriterPPTX = IOFactory::createWriter($objPHPPresentation, 'PowerPoint2007');
        $oWriterPPTX->save($fullPath);

        // Cleanup temp images
        $tempFiles = glob(storage_path('app/' . $directory . '/temp_slide_' . $presentation->id . '_*.png'));
        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Save relative path to DB
        $relativePath = 'presentations/' . $fileName;
        $presentation->update(['ppt_path' => $relativePath]);

        // Relative path for storage download
        return $relativePath;
    }
}
