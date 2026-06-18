<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\ToolJob;

class GuestJobController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/guest/jobs/{id}",
     *     tags={"7. Guest Tools"},
     *     summary="[Guest] Poll job status",
     *     description="Poll the status of an async guest job (Presentation or Audio). No authentication required. Only works for guest jobs (user_id is null). Poll every 3–5 seconds until status is `succeeded` or `failed`.",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer"), description="Job ID returned from a tool creation endpoint"),
     *     @OA\Response(
     *         response=200,
     *         description="Job status",
     *         @OA\JsonContent(
     *             @OA\Property(property="job_id", type="integer", example=42),
     *             @OA\Property(property="tool_type", type="string", enum={"presentation","audio","video-animation"}, example="presentation"),
     *             @OA\Property(property="status", type="string", enum={"queued","running","succeeded","failed"}, example="succeeded"),
     *             @OA\Property(property="result", type="object", nullable=true, description="The generated result (present when status=succeeded)"),
     *             @OA\Property(property="error_message", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Job not found or not a guest job")
     * )
     */
    public function show(int $id)
    {
        // Only allow access to guest jobs (user_id is null) for security
        $job = ToolJob::whereNull('user_id')
            ->where('id', $id)
            ->first();

        if (!$job) {
            return response()->json([
                'error' => 'Job not found or access denied.',
            ], 404);
        }

        $result = null;

        if ($job->status === 'succeeded' && $job->results) {
            $result = match($job->tool_type) {
                'presentation'   => [
                    'presentation_id' => $job->results['presentation_id'] ?? null,
                    'html'            => isset($job->results['presentation_id'])
                                            ? \App\Models\Presentation::find($job->results['presentation_id'])?->content
                                            : ($job->results['slides'] ?? [])
                ],
                'audio'          => [
                    'audio_url' => isset($job->results['audio_path'])
                        ? url('storage/' . $job->results['audio_path'])
                        : null,
                    'script'    => $job->results['script'] ?? null,
                ],
                'video-animation' => ['svg' => $job->results['svg'] ?? null],
                default          => $job->results,
            };
        }

        return response()->json([
            'job_id'        => $job->id,
            'tool_type'     => $job->tool_type,
            'status'        => $job->status,
            'result'        => $result,
            'error_message' => $job->error_message,
            'created_at'    => $job->created_at->toIso8601String(),
        ]);
    }
}
