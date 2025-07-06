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
     *                     property="images",
     *                     type="string",
     *                     format="binary",
     *                     description="Single image file (jpg, jpeg, png, gif, max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     example="Sample Title",
     *                     maxLength=255,
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     example="Sample Description",
     *                     maxLength=1000,
     *                     nullable=true
     *                 ),
     *                 @OA\Property(
     *                     property="category",
     *                     type="integer",
     *                     example=1,
     *                     minimum=1
     *                 )
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
        $request->validate([
            'images' => 'required|file|mimes:jpg,jpeg,png,gif',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|integer|min:1',
        ]);

        Log::info('Upload API called', ['input' => $request->all()]);

        try {
            $file = $request->file('images');

            if (!$file) {
                Log::error('No file found in request');
                return response()->json(['error' => 'No image provided'], 422);
            }

            $result = $imageService->uploadImage(
                $file,
                $request->input('title', 'Untitled'),
                $request->input('description', ''),
                $request->input('category')
            );

            if (!$result['success']) {
                Log::error('Upload failed', ['error' => $result['error']]);
                return response()->json(['error' => $result['error']], 500);
            }

            return response()->json([
                'success' => 'Image uploaded successfully',
                'images' => [
                    [
                        'url' => $result['url'],
                        'image_id' => $result['image_id'],
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Unhandled exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unexpected error occurred'], 500);
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

        // Search by title or image path
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('image', 'like', '%' . $search . '%');
            });
        }

        // Filter by image file extension
        if ($request->filled('type')) {
            $type = strtolower($request->type);
            $query->whereRaw('LOWER(image) LIKE ?', ['%.' . $type]);
        }

        // Order by created_at descending (change this to your actual timestamp column if needed)
        $images = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($images);
    }



    /**
     * @OA\Post(
     *     path="/api/uploadImageDF",
     *     summary="Upload an image (already hosted on ImgBB or base64 string)",
     *     tags={"Image"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"image"},
     *             @OA\Property(property="image", type="string", example="https://i.ibb.co/example.jpg", description="Image URL or base64 string"),
     *             @OA\Property(property="title", type="string", example="Sample Title", nullable=true),
     *             @OA\Property(property="description", type="string", example="Sample Description", nullable=true),
     *             @OA\Property(property="category", type="integer", example="1", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded and saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Image uploaded and saved"),
     *             @OA\Property(property="url", type="string", example="https://i.ibb.co/example.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function uploadImageDF(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'category' => 'integer'
        ]);

        try {
            $imageUrl = $request->image;

            $image = Image::create([
                'image' => $imageUrl,
                'title' => $request->input('title', 'Untitled'),
                'description' => $request->input('description', ''),
                'uploaded_by' => Auth::user()->id ?? 1,
                'category' => $request->input('category'),
            ]);

            Log::info('Image uploaded to ImgBB and saved', [
                'url' => $imageUrl,
                'image_id' => $image->id
            ]);

            return response()->json(['success' => 'Image uploaded and saved', 'url' => $imageUrl], 200);
        } catch (\Exception $e) {
            Log::error('Image upload to ImgBB failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred while uploading the image'], 500);
        }
    }
}
