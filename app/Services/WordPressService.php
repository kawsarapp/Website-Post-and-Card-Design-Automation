<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    public function createPost($news, $user, $customTitle = null, $customContent = null, $customCategories = [], $customImage = null)
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

        // 🔥 ক্যাটাগরি হ্যান্ডলিং (Array নিশ্চিত করা)
        $finalCategories = !empty($customCategories) ? $customCategories : [1];
        
        // যদি অ্যারে না হয়, অ্যারে বানিয়ে নেওয়া
        if (!is_array($finalCategories)) {
            $finalCategories = [$finalCategories];
        }
        
        // ইন্টিজারে কনভার্ট করা (নিরাপত্তার জন্য)
        $finalCategories = array_map('intval', $finalCategories);

        // ৪. ইমেজ আপলোড
        $imageUrlToUpload = $customImage ?? $news->thumbnail_url;
        $featuredMediaId = null;

        if (!empty($imageUrlToUpload)) {
            $uploadResult = $this->uploadImage($imageUrlToUpload, $postTitle, $domain, $username, $appPassword);
            if ($uploadResult['success']) {
                $featuredMediaId = $uploadResult['id'];
            }
        }

        // ৫. ফাইনাল পোস্ট পাবলিশ করা
        return $this->publishPost(
            $postTitle,
            $postContent,
            $domain,
            $username,
            $appPassword,
            $finalCategories, // ✅ Array পাঠানো হচ্ছে
            $featuredMediaId
        );
    }
	
	
	
	// app/Services/WordPressService.php এর ভেতরে এই মেথডটি যোগ করুন
public function updatePost($postId, $news, $user, $customTitle, $customContent, $customCategories, $customImage)
{
    $settings = $user->settings;
    $postTitle = $customTitle ?? $news->ai_title ?? $news->title;
    $postContent = $customContent ?? $news->ai_content ?? $news->content;

    // ইমেজ আপলোড (যদি নতুন ইমেজ থাকে)
    $featuredMediaId = null;
    if ($customImage) {
        $upload = $this->uploadImage($customImage, $postTitle, $settings->wp_url, $settings->wp_username, $settings->wp_app_password);
        if ($upload['success']) $featuredMediaId = $upload['id'];
    }

    // ওয়ার্ডপ্রেস এপিআই-তে PUT রিকোয়েস্ট পাঠানো (আপডেটের জন্য)
    $url = rtrim($settings->wp_url, '/') . '/wp-json/wp/v2/posts/' . $postId;
    $data = [
        'title'   => $postTitle,
        'content' => $postContent,
        'categories' => $customCategories,
        'status'  => 'publish',
    ];
    if ($featuredMediaId) $data['featured_media'] = $featuredMediaId;

    $response = Http::withBasicAuth($settings->wp_username, $settings->wp_app_password)->post($url, $data);

    if ($response->successful()) {
        return ['success' => true, 'post_id' => $response->json()['id']];
    }
    return ['success' => false, 'message' => $response->body()];
}

    /**
     * Helper: Publish Post to WordPress
     */
    public function publishPost($title, $content, $domain, $username, $password, $categoryIds = [1], $featuredMediaId = null)
    {
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/posts";

        // ডাটা প্রিপারেশন
        $data = [
            'title'    => $title,
            'content'  => $content,
            'status'   => 'publish',
            'categories' => $categoryIds, // ✅ এখন নামের বানান ঠিক আছে ($categoryIds)
        ];

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