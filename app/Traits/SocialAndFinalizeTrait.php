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

        // 🔥 নতুন লজিক: চেক করা হচ্ছে পোস্টটি স্টুডিও (Design) থেকে এসেছে কি না
        $isFromStudio = isset($this->customData['social_image']);

        // 🟢 শুধুমাত্র স্টুডিও থেকে আসলে সোশ্যাল মিডিয়ায় পোস্ট হবে
        if (!$skipSocial && $isFromStudio && ($settings->post_to_fb || $settings->post_to_telegram)) {
            
            $imageToPost = $socialImage; 
            Log::info("✨ Studio Post Detected. Sending Design to Social Media.");
            
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
            
            $newsLink = $publishedUrl ?: $news->original_link;
            $captionToPost = ($this->customData['social_caption'] ?? $finalTitle) . (!empty($hashtags) ? "\n\n" . $hashtags : "");

            // ফেসবুকে পোস্ট
            if ($settings->post_to_fb) {
                $fbResult = $socialPoster->postToFacebook($settings, $captionToPost, $imageToPost, $newsLink);
                $news->update(['fb_status' => $fbResult['success'] ? 'success' : 'failed', 'fb_error' => $fbResult['message'] ?? null]);
            }
            // টেলিগ্রামে পোস্ট
            if ($settings->post_to_telegram) {
                $tgResult = $socialPoster->postToTelegram($settings, $captionToPost, $imageToPost, $newsLink);
                $news->update(['tg_status' => $tgResult['success'] ? 'success' : 'failed', 'tg_error' => $tgResult['message'] ?? null]);
            }

            // স্টুডিওর টেম্পরারি ইমেজ ডিলিট করে দেওয়া
            if (strpos($imageToPost, 'news-cards/studio') !== false && file_exists($imageToPost)) {
                unlink($imageToPost);
            }
        } else {
            // 🔴 যদি স্টুডিও থেকে না আসে, তবে লগে মেসেজ দিয়ে সোশ্যাল মিডিয়া স্কিপ করবে
            if (!$isFromStudio) {
                Log::info("⏭️ Regular News Post. Skipping Social Media completely.");
            }
        }

        try { $user->notify(new PostPublishedNotification($finalTitle)); } catch (\Exception $e) {}
    }
}