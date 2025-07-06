<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $loadMore = $request->input('load_more', false);

        // Base query
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

        // If load_more, fetch all images up to the current page
        if ($loadMore) {
            $images = $query->take($perPage * $page)->get();
        } else {
            $images = $query->forPage($page, $perPage)->get();
        }

        // Transform image URLs
        $images->transform(function ($image) {
            if (str_starts_with($image->image, 'https://pornbb.xyz')) {
                try {
                    // Spoof the Referer and User-Agent to mimic desifakes.com
                    $response = Http::withHeaders([
                        'Referer' => 'https://desifakes.com',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    ])->timeout(10)->get($image->image);

                    if ($response->successful()) {
                        $contentType = $response->header('Content-Type', 'image/jpeg');
                        // Encode image as base64 data URL
                        $base64 = base64_encode($response->body());
                        $image->proxy_url = 'data:' . $contentType . ';base64,' . $base64;
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

        // Wrap images in a LengthAwarePaginator
        $images = new LengthAwarePaginator(
            $images,
            $total,
            $perPage,
            $page,
            ['path' => route('gallery.index')]
        );
        $images->appends(['search' => $search, 'type' => $type, 'per_page' => $perPage, 'load_more' => $loadMore]);

        return view('gallery', [
            'images' => $images,
            'search' => $search,
            'type' => $type,
            'perPage' => $perPage,
            'page' => $page
        ]);
    }
}
