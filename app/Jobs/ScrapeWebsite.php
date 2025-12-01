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
use Symfony\Component\DomCrawler\Crawler;

class ScrapeWebsite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $websiteId;
    protected $userId;

    // Puppeteer এবং স্ক্র্যাপিং এর জন্য সময় বেশি লাগতে পারে, তাই Timeout বাড়ানো হলো
    public $timeout = 1200; 

    public function __construct($websiteId, $userId)
    {
        $this->websiteId = $websiteId;
        $this->userId = $userId;
    }

    public function handle(NewsScraperService $scraper)
    {
        try {
            // ১. ওয়েবসাইট লোড করা
            $realId = is_array($this->websiteId) ? ($this->websiteId['id'] ?? null) : $this->websiteId;
            $website = Website::withoutGlobalScopes()->find($realId);

            if (!$website) {
                Log::error("❌ Scrape Job Failed: Website ID {$realId} not found.");
                return;
            }

            Log::info("🚀 Scraping Started for: {$website->name} ({$website->url})");

            // ২. লিস্ট পেজ ফেচ করা (Puppeteer ব্যবহার করে, কারণ অনেক সাইটে JS লোড থাকে)
            $listPageHtml = $scraper->runPuppeteer($website->url); 
            
            if (!$listPageHtml || strlen($listPageHtml) < 500) {
                Log::error("❌ List Page Failed: {$website->url} returned empty or blocked content.");
                return;
            }

            $crawler = new Crawler($listPageHtml);
            
            // ৩. সিলেক্টর দিয়ে কন্টেইনার খোঁজা
            $containerSelector = $website->selector_container ?? 'article';
            $containers = $crawler->filter($containerSelector);

            if ($containers->count() === 0) {
                Log::error("⚠️ No items found using selector '{$containerSelector}' on {$website->name}. Structure might have changed.");
                return;
            }

            $count = 0;
            $limit = 15; // একবারে সর্বোচ্চ ১৫টি নিউজ প্রসেস করবে

            $containers->each(function (Crawler $node) use ($website, $scraper, &$count, $limit) {
                if ($count >= $limit) return false; // লুপ ব্রেক

                try {
                    // --- A. টাইটেল এক্সট্রাকশন ---
                    $titleNode = $node->filter($website->selector_title ?? 'h2');
                    if ($titleNode->count() === 0) return; // টাইটেল না থাকলে বাদ
                    $title = trim($titleNode->text());

                    // --- B. লিংক এক্সট্রাকশন ---
                    $link = null;
                    if ($node->filter('a')->count() > 0) {
                        $link = $node->filter('a')->first()->attr('href');
                    } elseif ($titleNode->filter('a')->count() > 0) {
                        $link = $titleNode->filter('a')->attr('href');
                    }

                    if (!$link) return;

                    // Absolute URL বানানো
                    if (!str_starts_with($link, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $link = $baseUrl . '/' . ltrim($link, '/');
                    }

                    // --- C. ডুপ্লিকেট চেক ---
                    if (NewsItem::where('original_link', $link)->exists()) {
                        return; // যদি অলরেডি থাকে, তাহলে স্কিপ
                    }

                    // --- D. লিস্ট পেজ থেকে ইমেজ এক্সট্রাকশন (Thumbnail) ---
                    $listImage = null;
                    try {
                        $imgSelector = $website->selector_image ?? 'img';
                        if ($node->filter($imgSelector)->count() > 0) {
                            $imgNode = $node->filter($imgSelector)->first();
                            $listImage = $imgNode->attr('src') ?? $imgNode->attr('data-src');
                        }
                    } catch (\Exception $e) {}

                    // --- E. মেইন ডিটেইলস পেজ স্ক্র্যাপ করা ---
                    $customSelectors = [
                        'container' => $website->selector_content ?? $website->selector_container,
                        'content'   => $website->selector_content
                    ];
                    
                    $method = $website->scraper_method ?? 'node';

                    // সার্ভিস কল করা (এখন এটি অ্যারে রিটার্ন করে)
                    $scrapedData = $scraper->scrape($link, $customSelectors, $method);

                    // ভ্যালিডেশন
                    if (!$scrapedData || empty($scrapedData['body'])) {
                        Log::warning("⚠️ Empty Body for link: {$link}");
                        return; 
                    }

                    // --- F. ডাটা মার্জ করা ---
                    // ইমেজ: যদি স্ক্র্যাপার হাই-কোয়ালিটি ইমেজ পায় সেটা নেব, নাহলে লিস্ট পেজের ইমেজ
                    $finalImage = $scrapedData['image'] ?? $listImage;
                    
                    // ইমেজ URL ফিক্স (যদি রিলেটিভ হয়)
                    if ($finalImage && !str_starts_with($finalImage, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $finalImage = $baseUrl . '/' . ltrim($finalImage, '/');
                    }

                    // 🔥🔥🔥🔥 IMAGE CLEANING LOGIC START 🔥🔥🔥🔥
                    // Kaler Kantho বা অন্য সাইটের /og/ ফোল্ডার রিমুভ করে ক্লিন ইমেজ নেওয়া
                    if (!empty($finalImage) && strpos($finalImage, '/og/') !== false) {
                        $finalImage = str_replace('/og/', '/', $finalImage);
                        // অপশনাল: লগ রাখা যাতে আপনি বুঝতে পারেন কাজ হচ্ছে
                        // Log::info("✅ Image Cleaned: " . $finalImage); 
                    }
                    // 🔥🔥🔥🔥 IMAGE CLEANING LOGIC END 🔥🔥🔥🔥

                    // টাইটেল: অনেক সময় লিস্ট পেজের টাইটেল ছোট থাকে, ডিটেইল পেজেরটা ভালো হয়
                    $finalTitle = !empty($scrapedData['title']) && strlen($scrapedData['title']) > 10 
                                  ? $scrapedData['title'] 
                                  : $title;

                    // --- G. ডাটাবেসে সেভ ---
                    NewsItem::create([
                        'user_id'       => $this->userId,
                        'website_id'    => $website->id,
                        'title'         => $finalTitle,
                        'original_link' => $link,
                        'thumbnail_url' => $finalImage, // ক্লিন ইমেজ সেভ হবে
                        'content'       => $scrapedData['body'], // মেইন কন্টেন্ট
                        'published_at'  => now(),
                    ]);
                    
                    $count++;

                } catch (\Exception $e) {
                    Log::error("❌ Item Error in {$website->name}: " . $e->getMessage());
                }
            });

            Log::info("✅ Successfully scraped {$count} new items for {$website->name}");

        } catch (\Exception $e) {
            Log::error("🔥 CRITICAL JOB ERROR: " . $e->getMessage());
        }
    }
}