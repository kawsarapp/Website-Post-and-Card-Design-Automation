<?php

namespace App\Jobs;

use App\Models\Website;
use App\Models\NewsItem;
use App\Services\NewsScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\User;
use App\Notifications\NewsScrapedNotification;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; 
use Illuminate\Support\Facades\Storage;

class ScrapeWebsite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $websiteId;
    protected $userId;
    public $timeout = 600; 

    public function __construct($websiteId, $userId)
    {
        $this->websiteId = $websiteId;
        $this->userId = $userId;
    }

    public function handle(NewsScraperService $scraper)
    {
        \Illuminate\Support\Facades\Cache::put('scraping_user_' . $this->userId, true, now()->addMinutes(5));

        try {
            $realId = is_array($this->websiteId) ? ($this->websiteId['id'] ?? null) : $this->websiteId;
            $website = Website::withoutGlobalScopes()->find($realId);

            if (!$website) return;

            Log::info("🚀 JOB STARTED: {$website->name} | URL: {$website->url}");

            // ১. প্রক্সি লোড করা
            $proxy = $scraper->getProxyConfig($this->userId);
            if ($proxy) Log::info("🌐 Scraping with Proxy: " . parse_url($proxy, PHP_URL_HOST));

            // ২. লিস্ট পেজ লোড (Raw HTML) - ফিক্সড (Try-Catch যুক্ত করা হয়েছে)
            $listPageHtml = null;
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])->withOptions([
                    'proxy' => $proxy,
                    'verify' => false,
                    'connect_timeout' => 20,
                ])->timeout(60)->get($website->url);

                if ($response->successful()) {
                    $listPageHtml = $response->body();
                }
            } catch (\Exception $e) {
                // কানেকশন বা SSL এরর হলে লগ করবে, কিন্তু থামবে না
                Log::warning("⚠️ Direct HTTP Failed (Will try Puppeteer): " . $e->getMessage());
            }

            // যদি সরাসরি না আসে, তবে Puppeteer ব্যবহার হবে
            if (!$listPageHtml || strlen($listPageHtml) < 500) {
                Log::info("🔄 Falling back to Puppeteer with Proxy...");
                $listPageHtml = $scraper->runPuppeteer($website->url, $this->userId); 
            }

            if (!$listPageHtml || strlen($listPageHtml) < 500) {
                Log::error("❌ Failed to load list page content.");
                return;
            }

            $crawler = new Crawler($listPageHtml);

            // ==========================================
            // 🔥 SMART SELECTOR STRATEGY LOOP
            // ==========================================
            
            $strategies = [];

            // ১. ড্যাশবোর্ড সিলেক্টর
            if (!empty($website->selector_container)) {
                $strategies[] = [
                    'source'    => 'DASHBOARD',
                    'container' => $website->selector_container,
                    'title'     => $website->selector_title
                ];
            }

            // ২. কোড কনফিগ
            $codeConfig = $this->getDomainConfig($website->url);
            if ($codeConfig) {
                $strategies[] = [
                    'source'    => 'CODE (HARDCODED)',
                    'container' => $codeConfig['container'],
                    'title'     => $codeConfig['title']
                ];
            }

            // ৩. জেনেরিক স্মার্ট সিলেক্টর
            $strategies[] = [
                'source'    => 'GENERIC (SMART)',
                'container' => 'article a, .post a, .news a, h2 a, h3 a', 
                'title'     => null
            ];

            $activeContainer = null;
            $activeTitleSelector = null;
            $foundItems = null;

            foreach ($strategies as $strat) {
                $tempItems = $crawler->filter($strat['container']);
                $count = $tempItems->count();

                if ($count > 0) {
                    Log::info("✅ Selector Success using [{$strat['source']}]: Found {$count} items.");
                    $activeContainer = $tempItems;
                    $activeTitleSelector = $strat['title'];
                    $foundItems = $count;
                    break; 
                }
            }

            if (!$activeContainer || $foundItems === 0) {
                Log::error("❌ All strategies failed! Could not find any news items.");
                return;
            }

            $count = 0;
            $limit = 5; // লিমিট

            $activeContainer->each(function (Crawler $node, $i) use ($website, &$count, $limit, $activeTitleSelector) {
                
                if ($count >= $limit) return false; 

                try {
                    $title = "";
                    $link = null;

                    // --- LINK & TITLE EXTRACTION LOGIC (PRESERVED FOR ACCURACY) ---
                    if ($node->nodeName() === 'a') {
                        $link = $node->attr('href');
                        $title = trim($node->text());
                        
                        if (empty($title) && $node->filter('h1, h2, h3, h4, h5, h6, span')->count() > 0) {
                            $title = trim($node->filter('h1, h2, h3, h4, h5, h6, span')->first()->text());
                        }
                    } 
                    else {
                        $titleNode = $node->filter($activeTitleSelector ?? 'h2');
                        if ($titleNode->count() > 0) {
                            $title = trim($titleNode->text());
                            if ($titleNode->nodeName() === 'a') {
                                $link = $titleNode->attr('href');
                            } elseif ($titleNode->filter('a')->count() > 0) {
                                $link = $titleNode->filter('a')->attr('href');
                            }
                        }
                        if (!$link && $node->filter('a')->count() > 0) {
                            $link = $node->filter('a')->first()->attr('href');
                            if (empty($title)) $title = trim($node->text());
                        }
                    }

                    // ভ্যালিডেশন
                    if (!$link || strlen($title) < 5) return;

                    // URL Fix
                    if (!str_starts_with($link, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $link = $baseUrl . '/' . ltrim($link, '/');
                    }

                    // Duplicate Check (Database এ চেক করে ডিসপ্যাচ এড়ানোর জন্য)
                    if (NewsItem::where('original_link', $link)
                                ->where('user_id', $this->userId)
                                ->exists()) {
                        return; 
                    }

                    // Image Logic (লিস্ট পেজে ইমেজ থাকলে সেটা নিয়ে নেওয়া ভালো)
                    $listImage = null;
                    try {
                        $imgSelector = $website->selector_image ?? 'img';
                        $node->filter($imgSelector)->each(function ($imgNode) use (&$listImage) {
                            if ($listImage) return;
                            $src = $imgNode->attr('data-src') ?? $imgNode->attr('data-original') ?? $imgNode->attr('src');
                            if ($src) $listImage = $src;
                        });
                    } catch (\Exception $e) {}

                    // ==========================================
                    // 🔥 DISPATCH SINGLE JOB
                    // ==========================================
                    Log::info("⚡ Dispatching Job for: " . Str::limit($title, 30));
                    
                    // আপনার নতুন জবে প্যারামিটার হিসেবে যা যা লাগবে তা পাস করা হলো
                    \App\Jobs\ProcessSingleNews::dispatch(
                        $link, 
                        $title, 
                        $this->userId, 
                        $website->id, 
                        $listImage // অপশনাল: লিস্ট পেজের ইমেজ পাস করলে ভালো
                    );

                    $count++;

                } catch (\Exception $e) {
                    Log::warning("⚠️ Loop Error: " . $e->getMessage());
                }
            });

            Log::info("🏁 MAIN JOB FINISHED. Queued: {$count} jobs.");
            \Illuminate\Support\Facades\Cache::forget('scraping_user_' . $this->userId);
            
            // নোট: এখানে নোটিফিকেশন পাঠানো হচ্ছে যে "জব প্রসেসিং এ গেছে", 
            // কমপ্লিট হওয়ার নোটিফিকেশন চাইলে এখান থেকে সরানো লাগতে পারে।
            if ($count > 0) {
                $user = \App\Models\User::find($this->userId);
                if ($user) {
                    // মেসেজ আপডেট: News Scraped এর বদলে Queued
                     // $user->notify(new \App\Notifications\NewsScrapedNotification($count)); 
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::forget('scraping_user_' . $this->userId);
            Log::error("🔥 CRITICAL JOB ERROR: " . $e->getMessage());
        }
    }

    /**
     * 🔥 FALLBACK CONFIGURATION
     */
    private function getDomainConfig($url)
    {
        if (str_contains($url, 'jugantor.com')) {
            return ['container' => '#loadMoreContent .col-12, #loadMoreContent .row', 'title' => 'a.text-decoration-none'];
        }
        if (str_contains($url, 'kalerkantho.com')) {
            return ['container' => 'div.card h5.card-title a, .col-md-3 a', 'title' => null];
        }
        if (str_contains($url, 'thedailystar.net')) {
            return ['container' => 'div.card-presentation, div.card-view', 'title' => 'h3.title > a'];
        }
        if (str_contains($url, 'jamuna.tv')) {
            return ['container' => '.latest-news-list .news-item', 'title' => 'h3.title > a'];
        }
        if (str_contains($url, 'dhakapost.com')) {
             return ['container' => '.category-lead a, .section-content a', 'title' => null];
        }
        return null;
    }
}