<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ToolJob;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use App\Services\Ai\MindMapGenerator;
use App\Models\ChatMessage;

/**
 * @OA\Tag(
 *     name="4. Tools",
 * )
 */
class MindMapController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tools/mindmap",
     *     tags={"4. Tools"},
     *     summary="Generate Mind Map",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_id", "topic"},
     *             @OA\Property(property="session_id", type="string", example="a20bd548-20f6-408d-a5bd-e2a46ef9f202"),
     *             @OA\Property(property="topic", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Generation Complete",
     *         @OA\JsonContent(
     *             @OA\Property(property="job_id", type="integer"),
     *             @OA\Property(property="status", type="string", example="succeeded"),
     *             @OA\Property(property="result", type="string", description="Markdown content")
     *         )
     *     )
     * )
     */
    public function store(Request $request, MindMapGenerator $generator)
    {
        $request->validate([
            'session_id' => 'required|uuid|exists:chat_sessions,id',
            'topic' => 'required|string|max:1000',
        ]);

        $session = ChatSession::where('id', $request->session_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $job = ToolJob::create([
            'user_id' => Auth::id(),
            'chat_session_id' => $session->id,
            'tool_type' => 'mindmap',
            'status' => 'running',
            'params' => ['topic' => $request->topic]
        ]);

        try {
            // Synchronous generation for now as per current implementation
            $markdown = $generator->generate($request->topic);
            
            $job->update([
                'status' => 'succeeded',
                'results' => ['raw_markdown' => $markdown]
            ]);

            ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => "Status: MindMap Generated via API.",
                'tool_job_id' => $job->id,
            ]);

            return response()->json([
                'job_id' => $job->id,
                'status' => 'succeeded',
                'result' => $markdown
            ]);

        } catch (\Exception $e) {
            $job->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return response()->json(['message' => 'Failed', 'error' => $e->getMessage()], 500);
        }
    }
}
