<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    /**
     * ================================
     *  MERGED publishPost()
     * ================================
     *
     * নতুন ভার্সনের signature রাখা হয়েছে:
     * publishPost($title, $content, $domain, $username, $password, $categoryId, $featuredMediaId)
     * যেন ইউজারের আলাদা credentials কাজ করে।
     */
    public function publishPost($title, $content, $domain, $username, $password, $categoryId = 1, $featuredMediaId = null)
    {
        // ডোমেইন পরিষ্কার
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/posts";

        // ডেটা তৈরি
        $data = [
            'title'   => $title,
            'content' => $content,
            'status'  => 'publish',
            'categories' => [$categoryId],
        ];

        // ইমেজ থাকলে যোগ করা
        if ($featuredMediaId) {
            $data['featured_media'] = $featuredMediaId;
        }

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(60)
                ->post($endpoint, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'id'      => $response->json()['id'],
                    'link'    => $response->json()['link']
                ];
            }

            Log::error("WP Post Failed: " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("WP Connection Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ================================
     *  MERGED uploadImage()
     * ================================
     *
     * দুই ফাইলের uploadImage একইভাবে merge করা হয়েছে
     * নতুন credentials ($domain, $username, $password) রাখা হয়েছে
     */
    public function uploadImage($imageUrl, $title, $domain, $username, $password)
    {
        // সাইট URL পরিষ্কার
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/media";

        try {
            // ইমেজ URL থেকে query remove
            $imageUrl = preg_replace('/\?.*/', '', $imageUrl);

            // ইমেজ ডাউনলোড
            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->timeout(30)
                ->get($imageUrl);

            if ($response->failed()) return ['success' => false];

            $imageContent = $response->body();
            $contentType  = $response->header('Content-Type') ?: 'image/jpeg';
            $fileName     = 'news_' . time() . '.jpg';

            // মিডিয়া আপলোড
            $wpResponse = Http::withBasicAuth($username, $password)
                ->withHeaders([
                    'Content-Type'        => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])
                ->withBody($imageContent, $contentType)
                ->post($endpoint);

            if ($wpResponse->successful()) {
                $mediaId = $wpResponse->json()['id'];

                // Alt text update চেষ্টা
                try {
                    Http::withBasicAuth($username, $password)
                        ->post("$domain/wp-json/wp/v2/media/" . $mediaId, [
                            'alt_text' => $title,
                            'title'    => $title
                        ]);
                } catch (\Exception $e) {}

                return ['success' => true, 'id' => $mediaId];
            }

            return ['success' => false];

        } catch (\Exception $e) {
            return ['success' => false];
        }
    }
}
