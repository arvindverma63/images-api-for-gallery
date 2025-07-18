<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class TelegramController extends Controller
{
    protected $botToken;
    protected $jsonPath;

    public function __construct()
    {
        $this->botToken = '7035838003:AAG7y-77EzetNjbphWTKI95Ka2aDQ2LZd8s';
        $this->jsonPath = public_path('images.json');
    }

    // ✅ 1. Send images (with title) when user sends /start
    public function handleWebhook(Request $request)
    {
        $data = $request->all();

        if (isset($data['message']['text']) && $data['message']['text'] === '/start') {
            $chatId = $data['message']['chat']['id'];

            $images = $this->loadImagesFromJson();

            foreach (array_chunk($images, 10) as $batch) {
                foreach ($batch as $item) {
                    Http::get("https://api.telegram.org/bot{$this->botToken}/sendPhoto", [
                        'chat_id' => $chatId,
                        'photo' => $item['image'],  // 👈 updated here
                        'caption' => $item['title'] ?? '📷',
                    ]);
                    usleep(300000); // delay 0.3 sec between each photo
                }
                sleep(2); // 2 sec pause after each batch of 10
            }
        }

        return response()->json(['ok' => true]);
    }

    // 📁 2. Create initial JSON file from DB
    public function createImagesJson()
    {
        $images = Image::select('image', 'title')->get()->toArray();  // 👈 updated column
        File::put($this->jsonPath, json_encode($images, JSON_PRETTY_PRINT));
        return response()->json(['message' => 'images.json created!', 'total' => count($images)]);
    }

    // 🔁 3. Update JSON file with only new entries
    public function updateImagesJson()
    {
        $existing = $this->loadImagesFromJson();
        $existingImages = collect($existing)->pluck('image')->toArray();  // 👈 updated key

        $newImages = Image::whereNotIn('image', $existingImages)
                          ->select('image', 'title')
                          ->get()
                          ->toArray();

        $merged = array_merge($existing, $newImages);

        File::put($this->jsonPath, json_encode($merged, JSON_PRETTY_PRINT));
        return response()->json(['message' => 'images.json updated!', 'total' => count($merged)]);
    }

    // 🧰 4. Utility function to read the JSON file
    protected function loadImagesFromJson()
    {
        if (!File::exists($this->jsonPath)) {
            return [];
        }

        return json_decode(File::get($this->jsonPath), true) ?? [];
    }
}
