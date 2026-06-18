<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Services\Ai\MindMapGenerator;
use Illuminate\Http\Request;

class GuestMindMapController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/guest/tools/mindmap",
     *     tags={"7. Guest Tools"},
     *     summary="[Guest] Generate Mind Map",
     *     description="Generate a Markdown mind map from a topic. No authentication required. **Limit: 3 per day per IP.** Result is returned immediately (synchronous).",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic"},
     *             @OA\Property(property="topic", type="string", maxLength=500, example="Artificial Intelligence in Education")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mind map generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mind map generated successfully."),
     *             @OA\Property(property="guest_limit", type="object",
     *                 @OA\Property(property="daily_limit", type="integer", example=3),
     *                 @OA\Property(property="register_for_more", type="string", example="https://example.com/register")
     *             ),
     *             @OA\Property(property="result", type="string", description="Markmap-compatible Markdown", example="# AI in Education\n- Benefits\n  - Personalized learning\n- Challenges")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=429, description="Daily limit reached — register for unlimited access")
     * )
     */
    public function store(Request $request, MindMapGenerator $generator)
    {
        $request->validate([
            'topic' => 'required|string|max:500',
        ]);

        try {
            $markdown = $generator->generate($request->input('topic'));

            return response()->json([
                'message'     => 'Mind map generated successfully.',
                'guest_limit' => [
                    'daily_limit'       => 3,
                    'register_for_more' => url('/register'),
                ],
                'result' => $markdown,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Mind map generation failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
