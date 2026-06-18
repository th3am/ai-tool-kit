<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ToolJob;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GeneratePresentationJob;
use App\Models\ChatMessage;

/**
 * @OA\Tag(
 *     name="4. Tools",
 *     description="AI Generation Tools"
 * )
 */
class PresentationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tools/presentation",
     *     tags={"4. Tools"},
     *     summary="Generate Presentation",
     *     description="Starts a background job to generate a presentation",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id", "topic"},
     *             @OA\Property(property="session_id", type="string", example="a20bd548-20f6-408d-a5bd-e2a46ef9f202"),
     *             @OA\Property(property="topic", type="string", example="The utility of AI in Education"),
     *             @OA\Property(property="style", type="string", example="Modern"),
     *             @OA\Property(property="slide_count", type="integer", example=5),
     *             @OA\Property(property="instructions", type="string", example="Include images")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Job Accepted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Job started"),
     *             @OA\Property(property="job_id", type="integer", description="Use this ID to check status")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|uuid|exists:chat_sessions,id',
            'topic' => 'required|string|max:500',
            'style' => 'nullable|string',
            'slide_count' => 'nullable|integer|min:1|max:20',
        ]);

        $session = ChatSession::where('id', $request->session_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'presentation',
            'status' => 'queued',
            'params' => [
                'topic' => $request->topic,
                'style' => $request->style ?? 'Modern',
                'slide_count' => $request->slide_count ?? 5
            ]
        ]);

        GeneratePresentationJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $request->topic,
            $request->style ?? 'Modern',
            $request->slide_count ?? 5,
            $request->instructions ?? ''
        );

        // Immediate feedback in chat (optional for API but consistent with logic)
        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => "Status: Job Started via API.",
            'tool_job_id' => $job->id,
        ]);

        return response()->json([
            'message' => 'Job started',
            'job_id' => $job->id
        ], 202);
    }
}
