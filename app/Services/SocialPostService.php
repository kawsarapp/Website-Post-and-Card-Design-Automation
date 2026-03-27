<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialPostService
{
    /**
     * Post to active Facebook Pages of the user.
     * If $selectedPageIds is non-empty, only those pages receive the post.
     *
     * @param  mixed  $settings
     * @param  string $title
     * @param  string $imagePathOrUrl
     * @param  string $newsLink
     * @param  array  $selectedPageIds  DB IDs of FacebookPage records to post to
     * @return array  [['page_name' => ..., 'success' => bool, 'message' => ...], ...]
     */
    public function postToFacebook($settings, $title, $imagePathOrUrl, $newsLink, array $selectedPageIds = [])
    {
        // ১. User এর সব active Facebook page গুলো নিই
        $user  = User::with('facebookPages')->find($settings->user_id);
        $pages = $user?->facebookPages()->where('is_active', true)->get() ?? collect();

        // ২. যদি specific page IDs দেওয়া থাকে, শুধু সেগুলোতেই পোস্ট হবে
        if (!empty($selectedPageIds)) {
            $pages = $pages->filter(fn($p) => in_array($p->id, $selectedPageIds))->values();
        }

        // ৩. যদি নতুন multi-page সিস্টেমে কোনো page না থাকে,
        //    তাহলে পুরনো user_settings এর single page দিয়ে fallback করি
        if ($pages->isEmpty()) {
            if (empty($settings->fb_page_id) || empty($settings->fb_access_token)) {
                return [['success' => false, 'message' => 'কোনো Facebook Page কানেক্ট করা নেই।']];
            }
            $fakePage = (object)[
                'page_name'    => 'My Facebook Page',
                'page_id'      => $settings->fb_page_id,
                'access_token' => $settings->fb_access_token,
                'comment_link' => $settings->fb_comment_link ?? false,
            ];
            $pages = collect([$fakePage]);
        }

        $results = [];

        // ৪. প্রতিটি page-এ আলাদাভাবে পোস্ট করি
        foreach ($pages as $page) {
            $result                = $this->sendToOnePage($page, $title, $imagePathOrUrl, $newsLink);
            $result['page_name']   = $page->page_name ?? 'Unknown Page';
            $results[]             = $result;
        }

        return $results;
    }

    /**
     * একটি নির্দিষ্ট page-এ পোস্ট পাঠানো (internal helper)
     */
    private function sendToOnePage($page, $title, $imagePathOrUrl, $newsLink)
    {
        try {
            $endpoint = "https://graph.facebook.com/v19.0/{$page->page_id}/photos";
            $payload  = [
                'message'      => $title,
                'access_token' => $page->access_token,
                'published'    => true,
            ];

            if (file_exists($imagePathOrUrl)) {
                $response = Http::attach('source', file_get_contents($imagePathOrUrl), 'news-card.jpg')
                    ->post($endpoint, $payload);
            } else {
                $payload['url'] = $imagePathOrUrl;
                $response       = Http::post($endpoint, $payload);
            }

            $data   = $response->json();
            $postId = $data['post_id'] ?? null;

            if ($postId) {
                // Comment-এ link দেওয়া (যদি চালু থাকে)
                if (!empty($page->comment_link)) {
                    try {
                        Http::post("https://graph.facebook.com/v19.0/{$postId}/comments", [
                            'message'      => 'বিস্তারিত পড়ুন: ' . $newsLink,
                            'access_token' => $page->access_token,
                        ]);
                    } catch (\Exception $e) {
                        Log::error("❌ FB Comment Error [{$page->page_name}]: " . $e->getMessage());
                    }
                }

                Log::info("✅ FB Post Success [{$page->page_name}]: $postId");
                return ['success' => true, 'message' => null];
            } else {
                $errorData   = $data['error'] ?? [];
                $detailedMsg = $errorData['error_user_msg'] ?? null;
                $titleMsg    = $errorData['error_user_title'] ?? null;
                $genericMsg  = $errorData['message'] ?? 'Unknown Facebook Error';
                $finalError  = $detailedMsg ?: ($titleMsg ? "$titleMsg: $genericMsg" : $genericMsg);

                Log::error("❌ FB Error [{$page->page_name}]: " . json_encode($data));
                return ['success' => false, 'message' => $finalError];
            }

        } catch (\Exception $e) {
            Log::error("❌ FB Exception [{$page->page_name}]: " . $e->getMessage());
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
                'chat_id'    => $chatId,
                'caption'    => "📢 <b>{$title}</b>\n\n👇 বিস্তারিত পড়তে লিংকে ক্লিক করুন:\n{$newsLink}",
                'parse_mode' => 'HTML',
            ];

            if (file_exists($imagePathOrUrl)) {
                $response = Http::attach('photo', file_get_contents($imagePathOrUrl), 'news.jpg')
                    ->post($endpoint, $payload);
            } else {
                $payload['photo'] = $imagePathOrUrl;
                $response         = Http::post($endpoint, $payload);
            }

            if ($response->successful()) {
                Log::info('✅ Telegram Sent');
                return ['success' => true, 'message' => null];
            } else {
                $errorMsg = $response->json()['description'] ?? 'Unknown Telegram Error';
                Log::error('❌ Telegram Failed: ' . $errorMsg);
                return ['success' => false, 'message' => $errorMsg];
            }

        } catch (\Exception $e) {
            Log::error('❌ Telegram Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}