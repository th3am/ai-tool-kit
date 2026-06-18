<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizWebController extends Controller
{
    public function __construct(protected QuizService $quizService) {}

    public function index()
    {
        $quizzes = Auth::user()->quizzes()
            ->withCount('questions')
            ->withCount('attempts')
            ->latest()
            ->paginate(12);

        return view('quiz.index', compact('quizzes'));
    }

    public function create()
    {
        return view('quiz.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'nullable|string|max:255',
            'max_questions' => 'nullable|integer|min:1|max:20',
            'text'          => 'required_without:file|nullable|string|min:100',
            'file'          => 'required_without:text|nullable|file|mimes:pdf,doc,docx,pptx|max:20480',
        ]);

        $maxQuestions = $request->integer('max_questions', 5);
        $title        = $request->input('title', 'Quiz ' . now()->format('Y-m-d H:i'));

        try {
            if ($request->hasFile('file')) {
                $quiz = $this->quizService->createFromFile(
                    Auth::user(),
                    $request->file('file'),
                    $title,
                    $maxQuestions
                );
            } else {
                $quiz = $this->quizService->createFromText(
                    Auth::user(),
                    $request->input('text'),
                    $title,
                    $maxQuestions
                );
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'quiz_id' => $quiz->id,
                    'status' => $quiz->status,
                    'redirect_url' => route('quiz.show', $quiz),
                    'status_url' => route('quiz.status', $quiz),
                ]);
            }

            return redirect()
                ->route('quiz.show', $quiz)
                ->with('success', 'Your quiz is being generated! This may take up to a minute.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }
    }

    public function show(Quiz $quiz)
    {
        abort_unless($quiz->user_id === Auth::id(), 403);

        $quiz->load(['questions', 'attempts' => fn($q) => $q->latest()->take(20)]);

        return view('quiz.show', compact('quiz'));
    }

    public function toggleShare(Quiz $quiz)
    {
        abort_unless($quiz->user_id === Auth::id(), 403);

        $quiz->update(['is_public' => !$quiz->is_public]);

        $message = $quiz->is_public
            ? 'Quiz is now public. Share the link with your friends!'
            : 'Quiz has been set to private.';

        return back()->with('success', $message);
    }

    public function destroy(Quiz $quiz)
    {
        abort_unless($quiz->user_id === Auth::id(), 403);
        $quiz->delete();

        return redirect()->route('quiz.index')->with('success', 'Quiz deleted successfully.');
    }

    /**
     * API-style status check (used by the show page JS polling).
     */
    public function statusCheck(Quiz $quiz)
    {
        abort_unless($quiz->user_id === Auth::id(), 403);

        return response()->json([
            'status'          => $quiz->status,
            'error_message'   => $quiz->error_message,
            'questions_count' => $quiz->questions()->count(),
        ]);
    }
}
