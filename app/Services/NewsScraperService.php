<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;

class NewsScraperService
{
    public function scrape($url)
    {
        $htmlContent = null;

        // ১. সাধারণ HTTP চেষ্টা
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ])->timeout(15)->get($url);
            
            if ($response->successful()) {
                $htmlContent = $response->body();
            }
        } catch (\Exception $e) {}

        // ২. Puppeteer Fallback
        if (empty($htmlContent) || str_contains($htmlContent, 'Cloudflare')) {
            $htmlContent = $this->runPuppeteer($url);
        }

        if (!$htmlContent) return null;

        if (!str_contains($htmlContent, 'charset')) {
            $htmlContent = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $htmlContent;
        }

        $crawler = new Crawler($htmlContent);
        
        // ৩. ক্লিনিং (Garbage Removal)
        $crawler->filter('script, style, .advertisement, .ads, .share-buttons, .meta, header, footer, .comments-area, .related-news, .most-read, .sidebar, .print-section')->each(function (Crawler $crawler) {
            foreach ($crawler as $node) {
                if ($node->parentNode) $node->parentNode->removeChild($node);
            }
        });

        // ৪. কন্টেন্ট এক্সট্রাকশন
        $selectors = [
            '#details', '.details', '.article-details', 'div[itemprop="articleBody"]',
            'article', '#content', '.content-details', '.news-details', '.news-content'
        ];

        $bestContent = "";
        $maxLength = 0;

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $combinedText = "";
                
                // <p> ট্যাগ লুপ
                $crawler->filter($selector . ' p')->each(function (Crawler $node) use (&$combinedText) {
                    $text = trim($node->text());
                    // গার্বেজ ফিল্টার এবং ক্লিন টেক্সট
                    $text = $this->cleanSpecificText($text);
                    
                    if (strlen($text) > 20 && !$this->isGarbage($text)) {
                        $combinedText .= "<p>" . $text . "</p>";
                    }
                });

                // Fallback raw text (যদি p ট্যাগ না থাকে)
                if (strlen($combinedText) < 50) {
                    $rawText = trim($crawler->filter($selector)->text());
                    $rawText = $this->cleanSpecificText($rawText); // ক্লিন করা হচ্ছে
                    if (strlen($rawText) > 50) {
                        // লাইন ব্রেক দিয়ে প্যারাগ্রাফ বানানো
                        $combinedText = "<p>" . str_replace("\n", "</p><p>", $rawText) . "</p>";
                    }
                }

                if (strlen($combinedText) > $maxLength) {
                    $maxLength = strlen($combinedText);
                    $bestContent = $combinedText;
                }
            }
        }

        if ($maxLength > 150) return $bestContent;

        // ৫. Universal Fallback
        $universalText = "";
        $crawler->filter('body p')->each(function (Crawler $node) use (&$universalText) {
            $text = trim($node->text());
            $text = $this->cleanSpecificText($text);
            if (strlen($text) > 50 && !$this->isGarbage($text)) {
                $universalText .= "<p>" . $text . "</p>";
            }
        });

        return strlen($universalText) > 200 ? $universalText : null;
    }

    // ✅ নতুন ফাংশন: তারিখ এবং সময় রিমুভার
    private function cleanSpecificText($text)
    {
        // ১. যমুনা/অন্যান্য মিডিয়ার টাইমস্ট্যাম্প রিমুভ (Regex)
        // উদাহরণ: "প্রিন্ট: ২৫ নভেম্বর... ১২:১৫ পিএম" মুছে ফেলবে
        $text = preg_replace('/(প্রিন্ট|প্রকাশ|আপডেট)\s*:\s*.*?(এএম|পিএম|AM|PM)/u', '', $text);
        
        // ২. এক্সট্রা স্পেস ক্লিন
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    private function runPuppeteer($url)
    {
        $tempFile = storage_path("app/public/temp_" . time() . "_" . rand(100,999) . ".html");
        $scriptPath = base_path("scraper-detail.js");
        $jsCode = <<<'JS'
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';
puppeteer.use(StealthPlugin());
const url = process.argv[2];
const outputFile = process.argv[3];
(async () => {
  try {
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-blink-features=AutomationControlled']});
    const page = await browser.newPage();
    await page.setExtraHTTPHeaders({'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'});
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await new Promise(r => setTimeout(r, 5000)); 
    const html = await page.content();
    fs.writeFileSync(outputFile, html);
    await browser.close();
    process.exit(0);
  } catch (e) { process.exit(1); }
})();
JS;
        file_put_contents($scriptPath, $jsCode);
        $command = "node \"$scriptPath\" \"$url\" \"$tempFile\" 2>&1";
        shell_exec($command);
        
        if (file_exists($tempFile)) {
            $content = file_get_contents($tempFile);
            unlink($tempFile);
            return $content;
        }
        return null;
    }

    private function isGarbage($text) {
        $garbage = ['আরও পড়ুন', 'বিস্তারিত', 'বিজ্ঞাপন', 'Advertisement', 'Click to comment', 'Follow us', 'Google News', 'Share this', 'Latest News'];
        foreach ($garbage as $g) {
            if (stripos($text, $g) !== false) return true;
        }
        return false;
    }
}