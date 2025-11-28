<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressService
{
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
	
    public function publishPost($title, $content, $domain, $username, $password, $categoryId = 1, $featuredMediaId = null)
    {
        $domain = rtrim($domain, '/');
        $endpoint = "$domain/wp-json/wp/v2/posts";

        $data = [
            'title'   => $title,
            'content' => $content,
            'status'  => 'publish',
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
            $fileName     = 'news_' . time() . '.jpg';

            $wpResponse = Http::withBasicAuth($username, $password)
                ->withHeaders([
                    'Content-Type'        => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])
                ->withBody($imageContent, $contentType)
                ->post($endpoint);

            if ($wpResponse->successful()) {
                $mediaId = $wpResponse->json()['id'];

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
