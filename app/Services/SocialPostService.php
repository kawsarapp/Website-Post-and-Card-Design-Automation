<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialPostService
{
    
	
	public function postToFacebook($settings, $title, $imagePathOrUrl, $newsLink)
    {
        if (empty($settings->fb_page_id) || empty($settings->fb_access_token)) return;

        try {
            $endpoint = "https://graph.facebook.com/v19.0/{$settings->fb_page_id}/photos";
            $payload = [
                'message'      => $title,
                'access_token' => $settings->fb_access_token,
                'published'    => true
            ];

            // 🔥 চেক: এটি কি লোকাল ফাইল নাকি URL?
            if (file_exists($imagePathOrUrl)) {
                // লোকাল ফাইল হলে 'attach' করতে হবে
                $response = Http::attach(
                    'source', file_get_contents($imagePathOrUrl), 'news-card.jpg'
                )->post($endpoint, $payload);
            } else {
                // URL হলে সরাসরি পাঠাবে
                $payload['url'] = $imagePathOrUrl;
                $response = Http::post($endpoint, $payload);
            }

            $postId = $response->json()['post_id'] ?? null;

            // কমেন্টে লিংক দেওয়া
            if ($postId) {
                Http::post("https://graph.facebook.com/v19.0/{$postId}/comments", [
                    'message'      => "বিস্তারিত পড়ুন: " . $newsLink,
                    'access_token' => $settings->fb_access_token
                ]);
                Log::info("✅ FB Post Success");
            } else {
                Log::error("❌ FB Error: " . json_encode($response->json()));
            }

        } catch (\Exception $e) {
            Log::error("❌ FB Exception: " . $e->getMessage());
        }
    }

    public function postToTelegram($settings, $title, $imagePathOrUrl, $newsLink)
    {
        if (empty($settings->telegram_channel_id) || empty($settings->telegram_bot_token)) return;

        try {
            $botToken = $settings->telegram_bot_token;
            $chatId   = $settings->telegram_channel_id;
            $endpoint = "https://api.telegram.org/bot{$botToken}/sendPhoto";

            $payload = [
                'chat_id' => $chatId,
                'caption' => "📢 <b>{$title}</b>\n\n👇 বিস্তারিত পড়তে লিংকে ক্লিক করুন:\n{$newsLink}",
                'parse_mode' => 'HTML'
            ];

            if (file_exists($imagePathOrUrl)) {
                // লোকাল ফাইল আপলোড
                Http::attach('photo', file_get_contents($imagePathOrUrl), 'news.jpg')
                    ->post($endpoint, $payload);
            } else {
                // URL পাঠানো
                $payload['photo'] = $imagePathOrUrl;
                Http::post($endpoint, $payload);
            }

            Log::info("✅ Telegram Sent");

        } catch (\Exception $e) {
            Log::error("❌ Telegram Exception: " . $e->getMessage());
        }
    }
}