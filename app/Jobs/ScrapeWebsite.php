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
            // ২. লিস্ট পেজ লোড (Raw HTML)
            $listPageHtml = null;

            // 🚀 SmartProxy Universal Scraping API — Enabled per-website from Dashboard
            if ($website->use_scraping_api) {
                Log::info("🔐 Scraping API enabled for [{$website->name}] — using Universal Scraping API.");
                $listPageHtml = $scraper->fetchWithUniversalScrapingApi($website->url);

                // Fallback to Python if API is not configured or fails
                if (!$listPageHtml || strlen($listPageHtml) < 500) {
                    Log::info("🔄 Universal API failed or unconfigured — falling back to Python/Puppeteer.");
                    $listPageHtml = $scraper->fetchHtmlWithPython($website->url, $this->userId);
                }
                if (!$listPageHtml || strlen($listPageHtml) < 500) {
                    $listPageHtml = $scraper->runPuppeteer($website->url, $this->userId);
                }
            } else {
                // 🔥 JS-Rendered সাইটের জন্য সরাসরি Puppeteer ব্যবহার
                $jsRenderedDomains = ['somoynews.tv', 'ekhon.tv', 'dbcnews.tv', 'banglatribune.com', 'bdnews24.com'];
                $isJsRendered = collect($jsRenderedDomains)->some(fn($d) => str_contains($website->url, $d));

                if ($isJsRendered) {
                    Log::info("🎭 JS-Rendered Site detected. Using Puppeteer directly for list.");
                    $listPageHtml = $scraper->runPuppeteer($website->url, $this->userId);
                } else {
                    try {
                        // ১. Python bypass (curl_cffi) - সবচেয়ে দ্রুত এবং নির্ভরযোগ্য
                        $listPageHtml = $scraper->fetchHtmlWithPython($website->url, $this->userId);

                        // ২. Default Http Facade (যদি পাইথন কাজ না করে)
                        if (!$listPageHtml || strlen($listPageHtml) < 500) {
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
                        }
                    } catch (\Exception $e) {
                        Log::warning("⚠️ Direct HTTP/Python Failed (Will try Puppeteer): " . $e->getMessage());
                    }

                    // ৩. Puppeteer (Last Resort)
                    if (!$listPageHtml || strlen($listPageHtml) < 500) {
                        Log::info("🔄 Falling back to Puppeteer with Proxy...");
                        $listPageHtml = $scraper->runPuppeteer($website->url, $this->userId);
                    }
                }
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

            // ড্যাশবোর্ড vs কোড কনফিগ — প্রায়রিটি নির্ধারণ
            // যদি কোড-কনফিগ থাকে, আগে সেটা চেষ্টা করা হবে (সঠিক selector guaranteed)
            // না থাকলে ড্যাশবোর্ড আগে চেষ্টা হবে (user-defined selector)
            $codeConfig = $this->getDomainConfig($website->url);

            if ($codeConfig) {
                // १. কোড কনফিগ (PRIORITY for known domains)
                $strategies[] = [
                    'source'    => 'CODE (HARDCODED)',
                    'container' => $codeConfig['container'],
                    'title'     => $codeConfig['title']
                ];
            }

            // २. ড্যাশবোর্ড সিলেক্টর (user-defined — runs after code config)
            if (!empty($website->selector_container)) {
                $strategies[] = [
                    'source'    => 'DASHBOARD',
                    'container' => $website->selector_container,
                    'title'     => $website->selector_title
                ];
            }

            // ३. জেনেরিক স্মার্ট সিলেক্টর
            $strategies[] = [
                'source'    => 'GENERIC (SMART)',
                'container' => 'article a, .post a, .news a, h2 a, h3 a', 
                'title'     => null
            ];

            $activeContainer = null;
            $activeTitleSelector = null;
            $foundItems = null;

            foreach ($strategies as $strat) {
                try {
                    $tempItems = $crawler->filter($strat['container']);
                    $count = $tempItems->count();
                } catch (\Symfony\Component\CssSelector\Exception\SyntaxErrorException $e) {
                    Log::warning("⚠️ Selector syntax error [{$strat['source']}]: " . $e->getMessage() . " — Skipping.");
                    continue;
                } catch (\Exception $e) {
                    Log::warning("⚠️ Selector error [{$strat['source']}]: " . $e->getMessage() . " — Skipping.");
                    continue;
                }

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

                    // ভ্যালিডেশন — CSS selector string that leaked as title is rejected
                    $looksLikeCssSelector = preg_match('/^[.#\[\w-]+(\s+[.#\[\w-]+)*$/', trim($title)) && !preg_match('/[\x{0980}-\x{09FF}]/u', $title);
                    if (!$link || strlen($title) < 5 || $looksLikeCssSelector) return;

                    // URL Fix
                    $parsedUrl = parse_url($website->url);
                    $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https';
                    $baseUrl = $scheme . '://' . $parsedUrl['host'];

                    $lowerLink = strtolower($link);
                    if (str_starts_with($lowerLink, 'javascript:') || str_starts_with($lowerLink, 'tel:') || str_starts_with($lowerLink, 'mailto:') || str_starts_with($lowerLink, 'whatsapp:')) {
                        return;
                    }

                    if (str_starts_with($link, '//')) {
                        $link = $scheme . ':' . $link;
                    } elseif (!str_starts_with($link, 'http')) {
                        $link = $baseUrl . '/' . ltrim($link, '/');
                    }

                    // 🔥 Skip Homepage / Root URL exactly
                    if (rtrim($link, '/') === rtrim($baseUrl, '/')) {
                        return;
                    }

                    // 🔥 Category/Listing URL Filter — skip obvious non-article URLs
                    $skipPatterns = ['/category/', '/tag/', '/archive/', '/page/', '/author/', '/search/', '/latest-news$', '/recent$', '/live$', '/live/'];
                    foreach ($skipPatterns as $pattern) {
                        if (str_ends_with($pattern, '$') ? str_ends_with($link, rtrim($pattern, '$')) : str_contains($link, $pattern)) {
                            return;
                        }
                    }

                    // 🚨 Strict Check (News URLs must contain an ID or Year)
                    if (str_contains($website->url, 'bd-pratidin.com') && !preg_match('/\d{4,}/', $link)) {
                        return;
                    }

                    // 🔥 Smart Validation: Skip pure alphabetic nav categories (e.g. /national, /epaper)
                    $cleanPath = parse_url($link, PHP_URL_PATH) ?? '';
                    $cleanPath = trim($cleanPath, '/');
                    if (!empty($cleanPath)) {
                        $pathSegments = explode('/', $cleanPath);
                        $segmentCount = count($pathSegments);
                        
                        $hasNumbers = preg_match('/\d/', $cleanPath);
                        $hasHyphens = str_contains($cleanPath, '-');

                        // Categories are usually short and don't contain numbers or hyphens (like slugs)
                        if ($segmentCount == 1 && !$hasNumbers && strlen($cleanPath) < 20) {
                            return; // Highly likely a main category like /sports, /national
                        }

                        if ($segmentCount == 2 && !$hasNumbers && !$hasHyphens && strlen($cleanPath) < 25) {
                            return; // e.g. /news/national
                        }
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
        if (str_contains($url, 'samakal.com')) {
             return ['container' => '.latest-news-list .cat-post-item, .main-ticker a', 'title' => 'h4.media-heading a'];
        }
        if (str_contains($url, 'bartabazar.com')) {
            // Bartabazar: articles at /news/290468/ - use .hdl, wide selectors + Smart URL filter handles the rest
            return ['container' => '.hdl a, .col-xs-9 a, .col-sm-7 a, .cat-item a, article a, h2 a, h3 a', 'title' => null];
        }
        if (str_contains($url, 'somoynews.tv')) {
            // somoynews is full React — Puppeteer renders it; article links contain /news/
            return ['container' => 'a[href*="/news/"]', 'title' => null];
        }
        if (str_contains($url, 'ekhon.tv')) {
            // Ekhon TV articles are usually nested within specific content grids or have distinctive paths
            return ['container' => 'main a[href*="/news/"], article a, .news-list a, .latest-news a, .grid-cols-1 a[href*="-"], .content-area a', 'title' => null];
        }
        if (str_contains($url, 'jagonews24.com')) {
            // Latest news cards are in .col-sm-8.paddingTop10 — date/nav elements filtered by URL validator
            return ['container' => '.col-sm-8.paddingTop10 a, .newsList a, .list_content a', 'title' => null];
        }
        if (str_contains($url, 'dbcnews.tv')) {
            // dbcnews uses Tailwind CSS. We use broad headings and Tailwind grid/typography classes
            return ['container' => 'h2 a, h3 a, h4 a, .text-xl a, .text-lg a, .font-bold a, .col-span-12 a, .col-span-6 a', 'title' => null];
        }
        if (str_contains($url, 'banglatribune.com')) {
            // Bangla Tribune exact article target classes
            return ['container' => '.contents .title_holder a, .listing .title a, .top-news .title a, .feature_news .title a, .list_items h2 a, .story_list h2 a, .more_news_list a', 'title' => null];
        }
        if (str_contains($url, 'bd-pratidin.com')) {
            // Bangladesh Pratidin (stong URL filter allows very broad CSS selector)
            return ['container' => '.col-sm-3 a, .col-sm-4 a, .col-sm-6 a, .col-sm-8 a, .col-md-3 a, .col-md-4 a, .col-md-6 a, .col-md-8 a, .media a, .thumbnail a, .row a, ul li a, article a', 'title' => null];
        }
        if (str_contains($url, 'bdnews24.com')) {
            // bdnews24.com news links are inside SubCat-wrapper and similar grid classes
            return ['container' => '.SubCat-wrapper a, .category-wrapper a', 'title' => null];
        }
        return null;
    }
}