<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ToolJob;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateAnimationJob;
use App\Models\ChatMessage;

/**
 * @OA\Tag(
 *     name="4. Tools",
 * )
 */
class AnimationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tools/animation",
     *     tags={"4. Tools"},
     *     summary="Generate Video Animation",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id", "prompt"},
     *             @OA\Property(property="session_id", type="string", example="a20bd548-20f6-408d-a5bd-e2a46ef9f202"),
     *             @OA\Property(property="prompt", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Job Accepted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job started"),
     *             @OA\Property(property="job_id", type="integer")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|uuid|exists:chat_sessions,id',
            'prompt' => 'required|string|max:1000',
        ]);

        $session = ChatSession::where('id', $request->session_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'video-animation',
            'status' => 'queued',
            'params' => ['prompt' => $request->prompt]
        ]);

        GenerateAnimationJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $request->prompt
        );

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "Status: Animation Job Started via API.",
            'tool_job_id' => $job->id,
        ]);

        return response()->json([
            'message' => 'Job started',
            'job_id' => $job->id
        ], 202);
    }
}
