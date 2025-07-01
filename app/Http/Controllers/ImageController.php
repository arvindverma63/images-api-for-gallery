<?php

namespace App\Http\Controllers;

use App\Models\Image;
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
 *         @OA\Property(property="url", type="string", example="https://i.ibb.co/example.jpg"),
 *         @OA\Property(property="name", type="string", example="Sample Image", nullable=true)
 *     )
 * )
 */
class ImageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/uploadImageApi",
     *     summary="Upload an image (already hosted on ImgBB or base64 string)",
     *     tags={"Image"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"image"},
     *             @OA\Property(property="image", type="string", example="https://i.ibb.co/example.jpg", description="Image URL or base64 string"),
     *             @OA\Property(property="title", type="string", example="Sample Title", nullable=true),
     *             @OA\Property(property="description", type="string", example="Sample Description", nullable=true),
     *             @OA\Property(property="category", type="integer", example=1, nullable=true)
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
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The image field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while uploading the image")
     *         )
     *     )
     * )
     */
    public function uploadImageApi(Request $request)
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

    /**
     * @OA\Get(
     *     path="/api/getImages",
     *     summary="Get paginated list of images with optional search",
     *     tags={"Image"},
     *     description="Returns a random paginated list of images with optional search by ID, title, or description.",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by ID, title, or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number (default: 1)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of images per page (default: 20)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="images", type="array", @OA\Items(ref="#/components/schemas/Image")),
     *             @OA\Property(property="total", type="integer", example=100),
     *             @OA\Property(property="page", type="integer", example=1),
     *             @OA\Property(property="limit", type="integer", example=20),
     *             @OA\Property(property="has_more", type="boolean", example=true)
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
    public function getImages(Request $request)
    {
        $query = Image::query();

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply random ordering
        $query->inRandomOrder();

        // Pagination
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20); // Default to 20 images per page
        $offset = ($page - 1) * $limit;

        $total = $query->count();
        $images = $query->skip($offset)->take($limit)->get()->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => $image->image,
                'name' => $image->title ?? '',
            ];
        });

        return response()->json([
            'images' => $images,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'has_more' => ($offset + $images->count()) < $total,
        ]);
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
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", example="https://i.ibb.co/example.jpg"),
     *                 @OA\Property(property="title", type="string", example="Sample Image", nullable=true),
     *                 @OA\Property(property="description", type="string", example="Sample Description", nullable=true),
     *                 @OA\Property(property="uploaded_by", type="integer", example=1),
     *                 @OA\Property(property="category", type="integer", example=1, nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-30T17:56:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-30T17:56:00Z")
     *             )),
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
            ->orWhere('image','like','%'.$request->search.'%');
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
