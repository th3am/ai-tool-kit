<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="6. Public Quiz", description="Public quiz access (no authentication required)")
 */
class PublicQuizApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/quiz/{uuid}",
     *     tags={"6. Public Quiz"},
     *     summary="Get a public quiz",
     *     description="Returns quiz info and shuffled options. Correct answers are NOT included.",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string"), description="Quiz share UUID"),
     *     @OA\Response(response=200, description="Quiz with questions"),
     *     @OA\Response(response=404, description="Not found or not public")
     * )
     */
    public function show(string $uuid)
    {
        $quiz = Quiz::where('share_uuid', $uuid)
            ->where('is_public', true)
            ->where('status', 'done')
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id'         => $quiz->id,
                'title'      => $quiz->title,
                'created_by' => $quiz->user->name,
                'questions'  => $quiz->questions->map(fn ($q) => [
                    'id'           => $q->id,
                    'question_text'=> $q->question_text,
                    'all_options'  => $q->getAllOptionsShuffled(),
                    'order'        => $q->order,
                    // correct_answer is intentionally omitted here
                ]),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/public/quiz/{uuid}/attempt",
     *     tags={"6. Public Quiz"},
     *     summary="Submit a public quiz attempt",
     *     description="Submit answers for a public quiz without needing to log in. Returns score immediately.",
     *     @OA\Parameter(name="uuid", in="path", required=true, @OA\Schema(type="string"), description="Quiz share UUID"),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(property="participant_name", type="string", example="Ahmed"),
     *             @OA\Property(property="answers", type="object", description="Map of question_id to selected answer",
     *                 example={"12": "binary search", "13": "datasets"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Score result",
     *         @OA\JsonContent(
     *             @OA\Property(property="score", type="integer", example=3),
     *             @OA\Property(property="total", type="integer", example=5),
     *             @OA\Property(property="percentage", type="integer", example=60),
     *             @OA\Property(property="message", type="string", example="You scored 3 out of 5!"),
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
    public function attempt(Request $request, string $uuid)
    {
        $quiz = Quiz::where('share_uuid', $uuid)
            ->where('is_public', true)
            ->where('status', 'done')
            ->firstOrFail();

        $request->validate([
            'answers'          => 'required|array',
            'participant_name' => 'nullable|string|max:100',
        ]);

        $questions = $quiz->questions;
        $answers   = $request->input('answers');
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

        QuizAttempt::create([
            'quiz_id'          => $quiz->id,
            'user_id'          => null,
            'participant_name' => $request->input('participant_name', 'Anonymous'),
            'score'            => $score,
            'total'            => $questions->count(),
            'answers'          => $answers,
            'completed_at'     => now(),
        ]);

        return response()->json([
            'score'      => $score,
            'total'      => $questions->count(),
            'percentage' => $questions->count() > 0
                ? (int) round(($score / $questions->count()) * 100)
                : 0,
            'message'    => "You scored {$score} out of {$questions->count()}!",
            'results'    => $results,
        ]);
    }
}
