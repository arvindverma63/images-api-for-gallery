<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;

class ImageService
{
    public function uploadImage($file, $title = 'Untitled', $description = '')
    {
        try {
            $response = Http::attach(
                'image',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post('https://api.imgbb.com/1/upload', [
                'key' => "a900a8c0b90fbb3d484f0576960b9842",
            ]);

            if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] == 200) {
                $imageUrl = $response->json()['data']['url'] ?? null;
                if (!$imageUrl) {
                    Log::error('ImgBB API response missing URL', ['response' => $response->json()]);
                    return ['success' => false, 'error' => 'Failed to retrieve image URL'];
                }
                $image = Image::create([
                    'image' => $imageUrl,
                    'title' => $title,
                    'description' => $description,
                    'uploaded_by' => Auth::id(),
                ]);

                return ['success' => true, 'url' => $imageUrl, 'image_id' => $image->id];
            }

            Log::error('ImgBB API error', [
                'status' => $response->status(),
                'response' => $response->json()['error']['message'] ?? 'Unknown error',
            ]);
            return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to upload image'];
        } catch (\Exception $e) {
            Log::error('Image upload failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'An error occurred while uploading the image'];
        }
    }
}
