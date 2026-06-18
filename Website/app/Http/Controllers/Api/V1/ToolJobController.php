<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ToolJob;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="5. Jobs",
 *     description="Background Job Monitoring"
 * )
 */
class ToolJobController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/jobs",
     *     tags={"5. Jobs"},
     *     summary="List Recent Jobs",
     *     description="Get a paginated list of recent background jobs for the authenticated user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of Jobs",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        
        $jobs = ToolJob::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
            
        return response()->json($jobs);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/jobs/{id}",
     *     tags={"5. Jobs"},
     *     summary="Check Job Status",
     *     description="Get the status and results of a background job",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job Details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="status", type="string", example="succeeded", enum={"pending", "running", "succeeded", "failed"}),
     *             @OA\Property(property="results", type="object"),
     *             @OA\Property(property="error_message", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Job not found")
     * )
     */
    public function show(Request $request, $id)
    {
        // Ideally verify user owns the job
        $job = ToolJob::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $results = $this->normalizeResults($job, $request);

        // Automatically attach presentation HTML slides to the response if applicable
        if ($job->status === 'succeeded' && $job->tool_type === 'presentation' && isset($results['presentation_id'])) {
            $presentation = \App\Models\Presentation::find($results['presentation_id']);
            if ($presentation) {
                $results['html'] = $presentation->content; // Array of HTML slides
            }
        }

        return response()->json([
            'id' => $job->id,
            'chat_session_id' => $job->chat_session_id,
            'tool_type' => $job->tool_type,
            'status' => $job->status,
            'params' => $job->params ?? [],
            'results' => $results,
            'error_message' => $job->error_message,
            'created_at' => $job->created_at,
            'updated_at' => $job->updated_at,
        ]);
    }

    private function normalizeResults(ToolJob $job, Request $request): array
    {
        $results = $job->results ?? [];

        if (isset($results['audio_path'])) {
            $results['audio_url'] = asset('storage/'.$results['audio_path']);
            $results['url'] = $results['audio_url'];
        }

        if (isset($results['svg_content'])) {
            $results['svg'] = $results['svg_content'];
        }

        if (isset($results['svg_path'])) {
            $results['svg_url'] = asset('storage/'.$results['svg_path']);
        }

        if (isset($results['video_path'])) {
            $results['video_url'] = asset('storage/'.$results['video_path']);
            $results['url']       = $results['video_url'];
        }

        // For video-explainer: expose a download URL
        if ($job->tool_type === 'video-explainer' && isset($results['video_path'])) {
            $results['download_url'] = $this->downloadUrl("/api/v1/downloads/video-explainer/{$job->id}", $request);
        }

        if (isset($results['presentation_id'])) {
            $results['pdf_url'] = $this->downloadUrl("/api/v1/downloads/presentation/{$results['presentation_id']}/pdf", $request);
            $results['ppt_url'] = $this->downloadUrl("/api/v1/downloads/presentation/{$results['presentation_id']}/ppt", $request);
        }

        if ($job->tool_type === 'mindmap') {
            $results['png_url'] = $this->downloadUrl("/api/v1/downloads/mindmap/{$job->id}/png", $request);
            $results['svg_url'] = $this->downloadUrl("/api/v1/downloads/mindmap/{$job->id}/svg", $request);
        }

        return $results;
    }

    private function downloadUrl(string $path, Request $request): string
    {
        $url = url($path);
        $token = $request->bearerToken();

        return $token ? $url.'?token='.urlencode($token) : $url;
    }
}
