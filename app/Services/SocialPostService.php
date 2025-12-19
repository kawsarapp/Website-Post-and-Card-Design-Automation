<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialPostService
{
    /**
     * Post to Facebook Page
     * Returns: ['success' => bool, 'message' => string|null]
     */
    
	public function postToFacebook($settings, $title, $imagePathOrUrl, $newsLink)
    {
        if (empty($settings->fb_page_id) || empty($settings->fb_access_token)) {
            return ['success' => false, 'message' => 'Setup Missing: Page ID or Token not found.'];
        }

        try {
            $endpoint = "https://graph.facebook.com/v19.0/{$settings->fb_page_id}/photos";
            $payload = [
                'message'      => $title,
                'access_token' => $settings->fb_access_token,
                'published'    => true
            ];

            if (file_exists($imagePathOrUrl)) {
                $response = Http::attach(
                    'source', file_get_contents($imagePathOrUrl), 'news-card.jpg'
                )->post($endpoint, $payload);
            } else {
                $payload['url'] = $imagePathOrUrl;
                $response = Http::post($endpoint, $payload);
            }

            $data = $response->json();
            $postId = $data['post_id'] ?? null;

            if ($postId) {
                // কমেন্ট (Optional)
                try {
                    Http::post("https://graph.facebook.com/v19.0/{$postId}/comments", [
                        'message'      => "বিস্তারিত পড়ুন: " . $newsLink,
                        'access_token' => $settings->fb_access_token
                    ]);
                } catch (\Exception $e) {}

                Log::info("✅ FB Post Success: $postId");
                return ['success' => true, 'message' => null];
            } else {
                
                $errorData = $data['error'] ?? [];

                $detailedMsg = $errorData['error_user_msg'] ?? null;
                
                $titleMsg = $errorData['error_user_title'] ?? null;

                $genericMsg = $errorData['message'] ?? 'Unknown Facebook Error';

                if ($detailedMsg) {
                    $finalError = $detailedMsg; 
                } elseif ($titleMsg) {
                    $finalError = $titleMsg . ": " . $genericMsg;
                } else {
                    $finalError = $genericMsg;
                }

                Log::error("❌ FB Error Raw: " . json_encode($data));
                Log::error("❌ FB Error Display: " . $finalError);

                return ['success' => false, 'message' => $finalError];
            }

        } catch (\Exception $e) {
            Log::error("❌ FB Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Post to Telegram Channel
     * Returns: ['success' => bool, 'message' => string|null]
     */
    public function postToTelegram($settings, $title, $imagePathOrUrl, $newsLink)
    {
        if (empty($settings->telegram_channel_id) || empty($settings->telegram_bot_token)) {
            return ['success' => false, 'message' => 'Setup Missing'];
        }

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
                $response = Http::attach('photo', file_get_contents($imagePathOrUrl), 'news.jpg')
                    ->post($endpoint, $payload);
            } else {
                $payload['photo'] = $imagePathOrUrl;
                $response = Http::post($endpoint, $payload);
            }

            if ($response->successful()) {
                Log::info("✅ Telegram Sent");
                return ['success' => true, 'message' => null];
            } else {
                $errorMsg = $response->json()['description'] ?? 'Unknown Telegram Error';
                Log::error("❌ Telegram Failed: " . $errorMsg);
                return ['success' => false, 'message' => $errorMsg];
            }

        } catch (\Exception $e) {
            Log::error("❌ Telegram Exception: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}