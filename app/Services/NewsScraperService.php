<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsScraperService
{
    /**
     * Main Scrape Method
     * এই মেথডটি ৩টি ধাপে ডাটা আনার চেষ্টা করবে।
     */
    public function scrape($url, $customSelectors = [])
    {
        // --------------------------------------------------------
        // 1️⃣ STEP 1: Python Scraper (Ultimate Fast & Stealthy)
        // --------------------------------------------------------
        // পাইথন স্ক্রিপ্ট এখন সরাসরি ক্লিন JSON ডাটা রিটার্ন করে।
        $pythonData = $this->runPythonScraper($url);

        if ($pythonData && !empty($pythonData['body'])) {
            Log::info("✅ Python Scraper Successful: $url");
            return [
                'title'      => $pythonData['title'] ?? null,
                'image'      => $pythonData['image'] ?? null,
                'body'       => $pythonData['body'], // পাইথন নিজেই HTML ফরম্যাট করে দেয়
                'source_url' => $url
            ];
        }

        Log::info("⚠️ Python failed/blocked, trying PHP HTTP fallback...");

        // --------------------------------------------------------
        // 2️⃣ STEP 2: Direct PHP HTTP Request (Native)
        // --------------------------------------------------------
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

        // --------------------------------------------------------
        // 3️⃣ STEP 3: Puppeteer Node.js (Heavy & Powerful)
        // --------------------------------------------------------
        // যদি কন্টেন্ট কম থাকে বা ক্লাউডফ্লেয়ার (Just a moment) ডিটেক্ট হয়
        if (empty($htmlContent) || str_contains($htmlContent, 'Just a moment') || strlen($htmlContent) < 600) {
            Log::info("🔄 Switching to Puppeteer (Ultimate Mode) for: $url");

            // ২ বার চেষ্টা করবে (Retry Logic)
            for ($j = 0; $j < 2; $j++) {
                $htmlContent = $this->runPuppeteer($url);
                if ($htmlContent && strlen($htmlContent) > 1000) break;
                sleep(2);
            }
        }

        // --------------------------------------------------------
        // 4️⃣ FINAL CHECK
        // --------------------------------------------------------
        if (!$htmlContent || strlen($htmlContent) < 500) {
            Log::error("❌ All scraping methods failed for: $url");
            return null;
        }

        // --------------------------------------------------------
        // 5️⃣ PROCESS HTML (Fallback Parser)
        // --------------------------------------------------------
        // যদি Python ফেইল করে এবং PHP/Node.js দিয়ে HTML আসে, তখন এটি প্রসেস করবে।
        return $this->processHtml($htmlContent, $url, $customSelectors);
    }

    /**
     * 🔥 Run the Advanced Python Scraper
     */
    public function runPythonScraper($url)
    {
        $scriptPath = base_path("scraper.py"); 
        
        if (!file_exists($scriptPath)) return null;

        // .env থেকে পাথ নিবে, না পেলে OS ডিটেক্ট করবে
        $pythonCmd = env('PYTHON_PATH'); 

        if (!$pythonCmd) {
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        }

        // 2>&1 দিয়ে এরর সহ ক্যাপচার করা হচ্ছে
        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " 2>&1";
        $output = shell_exec($command);
        
        $data = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log::warning("Python JSON Error: " . substr($output, 0, 150)); // Debug only
            return null;
        }

        return (isset($data['body']) && !empty($data['body'])) ? $data : null;
    }

    /**
     * 🔥 Run the Ultimate Node.js Scraper
     */
    public function runPuppeteer($url)
    {
        $scriptPath = base_path("scraper-engine.js");
        if (!file_exists($scriptPath)) return null;

        // ইউনিক টেম্প ফাইল (Windows/Linux Safe)
        $tempFile = storage_path("app/public/temp_" . uniqid() . "_" . rand(1000,9999) . ".html");

        $nodeCmd = env('NODE_PATH');
        if (!$nodeCmd) {
            $nodeCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'node' : 'node'; // Linux এ সাধারণত /usr/bin/node লাগে
            if ($nodeCmd === 'node' && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $nodeCmd = trim(shell_exec('which node') ?: 'node');
            }
        }

        $command = "$nodeCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " " . escapeshellarg($tempFile) . " 2>&1";
        shell_exec($command);
        
        $htmlContent = null;
        if (file_exists($tempFile)) {
            $htmlContent = file_get_contents($tempFile);
            unlink($tempFile); // কাজ শেষে ডিলিট
        }
        
        return (strlen($htmlContent) > 500) ? $htmlContent : null;
    }

    /**
     * 🧠 HTML Processor (The Brain of PHP Fallback)
     */
    private function processHtml($html, $url, $customSelectors)
    {
        // বাংলা ফন্ট যাতে না ভাঙ্গে (UTF-8 Force)
        if (!mb_detect_encoding($html, 'UTF-8', true)) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        $crawler = new Crawler($html);
        $domain = parse_url($url, PHP_URL_HOST);

        // ১. গার্বেজ ক্লিনিং (স্ক্রিপ্ট, স্টাইল, অ্যাডস)
        $this->cleanGarbage($crawler);

        $data = [
            'title'      => $this->extractTitle($crawler),
            'image'      => $this->extractImage($crawler, $url),
            'body'       => null,
            'source_url' => $url
        ];

        // ২. JSON-LD চেক (গুগল নিউজের ফরম্যাট) - এটা সবচেয়ে নির্ভুল
        $jsonLdData = $this->extractFromJsonLD($crawler);
        if (!empty($jsonLdData['articleBody']) && strlen($jsonLdData['articleBody']) > 200) {
            $data['body'] = $this->formatText($jsonLdData['articleBody']);
            
            // JSON-LD তে ইমেজ থাকলে সেটা নিবে (High Priority)
            if (empty($data['image']) && !empty($jsonLdData['image'])) {
                $img = $jsonLdData['image'];
                $data['image'] = is_array($img) ? ($img['url'] ?? $img[0] ?? null) : $img;
            }
        }

        // ৩. ম্যানুয়াল এক্সট্রাকশন (যদি JSON-LD ফেইল করে)
        if (empty($data['body'])) {
            $data['body'] = $this->extractBodyManually($crawler, $customSelectors);
        }

        return !empty($data['body']) ? $data : null;
    }

    // ==========================================
    // 🛠️ HELPER FUNCTIONS (Logic Core)
    // ==========================================

    private function extractBodyManually(Crawler $crawler, $customSelectors)
    {
        // কমন সিলেক্টর লিস্ট
        $selectors = [
            'div[itemprop="articleBody"]', '.article-details', '#details', '.details', 
            '.content-details', 'article', '#content', '.news-content', 
            '.story-element-text', '.jw_article_body', '.description', 
            '.post-content', '.entry-content', '.section-content',
            '.post-body', '.td-post-content', '.main-content'
        ];

        // ইউজার যদি কাস্টম সিলেক্টর দেয়, সেটা সবার আগে চেক করবে
        if (!empty($customSelectors['content'])) {
            array_unshift($selectors, $customSelectors['content']);
        }

        $bestContent = "";
        $maxLength = 0;

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $container = $crawler->filter($selector);
                
                // কন্টেইনারের ভেতর থেকেও গার্বেজ রিমুভ
                $this->removeJunkElements($container);

                $text = "";
                $stopProcessing = false;

                $container->filter('p, h3, h4, h5, h6, ul li, blockquote')->each(function (Crawler $node) use (&$text, &$stopProcessing) {
                    if ($stopProcessing) return;

                    $tag = $node->nodeName();
                    $rawText = trim($node->text());

                    // ছোট লাইন বা গার্বেজ টেক্সট বাদ
                    if (strlen($rawText) < 5 || $this->isGarbageText($rawText)) return;

                    // নিউজ শেষ হওয়ার সিগন্যাল (যেমন: "আরো পড়ুন", "কপিরাইট")
                    if ($this->isEndSignal($rawText)) {
                        $stopProcessing = true;
                        return;
                    }

                    // ফরম্যাটিং
                    if (in_array($tag, ['h3', 'h4', 'h5', 'h6'])) {
                        $text .= "<h4>" . $rawText . "</h4>\n";
                    } elseif ($tag === 'li') {
                        $text .= "• " . $rawText . "<br>\n";
                    } elseif ($tag === 'blockquote') {
                        $text .= "<blockquote>" . $rawText . "</blockquote>\n";
                    } else {
                        $text .= "<p>" . $rawText . "</p>\n";
                    }
                });

                // সবচেয়ে বড় কন্টেন্টটি সেভ রাখবে
                if (strlen($text) > $maxLength && strlen($text) > 300) {
                    $maxLength = strlen($text);
                    $bestContent = $text;
                }
            }
        }
        return !empty($bestContent) ? trim($bestContent) : null;
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
        // সম্পূর্ণ পেজ থেকে বড় গার্বেজ রিমুভ
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
            if ($imageUrl) return; // ইতিমধ্যে ইমেজ পেলে আর লুপ ঘোরার দরকার নেই

            $src = $node->attr('data-original') 
                ?? $node->attr('data-src') 
                ?? $node->attr('src');
            
            // ইমেজের সাইজ চেক (ছোট আইকন বাদ)
            $width = $node->attr('width');
            if ($width && is_numeric($width) && $width < 300) return;

            if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                $imageUrl = $src;
            }
        });

        // রিলেটিভ পাথ ফিক্স (যেমন: /images/news.jpg -> https://site.com/images/news.jpg)
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
                // গ্রাফ ফরম্যাট হ্যান্ডেলিং
                if (isset($json['@graph'])) {
                    foreach ($json['@graph'] as $item) {
                        if (isset($item['articleBody'])) return $item;
                    }
                }
            }
        } catch (\Exception $e) {}
        return null;
    }

    // গার্বেজ টেক্সট ফিল্টার (বাংলা ও ইংরেজি)
    private function isGarbageText($text) {
        $garbage = ['শেয়ার করুন', 'Advertisement', 'Subscribe', 'Follow us', 'Read more', 'বিজ্ঞাপন', 'আরো পড়ুন'];
        foreach ($garbage as $g) {
            if (stripos($text, $g) !== false && strlen($text) < 50) return true;
        }
        return false;
    }

    // নিউজের শেষ চিহ্নিত করা (যাতে কপিরাইট টেক্সট না আসে)
    private function isEndSignal($text) {
        $signals = [
            'All rights reserved', 'Copyright', '©', 'সম্পাদক ও প্রকাশক', 
            'Contact us', 'Email:', 'Phone:', 'আরো পড়ুন', 'Related News'
        ];
        foreach ($signals as $signal) {
            if (stripos($text, $signal) === 0) return true; // লাইনের শুরুতে থাকলে
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