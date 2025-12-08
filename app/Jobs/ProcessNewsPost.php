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
            
            // 🔥 ক্যাটাগরি ভেরিয়েবল সেটআপ (সঠিক নাম: $categories)
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

            // ১. ওয়ার্ডপ্রেস পোস্টিং (WordPress Posting)
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
                    Log::error("❌ WP Post Failed: " . ($postResult['message'] ?? 'Unknown'));
                }
            }

            // ২. লারাভেল API পোস্টিং (Laravel API Posting)
            // এটি তখনই চলবে যদি সেটিংসে post_to_laravel = 1 থাকে
            if ($settings && $settings->post_to_laravel && $settings->laravel_site_url) {
                try {
                    $apiUrl = rtrim($settings->laravel_site_url, '/') . '/api/external-news-post';
                    
                    $response = Http::post($apiUrl, [
                        'token' => $settings->laravel_api_token,
                        'title' => $finalTitle,
                        'content' => $finalContent,
                        'image_url' => $finalImage,
                        'category_name' => $news->category ?? 'General',
                        // 🔥🔥 FIXED: এখানে $categories ব্যবহার করা হয়েছে (আগে ভুল ছিল)
                        'category_ids' => $categories, 
                        'original_link' => $news->original_link
                    ]);

                    if ($response->successful()) {
                        $laravelSuccess = true;
                        Log::info("✅ Laravel Post Success.");
                    } else {
                        // লারাভেল ফেইল করলেও যাতে জব বন্ধ না হয়, তাই শুধু লগ রাখা হলো
                        Log::error("❌ Laravel Post Failed: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Laravel Connection Error: " . $e->getMessage());
                }
            }

            // ৩. ফাইনাল আপডেট (যেকোনো একটা সফল হলেই হবে)
            if ($wpSuccess || $laravelSuccess) {

                DB::transaction(function () use ($news, $user, $wpPostId, $finalImage) {

                    $news->update([
                        'is_posted' => true,
                        'wp_post_id' => $wpPostId,
                        'posted_at' => now(),
                        'status' => 'published',
                        'thumbnail_url' => $finalImage
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
                            
                            Log::info("✅ Credit deducted via Job for User ID: {$user->id}");
                        }
                    }
                });

                try {
                    $user->notify(new PostPublishedNotification($finalTitle));
                } catch (\Exception $e) {}

            } else {
                // যদি দুটোই ফেইল করে বা কনফিগার করা না থাকে
                if (!$settings->wp_url && !$settings->post_to_laravel) {
                    Log::warning("⚠️ No destination configured (WP or Laravel). Job ending.");
                } else {
                    throw new \Exception("Posting failed on configured endpoints.");
                }
            }

        } catch (\Exception $e) {
            Log::error("ProcessNewsPost Job Exception: " . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        if ($news) {
            $news->update(['status' => 'failed']);
            Log::error("❌ Job Final Failure for News ID: {$this->newsId}");
        }
    }
}