<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    // ======================================================
    // 1. CREATE POST (Updated with Hashtags)
    // ======================================================
    public function createPost($news, $user, $customTitle = null, $customContent = null, $customCategories = [], $customImage = null, $hashtags = null)
    {
        // ১. সেটিংস লোড করা
        $settings = $user->settings;

        if (!$settings) {
            return ['success' => false, 'message' => 'User settings not found.'];
        }

        $domain = $settings->wp_url;
        $username = $settings->wp_username;
        $appPassword = $settings->wp_app_password;

        if (!$domain || !$username || !$appPassword) {
            return ['success' => false, 'message' => 'User WordPress credentials not set.'];
        }

        // ২. টাইটেল ও কন্টেন্ট সেট করা 
        $postTitle = $customTitle ?? $news->ai_title ?? $news->title;
        $postContent = $customContent ?? $news->ai_content ?? $news->content;

        // ৩. ক্যাটাগরি হ্যান্ডলিং
        $finalCategories = !empty($customCategories) ? $customCategories : [1];
        if (!is_array($finalCategories)) {
            $finalCategories = [$finalCategories];
        }
        $finalCategories = array_map('intval', $finalCategories);

        // ৪. 🔥 হ্যাসট্যাগ প্রসেসিং (Tag ID তে কনভার্ট করা)
        $tagIds = [];
        if (!empty($hashtags)) {
            $tagIds = $this->processTags($domain, $username, $appPassword, $hashtags);
        }

        // ৫. ইমেজ আপলোড
        $imageUrlToUpload = $customImage ?? $news->thumbnail_url;
        $featuredMediaId = null;

        if (!empty($imageUrlToUpload)) {
            $uploadResult = $this->uploadImage($imageUrlToUpload, $postTitle, $domain, $username, $appPassword);
            if ($uploadResult['success']) {
                $featuredMediaId = $uploadResult['id'];
            }
        }

        // ৬. ফাইনাল পোস্ট পাবলিশ করা
        return $this->publishPost(
            $postTitle,
            $postContent,
            $domain,
            $username,
            $appPassword,
            $finalCategories,
            $tagIds, // 🔥 ট্যাগ পাঠানো হচ্ছে
            $featuredMediaId
        );
    }

    // ======================================================
    // 2. UPDATE POST (Updated with Hashtags)
    // ======================================================
    public function updatePost($postId, $news, $user, $customTitle, $customContent, $customCategories, $customImage, $hashtags = null)
    {
        $settings = $user->settings;
        $postTitle = $customTitle ?? $news->ai_title ?? $news->title;
        $postContent = $customContent ?? $news->ai_content ?? $news->content;

        $domain = $settings->wp_url;
        $username = $settings->wp_username;
        $appPassword = $settings->wp_app_password;

        // ইমেজ আপলোড (যদি নতুন ইমেজ থাকে)
        $featuredMediaId = null;
        if ($customImage) {
            $upload = $this->uploadImage($customImage, $postTitle, $domain, $username, $appPassword);
            if ($upload['success']) $featuredMediaId = $upload['id'];
        }

        // 🔥 হ্যাসট্যাগ প্রসেসিং
        $tagIds = [];
        if (!empty($hashtags)) {
            $tagIds = $this->processTags($domain, $username, $appPassword, $hashtags);
        }

        // ওয়ার্ডপ্রেস এপিআই-তে রিকোয়েস্ট
        $url = rtrim($domain, '/') . '/wp-json/wp/v2/posts/' . $postId;
        
        $data = [
            'title'      => $postTitle,
            'content'    => $postContent,
            'categories' => $customCategories,
            'status'     => 'publish',
        ];

        // ট্যাগ ও ইমেজ থাকলে যোগ হবে
        if ($featuredMediaId) $data['featured_media'] = $featuredMediaId;
        if (!empty($tagIds)) $data['tags'] = $tagIds; // 🔥 ট্যাগ আপডেট

        $response = Http::withBasicAuth($username, $appPassword)->post($url, $data);

        if ($response->successful()) {
            return ['success' => true, 'post_id' => $response->json()['id']];
        }
        return ['success' => false, 'message' => $response->body()];
    }

    /**
     * Helper: Publish Post to WordPress
     */
    public function publishPost($title, $content, $domain, $username, $password, $categoryIds = [1], $tagIds = [], $featuredMediaId = null)
    {
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/posts";

        // ডাটা প্রিপারেশন
        $data = [
            'title'      => $title,
            'content'    => $content,
            'status'     => 'publish',
            'categories' => $categoryIds,
        ];

        // ট্যাগ যোগ করা
        if (!empty($tagIds)) {
            $data['tags'] = $tagIds; // 🔥 ট্যাগ যুক্ত করা হলো
        }

        if ($featuredMediaId) {
            $data['featured_media'] = $featuredMediaId;
        }

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(60)
                ->post($endpoint, $data);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success' => true,
                    'post_id' => $json['id'],
                    'link'    => $json['link']
                ];
            }

            Log::error("WP Post Failed: " . $response->body());
            return [
                'success' => false, 
                'message' => 'WP API Error: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("WP Connection Error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 🔥 Helper: Convert Hashtags String to WP Tag IDs
     * WP API সরাসরি ট্যাগ নেম নেয় না, আইডি চায়। তাই চেক করে আইডি বের করতে হয়।
     */
    public function processTags($domain, $username, $password, $hashtagsString)
    {
        $domain = rtrim($domain, '/');
        $tagsEndpoint = "$domain/wp-json/wp/v2/tags";
        $tagIds = [];

        // ১. স্ট্রিং থেকে ট্যাগ অ্যারে বানানো (# কেটে দেওয়া)
        // #News #Tech -> ['News', 'Tech']
        $tagsArray = array_filter(array_map(function($tag) {
            return trim(str_replace(['#', ','], '', $tag));
        }, explode(' ', $hashtagsString)));

        if (empty($tagsArray)) return [];

        foreach ($tagsArray as $tagName) {
            try {
                // ২. ট্যাগটি আছে কি না চেক করা
                $checkResponse = Http::withBasicAuth($username, $password)
                    ->get($tagsEndpoint, ['search' => $tagName]);

                if ($checkResponse->successful() && !empty($checkResponse->json())) {
                    // ট্যাগ পাওয়া গেলে আইডি নেওয়া
                    // এক্সাক্ট ম্যাচ চেক (কারণ search পার্শিয়াল রেজাল্ট দিতে পারে)
                    $existingTags = $checkResponse->json();
                    $foundId = null;
                    foreach ($existingTags as $t) {
                        if (strtolower($t['name']) === strtolower($tagName)) {
                            $foundId = $t['id'];
                            break;
                        }
                    }
                    if ($foundId) {
                        $tagIds[] = $foundId;
                        continue;
                    }
                }

                // ৩. ট্যাগ না থাকলে নতুন তৈরি করা
                $createResponse = Http::withBasicAuth($username, $password)
                    ->post($tagsEndpoint, ['name' => $tagName]);

                if ($createResponse->successful()) {
                    $tagIds[] = $createResponse->json()['id'];
                }

            } catch (\Exception $e) {
                Log::warning("Failed to process tag: $tagName - " . $e->getMessage());
            }
        }

        return $tagIds;
    }

    /**
     * Helper: Upload Image to WordPress
     */
    public function uploadImage($imageUrl, $title, $domain, $username, $password)
    {
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/media";

        try {
            $imageUrl = preg_replace('/\?.*/', '', $imageUrl);

            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->timeout(30)
                ->get($imageUrl);

            if ($response->failed()) return ['success' => false];

            $imageContent = $response->body();
            $contentType  = $response->header('Content-Type') ?: 'image/jpeg';
            
            $extension = 'jpg';
            if (str_contains($contentType, 'png')) $extension = 'png';
            elseif (str_contains($contentType, 'webp')) $extension = 'webp';

            $fileName = 'news_' . time() . '.' . $extension;

            $wpResponse = Http::withBasicAuth($username, $password)
                ->withHeaders([
                    'Content-Type'        => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])
                ->withBody($imageContent, $contentType)
                ->post($endpoint);

            if ($wpResponse->successful()) {
                $mediaId = $wpResponse->json()['id'];
                return ['success' => true, 'id' => $mediaId];
            }

            return ['success' => false];

        } catch (\Exception $e) {
            return ['success' => false];
        }
    }

    public function getCategories($domain, $username, $password)
    {
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/categories?per_page=100";

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->get($endpoint);

            if ($response->successful()) {
                return $response->json();
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}