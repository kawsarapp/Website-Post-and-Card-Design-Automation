<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

trait ApiPostingTrait
{
    // 🟢 $settings ভেরিয়েবলটি ProcessNewsPost জব থেকে সরাসরি Admin-এর সেটিংস হিসেবে আসবে
    protected function executeApiPost($news, $settings, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags, $remotePostId, $publishedUrl)
    {
        $result = ['success' => false, 'remote_id' => $remotePostId, 'published_url' => $publishedUrl];
        $baseUrl = rtrim($settings->laravel_site_url, '/');

        try {
            // 🟢 CUSTOM API LOGIC (Guzzle Multipart)
            if (!empty($settings->custom_api_url) && !empty($settings->custom_api_mapping)) {
                $apiUrl = $settings->custom_api_url;
                $mapping = json_decode($settings->custom_api_mapping, true);
                Log::info("🟢 Sending Dynamic Custom Request to: " . $apiUrl);

                $multipart = [];
                $addPart = function($name, $val) use (&$multipart) {
                    $multipart[] = ['name' => $name, 'contents' => (string) ($val ?? '')];
                };

                if (isset($mapping['title'])) $addPart($mapping['title'], $finalTitle);
                if (isset($mapping['content'])) $addPart($mapping['content'], $finalContent);
                if (isset($mapping['tags'])) $addPart($mapping['tags'], $hashtags);
                if (isset($mapping['date'])) $addPart($mapping['date'], now()->format('Y-m-d'));

                if (isset($mapping['extra']) && is_array($mapping['extra'])) {
                    foreach ($mapping['extra'] as $key => $val) { $addPart((string) $key, $val); }
                }

                if (isset($mapping['category'])) {
                    $catKey = str_replace('[]', '', $mapping['category']);
                    foreach ($categories as $cat) {
                        $multipart[] = ['name' => $catKey . '[]', 'contents' => (string) $cat];
                    }
                }

                if (isset($mapping['image']) && !empty($websiteImage)) {
                    try {
                        $imgResponse = Http::timeout(20)->get($websiteImage);
                        if ($imgResponse->successful()) {
                            $multipart[] = [
                                'name' => (string) $mapping['image'],
                                'contents' => $imgResponse->body(),
                                'filename' => basename(parse_url($websiteImage, PHP_URL_PATH)) ?: 'news_image.jpg'
                            ];
                            Log::info("🖼️ Image attached for API");
                        }
                    } catch (\Exception $e) { Log::warning("⚠️ Image Fetch Failed: " . $e->getMessage()); }
                }

                // 🔥 Header Auth Logic
                $headers = ['Accept' => 'application/json'];
                if (isset($mapping['header_auth']) && $mapping['header_auth'] == 'Bearer' && !empty($settings->laravel_api_token)) {
                    $headers['Authorization'] = 'Bearer ' . $settings->laravel_api_token;
                } elseif (isset($mapping['token']) && !empty($settings->laravel_api_token)) {
                    $addPart($mapping['token'], $settings->laravel_api_token);
                }

                // 🔥 ফিক্স: Timeout ৩০ সেকেন্ড থেকে বাড়িয়ে ১২০ সেকেন্ড করা হলো
                $client = new \GuzzleHttp\Client([
                    'timeout' => 120, 
                    'connect_timeout' => 30,
                    'verify' => false
                ]);
                
                $guzzleResponse = $client->post($apiUrl, [
                    'multipart' => $multipart, 
                    'headers' => $headers, 
                    'http_errors' => false
                ]);

                $responseBody = $guzzleResponse->getBody()->getContents();
                $statusCode = $guzzleResponse->getStatusCode();
                Log::info("🔍 Custom API Response (HTTP {$statusCode}): " . $responseBody);

                if ($statusCode >= 200 && $statusCode < 300) {
                    $result['success'] = true;
                    $respData = json_decode($responseBody, true) ?? [];
                    $idKey = $mapping['response_id_key'] ?? 'post_id';
                    
                    $extractedId = $respData[$idKey] ?? ($respData['data'][$idKey] ?? ($respData['id'] ?? $remotePostId));
                    $result['remote_id'] = $extractedId;
                    
                    $siteBase = rtrim($settings->laravel_site_url, '/');
                    $prefix = trim($settings->laravel_route_prefix ?? 'news', '/');
                    
                    $liveUrl = $respData['live_url'] ?? ($respData['data']['URLAlies'] ?? ($respData['link'] ?? ($siteBase . '/' . $prefix . '/' . $result['remote_id'])));
                    $result['published_url'] = filter_var($liveUrl, FILTER_VALIDATE_URL) ? $liveUrl : ($siteBase . '/' . ltrim($liveUrl, '/'));
                    
                    Log::info("✅ Custom API Success. ID: {$result['remote_id']}");
                }

            } 
            // 🔵 DEFAULT API LOGIC
            else {
                $apiUrl = $baseUrl . '/api/external-news-post';
                $payload = [
                    'token' => $settings->laravel_api_token, 'title' => $finalTitle, 'content' => $finalContent,
                    'image_url' => $websiteImage, 'hashtags' => $hashtags, 'category_name' => $news->category ?? 'General',
                    'category_ids' => $categories, 'original_link' => $news->original_link
                ];
                if ($news->wp_post_id) $payload['remote_id'] = $news->wp_post_id;

                $response = Http::timeout(120)->post($apiUrl, $payload); // এখানেও ১২০ সেকেন্ড করা হলো
                
                if ($response && $response->successful()) {
                    $result['success'] = true;
                    $respData = $response->json();
                    $result['remote_id'] = $respData['post_id'] ?? $respData['id'] ?? $remotePostId;
                    $siteBase = rtrim($settings->laravel_site_url, '/');
                    $prefix = trim($settings->laravel_route_prefix ?? 'news', '/');
                    $result['published_url'] = $respData['live_url'] ?? $respData['link'] ?? $respData['url'] ?? ($siteBase . '/' . $prefix . '/' . $result['remote_id']);
                    Log::info("✅ Default API Success. ID: {$result['remote_id']}");
                } else {
                    Log::error("❌ Default API Failed: " . ($response ? $response->body() : 'No Response'));
                }
            }
        } catch (\Exception $e) { Log::error("❌ API Connection Error: " . $e->getMessage()); }

        return $result;
    }
}