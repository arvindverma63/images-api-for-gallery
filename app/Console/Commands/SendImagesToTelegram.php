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

        Image::orderBy('id','desc')->chunk(100, function ($images) use ($bar) {
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
                    } elseif ($extension === 'webp') {
                        Http::post("https://api.telegram.org/bot{$this->botToken}/sendPhoto", [
                            'chat_id' => $this->chatId,
                            'photo' => $url,
                            'caption' => $caption,
                        ]);
                    } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
                        $media[] = [
                            'type' => 'photo',
                            'media' => $url,
                            'caption' => $caption,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Telegram API failed: {$e->getMessage()} for image: {$url}");
                }

                $bar->advance();

                // Send media group in smaller batches of 10 (Telegram's limit per media group)
                if (count($media) >= 10) {
                    $this->sendMediaGroup($media);
                    $media = []; // Reset media array
                }
            }

            // Send any remaining media
            if (!empty($media)) {
                $this->sendMediaGroup($media);
            }

            $this->info("\nBatch completed. Waiting 30 seconds...");
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
