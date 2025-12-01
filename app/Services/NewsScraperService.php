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
    public function scrape($url, $customSelectors = [], $method = 'node')
    {
        Log::info("🕷️ Scraping Started via [{$method}]: $url");

        // ১. পাইথন মেথড চেক
        if ($method === 'python') {
            return $this->runPythonScraper($url);
        }

        $htmlContent = null;

        // ২. HTTP Request
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->getRandomUserAgent(),
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            ])->timeout(20)->get($url);
            
            if ($response->successful()) {
                $htmlContent = $response->body();
            }
        } catch (\Exception $e) {
            Log::warning("HTTP Scrape Failed: " . $e->getMessage());
        }

        // ৩. Puppeteer Fallback
        if (empty($htmlContent) || str_contains($htmlContent, 'Cloudflare') || str_contains($htmlContent, 'Just a moment') || strlen($htmlContent) < 500) {
            Log::info("🔄 Switching to Puppeteer for: $url");
            $htmlContent = $this->runPuppeteer($url);
        }

        if (!$htmlContent) {
            Log::error("❌ All scraping methods failed for: $url");
            return null;
        }

        return $this->processHtml($htmlContent, $url, $customSelectors);
    }

    /**
     * 🛠️ HTML প্রসেসিং ইঞ্জিন
     */
    private function processHtml($html, $url, $customSelectors)
    {
        if (!mb_detect_encoding($html, 'UTF-8', true)) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        $crawler = new Crawler($html);
        $domain = parse_url($url, PHP_URL_HOST);

        $this->cleanGarbage($crawler, $domain);

        $data = [
            'title'      => $this->extractTitle($crawler),
            'image'      => $this->extractImage($crawler, $url),
            'body'       => null,
            'source_url' => $url
        ];

        // JSON-LD ডাটা
        $jsonLdData = $this->extractFromJsonLD($crawler);
        if (!empty($jsonLdData['articleBody']) && strlen($jsonLdData['articleBody']) > 200) {
            $data['body'] = $this->formatText($jsonLdData['articleBody']);
            
            if (empty($data['image']) && !empty($jsonLdData['image'])) {
                $img = $jsonLdData['image'];
                $data['image'] = is_array($img) ? ($img['url'] ?? $img[0] ?? null) : $img;
            }
        }

        if (empty($data['body'])) {
            $data['body'] = $this->extractBodyManually($crawler, $customSelectors, $domain);
        }

        return !empty($data['body']) ? $data : null;
    }

    // --- CLEANING & EXTRACTION ---

    private function cleanGarbage(Crawler $crawler, $domain)
    {
        $junkSelectors = [
            'script', 'style', 'iframe', 'nav', 'header', 'footer', 'form', 
            '.advertisement', '.ads', '.share-buttons', '.meta', '.comments-area', 
            '.related-news', '.most-read', '.sidebar', '.print-section', 
            '.author-section', '.tags', '.social-share', '.breadcrumb', 
            '.more-news', '.top-news', '[class*="popup"]', '[id*="cookie"]', 
            '.caption', '.image-caption'
        ];
        
        if (str_contains($domain, 'kalerkantho')) {
            $junkSelectors = array_merge($junkSelectors, ['.more_news', '.print-hide', '.summery', '.date']);
        }

        $crawler->filter(implode(', ', $junkSelectors))->each(function (Crawler $crawlerNode) {
            $node = $crawlerNode->getNode(0);
            if ($node && $node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        });
    }

    private function extractTitle(Crawler $crawler)
    {
        if ($crawler->filter('h1')->count() > 0) return trim($crawler->filter('h1')->first()->text());
        if ($crawler->filter('title')->count() > 0) return trim($crawler->filter('title')->text());
        return "Untitled News";
    }

    
	// NewsScraperService.php ফাইলের ভেতরে এই ফাংশনটি রিপ্লেস করুন
    
    private function extractImage(Crawler $crawler, $url)
    {
        $imageUrl = null;

        // 🔥 Priority 1: Body Image (আর্টিকেলের ভেতরের ছবি - সবচেয়ে নিরাপদ)
        // আমরা আগে বডি চেক করব, কারণ এখানকার ছবি সাধারণত ইউজার যা দেখে তাই (ক্লিন)
        $crawler->filter('article img, .content-details img, .news-details img, .story-element img, .post-content img')->each(function (Crawler $node) use (&$imageUrl) {
            if (!$imageUrl) {
                // হাই কোয়ালিটি সোর্স অ্যাট্রিবিউট খোঁজা
                $src = $node->attr('data-original') 
                    ?? $node->attr('data-full-url') 
                    ?? $node->attr('data-src') 
                    ?? $node->attr('src');

                // ১. সাইজ চেক (খুব ছোট আইকন বাদ)
                $width = $node->attr('width');
                if ($width && is_numeric($width) && $width < 200) {
                    return; 
                }

                // ২. লোগো বা আইকন ফিল্টার
                if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                    $imageUrl = $src;
                }
            }
        });

        // 🔥 Priority 2: JSON-LD (Fallback - যদি বডিতে কোনো ছবি না থাকে)
        // বডিতে ছবি না পেলে আমরা JSON-LD চেক করব
        if (!$imageUrl) {
            $jsonLdData = $this->extractFromJsonLD($crawler);
            if (!empty($jsonLdData['image'])) {
                $img = $jsonLdData['image'];
                if (is_array($img)) {
                    $imageUrl = $img['url'] ?? $img[0] ?? null;
                } elseif (is_string($img)) {
                    $imageUrl = $img;
                }
            }
        }

        // ৩. URL Clean & Fix
        if ($imageUrl) {
            // রিলেটিভ পাথ ফিক্স
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $parsedUrl = parse_url($url);
                $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $imageUrl = $root . '/' . ltrim($imageUrl, '/');
            }

            // প্যারামিটার রিমুভ (Clean Original Image)
            // image.jpg?width=600 -> image.jpg
            if (str_contains($imageUrl, '?')) {
                $parts = explode('?', $imageUrl);
                // নিশ্চিত হওয়া যে এটি ইমেজ ফাইল
                if (preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $parts[0])) {
                    $imageUrl = $parts[0]; 
                }
            }
        }

        return $imageUrl;
    }

    private function extractBodyManually(Crawler $crawler, $customSelectors, $domain)
    {
        $selectors = [
            'div[itemprop="articleBody"]', '.article-details', '#details', '.details', 
            '.content-details', 'article', '#content', '.news-content', 
            '.story-element-text', '.jw_article_body', '.description', 
            '.post-content', '.entry-content', '.section-content'
        ];

        if (str_contains($domain, 'dhakapost')) array_unshift($selectors, '.section-content article');
        if (!empty($customSelectors['content'])) array_unshift($selectors, $customSelectors['content']);

        $bestContent = "";
        $maxLength = 0;

        foreach ($selectors as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $combinedText = "";
                
                $crawler->filter($selector)->children()->each(function (Crawler $node) use (&$combinedText) {
                    $nodeName = $node->nodeName();
                    if (in_array($nodeName, ['p', 'div', 'h2', 'h3', 'h4', 'span'])) {
                        $text = trim($node->text());
                        $text = $this->cleanSpecificText($text);
                        if (strlen($text) > 20 && !$this->isGarbage($text)) {
                            $combinedText .= $text . "\n\n"; 
                        }
                    }
                });

                if (strlen($combinedText) < 100) {
                    $rawText = $crawler->filter($selector)->html();
                    $rawText = preg_replace('/<br\s*\/?>/i', "\n\n", $rawText);
                    $rawText = strip_tags($rawText); 
                    $combinedText = $this->formatText($rawText);
                }

                if (strlen($combinedText) > $maxLength) {
                    $maxLength = strlen($combinedText);
                    $bestContent = $combinedText;
                }
            }
        }

        return ($maxLength > 100) ? trim($bestContent) : null;
    }

    // --- RUNNERS ---

    public function runPuppeteer($url)
    {
        $tempFile = storage_path("app/public/temp_" . time() . "_" . rand(100,999) . ".html");
        $scriptPath = base_path("scraper-engine.js");
        if (!file_exists($scriptPath)) $scriptPath = base_path("scraper-detail.js");
        
        if (!file_exists($scriptPath)) {
            Log::error("Node.js Scraper script not found!");
            return null;
        }

        $command = "node " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " " . escapeshellarg($tempFile) . " body 2>&1";
        shell_exec($command);
        
        if (file_exists($tempFile)) {
            $htmlContent = file_get_contents($tempFile);
            unlink($tempFile);
            return (strlen($htmlContent) > 500) ? $htmlContent : null;
        }
        return null;
    }

    public function runPythonScraper($url)
    {
        $scriptPath = base_path("scraper.py"); 
        if (!file_exists($scriptPath)) return null;

        $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " 2>&1";
        $output = shell_exec($command);
        $data = json_decode($output, true);

        return (isset($data['body']) && !empty($data['body'])) ? $data : null;
    }

    // --- HELPERS ---

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

    private function cleanSpecificText($text) {
        $patterns = [
            '/(প্রিন্ট|প্রকাশ|আপডেট|সংগৃহীত|অনলাইন)\s*:\s*.*?(এএম|পিএম|AM|PM)/u',
            '/^নিজস্ব প্রতিবেদক.*?\|/u', '/^অনলাইন ডেস্ক.*?\|/u', '/^স্টাফ রিপোর্টার.*?\|/u', '/^ছবি:/u'
        ];
        foreach ($patterns as $pattern) $text = preg_replace($pattern, '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function isGarbage($text) {
        $garbage = ['আরও পড়ুন', 'বিস্তারিত', 'বিজ্ঞাপন', 'Advertisement', 'Click to comment', 'Follow us', 'Google News', 'Share this', 'Latest News', 'সাবস্ক্রাইব করুন'];
        foreach ($garbage as $g) if (stripos($text, $g) !== false) return true;
        return false;
    }

    private function isGarbageImage($url) {
        return preg_match('/(logo|icon|svg|avatar|profile|ad-|banner|share|button)/i', $url);
    }

    private function formatText($text) {
        return trim(preg_replace("/[\r\n]+/", "\n\n", $text));
    }

    private function getRandomUserAgent() {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        ];
        return $agents[array_rand($agents)];
    }
}