<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialPostService
{
    /**
     * 📘 Facebook Auto Post (With First Comment Link)
     */
    public function postToFacebook($settings, $title, $imageUrl, $newsLink)
    {
        // ইউজারের টোকেন না থাকলে রিটার্ন করবে
        if (empty($settings->fb_page_id) || empty($settings->fb_access_token)) {
            return; 
        }

        try {
            // ১. ছবি এবং ক্যাপশন পোস্ট করা
            $response = Http::post("https://graph.facebook.com/v19.0/{$settings->fb_page_id}/photos", [
                'url'          => $imageUrl,
                'message'      => $title, // ক্যাপশন
                'access_token' => $settings->fb_access_token,
                'published'    => true
            ]);

            $postId = $response->json()['post_id'] ?? null;

            // ২. পোস্ট সফল হলে প্রথম কমেন্টে লিংক দেওয়া
            if ($postId) {
                Http::post("https://graph.facebook.com/v19.0/{$postId}/comments", [
                    'message'      => "বিস্তারিত পড়ুন: " . $newsLink,
                    'access_token' => $settings->fb_access_token
                ]);
                Log::info("✅ FB Post Success for User ID: {$settings->user_id}");
            } else {
                Log::error("❌ FB Error: " . json_encode($response->json()));
            }

        } catch (\Exception $e) {
            Log::error("❌ FB Exception: " . $e->getMessage());
        }
    }

    /**
     * ✈️ Telegram Auto Post (Dynamic Bot)
     */
    public function postToTelegram($settings, $title, $imageUrl, $newsLink)
    {
        // ইউজারের বট টোকেন না থাকলে রিটার্ন করবে
        if (empty($settings->telegram_channel_id) || empty($settings->telegram_bot_token)) {
            return;
        }

        try {
            $botToken = $settings->telegram_bot_token;
            $chatId   = $settings->telegram_channel_id;

            // টেলিগ্রাম API কল
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendPhoto", [
                'chat_id' => $chatId,
                'photo'   => $imageUrl,
                'caption' => "📢 <b>{$title}</b>\n\n👇 বিস্তারিত পড়তে লিংকে ক্লিক করুন:\n{$newsLink}",
                'parse_mode' => 'HTML'
            ]);

            if($response->successful()) {
                Log::info("✅ Telegram Success for User ID: {$settings->user_id}");
            } else {
                Log::error("❌ Telegram Error: " . json_encode($response->json()));
            }

        } catch (\Exception $e) {
            Log::error("❌ Telegram Exception: " . $e->getMessage());
        }
    }
}