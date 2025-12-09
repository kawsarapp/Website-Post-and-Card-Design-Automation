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

class ScrapeWebsite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $websiteId;
    protected $userId;
    public $timeout = 1200; 

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

            if (!$website) {
                Log::error("❌ Job Failed: Website ID {$realId} not found in DB.");
                return;
            }

            Log::info("🚀 JOB STARTED: {$website->name} | URL: {$website->url}");

            // ১. পেজ লোড
            $listPageHtml = $scraper->runPuppeteer($website->url); 
            
            if (!$listPageHtml || strlen($listPageHtml) < 500) {
                Log::error("❌ Failed to load list page or content too short.");
                return;
            }

            $crawler = new Crawler($listPageHtml);

            // ==========================================
            // 🔥 SMART SELECTOR STRATEGY LOOP
            // ==========================================
            
            $strategies = [];

            // ১. ড্যাশবোর্ড সিলেক্টর (Priority 1)
            if (!empty($website->selector_container)) {
                $strategies[] = [
                    'source'    => 'DASHBOARD',
                    'container' => $website->selector_container,
                    'title'     => $website->selector_title
                ];
            }

            // ২. কোড কনফিগ (Priority 2 - Fallback)
            $codeConfig = $this->getDomainConfig($website->url);
            if ($codeConfig) {
                $strategies[] = [
                    'source'    => 'CODE (HARDCODED)',
                    'container' => $codeConfig['container'],
                    'title'     => $codeConfig['title']
                ];
            }

            $strategies[] = [
					'source'    => 'GENERIC (SMART)',
					'container' => 'article a, .post a, .news a, h2 a, h3 a', // ✅ শুধু আর্টিকেলের লিংক খুঁজবে
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
                    break; // কাজ হলে লুপ ব্রেক
                }
            }

            if (!$activeContainer || $foundItems === 0) {
                Log::error("❌ All strategies failed! Could not find any news items.");
                return;
            }

            $count = 0;
            $limit = 5; // 👈 শর্ত অনুযায়ী ৫টি লিমিট সেট করা হলো

            $activeContainer->each(function (Crawler $node, $i) use ($website, $scraper, &$count, $limit, $activeTitleSelector) {
                
                // ৫টি হয়ে গেলে লুপ ব্রেক করবে
                if ($count >= $limit) return false; 

                try {
                    $title = "";
                    $link = null;

                    // A. যদি সরাসরি <a> ট্যাগ ধরা হয়
                    if ($node->nodeName() === 'a') {
                        $link = $node->attr('href');
                        $title = trim($node->text());
                        
                        if (empty($title) && $node->filter('h1, h2, h3, h4, h5, h6, span')->count() > 0) {
                            $title = trim($node->filter('h1, h2, h3, h4, h5, h6, span')->first()->text());
                        }
                    } 
                    // B. যদি কন্টেইনার (div/article) ধরা হয়
                    else {
                        $titleNode = $node->filter($activeTitleSelector ?? 'h2');
                        if ($titleNode->count() > 0) {
                            $title = trim($titleNode->text());
                            // লিংক খোঁজা
                            if ($titleNode->nodeName() === 'a') {
                                $link = $titleNode->attr('href');
                            } elseif ($titleNode->filter('a')->count() > 0) {
                                $link = $titleNode->filter('a')->attr('href');
                            }
                        }
                        // টাইটেল সিলেক্টরে লিংক না পেলে বা টাইটেল সিলেক্টর না মিললে
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

                    // Duplicate Check
                    if (NewsItem::where('original_link', $link)->exists()) {
                        return; 
                    }

                    Log::info("⚡ Found New: " . Str::limit($title, 30));

                    // Image Logic (Updated with Filter)
                    $listImage = null;
                    try {
                        $imgSelector = $website->selector_image ?? 'img';
                        $node->filter($imgSelector)->each(function ($imgNode) use (&$listImage) {
                            if ($listImage) return; // ইতিমধ্যে ইমেজ পেলে আর দরকার নেই
                            $src = $imgNode->attr('data-src') ?? $imgNode->attr('data-original') ?? $imgNode->attr('src');
                            if (!$src) return;

                            // Bad Keywords Filter (Garbage image rodh kora)
                            $badKeywords = ['logo', 'icon', 'svg', 'avatar', 'user', 'profile', 'author', 'app-store', 'google-play', 'facebook', 'share'];
                            foreach ($badKeywords as $bad) {
                                if (stripos($src, $bad) !== false) return;
                            }
                            $listImage = $src;
                        });
                    } catch (\Exception $e) {}

                    // Detail Scrape
                    $scrapedData = $scraper->scrape($link, ['content' => $website->selector_content]);

                    if (!$scrapedData || empty($scrapedData['body'])) {
                        Log::warning("❌ Empty Body: $link");
                        return; 
                    }

                    $finalImage = $scrapedData['image'] ?? $listImage;
                    
                    if ($finalImage && !str_starts_with($finalImage, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $finalImage = $baseUrl . '/' . ltrim($finalImage, '/');
                    }
                    if ($finalImage && strpos($finalImage, '/og/') !== false) {
                        $finalImage = str_replace('/og/', '/', $finalImage);
                    }

                    $finalTitle = !empty($scrapedData['title']) && strlen($scrapedData['title']) > 10 
                                  ? $scrapedData['title'] : $title;

                    NewsItem::create([
                        'user_id'       => $this->userId,
                        'website_id'    => $website->id,
                        'title'         => $finalTitle,
                        'original_link' => $link,
                        'thumbnail_url' => $finalImage,
                        'content'       => $scrapedData['body'],
                        'published_at'  => now(),
                    ]);
                    
                    Log::info("✅ Saved DB: " . Str::limit($finalTitle, 30));
                    $count++;

                } catch (\Exception $e) {
                    // Silent fail for individual items
                }
            });

            Log::info("🏁 JOB FINISHED. Total Saved: {$count}");
			\Illuminate\Support\Facades\Cache::forget('scraping_user_' . $this->userId);
			
			if ($count > 0) {
				$user = \App\Models\User::find($this->userId);
				if ($user) {
					$user->notify(new \App\Notifications\NewsScrapedNotification($count));
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