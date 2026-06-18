<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(name="5. Quiz", description="AI-powered Quiz generation and management")
 */
class QuizController extends Controller
{
    public function __construct(protected QuizService $quizService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/quiz",
     *     tags={"5. Quiz"},
     *     summary="List my quizzes",
     *     description="Returns all quizzes created by the authenticated user.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of quizzes",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Chapter 3 Quiz"),
     *                     @OA\Property(property="status", type="string", enum={"pending","processing","done","failed"}, example="done"),
     *                     @OA\Property(property="is_public", type="boolean", example=true),
     *                     @OA\Property(property="share_url", type="string", example="https://example.com/quiz/public/uuid"),
     *                     @OA\Property(property="questions_count", type="integer", example=5),
     *                     @OA\Property(property="attempts_count", type="integer", example=12),
     *                     @OA\Property(property="source_type", type="string", enum={"text","pdf","docx","pptx"}, example="pdf"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {

        $quizzes = Auth::user()->quizzes()
            ->withCount('questions')
            ->withCount('attempts')
            ->latest()
            ->get()
            ->map(fn ($q) => [
                'id'              => $q->id,
                'title'           => $q->title,
                'status'          => $q->status,
                'is_public'       => $q->is_public,
                'share_url'       => $q->is_public ? $q->share_url : null,
                'max_questions'   => $q->max_questions,
                'questions_count' => $q->questions_count,
                'attempts_count'  => $q->attempts_count,
                'source_type'     => $q->source_type,
                'created_at'      => $q->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $quizzes]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/quiz",
     *     tags={"5. Quiz"},
     *     summary="Create a quiz",
     *     description="Create a quiz from raw text or upload a PDF/DOCX/PPTX file. Processing is asynchronous — poll the status endpoint.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", example="Chapter 3 Quiz"),
     *                 @OA\Property(property="max_questions", type="integer", example=5, minimum=1, maximum=20),
     *                 @OA\Property(property="text", type="string", description="Raw text (required if no file)", example="Binary search is an efficient algorithm..."),
     *                 @OA\Property(property="file", type="string", format="binary", description="Upload PDF/DOCX/PPTX (required if no text)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Quiz creation started",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quiz creation started. Poll the status endpoint."),
     *             @OA\Property(property="quiz_id", type="integer", example=7),
     *             @OA\Property(property="status", type="string", example="pending")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'         => 'nullable|string|max:255',
            'max_questions' => 'nullable|integer|min:1|max:20',
            'text'          => 'required_without:file|string|min:100',
            'file'          => 'required_without:text|file|mimes:pdf,doc,docx,pptx|max:20480',
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

            return response()->json([
                'message' => 'Quiz creation started. Poll the status endpoint.',
                'quiz_id' => $quiz->id,
                'status'  => $quiz->status,
            ], 202);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/quiz/{id}",
     *     tags={"5. Quiz"},
     *     summary="Get quiz details",
     *     description="Returns full quiz details with all questions and correct answers. Owner only.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Quiz ID"),
     *     @OA\Response(response=200, description="Quiz details with questions"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Quiz not found")
     * )
     */
    public function show(Quiz $quiz)
    {
        $this->ensureOwner($quiz);

        return response()->json([
            'data' => [
                'id'            => $quiz->id,
                'title'         => $quiz->title,
                'status'        => $quiz->status,
                'error_message' => $quiz->error_message,
                'is_public'     => $quiz->is_public,
                'share_url'     => $quiz->is_public ? $quiz->share_url : null,
                'max_questions' => $quiz->max_questions,
                'source_type'   => $quiz->source_type,
                'created_at'    => $quiz->created_at->toIso8601String(),
                'questions'     => $quiz->questions->map(fn ($q) => [
                    'id'             => $q->id,
                    'question_text'  => $q->question_text,
                    'all_options'    => $q->getAllOptionsShuffled(),
                    'order'          => $q->order,
                ]),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/quiz/{id}/status",
     *     tags={"5. Quiz"},
     *     summary="Poll quiz generation status",
     *     description="Poll this endpoint every 5 seconds after creating a quiz until status becomes 'done' or 'failed'.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Quiz ID"),
     *     @OA\Response(
     *         response=200,
     *         description="Current status",
     *         @OA\JsonContent(
     *             @OA\Property(property="quiz_id", type="integer", example=7),
     *             @OA\Property(property="status", type="string", enum={"pending","processing","done","failed"}, example="done"),
     *             @OA\Property(property="error_message", type="string", nullable=true),
     *             @OA\Property(property="questions_count", type="integer", example=5)
     *         )
     *     )
     * )
     */
    public function status(Quiz $quiz)
    {
        $this->ensureOwner($quiz);

        return response()->json([
            'quiz_id'         => $quiz->id,
            'status'          => $quiz->status,
            'error_message'   => $quiz->error_message,
            'questions_count' => $quiz->questions()->count(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/quiz/{id}/toggle-share",
     *     tags={"5. Quiz"},
     *     summary="Toggle public share link",
     *     description="Enable or disable the public shareable link for this quiz.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Quiz ID"),
     *     @OA\Response(
     *         response=200,
     *         description="Share status updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="share_url", type="string", example="https://example.com/quiz/public/uuid"),
     *             @OA\Property(property="message", type="string", example="Quiz is now public.")
     *         )
     *     )
     * )
     */
    public function toggleShare(Quiz $quiz)
    {
        $this->ensureOwner($quiz);

        $quiz->update(['is_public' => !$quiz->is_public]);

        return response()->json([
            'is_public' => $quiz->is_public,
            'share_url' => $quiz->is_public ? $quiz->share_url : null,
            'message'   => $quiz->is_public ? 'Quiz is now public.' : 'Quiz is now private.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/quiz/{id}/attempt",
     *     tags={"5. Quiz"},
     *     summary="Submit a quiz attempt",
     *     description="Submit answers for a quiz and receive the score immediately.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Quiz ID"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(property="answers", type="object", description="Map of question_id to selected answer",
     *                 example={"1": "binary search", "2": "datasets"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Score result",
     *         @OA\JsonContent(
     *             @OA\Property(property="score", type="integer", example=4),
     *             @OA\Property(property="total", type="integer", example=5),
     *             @OA\Property(property="percentage", type="integer", example=80),
     *             @OA\Property(property="message", type="string", example="You scored 4 out of 5!"),
     *             @OA\Property(property="results", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer", example=1),
     *                     @OA\Property(property="submitted_answer", type="string", example="binary search"),
     *                     @OA\Property(property="correct_answer", type="string", example="binary search"),
     *                     @OA\Property(property="is_correct", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function attempt(Request $request, Quiz $quiz)
    {
        $this->ensureOwner($quiz);

        if ($quiz->status !== 'done') {
            return response()->json(['message' => 'Quiz is not ready yet.'], 422);
        }

        $request->validate([
            'answers' => 'required|array',
        ]);

        return $this->processAttempt($quiz, $request->input('answers'), Auth::id(), null);
    }

    protected function ensureOwner(Quiz $quiz): void
    {
        abort_unless((int) $quiz->user_id === (int) Auth::id(), 403);
    }

    /**
     * Shared attempt processing logic.
     */
    protected function processAttempt(Quiz $quiz, array $answers, ?int $userId, ?string $participantName)
    {
        $questions = $quiz->questions;
        $score     = 0;
        $results   = [];

        foreach ($questions as $question) {
            $submitted = strtolower(trim($answers[$question->id] ?? ''));
            $isCorrect = $submitted === strtolower(trim($question->correct_answer));
            
            if ($isCorrect) {
                $score++;
            }

            $results[] = [
                'question_id' => $question->id,
                'submitted_answer' => $answers[$question->id] ?? null,
                'correct_answer' => $question->correct_answer,
                'is_correct' => $isCorrect,
            ];
        }

        $attempt = QuizAttempt::create([
            'quiz_id'          => $quiz->id,
            'user_id'          => $userId,
            'participant_name' => $participantName,
            'score'            => $score,
            'total'            => $questions->count(),
            'answers'          => $answers,
            'completed_at'     => now(),
        ]);

        return response()->json([
            'score'      => $score,
            'total'      => $questions->count(),
            'percentage' => $attempt->percentage,
            'message'    => "You scored {$score} out of {$questions->count()}!",
            'results'    => $results,
        ]);
    }
}
