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

        if (isset($data['message']['text']) && $data['message']['text'] === '/start') {
            $chatId = $data['message']['chat']['id'];
            $images = $this->loadImagesFromJson();

            foreach (array_chunk($images, 10) as $batch) {
                $proxy = $this->getRandomProxy(); // 🔁 Get a new proxy per batch

                $options = [];
                if ($proxy) {
                    $options['proxy'] = "http://{$proxy}";
                }

                foreach ($batch as $item) {
                    $url = $item['image'];
                    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $caption = $item['title'] ?? '';

                    $endpoint = ($ext === 'gif') ? 'sendAnimation' : 'sendPhoto';
                    $mediaKey = ($ext === 'gif') ? 'animation' : 'photo';

                    $response = Http::withOptions($options)->post("https://api.telegram.org/bot{$this->botToken}/{$endpoint}", [
                        'chat_id' => $chatId,
                        $mediaKey => $url,
                        'caption' => $caption,
                    ]);

                    Log::info("Proxy used: {$proxy}");
                    Log::info("Telegram response: " . json_encode($response->json()));

                    usleep(300000); // 0.3 sec between images
                }

                sleep(2); // Pause 2 sec after batch
            }
        }

        return response()->json(['ok' => true]);
    }

    protected function getRandomProxy()
    {
        $path = public_path('proxy.txt');

        if (!file_exists($path))
            return null;

        $proxies = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($proxies))
            return null;

        return trim($proxies[array_rand($proxies)]);
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
