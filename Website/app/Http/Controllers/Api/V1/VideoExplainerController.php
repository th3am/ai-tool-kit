<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateVideoExplainerJob;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ToolJob;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="4. Tools",
 * )
 */
class VideoExplainerController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tools/video-explainer",
     *     tags={"4. Tools"},
     *     summary="Generate Video Explainer",
     *     description="Starts a background job to generate a video explainer MP4. Poll GET /api/v1/jobs/{job_id} for status.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic"},
     *             @OA\Property(property="session_id", type="string", nullable=true, description="Existing session UUID. Auto-created if omitted."),
     *             @OA\Property(property="topic", type="string", maxLength=500, example="The Solar System"),
     *             @OA\Property(property="style", type="string", nullable=true, example="Modern"),
     *             @OA\Property(property="slide_count", type="integer", nullable=true, minimum=1, maximum=20, example=5),
     *             @OA\Property(property="instructions", type="string", nullable=true, example="Keep it simple for kids"),
     *             @OA\Property(property="language", type="string", nullable=true, example="ar"),
     *             @OA\Property(property="enable_captions", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Job Accepted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Video explainer generation started."),
     *             @OA\Property(property="job_id", type="integer", description="Use this ID with GET /api/v1/jobs/{job_id} to poll status"),
     *             @OA\Property(property="status", type="string", example="queued")
     *         )
     *     ),
     *     @OA\Response(response=402, description="Insufficient credits"),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request, CreditService $creditService)
    {
        $validated = $request->validate([
            'session_id'      => 'nullable|uuid|exists:chat_sessions,id',
            'topic'           => 'required|string|max:500',
            'style'           => 'nullable|string|max:100',
            'slide_count'     => 'nullable|integer|min:1|max:20',
            'instructions'    => 'nullable|string|max:2000',
            'language'        => 'nullable|string|max:10',
            'enable_captions' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Credit check
        if (! $creditService->check($user, 'video-explainer')) {
            $cost = $creditService->costFor('video-explainer');
            return response()->json([
                'message' => "Insufficient credits. This tool requires {$cost} credits. You have {$user->credits} credits remaining.",
            ], 402);
        }

        // Session: use provided or auto-create
        if (! empty($validated['session_id'])) {
            $session = ChatSession::where('id', $validated['session_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail();
        } else {
            $session = ChatSession::create([
                'user_id' => Auth::id(),
                'title'   => 'Video Explainer: ' . \Illuminate\Support\Str::limit($validated['topic'], 50),
            ]);
        }

        // Deduct credits
        $creditService->deduct($user, 'video-explainer');

        $style       = $validated['style'] ?? 'Modern';
        $slideCount  = $validated['slide_count'] ?? 5;
        $instructions = $validated['instructions'] ?? '';
        $language    = $validated['language'] ?? 'ar';
        $enableCaptions = isset($validated['enable_captions']) ? (bool) $validated['enable_captions'] : true;

        $job = ToolJob::create([
            'user_id'        => Auth::id(),
            'chat_session_id'=> $session->id,
            'tool_type'      => 'video-explainer',
            'status'         => 'queued',
            'params'         => [
                'topic'          => $validated['topic'],
                'style'          => $style,
                'slide_count'    => $slideCount,
                'instructions'   => $instructions,
                'language'       => $language,
                'enable_captions'=> $enableCaptions,
            ],
        ]);

        GenerateVideoExplainerJob::dispatch(
            Auth::id(),
            $session->id,
            $job->id,
            $validated['topic'],
            $style,
            $slideCount,
            $instructions,
            $language,
            $enableCaptions
        );

        ChatMessage::create([
            'session_id'  => $session->id,
            'role'        => 'assistant',
            'content'     => 'Status: Video Explainer Job Started via API.',
            'tool_job_id' => $job->id,
        ]);

        return response()->json([
            'message' => 'Video explainer generation started.',
            'job_id'  => $job->id,
            'status'  => 'queued',
        ], 202);
    }
}
