<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait WordPressPostingTrait
{
    protected function executeWordPressPost($wpService, $news, $user, $settings, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags, $publishedUrl)
    {
        $result = ['success' => false, 'remote_id' => $news->wp_post_id, 'published_url' => $publishedUrl];

        try {
            if ($news->wp_post_id) {
                Log::info("🔄 Updating existing WordPress post: ID {$news->wp_post_id}");
                $postResult = $wpService->updatePost(
                    $news->wp_post_id, $news, $user, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags
                );
            } else {
                Log::info("🆕 Creating new WordPress post");
                $postResult = $wpService->createPost(
                    $news, $user, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags
                );
            }

            if ($postResult['success']) {
                $result['success'] = true;
                $result['remote_id'] = $postResult['post_id'];
                $result['published_url'] = $postResult['link'] ?? $publishedUrl;
                Log::info("✅ WP Action Success: ID {$result['remote_id']} | Link: {$result['published_url']}");
            } else {
                $errorMsg = $postResult['message'] ?? 'Unknown WP Error';
                Log::error("❌ WP Action Failed: " . $errorMsg);
                if (!$settings->post_to_laravel) throw new \Exception("WP Failed: " . $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error("❌ WP Exception: " . $e->getMessage());
            if (!$settings->post_to_laravel) throw $e;
        }

        return $result;
    }
}