<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Services\QuizService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="7. Guest Tools", description="Public AI tools for unauthenticated users (rate-limited by IP)")
 */
class GuestQuizController extends Controller
{
    public function __construct(protected QuizService $quizService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/guest/tools/quiz",
     *     tags={"7. Guest Tools"},
     *     summary="[Guest] Generate Quiz",
     *     description="Generate a quiz from raw text. No authentication required. **Limit: 3 per day per IP. Max 3 questions per request.**",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", minLength=50, example="Binary search is an algorithm that finds the position of a target value within a sorted array..."),
     *             @OA\Property(property="max_questions", type="integer", minimum=1, maximum=3, example=3, description="Capped at 3 for guest users"),
     *             @OA\Property(property="title", type="string", example="My Quiz")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quiz generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quiz generated successfully."),
     *             @OA\Property(property="guest_limit", type="object",
     *                 @OA\Property(property="max_questions", type="integer", example=3),
     *                 @OA\Property(property="daily_limit", type="integer", example=3),
     *                 @OA\Property(property="register_for_more", type="string", example="https://example.com/register")
     *             ),
     *             @OA\Property(property="quiz", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="My Quiz"),
     *                 @OA\Property(property="status", type="string", example="done"),
     *                 @OA\Property(property="questions", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="question_text", type="string", example="What is binary search?"),
     *                         @OA\Property(property="all_options", type="array", @OA\Items(type="string"))
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=429, description="Daily limit reached — register for unlimited access")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'text'          => 'required|string|min:50',
            'max_questions' => 'nullable|integer|min:1|max:3',
            'title'         => 'nullable|string|max:255',
        ]);

        // Guest users are hard-capped at 3 questions
        $maxQuestions = min((int) $request->input('max_questions', 3), 3);
        $title        = $request->input('title', 'Guest Quiz ' . now()->format('Y-m-d H:i'));

        try {
            // Create a guest user placeholder — QuizService needs a user
            // We create a transient anonymous user object using a guest system account (id=null safe path)
            $quiz = $this->quizService->createFromTextSync(
                null,
                $request->input('text'),
                $title,
                $maxQuestions
            );

            $questions = $quiz->questions->map(fn ($q) => [
                'id'            => $q->id,
                'question_text' => $q->question_text,
                'all_options'   => $q->getAllOptionsShuffled(),
                'order'         => $q->order,
            ]);

            return response()->json([
                'message'     => 'Quiz generated successfully.',
                'guest_limit' => [
                    'max_questions'     => 3,
                    'daily_limit'       => 3,
                    'register_for_more' => url('/register'),
                ],
                'quiz' => [
                    'id'        => $quiz->id,
                    'title'     => $quiz->title,
                    'status'    => $quiz->status,
                    'questions' => $questions,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Quiz generation failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
