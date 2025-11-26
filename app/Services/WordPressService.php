<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WordPressService
{
    public function uploadImage($imageUrl, $title)
    {
        try {
            $imageUrl = preg_replace('/\?.*/', '', $imageUrl); 
            $response = Http::withOptions(['verify' => false])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->timeout(30)->get($imageUrl);

            if ($response->failed()) return ['success' => false];

            $imageContent = $response->body();
            $contentType = $response->header('Content-Type') ?: 'image/jpeg';
            $fileName = 'news-' . time() . '.jpg';
            $wpBaseUrl = rtrim(env('WP_SITE_URL'), '/');
            
            $wpResponse = Http::withBasicAuth(env('WP_USERNAME'), str_replace(' ', '', env('WP_APP_PASSWORD')))
                ->withHeaders([
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])
                ->withBody($imageContent, $contentType)
                ->post($wpBaseUrl . '/wp-json/wp/v2/media');

            if ($wpResponse->successful()) {
                $mediaId = $wpResponse->json()['id'];
                // Alt Text Update
                try {
                    Http::withBasicAuth(env('WP_USERNAME'), str_replace(' ', '', env('WP_APP_PASSWORD')))
                        ->post($wpBaseUrl . '/wp-json/wp/v2/media/' . $mediaId, ['alt_text' => $title, 'title' => $title]);
                } catch (\Exception $e) {}
                return ['success' => true, 'id' => $mediaId];
            }
            return ['success' => false];
        } catch (\Exception $e) { return ['success' => false]; }
    }

    public function publishPost($title, $content, $categoryId, $imageId)
    {
        $wpBaseUrl = rtrim(env('WP_SITE_URL'), '/');
        
        $postData = [
            'title'   => $title,
            'content' => $content,
            'status'  => 'publish',
            'categories' => [$categoryId], 
        ];

        if ($imageId) {
            $postData['featured_media'] = (int) $imageId;
        }

        $response = Http::withBasicAuth(env('WP_USERNAME'), str_replace(' ', '', env('WP_APP_PASSWORD')))
            ->post($wpBaseUrl . '/wp-json/wp/v2/posts', $postData);

        return $response->successful() ? $response->json() : null;
    }
}