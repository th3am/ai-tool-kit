<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ToolJob;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="3. Sessions",
 *     description="Manage Chat Sessions for Tool Context"
 * )
 */
class ChatSessionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/sessions",
     *     tags={"V1 Chat Sessions"},
     *     summary="List Sessions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user sessions",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function index()
    {
        return ChatSession::where('user_id', Auth::id())
            ->withCount(['messages', 'toolJobs'])
            ->latest()
            ->get()
            ->map(fn (ChatSession $session) => $this->sessionPayload($session));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/sessions",
     *     tags={"V1 Chat Sessions"},
     *     summary="Create Session",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="My Presentation Project")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Session Created",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $session = ChatSession::create([
            'user_id' => Auth::id(),
            'title' => $request->title ?? 'New API Session',
        ]);

        return response()->json($this->sessionPayload($session), 201);
    }

    public function show(ChatSession $session)
    {
        abort_unless($session->user_id === Auth::id(), 403);

        $session->load([
            'messages' => fn ($query) => $query->with('toolJob')->oldest(),
            'toolJobs' => fn ($query) => $query->latest(),
        ]);

        return response()->json([
            ...$this->sessionPayload($session),
            'messages' => $session->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'meta_data' => $message->meta_data,
                'tool_job_id' => $message->tool_job_id,
                'tool_job' => $message->toolJob ? $this->jobPayload($message->toolJob) : null,
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values(),
            'jobs' => $session->toolJobs->map(fn (ToolJob $job) => $this->jobPayload($job))->values(),
        ]);
    }

    private function sessionPayload(ChatSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'context_type' => $session->context_type,
            'context_id' => $session->context_id,
            'messages_count' => $session->messages_count ?? $session->messages()->count(),
            'jobs_count' => $session->tool_jobs_count ?? $session->toolJobs()->count(),
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }

    private function jobPayload(ToolJob $job): array
    {
        return [
            'id' => $job->id,
            'tool_type' => $job->tool_type,
            'status' => $job->status,
            'params' => $job->params,
            'results' => $job->results,
            'error_message' => $job->error_message,
            'created_at' => $job->created_at?->toIso8601String(),
            'updated_at' => $job->updated_at?->toIso8601String(),
        ];
    }
}
