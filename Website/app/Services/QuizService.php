<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Jobs\GenerateQuizJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class QuizService
{
    /**
     * Create a quiz from raw text and dispatch the generation job (authenticated users).
     */
    public function createFromText(User $user, string $text, string $title, int $maxQuestions = 5): Quiz
    {
        $quiz = Quiz::create([
            'user_id'       => $user->id,
            'title'         => $title,
            'source_type'   => 'text',
            'source_text'   => $text,
            'max_questions' => $maxQuestions,
            'status'        => 'pending',
        ]);

        GenerateQuizJob::dispatch($quiz);

        return $quiz;
    }

    /**
     * Create a quiz from raw text and generate questions SYNCHRONOUSLY (guest users).
     * Does not require an authenticated user. Calls QuestgenService directly.
     */
    public function createFromTextSync(?User $user, string $text, string $title, int $maxQuestions = 3): Quiz
    {
        $quiz = Quiz::create([
            'user_id'       => $user?->id,
            'title'         => $title,
            'source_type'   => 'text',
            'source_text'   => $text,
            'max_questions' => $maxQuestions,
            'status'        => 'processing',
        ]);

        try {
            $questgen  = app(QuestgenService::class);
            $questions = $questgen->generate($text, $maxQuestions);

            $this->saveQuestions($quiz, $questions);

            $quiz->update(['status' => 'done']);
            $quiz->load('questions');

        } catch (\Exception $e) {
            Log::error("GuestQuiz #{$quiz->id} generation failed: " . $e->getMessage());
            $quiz->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $quiz;
    }

    /**
     * Create a quiz from an uploaded file, extract text, then dispatch job.
     */
    public function createFromFile(User $user, UploadedFile $file, string $title, int $maxQuestions = 5): Quiz
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $text      = $this->extractText($file, $extension);

        $quiz = Quiz::create([
            'user_id'       => $user->id,
            'title'         => $title ?: $file->getClientOriginalName(),
            'source_type'   => $extension,
            'source_text'   => $text,
            'max_questions' => $maxQuestions,
            'status'        => 'pending',
        ]);

        GenerateQuizJob::dispatch($quiz);

        return $quiz;
    }

    /**
     * Extract text from an uploaded file based on its extension.
     */
    private function extractText(UploadedFile $file, string $extension): string
    {
        return match ($extension) {
            'pdf'         => $this->extractFromPdf($file),
            'docx', 'doc' => $this->extractFromDocx($file),
            'pptx', 'ppt' => $this->extractFromPptx($file),
            default       => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    private function extractFromPdf(UploadedFile $file): string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \Exception('PDF parser not installed. Run: composer require smalot/pdfparser');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($file->getRealPath());
        $text   = $pdf->getText();

        if (empty(trim($text))) {
            throw new \Exception('Could not extract text from PDF. The file may be a scanned image PDF.');
        }

        return trim($text);
    }

    private function extractFromDocx(UploadedFile $file): string
    {
        if (!class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            throw new \Exception('PHPWord not installed. Run: composer require phpoffice/phpword');
        }

        $phpWord  = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
        $sections = $phpWord->getSections();
        $text     = '';

        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text .= $childElement->getText() . ' ';
                        }
                    }
                }
            }
        }

        if (empty(trim($text))) {
            throw new \Exception('Could not extract text from DOCX file.');
        }

        return trim($text);
    }

    private function extractFromPptx(UploadedFile $file): string
    {
        if (!class_exists(\PhpOffice\PhpPresentation\IOFactory::class)) {
            throw new \Exception('PHPPresentation not installed. Run: composer require phpoffice/phppresentation');
        }

        $presentation = \PhpOffice\PhpPresentation\IOFactory::load($file->getRealPath());
        $text         = '';

        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $element) {
                            $text .= $element->getText() . ' ';
                        }
                    }
                }
            }
        }

        if (empty(trim($text))) {
            throw new \Exception('Could not extract text from PPTX file.');
        }

        return trim($text);
    }

    /**
     * Save questions from the Questgen API response into the database.
     */
    public function saveQuestions(Quiz $quiz, array $questions): void
    {
        $order = 1;
        foreach ($questions as $q) {
            $questionText  = $q['question_statement'] ?? $q['Question'] ?? null;
            $correctAnswer = $q['answer'] ?? $q['Answer'] ?? null;
            $options       = $q['options'] ?? [];

            if (!$questionText || !$correctAnswer) {
                continue;
            }

            QuizQuestion::create([
                'quiz_id'       => $quiz->id,
                'question_text' => $questionText,
                'correct_answer'=> strtolower(trim($correctAnswer)),
                'options'       => array_values($options),
                'order'         => $order++,
            ]);
        }
    }
}
