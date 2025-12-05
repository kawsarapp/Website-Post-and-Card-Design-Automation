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
use Illuminate\Support\Str; // ✅ এই লাইনটি যোগ করা হয়েছে (FIXED)
use Symfony\Component\DomCrawler\Crawler;

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
        try {
            $realId = is_array($this->websiteId) ? ($this->websiteId['id'] ?? null) : $this->websiteId;
            $website = Website::withoutGlobalScopes()->find($realId);

            if (!$website) {
                Log::error("❌ Job Failed: Website ID {$realId} not found in DB.");
                return;
            }

            Log::info("🚀 JOB STARTED: {$website->name} | URL: {$website->url}");

            // ১. লিস্ট পেজ ফেচ
            $listPageHtml = $scraper->runPuppeteer($website->url); 
            
            if (!$listPageHtml || strlen($listPageHtml) < 500) {
                Log::error("❌ Failed to load list page or content too short.");
                return;
            }

            $crawler = new Crawler($listPageHtml);
            $containerSelector = $website->selector_container ?? 'article';
            $containers = $crawler->filter($containerSelector);

            Log::info("🔎 Found {$containers->count()} potential news items using selector: '{$containerSelector}'");

            if ($containers->count() === 0) {
                Log::warning("⚠️ Zero items found! Check Selector configuration.");
                return;
            }

            $count = 0;
            $limit = 10; // সেফটির জন্য ১০টি

            $containers->each(function (Crawler $node, $i) use ($website, $scraper, &$count, $limit) {
                if ($count >= $limit) return false; 

                try {
                    // Title Check
                    $titleSelector = $website->selector_title ?? 'h2';
                    $titleNode = $node->filter($titleSelector);
                    if ($titleNode->count() === 0) {
                        return;
                    }
                    $title = trim($titleNode->text());

                    // Link Extraction Logic
                    $link = null;
                    if ($titleNode->filter('a')->count() > 0) {
                        $link = $titleNode->filter('a')->attr('href');
                    } elseif ($node->filter('a')->count() > 0) {
                        $link = $node->filter('a')->first()->attr('href');
                    }

                    if (!$link) {
                        Log::warning("⚠️ Item #{$i}: Title found ($title) but NO LINK.");
                        return;
                    }

                    // Fix URL
                    if (!str_starts_with($link, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $link = $baseUrl . '/' . ltrim($link, '/');
                    }

                    // Duplicate Check
                    if (NewsItem::where('original_link', $link)->exists()) {
                        Log::info("⏭️ Skipping Duplicate: $link");
                        return;
                    }

                    Log::info("⚡ Processing New Link: $link");

                    // List Image
                    $listImage = null;
                    try {
                        $imgSelector = $website->selector_image ?? 'img';
                        if ($node->filter($imgSelector)->count() > 0) {
                            $imgNode = $node->filter($imgSelector)->first();
                            $listImage = $imgNode->attr('data-src') 
                                      ?? $imgNode->attr('data-original') 
                                      ?? $imgNode->attr('src');
                        }
                    } catch (\Exception $e) {}

                    // Detail Scrape
                    $scrapedData = $scraper->scrape($link, [
                        'content' => $website->selector_content
                    ]);

                    if (!$scrapedData || empty($scrapedData['body'])) {
                        Log::warning("❌ Empty Body skipped: $link");
                        return; 
                    }

                    // Merge Image
                    $finalImage = $scrapedData['image'] ?? $listImage;
                    
                    // URL Fix
                    if ($finalImage && !str_starts_with($finalImage, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $finalImage = $baseUrl . '/' . ltrim($finalImage, '/');
                    }
                    // Clean Image
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
                    
                    Log::info("✅ Saved: " . Str::limit($finalTitle, 30));
                    $count++;

                } catch (\Exception $e) {
                    Log::error("❌ Item Error: " . $e->getMessage());
                }
            });

            Log::info("🏁 JOB FINISHED. Total Saved: {$count}");

        } catch (\Exception $e) {
            Log::error("🔥 CRITICAL JOB ERROR: " . $e->getMessage());
        }
    }
}