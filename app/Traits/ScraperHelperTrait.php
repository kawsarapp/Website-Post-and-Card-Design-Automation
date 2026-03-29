<?php

namespace App\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait ScraperHelperTrait
{
    private function cleanHtml($html) {
        return strip_tags($html, '<p><br><h3><h4><h5><h6><ul><li><b><strong><blockquote><img><a>');
    }

    // 🔥 SMART TITLE CLEANER (ব্র্যান্ডিং রিমুভার - EXTENDED)
    public function cleanTitle($title)
    {
        if (!$title) return null;

        // 🕒 Timestamp prefix stripping (ekhon.tv, some others add "X মিনিট আগে" before title)
        $title = preg_replace('/^\d+\s*(মিনিট|ঘণ্টা|সেকেন্ড|ঘন্টা)\s*আগে/u', '', $title);
        $title = trim($title);

        // কমন সেপারেটর যেগুলো দিয়ে ওয়েবসাইটের নাম যুক্ত করা থাকে
        $separators = [' | ', ' - ', ' – ', ' — ', ' :: ', ' : '];
        
        foreach ($separators as $sep) {
            if (str_contains($title, $sep)) {
                $parts = explode($sep, $title);
                $lastPart = trim(end($parts));
                
                // ওয়েবসাইটের নাম সাধারণত ছোট হয়। 
                // যদি শেষের অংশ ২৫ অক্ষরের ছোট হয়, তবে সেটা কেটে বাদ দেব।
                if (mb_strlen($lastPart, 'UTF-8') <= 25) {
                    array_pop($parts);
                    $title = implode($sep, $parts);
                }
            }
        }

        // এক্সট্রা সেফটি (আরও পপুলার সাইটের নাম যোগ করা হলো)
        $garbageBrands = [
            '|NPB', '|NTV', '|RTV', '|Dhaka Post', '|Jugantor', 
            '|Prothom Alo', '|Somoy TV', '|Kaler Kantho', '|Daily Star', 
            '|Bangla Tribune', '|Ittefaq', '|Jamuna TV', '|Barta24', 
            '|Jagonews24', '|Samakal', '|Ajker Patrika', ' - প্রথম আলো'
        ];
        $title = str_ireplace($garbageBrands, '', $title);

        return trim($title);
    }

    // 🔥 POWER UP: Extensive Junk Element Removal
    private function removeJunkElements(Crawler $container)
    {
        $junkSelectors = [
            '.related-news', '.read-more', '.more-news', '.also-read',
            '.advertisement', '.ads', '.ad-box', '.social-share', 
            '.share-buttons', '.author-bio', '.tags', '.meta', 
            '.print-only', '.video-container', '.embed-code',
            '[class*="related"]', '[id*="related"]',
            '[class*="taboola"]', '[id*="taboola"]',
            '.author-info', '.newsletter', '.comments', '.comment-list',
            '.breadcrumb', '.post-meta', '.social-links', 'style', 'script', 'noscript',
            '.caption', 'figcaption', '.source-link', '.news-update', '.google-news',
            '.ad-slot', '.hidden', '.d-none', '.jwplayer', '.popup' // New Added
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
        $junkSelectors = ['script', 'style', 'iframe', 'nav', 'header', 'footer', 'form', '.advertisement', '.ads', '.share-buttons', '.meta', '.comments-area', '.sidebar', '.menu'];
        $crawler->filter(implode(', ', $junkSelectors))->each(function (Crawler $node) {
            if ($node->getNode(0)->parentNode) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0));
            }
        });
    }

    //start fixVendorImages
    private function fixVendorImages($imageUrl)
    {
        if (!$imageUrl) return null;

        if (str_contains($imageUrl, 'jugantor.com') && str_contains($imageUrl, '/social-thumbnail/')) {
            $imageUrl = str_replace('/social-thumbnail/', '/', $imageUrl);
        }

        if (str_contains($imageUrl, 'npbnews.com') && str_contains($imageUrl, 'cache-images')) {
            $imageUrl = str_replace('cache-images', 'assets', $imageUrl);
            $imageUrl = preg_replace('/resize-[0-9x]+-/', '', $imageUrl);
        }

        if (str_contains($imageUrl, 'dhakapost.com')) {
            if (str_contains($imageUrl, '/og-image/')) {
                $imageUrl = str_replace('assets.dhakapost.com/og-image/', 'cdn.dhakapost.com/', $imageUrl);
            }
            $imageUrl = strtok($imageUrl, '?'); 
        }

        if (str_contains($imageUrl, 'ntvbd.com') || str_contains($imageUrl, 'thedailystar.net')) {
            if (str_contains($imageUrl, '/styles/')) {
                $imageUrl = preg_replace('/\/styles\/[^\/]+\/public\//', '/', $imageUrl);
            }
            $imageUrl = strtok($imageUrl, '?'); 
        }

        if (str_contains($imageUrl, 'bbc.co.uk') || str_contains($imageUrl, 'bbci.co.uk')) {
            if (str_contains($imageUrl, '/branded_bengali/')) {
                $imageUrl = preg_replace('/news\/\d+\/branded_bengali/', 'ace/ws/800/cpsprodpb', $imageUrl);
            }
        }

        if (str_contains($imageUrl, 'rtvonline.com') || str_contains($imageUrl, 'kalerkantho.com') || str_contains($imageUrl, 'jamuna.tv') || str_contains($imageUrl, 'samakal.com')) {
            $imageUrl = strtok($imageUrl, '?');
        }

        // 🔥 NEW: Generic resolution limiter remover for ALL sites (e.g. ?w=300, &height=150)
        $imageUrl = preg_replace('/\?(w|h|width|height)=\d+&?.*/i', '', $imageUrl);

        return trim($imageUrl);
    }
    //end fixVendorImages

    // 🔥 POWER UP: Strict Text Filtering (EXTENDED)
    private function isGarbageText($text) {
        $garbage = [
            'শেয়ার করুন', 'Advertisement', 'Subscribe', 'Follow us', 'Read more', 
            'বিজ্ঞাপন', 'আরো পড়ুন', 'আরও পড়ুন', 'গুগল নিউজ', 'টেলিগ্রাম চ্যানেল', 
            'হোয়াটসঅ্যাপ', 'ফেসবুক পেজ', 'ইউটিউব চ্যানেল', 'টুইটার', 'ভিডিওটি দেখতে', 
            'ছবি: সংগৃহীত', 'সূত্র:', 'আমাদের ফলো করুন', 'এমটিআই', 'ঢাকা পোস্ট', 'আমার দেশের খবর',
            'বিস্তারিত আসছে...', 'অ্যাপ ডাউনলোড করুন', 'Page Follow করুন', 'বিস্তারিত জানতে', 'ক্লিক করুন', 'আরও খবর:'
        ];
        
        foreach ($garbage as $g) {
            if (stripos($text, $g) !== false && mb_strlen($text, 'UTF-8') < 200) {
                return true;
            }
        }
        return false;
    }

    // 🔥 POWER UP: Advanced Branding Image Filtering (EXTENDED)
    public function isGarbageImage($url) {
        // লোগো, আইকন, ডিফল্ট, সোশ্যাল ব্র্যান্ডিং এবং সাইজ ইমেজ ব্লক করা হলো
        return preg_match('/(logo|icon|\.svg|avatar|profile|\/ad-|banner|share_button|share-icon|facebook|twitter|whatsapp|placeholder|default-image|default_image|lazy|blank|spinner|thumbs|300x250|branding|og-image|og_image|base64|\.gif)/i', $url);
    }

    // 🔥 FORMAT TEXT (IMPROVED)
    private function formatText($text) {
        // একাধিক লাইনব্রেক থাকলে সেগুলোকে সুন্দর করে <p> ট্যাগ দিয়ে সাজাবে
        $text = preg_replace('/(\r\n|\r|\n)+/', "</p><p>", trim($text));
        $text = "<p>" . $text . "</p>";
        // ফালতু ফাঁকা প্যারাগ্রাফ মুছে ফেলবে
        $text = str_replace('<p></p>', '', $text); 
        return $text;
    }

    // 🔥 USER AGENTS (MODERNIZED)
    private function getRealBrowserHeaders() {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0'
        ];

        return [
            'User-Agent' => $userAgents[array_rand($userAgents)],
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'bn-BD,bn;q=0.9,en-US;q=0.8,en;q=0.7',
            'Upgrade-Insecure-Requests' => '1',
            'Cache-Control' => 'max-age=0',
            'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="121", "Google Chrome";v="121"',
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