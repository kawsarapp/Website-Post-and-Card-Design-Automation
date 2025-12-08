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
        // ১. পাইথন স্ক্র্যাপার কল করা
        $pythonData = $this->runPythonScraper($url);

        if ($pythonData && !empty($pythonData['body'])) {
            Log::info("✅ Python Scraper Successful: $url");
            return $pythonData; 
        }

        // ২. পাইথন ফেইল করলে HTTP Request ট্রাই করা
        Log::info("⚠️ Python failed, trying PHP HTTP fallback...");
        
        $htmlContent = null;
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->getRandomUserAgent(),
            ])->timeout(20)->get($url);
            
            if ($response->successful()) {
                $htmlContent = $response->body();
            }
        } catch (\Exception $e) {
            Log::warning("HTTP Scrape Failed: " . $e->getMessage());
        }

        // ৩. যদি ক্লাউডফ্লেয়ার থাকে বা কন্টেন্ট কম হয় -> Puppeteer
        if (empty($htmlContent) || str_contains($htmlContent, 'Cloudflare') || strlen($htmlContent) < 500) {
            Log::info("🔄 Switching to Puppeteer (Node.js) for: $url");
            $htmlContent = $this->runPuppeteer($url);
        }

        if (!$htmlContent) {
            Log::error("❌ All scraping methods failed for: $url");
            return null;
        }

        // ৪. PHP Parsing Logic
        return $this->processHtml($htmlContent, $url, $customSelectors);
    }

    /**
     * Process HTML Content
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

        // JSON-LD থেকে বের করার চেষ্টা
        $jsonLdData = $this->extractFromJsonLD($crawler);
        if (!empty($jsonLdData['articleBody']) && strlen($jsonLdData['articleBody']) > 200) {
            $data['body'] = $this->formatText($jsonLdData['articleBody']);
            
            if (empty($data['image']) && !empty($jsonLdData['image'])) {
                $img = $jsonLdData['image'];
                $data['image'] = is_array($img) ? ($img['url'] ?? $img[0] ?? null) : $img;
            }
        }

        // ম্যানুয়াল এক্সট্রাকশন (Advanced Logic)
        if (empty($data['body'])) {
            $data['body'] = $this->extractBodyManually($crawler, $customSelectors, $domain);
        }

        return !empty($data['body']) ? $data : null;
    }

    // 🔥 Advanced Body Extraction Method
    private function extractBodyManually(Crawler $crawler, $customSelectors, $domain)
    {
        $selectors = [
            'div[itemprop="articleBody"]', 
            '.article-details', '#details', '.details', 
            '.content-details', 'article', '#content', 
            '.news-content', '.story-element-text', 
            '.jw_article_body', '.description', 
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
                
                // ১. গার্বেজ ক্লিনিং
                $this->removeJunkElements($container);

                $text = "";
                $stopProcessing = false;

                // ২. প্যারাগ্রাফ ও হেডিং প্রসেসিং
                $container->filter('p, h3, h4, h5, h6, blockquote, ul li')->each(function (Crawler $node) use (&$text, &$stopProcessing) {
                    
                    if ($stopProcessing) return;

                    $tag = $node->nodeName();
                    $rawText = trim($node->text());

                    if (strlen($rawText) < 3) return;

                    // গার্বেজ হলে বাদ
                    if ($this->isGarbageText($rawText)) return;

                    // নিউজ শেষ হওয়ার সিগন্যাল
                    if ($this->isEndSignal($rawText)) {
                        $stopProcessing = true;
                        return;
                    }

                    // ফরম্যাটিং
                    if (in_array($tag, ['h3', 'h4', 'h5', 'h6'])) {
                        $text .= "<h4>" . $rawText . "</h4>\n";
                    } 
                    elseif ($tag === 'blockquote') {
                        $text .= "<blockquote>" . $rawText . "</blockquote>\n";
                    }
                    elseif ($tag === 'li') {
                        $text .= "• " . $rawText . "<br>\n";
                    }
                    else {
                        $text .= "<p>" . $rawText . "</p>\n";
                    }
                });

                // অন্তত ৩টি প্যারাগ্রাফ বা ৩০০ ক্যারেক্টার হতে হবে
                if (strlen($text) > $maxLength && strlen($text) > 300) {
                    $maxLength = strlen($text);
                    $bestContent = $text;
                }
            }
        }

        return !empty($bestContent) ? trim($bestContent) : null;
    }

    // 🔥 গার্বেজ রিমুভার
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

    // 🔥 নিউজ শেষ হওয়ার সিগন্যাল
    private function isEndSignal($text)
    {
        $signals = [
            'বাংলানিউজটোয়েন্টিফোর', 'bdnews24', 'প্রথম আলো', 'Jugantor', 'Daily Star',
            'স্বত্ব সংরক্ষিত', 'Copyright', '©', 'All rights reserved',
            'সম্পাদক ও প্রকাশক', 'Email:', 'Phone:', 'Contact:',
            'সামাজিক মাধ্যমে ফলো করুন', 'Join our Whatsapp', 'Google News',
            'আরো পড়ুন', 'আরও পড়ুন', 'আরও খবর', 'অন্যান্য খবর',
            'সম্পর্কিত খবর', 'এই বিভাগের আরো খবর', 'টপ নিউজ',
            'জনপ্রিয় সংবাদ', 'সর্বশেষ', 'আজকের তাজা খবর',
            'Read more', 'Also read', 'Related News', 'More News',
            'Next Story', 'Read Next', 'You may also like'
        ];

        foreach ($signals as $signal) {
            if (stripos($text, $signal) !== false) {
                // শর্ত ১: লাইন ছোট হলে
                if (strlen($text) < 150) return true;
                // শর্ত ২: লাইনের শুরুতে থাকলে
                if (stripos($text, $signal) === 0) return true;
            }
        }
        return false;
    }

    // --- HELPERS ---

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

    private function extractImage(Crawler $crawler, $url)
    {
        $imageUrl = null;
        $crawler->filter('img')->each(function (Crawler $node) use (&$imageUrl) {
            if (!$imageUrl) {
                $src = $node->attr('data-original') 
                    ?? $node->attr('data-full-url') 
                    ?? $node->attr('data-src') 
                    ?? $node->attr('src');

                $width = $node->attr('width');
                if ($width && is_numeric($width) && $width < 300) return; 

                if ($src && strlen($src) > 20 && !$this->isGarbageImage($src)) {
                    $imageUrl = $src;
                }
            }
        });
        
        if ($imageUrl) {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $parsedUrl = parse_url($url);
                $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                $imageUrl = $root . '/' . ltrim($imageUrl, '/');
            }
            if (str_contains($imageUrl, '?')) {
                $parts = explode('?', $imageUrl);
                if (preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $parts[0])) {
                    $imageUrl = $parts[0]; 
                }
            }
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
        $garbage = ['শেয়ার করুন', 'Advertisement', 'Subscribe', 'Follow us'];
        foreach ($garbage as $g) {
            if (stripos($text, $g) !== false && strlen($text) < 50) return true;
        }
        return false;
    }

    private function isGarbageImage($url) {
        return preg_match('/(logo|icon|svg|avatar|profile|ad-|banner|share|button)/i', $url);
    }

    private function formatText($text) {
        return "<p>" . str_replace("\n", "</p><p>", trim($text)) . "</p>";
    }

    private function getRandomUserAgent() {
        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    }

    // --- Python & Node.js Runners ---
    
    public function runPythonScraper($url)
    {
        $scriptPath = base_path("scraper.py"); 
        if (!file_exists($scriptPath)) return null;

        $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        if (file_exists(base_path('venv/bin/python'))) $pythonCmd = base_path('venv/bin/python');

        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " 2>&1";
        $output = shell_exec($command);
        
        $data = json_decode($output, true);
        return (json_last_error() === JSON_ERROR_NONE && !empty($data['body'])) ? $data : null;
    }

    public function runPuppeteer($url)
    {
        $tempFile = storage_path("app/public/temp_" . time() . "_" . rand(100,999) . ".html");
        $scriptPath = base_path("scraper-engine.js");
        
        if (!file_exists($scriptPath)) return null;

        $command = "node " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " " . escapeshellarg($tempFile) . " 2>&1";
        shell_exec($command);
        
        if (file_exists($tempFile)) {
            $htmlContent = file_get_contents($tempFile);
            unlink($tempFile);
            return (strlen($htmlContent) > 500) ? $htmlContent : null;
        }
        return null;
    }
}