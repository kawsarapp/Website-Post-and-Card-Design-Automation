<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Notifications\PostPublishedNotification;

trait SocialAndFinalizeTrait
{
    protected function executeFinalization($news, $user, $settings, $wpSuccess, $laravelSuccess, $socialOnly, $skipSocial, $remotePostId, $publishedUrl, $websiteImage, $socialImage, $hashtags, $finalTitle, $socialPoster, $cardGenerator)
    {
        if (!$wpSuccess && !$laravelSuccess && !$socialOnly) {
            throw new \Exception("Posting failed on all configured endpoints.");
        }

        // 🔥 Staff ID বের করা (Job-এর $this->userId থেকে)
        $staffId = ($this->userId != $user->id) ? $this->userId : null;

        DB::transaction(function () use ($news, $user, $remotePostId, $publishedUrl, $websiteImage, $socialOnly, $hashtags, $staffId) {
            $updateData = [
                'is_posted' => true, 'posted_at' => now(), 'status' => 'published',
                'live_url' => $publishedUrl, 'error_message' => null, 'hashtags' => $hashtags 
            ];

            if ($remotePostId) $updateData['wp_post_id'] = $remotePostId;
            if (!$socialOnly) $updateData['thumbnail_url'] = $websiteImage;
            
            // 🔥 স্টাফ আইডি আপডেট
            if ($staffId) {
                $updateData['staff_id'] = $staffId;
            }

            $news->update($updateData);

            if (!$this->skipCreditDeduction && $user->role !== 'super_admin' && $user->credits > 0) {
                $user->decrement('credits');
                \App\Models\CreditHistory::create([
                    'user_id' => $user->id, 
                    'staff_id' => $staffId, // 🔥 স্টাফ আইডি ট্র্যাকিং
                    'action_type' => 'auto_post',
                    'description' => 'Published/Updated via Job', 'credits_change' => -1, 'balance_after' => $user->credits
                ]);
            }
        });

        if (!$skipSocial && ($settings->post_to_fb || $settings->post_to_telegram)) {
            $imageToPost = $socialImage; 
            $localCardPath = null;

            if (!isset($this->customData['social_image'])) {
                Log::info("🎨 Generating Auto News Card...");
                $localCardPath = $cardGenerator->generate($news, $settings);
                if ($localCardPath) $imageToPost = $localCardPath;
            } else {
                Log::info("✨ Using Studio Designed Image.");
                $appUrl = config('app.url');
                if (strpos($imageToPost, $appUrl) !== false) {
                    $relativePath = ltrim(strtok(str_replace($appUrl, '', $imageToPost), '?'), '/');
                    if (file_exists(public_path($relativePath))) $imageToPost = public_path($relativePath);
                } elseif (strpos($imageToPost, '/storage/') !== false) {
                    $parts = explode('/storage/', $imageToPost);
                    if (count($parts) > 1 && file_exists(storage_path('app/public/' . strtok($parts[1], '?')))) {
                        $imageToPost = storage_path('app/public/' . strtok($parts[1], '?'));
                    }
                }
            }
            
            $newsLink = $publishedUrl ?: $news->original_link;
            $captionToPost = ($this->customData['social_caption'] ?? $finalTitle) . (!empty($hashtags) ? "\n\n" . $hashtags : "");

            if ($settings->post_to_fb) {
                $fbResult = $socialPoster->postToFacebook($settings, $captionToPost, $imageToPost, $newsLink);
                $news->update(['fb_status' => $fbResult['success'] ? 'success' : 'failed', 'fb_error' => $fbResult['message'] ?? null]);
            }
            if ($settings->post_to_telegram) {
                $tgResult = $socialPoster->postToTelegram($settings, $captionToPost, $imageToPost, $newsLink);
                $news->update(['tg_status' => $tgResult['success'] ? 'success' : 'failed', 'tg_error' => $tgResult['message'] ?? null]);
            }

            if ($localCardPath && file_exists($localCardPath)) unlink($localCardPath);
            if (isset($this->customData['social_image']) && strpos($imageToPost, 'news-cards/studio') !== false && file_exists($imageToPost)) {
                unlink($imageToPost);
            }
        }

        try { $user->notify(new PostPublishedNotification($finalTitle)); } catch (\Exception $e) {}
    }
}