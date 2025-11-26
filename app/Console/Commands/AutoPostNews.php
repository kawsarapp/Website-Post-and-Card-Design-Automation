<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewsItem;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;

class AutoPostNews extends Command
{
    protected $signature = 'news:autopost';
    protected $description = 'Automatically post news to WordPress with random delay';

    // সার্ভিস ইনজেকশন
    private $scraper;
    private $aiWriter;
    private $wpService;

    // ক্যাটাগরি ম্যাপ (কন্ট্রোলার থেকে এখানেও লাগবে)
    private $wpCategories = [
        'Politics' => 14, 'International' => 37, 'Sports' => 15, 
        'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1, 
        'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1
    ];

    public function __construct(NewsScraperService $scraper, AIWriterService $aiWriter, WordPressService $wpService)
    {
        parent::__construct();
        $this->scraper = $scraper;
        $this->aiWriter = $aiWriter;
        $this->wpService = $wpService;
    }

    public function handle()
    {
        // ১. চেক করুন অটোমেশন অন আছে কিনা
        if (!Cache::get('auto_post_enabled', false)) {
            $this->info('Automation is OFF.');
            return;
        }

        // ২. চেক করুন সময় হয়েছে কিনা (Time Check)
        $nextRunTime = Cache::get('next_auto_post_time', now());
        
        if (now()->lessThan($nextRunTime)) {
            $this->info('Waiting for next slot: ' . $nextRunTime->format('h:i:s A'));
            return;
        }

        // ৩. পোস্ট করা হয়নি এমন একটি নিউজ খুঁজুন
        $news = NewsItem::where('is_posted', false)
            ->whereNotNull('thumbnail_url') // ইমেজ ছাড়া নিউজ বাদ
            ->orderBy('id', 'desc') // লেটেস্ট আগে
            ->first();

        if (!$news) {
            $this->info('No pending news found.');
            return;
        }
		
		
		if ($wpPost) {
                $news->update([
                    'rewritten_content' => $finalContent, 
                    'is_posted' => true, 
                    'wp_post_id' => $wpPost['id']
                ]);
                $this->info("✅ Posted Successfully! ID: " . $wpPost['id']);


                $interval = (int) Cache::get('auto_post_interval', 5); 

                $nextTime = now()->addMinutes($interval);
                Cache::put('next_auto_post_time', $nextTime);
                
                $this->info("Next post scheduled in {$interval} minutes at: " . $nextTime->format('h:i:s A'));

            } else {
                $this->error("WP Post Failed.");
            }

        $this->info("Processing ID: {$news->id} - {$news->title}");

        try {
            // --- STEP A: SCRAPE ---
            if (empty($news->content) || strlen($news->content) < 150) {
                $content = $this->scraper->scrape($news->original_link);
                if ($content) {
                    $news->update(['content' => $this->cleanUtf8($content)]);
                } else {
                    $this->error("Scraping failed.");
                    // ফেইল করলে এটাকে স্কিপ করার ব্যবস্থা (সাময়িক ফ্ল্যাগ বা লগ)
                    return;
                }
            }

            // --- STEP B: AI REWRITE ---
            $inputText = "HEADLINE: " . $news->title . "\n\nBODY:\n" . strip_tags($news->content);
            $cleanText = $this->cleanUtf8($inputText);
            
            $aiResponse = $this->aiWriter->rewrite($cleanText);
            
            if (!$aiResponse) {
                $rewrittenContent = $news->content; 
                $categoryId = $this->wpCategories['Others'];
            } else {
                $rewrittenContent = $aiResponse['content'];
                $detectedCategory = $aiResponse['category'];
                $categoryId = $this->wpCategories[$detectedCategory] ?? $this->wpCategories['Others'];
            }

            // --- STEP C: IMAGE UPLOAD ---
            $imageId = null;
            if ($news->thumbnail_url) {
                $upload = $this->wpService->uploadImage($news->thumbnail_url, $news->title);
                if ($upload['success']) {
                    $imageId = $upload['id'];
                } else {
                    $rewrittenContent = '<img src="' . $news->thumbnail_url . '" style="width:100%; margin-bottom:15px;"><br>' . $rewrittenContent;
                }
            }

            // --- STEP D: PUBLISH ---
            $credit = '<hr><p style="text-align:center; font-size:13px; color:#888;">তথ্যসূত্র: অনলাইন ডেস্ক</p>';
            $finalContent = $this->cleanUtf8($rewrittenContent . $credit);
            $finalTitle = $this->cleanUtf8($news->title);

            $wpPost = $this->wpService->publishPost($finalTitle, $finalContent, $categoryId, $imageId);

            if ($wpPost) {
                $news->update([
                    'rewritten_content' => $finalContent, 
                    'is_posted' => true, 
                    'wp_post_id' => $wpPost['id']
                ]);
                $this->info("✅ Posted Successfully! ID: " . $wpPost['id']);

                // ✅ ৪. পরবর্তী রান টাইম সেট করা (২ থেকে ৮ মিনিটের মধ্যে র‍্যান্ডম)
                $minutes = rand(2, 8);
                $nextTime = now()->addMinutes($minutes);
                Cache::put('next_auto_post_time', $nextTime);
                
                $this->info("Next post scheduled at: " . $nextTime->format('h:i:s A'));

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