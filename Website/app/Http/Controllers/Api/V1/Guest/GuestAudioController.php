<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAudioJob;
use App\Models\ToolJob;
use Illuminate\Http\Request;

class GuestAudioController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/guest/tools/audio",
     *     tags={"7. Guest Tools"},
     *     summary="[Guest] Generate Audio Narration",
     *     description="Generate audio narration from text. No authentication required. **Limit: 2 per day per IP. Max 500 characters.** Processing is async — poll `GET /api/v1/guest/jobs/{job_id}`.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", maxLength=500, example="Welcome to EduAI. This platform uses artificial intelligence to help students and teachers create educational content instantly.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Job accepted — poll for result",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Audio job started. Poll the job status endpoint."),
     *             @OA\Property(property="job_id", type="integer", example=43),
     *             @OA\Property(property="poll_url", type="string", example="https://example.com/api/v1/guest/jobs/43"),
     *             @OA\Property(property="guest_limit", type="object",
     *                 @OA\Property(property="max_chars", type="integer", example=500),
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
            'text' => 'required|string|max:500',
        ]);

        $job = ToolJob::create([
            'user_id'         => null,
            'chat_session_id' => null,
            'tool_type'       => 'audio',
            'status'          => 'queued',
            'params'          => [
                'inputType' => 'text',
                'is_guest'  => true,
            ],
        ]);

        GenerateAudioJob::dispatch(
            null,   // user_id
            null,   // session_id
            $job->id,
            $request->input('text'),
            'text'
        );

        return response()->json([
            'message'     => 'Audio job started. Poll the job status endpoint.',
            'job_id'      => $job->id,
            'poll_url'    => url("/api/v1/guest/jobs/{$job->id}"),
            'guest_limit' => [
                'max_chars'         => 500,
                'daily_limit'       => 2,
                'register_for_more' => url('/register'),
            ],
        ], 202);
    }
}
