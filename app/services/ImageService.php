<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;

class ImageService
{
    public function uploadImage(UploadedFile $file, string $title = 'Untitled', string $description = '', int $category = 1)
    {
        try {
            // Validate file
            if (!$file->isValid()) {
                Log::error('Invalid file uploaded', ['file' => $file->getClientOriginalName()]);
                return ['success' => false, 'error' => 'Invalid file uploaded'];
            }

            // Get API key from environment
            $apiKey = config('services.imgbb.key');
            if (!$apiKey) {
                Log::error('ImgBB API key not configured');
                return ['success' => false, 'error' => 'API key not configured'];
            }

            // Upload to ImgBB
            $response = Http::attach(
                'image',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post('https://api.imgbb.com/1/upload', [
                'key' => $apiKey,
            ]);

            if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] == 200) {
                $imageUrl = $response->json()['data']['url'] ?? null;
                if (!$imageUrl) {
                    Log::error('ImgBB API response missing URL', ['response' => $response->json()]);
                    return ['success' => false, 'error' => 'Failed to retrieve image URL'];
                }

                // Ensure user is authenticated
                $userId = Auth::id();
                if (!$userId) {
                    Log::error('No authenticated user found');
                    return ['success' => false, 'error' => 'User not authenticated'];
                }

                // Save image metadata
                $image = Image::create([
                    'image' => $imageUrl,
                    'title' => $title,
                    'description' => $description,
                    'category' => $category,
                    'uploaded_by' => $userId,
                ]);

                Log::info('Image uploaded and saved', [
                    'url' => $imageUrl,
                    'image_id' => $image->id,
                    'user_id' => $userId,
                ]);

                return ['success' => true, 'url' => $imageUrl, 'image_id' => $image->id];
            }

            $errorMessage = $response->json()['error']['message'] ?? 'Unknown error';
            Log::error('ImgBB API error', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);
            return ['success' => false, 'error' => $errorMessage];
        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'An error occurred while uploading the image: ' . $e->getMessage()];
        }
    }
}
