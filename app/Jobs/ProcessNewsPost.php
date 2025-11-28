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

class ProcessNewsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsId;
    protected $userId;

    // টাইমআউট ৫ মিনিট (যাতে AI দেরি করলেও সমস্যা না হয়)
    public $timeout = 300;

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
        // ১. ডাটা লোড (Global Scope ছাড়া)
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        $user = User::find($this->userId);
        
        if (!$news || !$user) return;
        
        $settings = $user->settings;
        if (!$settings) return;

        try {
            // ২. স্ক্র্যাপ (যদি কন্টেন্ট না থাকে)
            if (empty($news->content) || strlen($news->content) < 150) {
                $content = $scraper->scrape($news->original_link);
                if ($content) {
                    $news->update(['content' => mb_convert_encoding($content, 'UTF-8', 'UTF-8')]);
                } else {
                    Log::error("Job Failed: Content not found for News ID {$news->id}");
                    return;
                }
            }

            // ৩. AI রিরাইট
            $inputText = "HEADLINE: " . $news->title . "\n\nBODY:\n" . strip_tags($news->content);
            $cleanText = mb_convert_encoding($inputText, 'UTF-8', 'UTF-8');
            $aiResponse = $aiWriter->rewrite($cleanText);

            $rewrittenContent = $news->content;
            $categoryId = 1;

            // ক্যাটাগরি ম্যাপিং
            $wpCategories = [
                'Politics' => 14, 'International' => 37, 'Sports' => 15,
                'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1,
                'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1
            ];

            if ($aiResponse) {
                $rewrittenContent = $aiResponse['content'];
                $detectedCategory = $aiResponse['category'] ?? 'Others';
                $categoryId = $wpCategories[$detectedCategory] ?? 1;

                // ক্রেডিট এবং লিমিট চেক ও ডিডাকশন
                if ($user->role !== 'super_admin') {
                    // ডেইলি লিমিট চেক
                    if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                        Log::info("Daily limit reached for user {$user->id} inside Job");
                        return;
                    }
                    
                    // ব্যালেন্স চেক
                    if ($user->credits <= 0) return;

                    $user->decrement('credits');
                    
                    CreditHistory::create([
                        'user_id' => $user->id,
                        'action_type' => 'manual_post',
                        'description' => 'Post: ' . Str::limit($news->title, 40),
                        'credits_change' => -1,
                        'balance_after' => $user->credits
                    ]);
                }
            }

            // ৪. ইমেজ আপলোড
            $imageId = null;
            if ($news->thumbnail_url) {
                $upload = $wpService->uploadImage(
                    $news->thumbnail_url, 
                    $news->title,
                    $settings->wp_url,
                    $settings->wp_username,
                    $settings->wp_app_password
                );

                if ($upload && $upload['success']) {
                    $imageId = $upload['id'];
                } else {
                    $rewrittenContent = '<img src="' . $news->thumbnail_url . '" style="width:100%; margin-bottom:15px;"><br>' . $rewrittenContent;
                }
            }

            // ৫. পোস্ট পাবলিশ
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
                }
                
                Log::info("Job Success: Post ID {$wpPost['id']} for User {$user->id}");
            }

        } catch (\Exception $e) {
            Log::error("Job Error News ID {$news->id}: " . $e->getMessage());
        }
    }
}