<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramSubscriber;

class TelegramService
{
    public function send($title, $link)
    {
        $token = env('TELEGRAM_BOT_TOKEN');

        if (!$token) {
            Log::warning("Telegram Token Missing");
            return;
        }

        $message = "🔥 <b>{$title}</b>\n\n👇 বিস্তারিত পড়ুন:\n{$link}";

        // ১. মেইন চ্যানেল (যদি থাকে)
        $mainChannel = env('TELEGRAM_CHAT_ID');
        if ($mainChannel) {
            $this->sendMessage($token, $mainChannel, $message);
        }

        // ২. সাবস্ক্রাইবারদের পাঠানো
        $subscribers = TelegramSubscriber::all();
        
        Log::info("Sending News to " . $subscribers->count() . " subscribers.");

        foreach ($subscribers as $sub) {
            $this->sendMessage($token, $sub->chat_id, $message);
        }
    }

    private function sendMessage($token, $chatId, $message)
    {
        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send to $chatId: " . $e->getMessage());
        }
    }
}