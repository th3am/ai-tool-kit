<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class PublicQuizController extends Controller
{
    /**
     * Show the public quiz taking page.
     */
    public function show(string $uuid)
    {
        $quiz = Quiz::where('share_uuid', $uuid)
            ->where('is_public', true)
            ->where('status', 'done')
            ->with('questions')
            ->firstOrFail();

        return view('quiz.public', compact('quiz'));
    }

    /**
     * Process and score the submitted quiz attempt.
     */
    public function attempt(Request $request, string $uuid)
    {
        $quiz = Quiz::where('share_uuid', $uuid)
            ->where('is_public', true)
            ->where('status', 'done')
            ->with('questions')
            ->firstOrFail();

        $request->validate([
            'answers'          => 'required|array',
            'participant_name' => 'nullable|string|max:100',
        ]);

        $questions = $quiz->questions;
        $answers   = $request->input('answers', []);
        $score     = 0;
        $results   = [];

        foreach ($questions as $question) {
            $submitted = strtolower(trim($answers[$question->id] ?? ''));
            $correct   = strtolower(trim($question->correct_answer));
            $isCorrect = $submitted === $correct;

            if ($isCorrect) $score++;

            $results[] = [
                'question'       => $question->question_text,
                'your_answer'    => $answers[$question->id] ?? 'Not answered',
                'correct_answer' => $question->correct_answer,
                'is_correct'     => $isCorrect,
            ];
        }

        $attempt = QuizAttempt::create([
            'quiz_id'          => $quiz->id,
            'user_id'          => auth()->id(),
            'participant_name' => $request->input('participant_name', 'Anonymous'),
            'score'            => $score,
            'total'            => $questions->count(),
            'answers'          => $answers,
            'completed_at'     => now(),
        ]);

        $percentage = $questions->count() > 0
            ? (int) round(($score / $questions->count()) * 100)
            : 0;

        return view('quiz.result', compact('quiz', 'attempt', 'results', 'score', 'percentage'));
    }
}
