<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsScraperService
{
    /**
     * Main Scrape Method
     */
    public function scrape($url, $customSelectors = [])
    {
        // 1️⃣ STEP 1: Python Scraper
        $pythonData = $this->runPythonScraper($url);

        if ($pythonData && !empty($pythonData['body'])) {
            Log::info("✅ Python Scraper Successful: $url");
            
            // 🔥 FIX: cleanHtml ফাংশন এখন নিচে যোগ করা হয়েছে
            // এবং ডুপ্লিকেট লাইন রিমুভ করা হয়েছে
            return [
                'title'      => $pythonData['title'] ?? null,
                'image'      => $pythonData['image'] ?? null,
                'body'       => $this->cleanHtml($pythonData['body']), 
                'source_url' => $url
            ];
        }

        Log::info("⚠️ Python failed/blocked, trying PHP HTTP fallback...");

        // 2️⃣ STEP 2: PHP HTTP Request
        $htmlContent = null;
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->getRandomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(20)->get($url);

            if ($response->successful()) {
                $htmlContent = $response->body();
            }
        } catch (\Exception $e) {
            Log::warning("HTTP Scrape Failed: " . $e->getMessage());
        }

        // 3️⃣ STEP 3: Puppeteer Node.js
        if (empty($htmlContent) || str_contains($htmlContent, 'Just a moment') || strlen($htmlContent) < 600) {
            Log::info("🔄 Switching to Puppeteer for: $url");
            for ($j = 0; $j < 2; $j++) {
                $htmlContent = $this->runPuppeteer($url);
                if ($htmlContent && strlen($htmlContent) > 1000) break;
                sleep(2);
            }
        }

        // 4️⃣ FINAL CHECK
        if (!$htmlContent || strlen($htmlContent) < 500) {
            Log::error("❌ All scraping methods failed for: $url");
            return null;
        }

        // 5️⃣ PROCESS HTML
        return $this->processHtml($htmlContent, $url, $customSelectors);
    }

    public function runPythonScraper($url)
    {
        $scriptPath = base_path("scraper.py"); 
        if (!file_exists($scriptPath)) return null;

        $pythonCmd = env('PYTHON_PATH'); 
        if (!$pythonCmd) {
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        }

        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " 2>&1";
        $output = shell_exec($command);
        
        $data = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) return null;

        return (isset($data['body']) && !empty($data['body'])) ? $data : null;
    }

    public function runPuppeteer($url)
    {
        $scriptPath = base_path("scraper-engine.js");
        if (!file_exists($scriptPath)) return null;

        $tempFile = storage_path("app/public/temp_" . uniqid() . "_" . rand(1000,9999) . ".html");
        $nodeCmd = env('NODE_PATH');
        
        if (!$nodeCmd) {
            $nodeCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'node' : 'node';
            if ($nodeCmd === 'node' && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $nodeCmd = trim(shell_exec('which node') ?: 'node');
            }
        }

        $command = "$nodeCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " " . escapeshellarg($tempFile) . " 2>&1";
        shell_exec($command);
        
        $htmlContent = null;
        if (file_exists($tempFile)) {
            $htmlContent = file_get_contents($tempFile);
            unlink($tempFile);
        }
        
        return (strlen($htmlContent) > 500) ? $htmlContent : null;
    }

    private function processHtml($html, $url, $customSelectors)
    {
        if (!mb_detect_encoding($html, 'UTF-8', true)) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        $crawler = new Crawler($html);
        $this->cleanGarbage($crawler);

        $data = [
            'title'      => $this->extractTitle($crawler),
            'image'      => $this->extractImage($crawler, $url),
            'body'       => null,
            'source_url' => $url
        ];

        $jsonLdData = $this->extractFromJsonLD($crawler);
        if (!empty($jsonLdData['articleBody']) && strlen($jsonLdData['articleBody']) > 200) {
            // JSON-LD ডাটাকেও ফরম্যাট করা হচ্ছে যাতে প্যারাগ্রাফ থাকে
            $data['body'] = $this->formatText($jsonLdData['articleBody']);
            
            if (empty($data['image']) && !empty($jsonLdData['image'])) {
                $img = $jsonLdData['image'];
                $data['image'] = is_array($img) ? ($img['url'] ?? $img[0] ?? null) : $img;
            }
        }

        if (empty($data['body'])) {
            $data['body'] = $this->extractBodyManually($crawler, $customSelectors);
        }

        return !empty($data['body']) ? $data : null;
    }

    // ==========================================
    // 🛠️ HELPER FUNCTIONS
    // ==========================================

    /**
     * 🔥 MISSING FUNCTION ADDED: cleanHtml
     * এটি স্ক্রিপ্ট ট্যাগ রিমুভ করে কিন্তু প্যারাগ্রাফ ট্যাগ রাখে।
     */
    private function cleanHtml($html) {
        // শুধুমাত্র নির্দিষ্ট ট্যাগগুলো রাখা হবে, বাকি সব রিমুভ (যেমন script, style, iframe)
        return strip_tags($html, '<p><br><h3><h4><h5><h6><ul><li><b><strong><blockquote><img><a>');
    }

    private function extractBodyManually(Crawler $crawler, $customSelectors)
    {
        $selectors = [
            'div[itemprop="articleBody"]', '.article-details', '#details', '.details', 
            '.content-details', 'article', '#content', '.news-content', 
            '.story-element-text', '.jw_article_body', '.description', 
            '.post-content', '.entry-content', '.section-content',
            '.post-body', '.td-post-content', '.main-content'
        ];

        if (!empty($customSelectors['content'])) {
            array_unshift($selectors, $customSelectors['content']);
        }

        $bestContent = "";
        $maxLength = 0;

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $container = $crawler->filter($selector);
                $this->removeJunkElements($container);

                $text = "";
                // প্যারাগ্রাফ এবং হেডিং আলাদা করে ধরা
                $container->filter('p, h3, h4, h5, h6, ul, blockquote')->each(function (Crawler $node) use (&$text) {
                    $tag = $node->nodeName();
                    $rawHtml = trim($node->html());
                    // ভেতরের বোল্ড বা লিংক ট্যাগ রাখা হচ্ছে
                    $cleanText = strip_tags($rawHtml, '<b><strong><a><i><em>'); 

                    if (strlen(strip_tags($cleanText)) < 5 || $this->isGarbageText(strip_tags($cleanText))) return;

                    if (in_array($tag, ['h3', 'h4', 'h5', 'h6'])) {
                        $text .= "<h3>" . $cleanText . "</h3>\n";
                    } elseif ($tag === 'ul') {
                        $text .= "<ul>" . $cleanText . "</ul>\n";
                    } elseif ($tag === 'blockquote') {
                        $text .= "<blockquote>" . $cleanText . "</blockquote>\n";
                    } else {
                        $text .= "<p>" . $cleanText . "</p>\n";
                    }
                });

                if (strlen($text) > $maxLength) {
                    $maxLength = strlen($text);
                    $bestContent = $text;
                }
            }
        }
        return !empty($bestContent) ? $bestContent : null;
    }

    private function removeJunkElements(Crawler $container)
    {
        $junkSelectors = [
            '.related-news', '.read-more', '.more-news', '.also-read',
            '.advertisement', '.ads', '.ad-box', '.social-share', 
            '.share-buttons', '.author-bio', '.tags', '.meta', 
            '.print-only', '.video-container', '.embed-code',
            '[class*="related"]', '[id*="related"]',
            '[class*="taboola"]', '[id*="taboola"]'
        ];

        foreach ($junkSelectors as $junk) {
            $container->filter($junk)->each(function (Crawler $node) {
                if ($node->getNode(0)->parentNode) {
                    $node->getNode(0)->parentNode->removeChild($node->getNode(0));
                }
            });
        }
    }

    private function cleanGarbage(Crawler $crawler)
    {
        $junkSelectors = ['script', 'style', 'iframe', 'nav', 'header', 'footer', 'form', '.advertisement', '.ads', '.share-buttons', '.meta', '.comments-area', '.sidebar'];
        $crawler->filter(implode(', ', $junkSelectors))->each(function (Crawler $node) {
            if ($node->getNode(0)->parentNode) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0));
            }
        });
    }

    private function extractTitle(Crawler $crawler)
    {
        if ($crawler->filter('h1')->count() > 0) return trim($crawler->filter('h1')->first()->text());
        if ($crawler->filter('title')->count() > 0) return trim($crawler->filter('title')->text());
        return "Untitled News";
    }

    private function extractImage(Crawler $crawler, $url)
    {
        $imageUrl = null;
        $crawler->filter('img')->each(function (Crawler $node) use (&$imageUrl) {
            if ($imageUrl) return; 

            $src = $node->attr('data-original') ?? $node->attr('data-src') ?? $node->attr('src');
            
            $width = $node->attr('width');
            // Check if width is a number before comparing
            if ($width && is_numeric($width) && $width < 300) return;

            if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                $imageUrl = $src;
            }
        });

        if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($url);
            $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $imageUrl = $root . '/' . ltrim($imageUrl, '/');
        }
        return $imageUrl;
    }

    private function extractFromJsonLD($crawler) {
        try {
            $scripts = $crawler->filter('script[type="application/ld+json"]');
            foreach ($scripts as $script) {
                $json = json_decode($script->nodeValue, true);
                if (isset($json['articleBody'])) return $json;
                if (isset($json['@graph'])) {
                    foreach ($json['@graph'] as $item) {
                        if (isset($item['articleBody'])) return $item;
                    }
                }
            }
        } catch (\Exception $e) {}
        return null;
    }

    private function isGarbageText($text) {
        $garbage = ['শেয়ার করুন', 'Advertisement', 'Subscribe', 'Follow us', 'Read more', 'বিজ্ঞাপন', 'আরো পড়ুন'];
        foreach ($garbage as $g) {
            if (stripos($text, $g) !== false && strlen($text) < 50) return true;
        }
        return false;
    }

    private function isGarbageImage($url) {
        return preg_match('/(logo|icon|svg|avatar|profile|ad-|banner|share|button|facebook|twitter)/i', $url);
    }

    private function formatText($text) {
        return "<p>" . str_replace("\n", "</p><p>", trim($text)) . "</p>";
    }

    private function getRandomUserAgent() {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    }
}