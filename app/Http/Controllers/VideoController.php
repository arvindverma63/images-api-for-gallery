<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::query();

        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $videos = $query->paginate(18); // 18 videos (3 rows of 6 for desktop)

        // Add proxy headers for video URLs
        $videos->getCollection()->transform(function ($video) {
            $video->proxied_url = $this->getProxiedVideoUrl($video->url);
            return $video;
        });

        return view('videos.index', compact('videos'));
    }

    private function getProxiedVideoUrl($url)
    {
        try {
            $response = Http::withHeaders([
                'Referer' => 'https://desifakes.com',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
            ])->get($url);

            if ($response->successful()) {
                // Assuming the URL is directly playable or returns a playable URL
                return $url; // Modify if the response provides a different playable URL
            }
            return $url; // Fallback to original URL if proxy fails
        } catch (\Exception $e) {
            // Log error if needed: \Log::error('Proxy URL fetch failed: ' . $e->getMessage());
            return $url; // Fallback to original URL
        }
    }

    /**
     * @OA\Post(
     *     path="/api/upload-videos",
     *     summary="Upload a new video",
     *     description="Uploads a video URL along with optional title and description.",
     *     operationId="uploadVideo",
     *     tags={"Videos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="My Sample Video"),
     *             @OA\Property(property="description", type="string", maxLength=1000, example="This is a sample video description."),
     *             @OA\Property(property="url", type="string", example="https://example.com/video.mp4")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Video uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="video uploaded successfully"),
     *             @OA\Property(
     *                 property="video",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="url", type="string", example="https://example.com/video.mp4"),
     *                     @OA\Property(property="video", type="string", example="My Sample Video")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error uploading video",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function uploadVideosApi(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'url' => 'required|string',
        ]);

        Log::info('Upload Videos API called', ['input' => $request->all()]);

        try {
            $result = Video::created($request);

            if (!$result['success']) {
                Log::error('Upload failed', ['error' => $result['error']]);
                return response()->json(['error' => $result['error']], 500);
            }

            return response()->json([
                'success' => 'video uploaded successfully',
                'video' => [
                    [
                        'url' => $result['url'],
                        'video' => $result['title'],
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Unhandled exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
        }
    }
}
