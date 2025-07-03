<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="My API",
 *     version="1.0.0",
 *     description="This is the API documentation for my Laravel app using Swagger OpenAPI.",
 *     @OA\Contact(
 *         email="support@example.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * @OA\Components(
 *     @OA\Schema(
 *         schema="Image",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="image", type="string", example="https://i.ibb.co/example.jpg"),
 *         @OA\Property(property="title", type="string", example="Sample Image", nullable=true),
 *         @OA\Property(property="description", type="string", example="Sample Description", nullable=true),
 *         @OA\Property(property="uploaded_by", type="integer", example=1),
 *         @OA\Property(property="category", type="integer", example=1, nullable=true),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-30T17:56:00Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-30T17:56:00Z")
 *     )
 * )
 */
class ImageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/uploadImageApi",
     *     summary="Upload one or more images",
     *     tags={"Image"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"images", "category"},
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="Array of image files (jpg, jpeg, png, gif, max 2MB each)"
     *                 ),
     *                 @OA\Property(property="title", type="string", example="Sample Title", nullable=true, maxLength=255),
     *                 @OA\Property(property="description", type="string", example="Sample Description", nullable=true, maxLength=1000),
     *                 @OA\Property(property="category", type="integer", example=1, minimum=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Images uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Images uploaded successfully"),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="url", type="string", example="https://i.ibb.co/example.jpg"),
     *                     @OA\Property(property="image_id", type="string", example="123")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The images field is required or invalid file type")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while processing the images")
     *         )
     *     )
     * )
     */
    public function uploadImageApi(Request $request, ImageService $imageService)
    {
        // Validate the request
        $request->validate([
            'images.*' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048', // Support multiple images, max 2MB each
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|integer|min:1',
        ]);

        try {
            $images = $request->file('images');
            if (empty($images)) {
                return response()->json(['error' => 'No images provided'], 422);
            }

            $uploadedImages = [];
            foreach ($images as $image) {
                $result = $imageService->uploadImage(
                    $image,
                    $request->input('title', 'Untitled'),
                    $request->input('description', ''),
                    $request->input('category')
                );

                if (!$result['success']) {
                    Log::error('Image upload failed for one file', ['error' => $result['error']]);
                    return response()->json(['error' => $result['error']], 500);
                }

                $uploadedImages[] = [
                    'url' => $result['url'],
                    'image_id' => $result['image_id'],
                ];
            }

            Log::info('Images uploaded successfully', [
                'count' => count($uploadedImages),
                'urls' => array_column($uploadedImages, 'url'),
            ]);

            return response()->json([
                'success' => 'Images uploaded successfully',
                'images' => $uploadedImages,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Image upload process failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while processing the images'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/images",
     *     summary="Get paginated list of images with optional search and extension filter",
     *     tags={"Image"},
     *     description="Returns a paginated list of images with optional search by title and filter by file extension (jpg, png, etc.).",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by title",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by image extension (e.g., jpg, jpeg, png, gif)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"jpg", "jpeg", "png", "gif"})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of images per page (default: 50)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Image")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=50),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="last_page", type="integer", example=2),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="to", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while fetching images")
     *         )
     *     )
     * )
     */
    public function getImage(Request $request)
    {
        $query = Image::query();

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%')
                ->orWhere('image', 'like', '%' . $request->search . '%');
        }

        // Filter by image file extension
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $query->where('image', 'like', '%.' . $type);
        }

        $images = $query->paginate(50);

        return response()->json($images);
    }
}
