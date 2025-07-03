<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;

class ImageService
{
    public function uploadImage(UploadedFile $file, string $title, string $description, int $category)
    {
        try {
            Log::info('Starting image upload process', [
                'filename' => $file->getClientOriginalName(),
                'title' => $title,
                'description' => $description,
                'category' => $category
            ]);

            // Validate file
            if (!$file->isValid()) {
                Log::error('Invalid file uploaded', [
                    'file' => $file->getClientOriginalName(),
                    'error' => 'File is not valid'
                ]);
                return ['success' => false, 'error' => 'Invalid file uploaded'];
            }

            // Get API key from environment
            $apiKey = env('IMGBB_API_KEY');
            if (!$apiKey) {
                Log::error('ImgBB API key not configured');
                return ['success' => false, 'error' => 'API key not configured'];
            }

            Log::info('Uploading image to ImgBB', ['filename' => $file->getClientOriginalName()]);

            // Upload to ImgBB
            $response = Http::timeout(10)->attach(
                'image',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post('https://api.imgbb.com/1/upload', [
                        'key' => $apiKey,
                    ]);

            Log::info('ImgBB API response received', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] == 200) {
                $imageUrl = $response->json()['data']['url'] ?? null;

                if (!$imageUrl) {
                    Log::error('ImgBB API response missing URL', ['response' => $response->json()]);
                    return ['success' => false, 'error' => 'Failed to retrieve image URL'];
                }


                $userId = 1;
                // Save image metadata
                $image = Image::create([
                    'image' => $imageUrl,
                    'title' => $title,
                    'description' => $description,
                    'category' => $category,
                    'uploaded_by' => $userId,
                ]);

                Log::info('Image uploaded and saved successfully', [
                    'url' => $imageUrl,
                    'image_id' => $image->id,
                    'user_id' => $userId
                ]);

                return ['success' => true, 'url' => $imageUrl, 'image_id' => $image->id];
            }

            $errorMessage = $response->json()['error']['message'] ?? 'Unknown error';

            Log::error('ImgBB API error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $response->json()
            ]);

            return ['success' => false, 'error' => $errorMessage];
        } catch (\Exception $e) {
            Log::error('Image upload failed with exception', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ['success' => false, 'error' => 'An error occurred while uploading the image: ' . $e->getMessage()];
        }
    }
}
