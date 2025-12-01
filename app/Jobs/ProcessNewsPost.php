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
            // যেহেতু Queue Worker এর সময় কোনো User লগইন থাকে না, তাই withoutGlobalScopes() দিতেই হবে
            $news = NewsItem::withoutGlobalScopes()
                ->with(['website' => function ($query) {
                    $query->withoutGlobalScopes(); 
                }])->find($this->newsId);

            $user = User::find($this->userId);

            if (!$news || !$user) {
                Log::error("Job Failed: News or User not found. ID: {$this->newsId}");
                return;
            }

            // প্রায়োরিটি লজিক (Custom > AI > Original)
            $finalTitle = $this->customData['title'] ?? $news->ai_title ?? $news->title;
            $finalContent = $this->customData['content'] ?? $news->ai_content ?? $news->content;
            
            // 🔥 ২. ইমেজ সিলেকশন (আপনার মডেলে thumbnail_url আছে, তাই সেটি ব্যবহার করছি)
            $finalImage = $this->customData['featured_image'] ?? $news->thumbnail_url; 

            // 🔥 ৩. '/og/' ফোল্ডার রিমুভ করার লজিক (Kaler Kantho fix)
            // এটি চেক করবে লিংকে '/og/' আছে কিনা, থাকলে রিমুভ করে দিবে
            if (!empty($finalImage) && strpos($finalImage, '/og/') !== false) {
                $finalImage = str_replace('/og/', '/', $finalImage);
                Log::info("✅ Image URL Cleaned: " . $finalImage);
            }
            
            $categoryId = $this->customData['category_id'] ?? null;

            // ৪. ওয়ার্ডপ্রেসে পোস্ট করা (ক্লিন ইমেজ সহ)
            $postResult = $wpService->createPost($news, $user, $finalTitle, $finalContent, $categoryId, $finalImage);

            if ($postResult['success']) {
                
                // ৫. ডাটাবেস ট্রানজেকশন (নিরাপদ আপডেট)
                DB::transaction(function () use ($news, $user, $postResult, $finalImage) {
                    
                    // নিউজ স্ট্যাটাস এবং ক্লিন ইমেজ আপডেট
                    $news->update([
                        'is_posted' => true,
                        'wp_post_id' => $postResult['post_id'],
                        'posted_at' => now(),
                        'status' => 'published',
                        'thumbnail_url' => $finalImage // 🔥 ক্লিন করা ইমেজটি ডাটাবেসে সেভ করে দিচ্ছি
                    ]);

                    // ৬. ক্রেডিট কাটা (যদি সুপার এডমিন না হয়)
                    if ($user->role !== 'super_admin') {
                        $user->decrement('credits');
                        Log::info("✅ Credit deducted for User ID: {$user->id}");
                    }
                });

                Log::info("✅ Post Published Successfully (WP ID: {$postResult['post_id']})");

                // ৭. নোটিফিকেশন পাঠানো
                try {
                    $user->notify(new PostPublishedNotification($finalTitle));
                } catch (\Exception $e) {
                    Log::error("Notification Error: " . $e->getMessage());
                }

            } else {
                // WP ফেইল করলে
                Log::error("WP Post Failed for News ID {$news->id}: " . json_encode($postResult));
                throw new \Exception("WordPress Posting Failed: " . ($postResult['message'] ?? 'Unknown Error'));
            }

        } catch (\Exception $e) {
            Log::error("ProcessNewsPost Job Exception: " . $e->getMessage());
            throw $e; 
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