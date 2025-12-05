<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Models\User;
use App\Services\WordPressService;
use App\Notifications\PostPublishedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessNewsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsId;
    protected $userId;
    protected $customData;

    // 🔥 রিট্রাই কনফিগারেশন
    public $tries = 3; 
    public $backoff = 60; 

    public function __construct($newsId, $userId, $customData = [])
    {
        $this->newsId = $newsId;
        $this->userId = $userId;
        $this->customData = $customData;
    }

    public function handle(WordPressService $wpService)
{
    try {
        Log::info("🚀 Publishing Job Started for News ID: {$this->newsId}");

        // 🔥 ১. Global Scope বাইপাস করা (জরুরি)
        $news = NewsItem::withoutGlobalScopes()
            ->with(['website' => function ($query) {
                $query->withoutGlobalScopes(); 
            }])->find($this->newsId);

        $user = User::find($this->userId);

        if (!$news || !$user) {
            Log::error("Job Failed: News or User not found. ID: {$this->newsId}");
            return;
        }

        // সেটিংস লোড করা
        $settings = $user->settings;

        // প্রায়োরিটি লজিক (Custom > AI > Original)
        $finalTitle = $this->customData['title'] ?? $news->ai_title ?? $news->title;
        $finalContent = $this->customData['content'] ?? $news->ai_content ?? $news->content;
        $finalImage = $this->customData['featured_image'] ?? $news->thumbnail_url;
        $categoryId = $this->customData['category_id'] ?? null;

        // 🔥 ৩. '/og/' ফোল্ডার রিমুভ করার লজিক
        if (!empty($finalImage) && strpos($finalImage, '/og/') !== false) {
            $finalImage = str_replace('/og/', '/', $finalImage);
            Log::info("✅ Image URL Cleaned: " . $finalImage);
        }

        // স্ট্যাটাস ট্র্যাকার
        $wpSuccess = false;
        $laravelSuccess = false;
        $wpPostId = null;

        // ==========================
        // 🌍 1. WORDPRESS POSTING
        // ==========================
        if ($settings && $settings->wp_url && $settings->wp_username) {

            $postResult = $wpService->createPost(
                $news,
                $user,
                $finalTitle,
                $finalContent,
                $categoryId,
                $finalImage
            );

            if ($postResult['success']) {
                $wpSuccess = true;
                $wpPostId = $postResult['post_id'];
                Log::info("✅ WP Post Success: ID {$wpPostId}");
            } else {
                Log::error("❌ WP Post Failed: " . ($postResult['message'] ?? 'Unknown'));
            }
        }

        // ==========================
        // 🚀 2. LARAVEL POSTING
        // ==========================
        if ($settings && $settings->post_to_laravel && $settings->laravel_site_url) {
            try {
                $apiUrl = rtrim($settings->laravel_site_url, '/') . '/api/external-news-post';

                $response = \Illuminate\Support\Facades\Http::post($apiUrl, [
                    'token' => $settings->laravel_api_token,
                    'title' => $finalTitle,
                    'content' => $finalContent,
                    'image_url' => $finalImage,
                    'category_name' => $news->category ?? 'General',
                    'original_link' => $news->original_link
                ]);

                if ($response->successful()) {
                    $laravelSuccess = true;
                    Log::info("✅ Laravel Post Success: " . $response->body());
                } else {
                    Log::error("❌ Laravel Post Failed: " . $response->status() . ' - ' . $response->body());
                }

            } catch (\Exception $e) {
                Log::error("❌ Laravel Connection Error: " . $e->getMessage());
            }
        }

        // ==========================
        // 🏁 3. FINAL UPDATE
        // ==========================
        if ($wpSuccess || $laravelSuccess) {

            DB::transaction(function () use ($news, $user, $wpPostId, $finalImage) {

                $news->update([
                    'is_posted' => true,
                    'wp_post_id' => $wpPostId,
                    'posted_at' => now(),
                    'status' => 'published',
                    'thumbnail_url' => $finalImage
                ]);

                if ($user->role !== 'super_admin') {
                    $user->decrement('credits');
                    Log::info("✅ Credit deducted for User ID: {$user->id}");
                }
            });

            try {
                $user->notify(new PostPublishedNotification($finalTitle));
            } catch (\Exception $e) {
                Log::error("Notification Error: " . $e->getMessage());
            }

        } else {
            throw new \Exception("Posting failed on both WordPress and Laravel endpoints.");
        }

    } catch (\Exception $e) {
        Log::error("ProcessNewsPost Job Exception: " . $e->getMessage());
        $this->fail($e);
    }
}


    /**
     * জব ফেইল হলে (৩ বার চেষ্টার পর)
     */
    public function failed(\Throwable $exception)
    {
        // এখানেও withoutGlobalScopes() লাগবে, নাহলে নিউজ খুঁজে পাবে না
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        
        if ($news) {
            $news->update(['status' => 'failed']);
            Log::error("❌ Job Final Failure for News ID: {$this->newsId}");
        }
    }
}