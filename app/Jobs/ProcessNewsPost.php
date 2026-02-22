<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Models\User;
use App\Services\WordPressService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\SocialPostService;
use App\Services\NewsCardGeneratorService;

// 🔥 Traits Import
use App\Traits\WordPressPostingTrait;
use App\Traits\ApiPostingTrait;
use App\Traits\SocialAndFinalizeTrait;

class ProcessNewsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    // 🔥 Traits Use
    use WordPressPostingTrait, ApiPostingTrait, SocialAndFinalizeTrait;

    protected $newsId;
    protected $userId;
    protected $customData;
    protected $skipCreditDeduction;

    public $tries = 3; 
    public $backoff = 60; 

    public function __construct($newsId, $userId, $customData = [], $skipCreditDeduction = false)
    {
        $this->newsId = $newsId;
        $this->userId = $userId;
        $this->customData = $customData;
        $this->skipCreditDeduction = $skipCreditDeduction;
    }

    public function handle(WordPressService $wpService, SocialPostService $socialPoster, NewsCardGeneratorService $cardGenerator) 
    {
        try {
            Log::info("🚀 Publishing/Updating Job Started for News ID: {$this->newsId}");

            $news = NewsItem::withoutGlobalScopes()->with(['website' => function ($q) { $q->withoutGlobalScopes(); }])->find($this->newsId);
            $user = User::find($this->userId);

            if (!$news || !$user) {
                Log::error("Job Failed: News or User not found. ID: {$this->newsId}");
                return;
            }

            $settings = $user->settings;

            // --- Data Preparation ---
            $finalTitle   = $this->customData['title'] ?? $news->ai_title ?? $news->title;
            $finalContent = $this->customData['content'] ?? $news->ai_content ?? $news->content;
            $websiteImage = $this->customData['website_image'] ?? $this->customData['featured_image'] ?? $news->thumbnail_url;
            $socialImage  = $this->customData['social_image'] ?? $websiteImage;
            $hashtags     = $this->customData['hashtags'] ?? $news->hashtags ?? '';
            $socialOnly   = $this->customData['social_only'] ?? false;
            $skipSocial   = $this->customData['skip_social'] ?? false;
            
            if ($socialOnly) Log::info("🚀 Social Only Mode Activated.");
            if ($skipSocial) Log::info("⏭️ Skipping Social Posting for now.");

            $categories = $this->customData['category_ids'] ?? (isset($this->customData['category_id']) ? [$this->customData['category_id']] : [1]);
            if (!is_array($categories)) $categories = [$categories];
            $categories = array_values(array_filter(array_unique(array_map('intval', $categories))));
            if (empty($categories)) $categories = [1];
            
            if (!empty($websiteImage) && strpos($websiteImage, '/og/') !== false) $websiteImage = str_replace('/og/', '/', $websiteImage);

            $wpSuccess = false;
            $laravelSuccess = false;
            $remotePostId = $news->wp_post_id; 
            $publishedUrl = $news->live_url; 

            // --- 1. WordPress Posting ---
            if (!$socialOnly && $settings && $settings->wp_url && $settings->wp_username) {
                $wpResult = $this->executeWordPressPost($wpService, $news, $user, $settings, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags, $publishedUrl);
                $wpSuccess = $wpResult['success'];
                $remotePostId = $wpResult['remote_id'];
                $publishedUrl = $wpResult['published_url'];
            }

            // --- 2. API Posting ---
            if (!$socialOnly && $settings && ($settings->post_to_laravel || !empty($settings->custom_api_url)) && $settings->laravel_site_url) {
                $apiResult = $this->executeApiPost($news, $settings, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags, $remotePostId, $publishedUrl);
                $laravelSuccess = $apiResult['success'];
                $remotePostId = $apiResult['remote_id'];
                $publishedUrl = $apiResult['published_url'];
            }

            // 🔥 NEW: Update Database Immediately for Reporters
            if ($wpSuccess || $laravelSuccess) {
                $news->update([
                    'is_posted'  => 1,
                    'live_url'   => $publishedUrl,
                    'wp_post_id' => $remotePostId,
                    'status'     => 'published',
                    'error_message' => null
                ]);
            }

            // --- 3. Finalize & Social ---
            $this->executeFinalization($news, $user, $settings, $wpSuccess, $laravelSuccess, $socialOnly, $skipSocial, $remotePostId, $publishedUrl, $websiteImage, $socialImage, $hashtags, $finalTitle, $socialPoster, $cardGenerator);

        } catch (\Exception $e) {
            Log::error("ProcessNewsPost Job Exception: " . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        if ($news) {
            $news->update(['status' => 'failed', 'error_message' => 'Action Error: ' . $exception->getMessage()]);
        }
    }
}