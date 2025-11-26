<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramSubscriber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TelegramBotController extends Controller
{
    public function handle(Request $request)
    {
        $update = $request->all();
        
        // ১. লগ করা (যাতে বোঝা যায় টেলিগ্রাম নক দিচ্ছে কিনা)
        Log::info('Telegram Webhook Received:', $update);

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $firstName = $update['message']['chat']['first_name'] ?? 'Subscriber';
            $text = $update['message']['text'] ?? '';

            // ২. '/start' চেক করা (যেকোনো টেক্সটের শুরুতে start থাকলেই হবে)
            if (str_starts_with($text, '/start')) {
                
                // ৩. ডাটাবেসে সেভ করা
                $subscriber = TelegramSubscriber::firstOrCreate(
                    ['chat_id' => $chatId],
                    ['first_name' => $firstName]
                );

                if ($subscriber->wasRecentlyCreated) {
                    Log::info("New Subscriber Added: $firstName ($chatId)");
                    $this->sendWelcomeMessage($chatId, $firstName);
                } else {
                    Log::info("Old Subscriber Re-started: $firstName ($chatId)");
                    // চাইলে এখানে বলতে পারেন "আপনি ইতিমধ্যেই সাবস্ক্রাইব করা আছেন"
                }
            }
        }

        return response('OK', 200);
    }

    private function sendWelcomeMessage($chatId, $name)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $text = "স্বাগতম {$name}! 🎉\n\nআমাদের নিউজ সার্ভিসে যুক্ত হওয়ার জন্য ধন্যবাদ। এখন থেকে ব্রেকিং নিউজগুলো সরাসরি আপনার ইনবক্সে পৌঁছে যাবে।";

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text
            ]);
        } catch (\Exception $e) {
            Log::error("Welcome Msg Failed: " . $e->getMessage());
        }
    }
}