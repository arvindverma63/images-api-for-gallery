<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class GalleryController extends Controller
{
    /**
     * Display the image gallery with pagination and handle fullscreen mode.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Retrieve input parameters
        $search = $request->input('search');
        $type = $request->input('type');
        $perPage = (int) $request->input('per_page', 10);
        $page = (int) $request->input('page', 1);
        $fullscreen = $request->input('fullscreen'); // Image ID for fullscreen mode

        // Build the base query
        $query = Image::query()
            ->when($search, function ($query, $search) {
                return $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->when($type, function ($query, $type) {
                return $query->where('image', 'like', '%.' . $type);
            })
            ->orderBy('id', 'DESC');

        // Get total count for pagination
        $total = $query->count();

        // Fetch images for the current page only
        $offset = ($page - 1) * $perPage;
        $images = $query->offset($offset)->take($perPage)->get();

        // Transform image URLs
        $images->transform(function ($image) {
            if (str_starts_with($image->image, 'https://pornbb.xyz')) {
                try {
                    // Spoof headers to fetch image
                    $response = Http::withHeaders([
                        'Referer' => 'https://desifakes.com',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    ])->timeout(10)->get($image->image);

                    if ($response->successful()) {
                        $contentType = $response->header('Content-Type', 'image/jpeg');
                        $base64 = base64_encode($response->body());
                        $image->proxy_url = "data:{$contentType};base64,{$base64}";
                    } else {
                        $image->proxy_url = 'https://via.placeholder.com/200x200?text=Image+Not+Found';
                    }
                } catch (\Exception $e) {
                    $image->proxy_url = 'https://via.placeholder.com/200x200?text=Error+Fetching+Image';
                }
            } else {
                $image->proxy_url = $image->image;
            }
            return $image;
        });

        // Create paginator
        $images = new LengthAwarePaginator(
            $images,
            $total,
            $perPage,
            $page,
            ['path' => route('gallery.index')]
        );
        $images->appends(['search' => $search, 'type' => $type, 'per_page' => $perPage]);

        // Handle fullscreen mode
        $fullscreenImage = null;
        if ($fullscreen) {
            $fullscreenImage = Image::find($fullscreen);
            if ($fullscreenImage) {
                // Transform fullscreen image URL
                if (str_starts_with($fullscreenImage->image, 'https://pornbb.xyz')) {
                    try {
                        $response = Http::withHeaders([
                            'Referer' => 'https://desifakes.com',
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        ])->timeout(10)->get($fullscreenImage->image);

                        if ($response->successful()) {
                            $contentType = $response->header('Content-Type', 'image/jpeg');
                            $base64 = base64_encode($response->body());
                            $fullscreenImage->proxy_url = "data:{$contentType};base64,{$base64}";
                        } else {
                            $fullscreenImage->proxy_url = 'https://via.placeholder.com/200x200?text=Image+Not+Found';
                        }
                    } catch (\Exception $e) {
                        $fullscreenImage->proxy_url = 'https://via.placeholder.com/200x200?text=Error+Fetching+Image';
                    }
                } else {
                    $fullscreenImage->proxy_url = $fullscreenImage->image;
                }
            }
        }

        // Return view
        return view('gallery', [
            'images' => $images,
            'search' => $search,
            'type' => $type,
            'perPage' => $perPage,
            'page' => $page,
            'fullscreenImage' => $fullscreenImage,
        ]);
    }
}
