<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSetting;

class NewsScraperService
{
    /**
     * ==========================================
     * 🚀 MAIN SCRAPING FUNCTION (ULTRA MERGED)
     * ==========================================
     */
    public function scrape($url, $customSelectors = [], $userId = null)
    {
        // ১. প্রক্সি কনফিগারেশন
        $proxy = $this->getProxyConfig($userId);
        $proxyLog = $proxy ? parse_url($proxy, PHP_URL_HOST) : "Direct";
        Log::info("🚀 START SCRAPE: $url | via $proxyLog");

        // ২. হার্ড সাইট চেকিং (Smart Identification)
        $hardSites = ['jamuna.tv', 'kalerkantho.com', 'somoynews.tv', 'dailyamardesh.com']; 
        $isHardSite = false;
        foreach ($hardSites as $site) {
            if (str_contains($url, $site)) {
                $isHardSite = true;
                break;
            }
        }

        // =========================================================
        // 🐍 STEP 1: PYTHON SCRAPER (PRIORITY #1 - FASTEST)
        // =========================================================
        // হার্ড সাইট হলেও আগে Python দিয়ে চেষ্টা করব। কারণ আমাদের নতুন scraper.py
        // ক্লাউডফ্লেয়ার বাইপাস করতে পারে। এটি সফল হলে সময় লাগবে ৩-৫ সেকেন্ড।
        
        $pythonData = $this->runPythonScraper($url, $userId);

        if ($pythonData && !empty($pythonData['body'])) {
            Log::info("✅ Python Scraper Success");
            
            // GitHub এর ইমেজ ফিক্সিং লজিক
            $fixedImage = $this->fixVendorImages($pythonData['image'] ?? null);

            return [
                'title'      => $pythonData['title'] ?? null,
                'image'      => $fixedImage,
                'body'       => $this->cleanHtml($pythonData['body']), 
                'source_url' => $url
            ];
        }

        Log::info("⚠️ Python failed. Checking fallback strategy...");

        // =========================================================
        // 🐘 STEP 2: PHP HTTP REQUEST (Skip if Hard Site)
        // =========================================================
        $htmlContent = null;

        // যদি হার্ড সাইট না হয়, তবেই কেবল PHP দিয়ে চেষ্টা করব।
        // হার্ড সাইট (যেমন যমুনা) PHP তে 403 দেয়, তাই চেষ্টা করে লাভ নেই।
        if (!$isHardSite) {
            try {
                $timeout = $proxy ? 20 : 15; 
                $httpRequest = Http::withHeaders($this->getRealBrowserHeaders())
                    ->timeout($timeout)
                    ->withOptions([
                        'verify' => false,
                        'connect_timeout' => 10,
                    ]);

                if ($proxy) {
                    $httpRequest->withOptions(['proxy' => $proxy]);
                }

                $response = $httpRequest->get($url);

                if ($response->successful()) {
                    $htmlContent = $response->body();
                } else {
                    Log::warning("PHP HTTP Status: " . $response->status());
                }
            } catch (\Exception $e) {
                Log::warning("PHP HTTP Failed: " . $e->getMessage());
            }
        } else {
            Log::info("🛡️ Skipping PHP fallback for Hard Site (Cloudflare protected).");
        }

        // =========================================================
        // 🤖 STEP 3: PUPPETEER (Last Resort)
        // =========================================================
        // যদি PHP ফেইল করে অথবা Hard Site হওয়ার কারণে স্কিপ করা হয়, তবেই পাপেটিয়ার
        if (empty($htmlContent) || str_contains($htmlContent, 'Just a moment') || strlen($htmlContent) < 600) {
            Log::info("🔄 All Fast Methods Failed. Engaging Puppeteer Engine...");
            return $this->scrapeWithPuppeteer($url, $customSelectors, $userId);
        }

        // 4️⃣ FINAL PROCESSING (For PHP Data)
        if ($htmlContent && strlen($htmlContent) > 500) {
            $scrapedData = $this->processHtml($htmlContent, $url, $customSelectors);
            
            if (isset($scrapedData['image'])) {
                $scrapedData['image'] = $this->fixVendorImages($scrapedData['image']);
            }
            
            // টাইটেল বা বডি না পেলে ফেইল হিসেবে ধরব এবং পাপেটিয়ারে পাঠাব
            if (empty($scrapedData['title']) || empty($scrapedData['body'])) {
                 Log::warning("⚠️ Content Parsing Failed. Retrying with Puppeteer...");
                 return $this->scrapeWithPuppeteer($url, $customSelectors, $userId);
            }

            return $scrapedData;
        }

        Log::error("❌ CRITICAL: Scrape totally failed for: $url");
        return null;
    }

    /**
     * ==========================================
     * 🛠️ PROXY & RUNNER FUNCTIONS
     * ==========================================
     */
     
    public function getProxyConfig($userId = null)
    {
        $uid = $userId ?? Auth::id();
        if (!$uid) return null;

        $settings = \App\Models\UserSetting::where('user_id', $uid)->first();

        if ($settings && $settings->proxy_host && $settings->proxy_port) {
            $auth = "";
            if ($settings->proxy_username && $settings->proxy_password) {
                // সেশন রোটেশন: প্রতি মিনিটে নতুন আইপি
                $sessionId = date('Hi'); 
                $rotatingUser = $settings->proxy_username . "-session-" . $sessionId;
                $auth = "{$rotatingUser}:{$settings->proxy_password}@";
            }
            return "http://{$auth}{$settings->proxy_host}:{$settings->proxy_port}";
        }
        return null;
    }

    public function runPythonScraper($url, $userId = null)
    {
        $proxy = $this->getProxyConfig($userId);
        $scriptPath = base_path("scraper.py"); 
        if (!file_exists($scriptPath)) return null;

        $pythonCmd = env('PYTHON_PATH'); 
        if (!$pythonCmd) {
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        }

        // প্রক্সি সহ কমান্ড রান
        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url);
        if ($proxy) $command .= " " . escapeshellarg($proxy);
        $command .= " 2>&1";

        $output = shell_exec($command);
        $data = json_decode($output, true);
        
        return (json_last_error() === JSON_ERROR_NONE && isset($data['body'])) ? $data : null;
    }

    private function scrapeWithPuppeteer($url, $customSelectors, $userId)
    {
        // Retry logic: ১ বার চেষ্টা করাই যথেষ্ট কারণ scraper-engine.js এখন অনেক স্মার্ট
        $htmlContent = $this->runPuppeteer($url, $userId);

        if ($htmlContent && strlen($htmlContent) > 500) {
            $scrapedData = $this->processHtml($htmlContent, $url, $customSelectors);
            if (isset($scrapedData['image'])) {
                $scrapedData['image'] = $this->fixVendorImages($scrapedData['image']);
            }
            return $scrapedData;
        }
        return null;
    }

    public function runPuppeteer($url, $userId = null)
    {
        $proxy = $this->getProxyConfig($userId);
        $scriptPath = base_path("scraper-engine.js");
        if (!file_exists($scriptPath)) return null;

        $tempFile = storage_path("app/public/temp_" . uniqid() . "_" . rand(1000,9999) . ".html");
        
        $nodeCmd = env('NODE_PATH');
        if (!$nodeCmd) {
            $nodeCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'node' : 'node';
        }

        $command = "$nodeCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " " . escapeshellarg($tempFile) . " " . escapeshellarg($proxy ?? '') . " 2>&1";
        
        Log::info("🔄 Engaging Node Engine...");
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

        // 1. JSON-LD Extraction
        $jsonLdData = $this->extractFromJsonLD($crawler);
        if (!empty($jsonLdData['articleBody']) && strlen($jsonLdData['articleBody']) > 200) {
            $data['body'] = $this->formatText($jsonLdData['articleBody']);
            
            if (empty($data['image']) && !empty($jsonLdData['image'])) {
                $img = $jsonLdData['image'];
                $data['image'] = is_array($img) ? ($img['url'] ?? $img[0] ?? null) : $img;
            }
        }

        // 2. Manual Extraction
        if (empty($data['body'])) {
            $data['body'] = $this->extractBodyManually($crawler, $customSelectors);
        }

        return !empty($data['body']) ? $data : null;
    }

    // ==========================================
    // 🛠️ HELPER FUNCTIONS (Preserved from GitHub)
    // ==========================================

    private function cleanHtml($html) {
        return strip_tags($html, '<p><br><h3><h4><h5><h6><ul><li><b><strong><blockquote><img><a>');
    }

    private function extractBodyManually(Crawler $crawler, $customSelectors)
    {
        $selectors = [
            // User Custom
            $customSelectors['content'] ?? null,

            // Specific Sites
            '.story-element-text', '.article-details-body', '.jw_article_body',
            '.content-details', '.news-article-text', '#news-content',
            '.details-text', '.article-content',

            // Standards
            'div[itemprop="articleBody"]', '.article-details', '#details', '.details', 
            'article', '#content', '.news-content', '.post-content', '.entry-content', 
            '.section-content', '.post-body', '.td-post-content', '.main-content'
        ];
        
        $selectors = array_unique(array_filter($selectors));
        $bestContent = "";
        $maxLength = 0;

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $container = $crawler->filter($selector);
                $this->removeJunkElements($container);

                $text = "";
                $container->filter('p, h3, h4, h5, h6, ul, blockquote, div.content-text')->each(function (Crawler $node) use (&$text) {
                    $tag = $node->nodeName();
                    $rawHtml = trim($node->html());
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
    
    // 🔥 GitHub Version's Image Fix Logic Kept Intact
    private function fixVendorImages($imageUrl)
    {
        if (!$imageUrl) return null;

        if (str_contains($imageUrl, 'npbnews.com') && str_contains($imageUrl, 'cache-images')) {
            $imageUrl = str_replace('cache-images', 'assets', $imageUrl);
            $imageUrl = preg_replace('/resize-[0-9x]+-/', '', $imageUrl);
        }

        if (str_contains($imageUrl, 'jugantor.com') && str_contains($imageUrl, '/social-thumbnail/')) {
            $imageUrl = str_replace('/social-thumbnail/', '/', $imageUrl);
        }

        return $imageUrl;
    }

    private function extractImage(Crawler $crawler, $url)
    {
        $imageUrl = null;
        
        if ($crawler->filter('meta[property="og:image"]')->count() > 0) {
            $imageUrl = $crawler->filter('meta[property="og:image"]')->attr('content');
        }

        if (!$imageUrl) {
            $crawler->filter('img')->each(function (Crawler $node) use (&$imageUrl) {
                if ($imageUrl) return; 

                $src = $node->attr('data-original') ?? $node->attr('data-src') ?? $node->attr('src');
                $width = $node->attr('width');
                
                if ($width && is_numeric($width) && $width < 300) return;

                if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                    $imageUrl = $src;
                }
            });
        }

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
        $garbage = ['শেয়ার করুন', 'Advertisement', 'Subscribe', 'Follow us', 'Read more', 'বিজ্ঞাপন', 'আরো পড়ুন',
		'গুগল নিউজ চ্যানেল ফলো করুন','ফরহাদুজ্জামান ফারুক/আরএআর','এমটিআই', 'ঢাকা পোস্ট','আমার দেশের খবর'
		];
        foreach ($garbage as $g) {
        // mb_strlen ব্যবহার করা হয়েছে যাতে বাংলা ক্যারেক্টার ঠিকমতো কাউন্ট হয়
        if (stripos($text, $g) !== false && mb_strlen($text, 'UTF-8') < 150) {
            return true;
        }
    }
    return false;
	}

    private function isGarbageImage($url) {
        return preg_match('/(logo|icon|svg|avatar|profile|ad-|banner|share|button|facebook|twitter)/i', $url);
    }

    private function formatText($text) {
        return "<p>" . str_replace(["\r\n", "\r", "\n"], "</p><p>", trim($text)) . "</p>";
    }

    // 🔥 ULTRA FEATURE: Real Browser Headers
    private function getRealBrowserHeaders() {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile' => '?0',
            'Sec-Ch-Ua-Platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Connection' => 'keep-alive'
        ];
    }
}