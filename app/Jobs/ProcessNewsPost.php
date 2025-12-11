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
use Illuminate\Support\Facades\Http; 

class ProcessNewsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

    public function handle(WordPressService $wpService)
    {
        try {
            Log::info("🚀 Publishing Job Started for News ID: {$this->newsId}");

            $news = NewsItem::withoutGlobalScopes()
                ->with(['website' => function ($query) {
                    $query->withoutGlobalScopes(); 
                }])->find($this->newsId);

            $user = User::find($this->userId);

            if (!$news || !$user) {
                Log::error("Job Failed: News or User not found. ID: {$this->newsId}");
                return;
            }

            $settings = $user->settings;

            // ডাটা প্রিপারেশন
            $finalTitle = $this->customData['title'] ?? $news->ai_title ?? $news->title;
            $finalContent = $this->customData['content'] ?? $news->ai_content ?? $news->content;
            $finalImage = $this->customData['featured_image'] ?? $news->thumbnail_url;
            
            // ক্যাটাগরি সেটআপ
            $categories = $this->customData['category_ids'] ?? [];
            
            if (empty($categories) && isset($this->customData['category_id'])) {
                $categories = [$this->customData['category_id']];
            }
            
            if (empty($categories)) {
                $categories = [1];
            }

            // OG ইমেজ ক্লিনআপ
            if (!empty($finalImage) && strpos($finalImage, '/og/') !== false) {
                $finalImage = str_replace('/og/', '/', $finalImage);
            }

            $wpSuccess = false;
            $laravelSuccess = false;
            $wpPostId = null;
			

            // ১. ওয়ার্ডপ্রেস পোস্টিং
            if ($settings && $settings->wp_url && $settings->wp_username) {
                
                $postResult = $wpService->createPost(
                    $news, 
                    $user, 
                    $finalTitle, 
                    $finalContent, 
                    $categories, 
                    $finalImage
                );

                if ($postResult['success']) {
                    $wpSuccess = true;
                    $wpPostId = $postResult['post_id'];
                    Log::info("✅ WP Post Success: ID {$wpPostId}");
                } else {
                    // ওয়ার্ডপ্রেসের স্পেসিফিক এরর লগ করা
                    $errorMsg = $postResult['message'] ?? 'Unknown WP Error';
                    Log::error("❌ WP Post Failed: " . $errorMsg);
                    // যদি লারাভেল পোস্টিং অফ থাকে, তবে এখনই এক্সেপশন থ্রো করা যাতে failed() মেথড কল হয়
                    if (!$settings->post_to_laravel) {
                        throw new \Exception("WP Failed: " . $errorMsg);
                    }
                }
            }

            // ২. লারাভেল API পোস্টিং
			
			Log::info("🔍 Checking Laravel Settings:", [
                'toggle_status' => $settings->post_to_laravel,
                'url' => $settings->laravel_site_url,
                'token_exists' => !empty($settings->laravel_api_token)
            ]);
			
            if ($settings && $settings->post_to_laravel && $settings->laravel_site_url) {
                try {
                    $apiUrl = rtrim($settings->laravel_site_url, '/') . '/api/external-news-post';
                    
                    $response = Http::post($apiUrl, [
                        'token' => $settings->laravel_api_token,
                        'title' => $finalTitle,
                        'content' => $finalContent,
                        'image_url' => $finalImage,
                        'category_name' => $news->category ?? 'General',
                        'category_ids' => $categories, 
                        'original_link' => $news->original_link
                    ]);

                    if ($response->successful()) {
                        $laravelSuccess = true;
                        Log::info("✅ Laravel Post Success.");
                    } else {
                        Log::error("❌ Laravel Post Failed: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Laravel Connection Error: " . $e->getMessage());
                }
            }

            // ৩. ফাইনাল আপডেট
            if ($wpSuccess || $laravelSuccess) {

                DB::transaction(function () use ($news, $user, $wpPostId, $finalImage) {

                    $news->update([
                        'is_posted' => true,
                        'wp_post_id' => $wpPostId,
                        'posted_at' => now(),
                        'status' => 'published',
                        'thumbnail_url' => $finalImage,
                        'error_message' => null // সফল হলে এরর মেসেজ ক্লিন করা
                    ]);

                    if (!$this->skipCreditDeduction && $user->role !== 'super_admin') {
                        if ($user->credits > 0) {
                            $user->decrement('credits');
                            \App\Models\CreditHistory::create([
                                'user_id' => $user->id,
                                'action_type' => 'auto_post',
                                'description' => 'Auto Published via Job',
                                'credits_change' => -1,
                                'balance_after' => $user->credits
                            ]);
                        }
                    }
                });

                try {
                    $user->notify(new PostPublishedNotification($finalTitle));
                } catch (\Exception $e) {}

            } else {
                if (!$settings->wp_url && !$settings->post_to_laravel) {
                    throw new \Exception("Settings Error: No WP or Laravel destination configured.");
                } else {
                    throw new \Exception("Posting failed on all configured endpoints.");
                }
            }

        } catch (\Exception $e) {
            Log::error("ProcessNewsPost Job Exception: " . $e->getMessage());
            $this->fail($e);
        }
    }

    // 🔥 গুরুত্বপূর্ণ আপডেট: ডাটাবেসে এরর সেভ করা
    public function failed(\Throwable $exception)
    {
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        if ($news) {
            $news->update([
                'status' => 'failed',
                'error_message' => 'Publish Error: ' . $exception->getMessage() // ইউজারকে দেখানোর জন্য
            ]);
            Log::error("❌ Job Final Failure for News ID: {$this->newsId}. Error saved to DB.");
        }
    }
}