<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $type = $request->input('type', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 50);

        $response = Http::get('https://images.afterdarkhub.com/api/images', [
            'search' => $search,
            'type' => $type,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $data = $response->json();

        return view('gallery', [
            'images' => $data['data'] ?? [],
            'total' => $data['total'] ?? 0,
            'page' => $page,
            'perPage' => $perPage,
            'search' => $search,
            'type' => $type,
            'loading' => false, // Set to true if you want to simulate loading
        ]);
    }
}
