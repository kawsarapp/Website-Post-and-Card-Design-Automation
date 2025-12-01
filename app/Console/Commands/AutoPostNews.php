<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewsItem;
use App\Models\UserSetting; // ✅ User Setting Model Import
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoPostNews extends Command
{
    protected $signature = 'news:autopost';
    protected $description = 'Automatically post news to WordPress with random delay';

    private $scraper;
    private $aiWriter;
    private $wpService;

    public function __construct(NewsScraperService $scraper, AIWriterService $aiWriter, WordPressService $wpService)
    {
        parent::__construct();
        $this->scraper = $scraper;
        $this->aiWriter = $aiWriter;
        $this->wpService = $wpService;
    }

    public function handle()
    {
        // ১. অটোমেশন চেক (যেকোনো একজন ইউজার যার অটোমেশন অন আছে)
        // এখানে লজিক একটু জটিল কারণ এটি মাল্টি-ইউজার। আপাতত আমরা এমন নিউজ খুঁজব যা পোস্ট হয়নি।
        
        $news = NewsItem::where('is_posted', false)
            ->whereNotNull('thumbnail_url')
            ->orderBy('id', 'desc')
            ->first();

        if (!$news) {
            $this->info('No pending news found.');
            return;
        }

        // ইউজারের অটোমেশন অন আছে কিনা চেক
        $userSettings = UserSetting::where('user_id', $news->user_id)->first();
        if (!$userSettings || !$userSettings->is_auto_posting) {
            $this->info("User automation is OFF for News ID: {$news->id}");
            return;
        }

        // টাইম চেক
        $lastPostTime = $userSettings->last_auto_post_at ? \Carbon\Carbon::parse($userSettings->last_auto_post_at) : now()->subHour();
        $interval = $userSettings->auto_post_interval ?? 10;
        
        if (now()->diffInMinutes($lastPostTime) < $interval) {
            $this->info("Waiting for interval... Next post in " . ($interval - now()->diffInMinutes($lastPostTime)) . " mins");
            return;
        }

        $this->info("Processing ID: {$news->id} - {$news->title}");

        try {
            // --- STEP A: SCRAPE (যদি কন্টেন্ট না থাকে) ---
            if (empty($news->content) || strlen($news->content) < 150) {
                
                $website = $news->website;
                
                // ড্যাশবোর্ডের সিলেক্টর পাঠানো
                $customSelectors = [
                    'container' => $website->selector_container ?? null,
                    'content'   => $website->selector_content ?? null
                ];
                
                $method = $website->scraper_method ?? 'node';

                // স্ক্র্যাপার কল
                $content = $this->scraper->scrape($news->original_link, $customSelectors, $method);
                
                if ($content) {
                    $news->update(['content' => $this->cleanUtf8($content)]);
                } else {
                    $this->error("Scraping failed.");
                    // ফেইলড হিসেবে মার্ক করা যেতে পারে যাতে বারবার চেষ্টা না করে
                    return;
                }
            }

            // --- STEP B: AI REWRITE ---
            // এখানে আমাদের আগের AI সার্ভিস ব্যবহার হবে (DeepSeek)
            $aiResponse = $this->aiWriter->rewrite($news->content, $news->title);
            
            $finalTitle = $aiResponse['title'] ?? $news->title;
            $finalContent = $aiResponse['content'] ?? $news->content;

            // --- STEP C: PUBLISH ---
            // এখানে WordPressService এর createPost মেথড কল করা হবে যা আমরা আগেই ঠিক করেছি
            $user = $news->user; // নিউজ আইটেমের মালিক
            
            $postResult = $this->wpService->createPost($news, $user, $finalTitle, $finalContent);

            if ($postResult['success']) {
                $news->update([
                    'is_posted' => true, 
                    'wp_post_id' => $postResult['post_id'],
                    'posted_at' => now()
                ]);
                
                // লাস্ট পোস্ট টাইম আপডেট
                $userSettings->update(['last_auto_post_at' => now()]);
                
                $this->info("✅ Posted Successfully! ID: " . $postResult['post_id']);

            } else {
                $this->error("WP Post Failed.");
            }

        } catch (\Exception $e) {
            Log::error("Auto Post Error: " . $e->getMessage());
            $this->error($e->getMessage());
        }
    }

    private function cleanUtf8($string) {
        if (is_string($string)) return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return $string;
    }
}