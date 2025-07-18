<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\ImageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected $botToken;
    protected $jsonPath;

    public function __construct()
    {
        $this->botToken = '7035838003:AAG7y-77EzetNjbphWTKI95Ka2aDQ2LZd8s';
        $this->jsonPath = public_path('images.json');
    }


    public function handleWebhook(Request $request)
    {
        $data = $request->all();

        if (!isset($data['message']['text']) || $data['message']['text'] !== '/start') {
            return response()->json(['ok' => true]);
        }

        $chatId = $data['message']['chat']['id'];
        $images = $this->loadImagesFromJson();

        foreach (array_chunk($images, 10) as $batch) {
            foreach ($batch as $item) {
                $url = $item['image'];
                $caption = $item['title'] ?? '';
                $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

                $endpoint = $ext === 'gif' ? 'sendAnimation' : 'sendPhoto';
                $mediaType = $ext === 'gif' ? 'animation' : 'photo';

                $response = Http::post("https://api.telegram.org/bot{$this->botToken}/{$endpoint}", [
                    'chat_id' => $chatId,
                    $mediaType => $url,
                    'caption' => $caption,
                ]);

                // Debug response
                Log::info('Telegram response:', $response->json());

                // Optional: Short delay
                usleep(300000);
            }

            sleep(2);
        }

        return response()->json(['status' => 'sent']);
    }


    // 📁 2. Create initial JSON from DB
    public function createImagesJson()
    {
        $images = Image::select('image', 'title')->get()->toArray();
        File::put($this->jsonPath, json_encode($images, JSON_PRETTY_PRINT));
        return response()->json(['message' => 'images.json created!', 'total' => count($images)]);
    }

    // 🔁 3. Update JSON with new DB entries only
    public function updateImagesJson()
    {
        $existing = $this->loadImagesFromJson();
        $existingImages = collect($existing)->pluck('image')->toArray();

        $newImages = Image::whereNotIn('image', $existingImages)
            ->select('image', 'title')
            ->get()
            ->toArray();

        $merged = array_merge($existing, $newImages);
        File::put($this->jsonPath, json_encode($merged, JSON_PRETTY_PRINT));

        return response()->json(['message' => 'images.json updated!', 'total' => count($merged)]);
    }

    // 🧰 4. Utility: load from images.json
    protected function loadImagesFromJson()
    {
        if (!File::exists($this->jsonPath)) {
            return [];
        }

        return json_decode(File::get($this->jsonPath), true) ?? [];
    }
}
