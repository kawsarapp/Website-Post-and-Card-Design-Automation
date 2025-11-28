<?php

namespace App\Jobs;

use App\Models\Website;
use App\Models\NewsItem;
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

    public $timeout = 600;

    public function __construct($websiteId, $userId)
    {
        $this->websiteId = $websiteId;
        $this->userId = $userId;
    }

    public function handle()
    {
        try {
            $website = Website::withoutGlobalScopes()->find($this->websiteId);
            
            if (!$website) {
                Log::error("Scrape Job Failed: Website ID {$this->websiteId} not found.");
                return;
            }

            Log::info("🚀 Starting Scraping for: " . $website->name);

            $fileName = "scrape_" . time() . "_{$website->id}.html";
            $tempFile = storage_path("app/public/{$fileName}");
            $scriptPath = base_path("scraper-engine.js");

            $jsCode = <<<'JS'
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];
const selector = process.argv[4]; 

if (!url || !outputFile) process.exit(1);

(async () => {
  const browser = await puppeteer.launch({
    headless: "new",
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-accelerated-2d-canvas',
      '--disable-gpu',
      '--window-size=1920,1080',
      '--disable-infobars',
      '--exclude-switches=enable-automation'
    ]
  });

  try {
    const page = await browser.newPage();
    
    // রিসোর্স ব্লক (ইমেজ/ফন্ট লোড হবে না - স্পিড বাড়বে)
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
            req.abort();
        } else {
            req.continue();
        }
    });

    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

    // ফাস্ট লোড
    try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (e) {}

    // সিলেক্টরের জন্য অপেক্ষা
    try {
        if(selector) await page.waitForSelector(selector, { timeout: 8000 });
    } catch(e) {}

    // ফাস্ট স্ক্রলিং
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 500; 
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;
                if (totalHeight >= scrollHeight || totalHeight > 3000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 100);
        });
    });

    // ইমেজ সোর্স ফিক্স
    await page.evaluate(() => {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const hiddenSrc = img.getAttribute('data-src') || img.getAttribute('data-original');
            if (hiddenSrc) img.setAttribute('src', hiddenSrc);
        });
    });

    const html = await page.content();
    fs.writeFileSync(outputFile, html);
    
    await browser.close();
    process.exit(0);

  } catch (error) {
    console.error('Puppeteer Error:', error);
    await browser.close();
    process.exit(1);
  }
})();
JS;

            file_put_contents($scriptPath, $jsCode);

            $command = "node \"$scriptPath\" \"{$website->url}\" \"$tempFile\" \"{$website->selector_container}\" 2>&1";
            
            $output = shell_exec($command);

            if (!file_exists($tempFile)) {
                Log::error("Scraper Failed. Output: " . $output);
                return;
            }

            $html = file_get_contents($tempFile);
            unlink($tempFile);

            $crawler = new Crawler($html);
            $containers = $crawler->filter($website->selector_container);

            if ($containers->count() === 0) {
                Log::warning("No content found for {$website->name} with selector: {$website->selector_container}");
                return;
            }

            $count = 0;
            $limit = 15; // সর্বোচ্চ ১৫টি নিউজ আনবে

            $containers->each(function (Crawler $node) use ($website, &$count, $limit) {
                if ($count >= $limit) return false;

                try {
                    $titleNode = $node->filter($website->selector_title);
                    if ($titleNode->count() === 0) return;
                    $title = trim($titleNode->text());

                    $link = null;
                    $anchor = $node->filter('a');
                    if ($anchor->count() > 0) $link = $anchor->first()->attr('href');
                    else {
                        $titleLink = $node->filter($website->selector_title)->filter('a');
                        if ($titleLink->count() > 0) $link = $titleLink->attr('href');
                    }
                    if (!$link) return;

                    if (!str_starts_with($link, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $link = $baseUrl . '/' . ltrim($link, '/');
                    }

                    $image = null;
                    // ইমেজ লজিক (সহজ করা হয়েছে)
                    try {
                        if ($website->selector_image && $node->filter($website->selector_image)->count() > 0) {
                            $imgNode = $node->filter($website->selector_image);
                            $image = $imgNode->attr('src') ?? $imgNode->attr('data-src');
                        } elseif ($node->filter('img')->count() > 0) {
                            $imgNode = $node->filter('img')->first();
                            $image = $imgNode->attr('src') ?? $imgNode->attr('data-src');
                        }
                    } catch (\Exception $e) {}

                    if ($image) {
                        if (!str_starts_with($image, 'http')) {
                            $parsedUrl = parse_url($website->url);
                            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                            $image = $baseUrl . '/' . ltrim($image, '/');
                        }
                    }

                    NewsItem::updateOrCreate(
                        [
                            'original_link' => $link,
                            'user_id' => $this->userId 
                        ],
                        [
                            'website_id' => $website->id,
                            'title' => $title,
                            'thumbnail_url' => $image,
                            'published_at' => now()->subSeconds($count), 
                        ]
                    );
                    $count++;

                } catch (\Exception $e) {}
            });

            Log::info("✅ Scraped {$count} news items for {$website->name}");

        } catch (\Exception $e) {
            Log::error("Scrape Job Exception: " . $e->getMessage());
        }
    }
}