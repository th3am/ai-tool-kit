<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePresentationJob;
use App\Models\ToolJob;
use Illuminate\Http\Request;

class GuestPresentationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/guest/tools/presentation",
     *     tags={"7. Guest Tools"},
     *     summary="[Guest] Generate Presentation",
     *     description="Start generating a presentation. No authentication required. **Limit: 2 per day per IP. Max 3 slides.** Processing is async — poll `GET /api/v1/guest/jobs/{job_id}` until status is `succeeded`.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic"},
     *             @OA\Property(property="topic", type="string", maxLength=300, example="The Role of AI in Modern Education"),
     *             @OA\Property(property="style", type="string", enum={"Modern","Professional","Creative","Minimalist"}, example="Modern"),
     *             @OA\Property(property="slide_count", type="integer", minimum=1, maximum=3, example=3, description="Capped at 3 for guest users"),
     *             @OA\Property(property="instructions", type="string", example="Keep it simple and visual")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Job accepted — poll for result",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Presentation job started. Poll the job status endpoint."),
     *             @OA\Property(property="job_id", type="integer", example=42),
     *             @OA\Property(property="poll_url", type="string", example="https://example.com/api/v1/guest/jobs/42"),
     *             @OA\Property(property="guest_limit", type="object",
     *                 @OA\Property(property="max_slides", type="integer", example=3),
     *                 @OA\Property(property="daily_limit", type="integer", example=2),
     *                 @OA\Property(property="register_for_more", type="string", example="https://example.com/register")
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
            'topic'        => 'required|string|max:300',
            'style'        => 'nullable|string|in:Modern,Professional,Creative,Minimalist',
            'slide_count'  => 'nullable|integer|min:1|max:3',
            'instructions' => 'nullable|string|max:500',
        ]);

        // Guest users are hard-capped at 3 slides
        $slideCount   = min((int) $request->input('slide_count', 3), 3);
        $style        = $request->input('style', 'Modern');
        $instructions = $request->input('instructions', '');

        $job = ToolJob::create([
            'user_id'         => null,
            'chat_session_id' => null,
            'tool_type'       => 'presentation',
            'status'          => 'queued',
            'params'          => [
                'topic'        => $request->topic,
                'style'        => $style,
                'slide_count'  => $slideCount,
                'is_guest'     => true,
            ],
        ]);

        GeneratePresentationJob::dispatch(
            null,           // user_id
            null,           // session_id
            $job->id,
            $request->topic,
            $style,
            $slideCount,
            $instructions
        );

        return response()->json([
            'message'     => 'Presentation job started. Poll the job status endpoint.',
            'job_id'      => $job->id,
            'poll_url'    => url("/api/v1/guest/jobs/{$job->id}"),
            'guest_limit' => [
                'max_slides'        => 3,
                'daily_limit'       => 2,
                'register_for_more' => url('/register'),
            ],
        ], 202);
    }
}
