<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    /**
     * 🔥 Main Function: Orchestrates Image Upload & Post Publishing
     */
    public function createPost($news, $user, $customTitle = null, $customContent = null, $customCategory = null)
    {
        // ১. ওয়েবসাইট ক্রেডেনশিয়ালস বের করা
        $website = $news->website;

        if (!$website) {
            return [
                'success' => false, 
                'message' => 'No website connected to this news source.'
            ];
        }

        // ইউজার সেটিংস থেকে ক্রেডেনশিয়াল নেওয়া (যদি ওয়েবসাইট টেবিলে না থাকে)
        // অথবা ওয়েবসাইট টেবিল থেকে নেওয়া (আপনার লজিক অনুযায়ী)
        // এখানে ধরে নিচ্ছি ওয়েবসাইটের নিজস্ব ক্রেডেনশিয়াল আছে, অথবা ইউজারের গ্লোবাল সেটিংস ব্যবহার হবে
        
        $settings = $user->settings;
        
        // এখানে লজিক: নিউজ সোর্স ওয়েবসাইটের ক্রেডেনশিয়াল নাকি ইউজারের নিজের ওয়ার্ডপ্রেস?
        // আমাদের সিস্টেমে পোস্ট হবে ইউজারের ওয়ার্ডপ্রেসে। তাই $user->settings থেকে নিতে হবে।
        
        $domain = $settings->wp_url;
        $username = $settings->wp_username;
        $appPassword = $settings->wp_app_password; 

        if (!$domain || !$username || !$appPassword) {
            return [
                'success' => false,
                'message' => 'User WordPress credentials not set.'
            ];
        }

        // ২. টাইটেল ও কন্টেন্ট সেট করা (কাস্টম > AI > অরিজিনাল)
        $postTitle = $customTitle ?? $news->title;
        $postContent = $customContent ?? $news->content;

        // ৩. ক্যাটাগরি সেট করা (ডিফল্ট ১ = Uncategorized)
        // যদি কাস্টম ক্যাটাগরি না থাকে, তবে সেটিংসের ম্যাপিং চেক করবে
        $categoryId = $customCategory;
        
        if (!$categoryId && !empty($settings->category_mapping)) {
            // যদি নিউজের অরিজিনাল ক্যাটাগরি থাকে, সেটা ম্যাপ করা
            // আপাতত ডিফল্ট ১ দিচ্ছি
            $categoryId = 1; 
        }
        $categoryId = $categoryId ?? 1;

        // ৪. ইমেজ আপলোড প্রসেস (যদি ইমেজ থাকে)
        $featuredMediaId = null;
        if (!empty($news->thumbnail_url)) {
            $uploadResult = $this->uploadImage($news->thumbnail_url, $postTitle, $domain, $username, $appPassword);
            if ($uploadResult['success']) {
                $featuredMediaId = $uploadResult['id'];
            } else {
                Log::warning("Image upload failed for News ID: {$news->id}");
            }
        }

        // ৫. ফাইনাল পোস্ট পাবলিশ করা
        return $this->publishPost(
            $postTitle, 
            $postContent, 
            $domain, 
            $username, 
            $appPassword, 
            $categoryId, 
            $featuredMediaId
        );
    }

    /**
     * Helper: Publish Post to WordPress
     */
    public function publishPost($title, $content, $domain, $username, $password, $categoryId = 1, $featuredMediaId = null)
    {
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/posts";

        $data = [
            'title'    => $title,
            'content'  => $content,
            'status'   => 'publish',
            'categories' => [$categoryId],
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
                    'post_id' => $json['id'], // 'id' কে 'post_id' হিসেবে রিটার্ন করছি জবের সুবিধার্থে
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
            // ক্লিন ইমেজ URL (Query param রিমুভ)
            $imageUrl = preg_replace('/\?.*/', '', $imageUrl);

            // ১. ইমেজ ডাউনলোড করা
            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->timeout(30)
                ->get($imageUrl);

            if ($response->failed()) return ['success' => false];

            $imageContent = $response->body();
            $contentType  = $response->header('Content-Type') ?: 'image/jpeg';
            
            // ফাইলের এক্সটেনশন ডিটেক্ট করা
            $extension = 'jpg';
            if (str_contains($contentType, 'png')) $extension = 'png';
            elseif (str_contains($contentType, 'webp')) $extension = 'webp';

            $fileName = 'news_' . time() . '.' . $extension;

            // ২. ওয়ার্ডপ্রেসে আপলোড করা
            $wpResponse = Http::withBasicAuth($username, $password)
                ->withHeaders([
                    'Content-Type'        => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])
                ->withBody($imageContent, $contentType)
                ->post($endpoint);

            if ($wpResponse->successful()) {
                $mediaId = $wpResponse->json()['id'];

                // ৩. অল্ট টেক্সট (Alt Text) সেট করা (অপশনাল কিন্তু ভালো)
                try {
                    Http::withBasicAuth($username, $password)
                        ->post("$domain/wp-json/wp/v2/media/" . $mediaId, [
                            'alt_text' => $title,
                            'title'    => $title,
                            'caption'  => $title
                        ]);
                } catch (\Exception $e) {
                    // Alt text সেট না হলেও সমস্যা নেই
                }

                return ['success' => true, 'id' => $mediaId];
            }

            Log::warning("WP Media Upload Failed: " . $wpResponse->body());
            return ['success' => false];

        } catch (\Exception $e) {
            Log::error("Image Upload Exception: " . $e->getMessage());
            return ['success' => false];
        }
    }

    /**
     * Helper: Get Categories (Optional Usage)
     */
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