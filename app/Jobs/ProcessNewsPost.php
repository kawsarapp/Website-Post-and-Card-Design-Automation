<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Models\User;
use App\Models\CreditHistory;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessNewsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsId;
    protected $userId;

    // Timeout: 10 minutes (AI delay handle করার জন্য)
    public $timeout = 600;
    public $tries = 1; // Retry করবে না (ডুপ্লিকেট এড়াতে)

    public function __construct($newsId, $userId)
    {
        $this->newsId = $newsId;
        $this->userId = $userId;
    }

    public function handle(
        NewsScraperService $scraper, 
        AIWriterService $aiWriter, 
        WordPressService $wpService, 
        TelegramService $telegram
    ) {
        Log::info("🚀 Job Started for News ID: {$this->newsId} | User ID: {$this->userId}");

        // ✅ ১. ডুপ্লিকেট চেক (Lock Mechanism)
        // একই নিউজ যদি প্রসেসিং এ থাকে, তবে দ্বিতীয়বার রান করবে না
        $lockKey = "processing_news_{$this->newsId}";
        if (!Cache::add($lockKey, true, 300)) { // ৫ মিনিটের জন্য লক
            Log::warning("⚠️ News ID {$this->newsId} is already being processed. Skipping.");
            return;
        }

        try {
            // ২. ডাটা লোড (Global Scope Bypass)
            $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
            $user = User::find($this->userId);
            
            if (!$news || !$user) {
                Log::error("❌ Job Failed: News or User not found.");
                return;
            }

            // যদি ইতিমধ্যে পোস্ট হয়ে গিয়ে থাকে
            if ($news->is_posted) {
                Log::info("ℹ️ News ID {$this->newsId} is already posted. Skipping.");
                return;
            }
            
            $settings = $user->settings;
            if (!$settings) {
                Log::error("❌ Job Failed: User settings not found.");
                return;
            }

            // ৩. স্ক্র্যাপ কন্টেন্ট (যদি না থাকে)
            if (empty($news->content) || strlen($news->content) < 150) {
                Log::info("⏳ Content missing/short, scraping original link...");
                $content = $scraper->scrape($news->original_link);
                if ($content) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                    $news->update(['content' => $content]);
                    Log::info("✅ Scrape Successful.");
                } else {
                    Log::error("❌ Job Failed: Content not found/scrape failed for News ID {$news->id}");
                    return;
                }
            }

            // ৪. AI রিরাইট
            Log::info("🤖 Starting AI Rewrite...");
            $inputText = "HEADLINE: " . $news->title . "\n\nBODY:\n" . strip_tags($news->content);
            $cleanText = mb_convert_encoding($inputText, 'UTF-8', 'UTF-8');
            
            $aiResponse = $aiWriter->rewrite($cleanText);

            $rewrittenContent = $news->content;
            $categoryId = 1; 

            // Default WP Categories
            $wpCategories = [
                'Politics' => 14, 'International' => 37, 'Sports' => 15,
                'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1,
                'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1
            ];

            if ($aiResponse) {
                Log::info("✅ AI Response Received.");
                
                $rewrittenContent = $aiResponse['content'];
                $detectedCategory = $aiResponse['category'] ?? 'Others';
                
                // Dynamic Mapping
                $userMapping = $settings->category_mapping ?? [];
                
                if (isset($userMapping[$detectedCategory]) && !empty($userMapping[$detectedCategory])) {
                    $categoryId = $userMapping[$detectedCategory];
                } elseif (isset($userMapping['Others']) && !empty($userMapping['Others'])) {
                    $categoryId = $userMapping['Others'];
                } else {
                    $categoryId = $wpCategories[$detectedCategory] ?? 1;
                }
                
                Log::info("📂 Category Selected: {$detectedCategory} -> ID: {$categoryId}");

                // Credit & Limit Check
                if ($user->role !== 'super_admin') {
                    if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                        Log::warning("⛔ Daily limit reached for user {$user->id}. Stopping Job.");
                        return;
                    }
                    if ($user->credits <= 0) {
                        Log::warning("⛔ Insufficient credits for user {$user->id}. Stopping Job.");
                        return;
                    }

                    $user->decrement('credits');
                    
                    CreditHistory::create([
                        'user_id' => $user->id,
                        'action_type' => 'manual_post', 
                        'description' => 'Post: ' . Str::limit($news->title, 40),
                        'credits_change' => -1,
                        'balance_after' => $user->credits
                    ]);
                    
                    Log::info("💰 Credit Deducted. New Balance: {$user->credits}");
                }
            } else {
                Log::warning("⚠️ AI Rewrite returned null. Using original content.");
            }

            // ৫. ইমেজ আপলোড
            $imageId = null;
            // Fallback Image Logic (যদি ইমেজ না থাকে)
            if ($news->thumbnail_url) {
                Log::info("🖼️ Uploading Image...");
                $upload = $wpService->uploadImage(
                    $news->thumbnail_url, 
                    $news->title,
                    $settings->wp_url,
                    $settings->wp_username,
                    $settings->wp_app_password
                );

                if ($upload && $upload['success']) {
                    $imageId = $upload['id'];
                    Log::info("✅ Image Uploaded. ID: {$imageId}");
                } else {
                    Log::warning("⚠️ Image Upload Failed. Embedding in content.");
                    $rewrittenContent = '<img src="' . $news->thumbnail_url . '" style="width:100%; margin-bottom:15px;"><br>' . $rewrittenContent;
                }
            } else {
                Log::warning("⚠️ No Thumbnail found for News ID {$news->id}");
            }

            // ৬. পোস্ট পাবলিশ
            Log::info("🚀 Publishing to WordPress...");
            
            $credit = '<hr><p style="text-align:center; font-size:13px; color:#888;">তথ্যসূত্র: অনলাইন ডেস্ক</p>';
            $finalContent = $rewrittenContent . $credit;
            
            $wpPost = $wpService->publishPost(
                $news->title, 
                $finalContent, 
                $settings->wp_url,
                $settings->wp_username,
                $settings->wp_app_password,
                $categoryId,
                $imageId
            );

            if ($wpPost) {
                $news->update([
                    'rewritten_content' => $finalContent,
                    'is_posted' => true,
                    'wp_post_id' => $wpPost['id']
                ]);

                if ($settings->telegram_channel_id) {
                    $telegram->sendToChannel($settings->telegram_channel_id, $news->title, $wpPost['link']);
                    Log::info("📱 Sent to Telegram.");
                }
                
                Log::info("✅ Job Success! Post ID: {$wpPost['id']}");
            } else {
                Log::error("❌ WP Post Failed (API Error).");
                // Optional Refund Logic
                 if ($user->role !== 'super_admin') {
                    $user->increment('credits');
                    CreditHistory::latest()->where('user_id', $user->id)->first()->delete();
                    Log::info("🔄 Credit Refunded due to failure.");
                 }
            }

        } catch (\Exception $e) {
            Log::error("❌ Job Exception News ID {$this->newsId}: " . $e->getMessage());
        } finally {
            // কাজ শেষ হলে বা এরর হলে লক রিলিজ করা
            Cache::forget($lockKey);
        }
    }
}