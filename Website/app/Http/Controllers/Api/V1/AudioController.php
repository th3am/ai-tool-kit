<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ToolJob;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateAudioJob;
use App\Models\ChatMessage;

/**
 * @OA\Tag(
 *     name="4. Tools",
 * )
 */
class AudioController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tools/audio",
     *     tags={"4. Tools"},
     *     summary="Generate Audio Narration",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id", "text"},
     *             @OA\Property(property="session_id", type="string", example="a20bd548-20f6-408d-a5bd-e2a46ef9f202"),
     *             @OA\Property(property="text", type="string")
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
            'text' => 'required|string|max:5000',
        ]);

        $session = ChatSession::where('id', $request->session_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'audio',
            'status' => 'queued',
            'params' => ['inputType' => 'text']
        ]);

        GenerateAudioJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $request->text,
            'text'
        );

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "Status: Audio Job Started via API.",
            'tool_job_id' => $job->id,
        ]);

        return response()->json([
            'message' => 'Job started',
            'job_id' => $job->id
        ], 202);
    }
}
