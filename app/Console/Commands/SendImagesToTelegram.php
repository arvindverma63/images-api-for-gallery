<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendImagesToTelegram extends Command
{
    protected $signature = 'telegram:send-images';
    protected $description = 'Send all images to Telegram in batches with GIF and WEBP support';

    protected $botToken;
    protected $chatId;

    public function __construct()
    {
        parent::__construct();

        $this->botToken = '7035838003:AAG7y-77EzetNjbphWTKI95Ka2aDQ2LZd8s';
        $this->chatId = env('TELEGRAM_CHAT_ID'); // e.g., 1679895915
    }

    public function handle()
    {
        ini_set('max_execution_time', 0); // Remove 60s limit

        $total = Image::count();
        $this->info("Sending {$total} images to Telegram...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Image::orderBy('id', 'desc')->chunk(100, function ($images) use ($bar) {
            $media = [];

            foreach ($images as $image) {
                $url = $image->image;
                $caption = $image->title ?? '';
                $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

                try {
                    if ($extension === 'gif') {
                        Http::post("https://api.telegram.org/bot{$this->botToken}/sendAnimation", [
                            'chat_id' => $this->chatId,
                            'animation' => $url,
                            'caption' => $caption,
                        ]);
                        $bar->advance();
                    } elseif ($extension === 'webp') {
                        Http::post("https://api.telegram.org/bot{$this->botToken}/sendPhoto", [
                            'chat_id' => $this->chatId,
                            'photo' => $url,
                            'caption' => $caption,
                        ]);
                        $bar->advance();
                    } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
                        $media[] = [
                            'type' => 'photo',
                            'media' => $url,
                            'caption' => $caption,
                        ];

                        // Advance here only when we successfully send batch later
                    }
                } catch (\Exception $e) {
                    Log::error("Telegram API failed: {$e->getMessage()} for image: {$url}");
                    $bar->advance(); // Still count failed one to avoid hanging
                }

                if (count($media) >= 10) {
                    $this->sendMediaGroup($media);
                    $bar->advance(count($media)); // Advance bar properly
                    $media = []; // Reset
                    sleep(2); // Slight pause for Telegram API
                }
            }

            // Send remaining images
            if (!empty($media)) {
                $this->sendMediaGroup($media);
                $bar->advance(count($media));
            }

            $this->info("\nBatch completed. Waiting 30 seconds...");
            sleep(30);
        });

        $bar->finish();
        $this->info("\n✅ All images sent to Telegram!");
    }


    private function sendMediaGroup(array $media)
    {
        if (count($media) >= 2) {
            Http::post("https://api.telegram.org/bot{$this->botToken}/sendMediaGroup", [
                'chat_id' => $this->chatId,
                'media' => json_encode($media),
            ]);
        } elseif (count($media) === 1) {
            $item = $media[0];
            Http::post("https://api.telegram.org/bot{$this->botToken}/sendPhoto", [
                'chat_id' => $this->chatId,
                'photo' => $item['media'],
                'caption' => $item['caption'],
            ]);
        }
    }
}
