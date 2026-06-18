<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Services\Ai\AnimationGenerator;
use App\Models\ToolJob;
use Illuminate\Http\Request;

class GuestAnimationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/guest/tools/animation",
     *     tags={"7. Guest Tools"},
     *     summary="[Guest] Generate SVG Animation",
     *     description="Generate an animated SVG from a prompt. No authentication required. **Limit: 2 per day per IP.** Returns the SVG content directly (synchronous).",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prompt"},
     *             @OA\Property(property="prompt", type="string", maxLength=500, example="A rocket flying through a starry night sky")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Animation generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Animation generated successfully."),
     *             @OA\Property(property="guest_limit", type="object",
     *                 @OA\Property(property="daily_limit", type="integer", example=2),
     *                 @OA\Property(property="register_for_more", type="string", example="https://example.com/register")
     *             ),
     *             @OA\Property(property="svg", type="string", description="Full SVG XML content ready to embed in HTML")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=429, description="Daily limit reached — register for unlimited access")
     * )
     */
    public function store(Request $request, AnimationGenerator $generator)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        try {
            $svg = $generator->generate($request->input('prompt'));

            return response()->json([
                'message'     => 'Animation generated successfully.',
                'guest_limit' => [
                    'daily_limit'       => 2,
                    'register_for_more' => url('/register'),
                ],
                'svg' => $svg,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Animation generation failed.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
