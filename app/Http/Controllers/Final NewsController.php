<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class NewsController extends Controller
{
    private $wpCategories = [
        'Politics'      => 14,
        'International' => 37,
        'Sports'        => 15,
        'Entertainment' => 11,
        'Technology'    => 1,
        'Economy'       => 1,
        'Bangladesh'    => 14,
        'Crime'         => 1,
        'Others'        => 1
    ];

    public function index()
    {
        $newsItems = NewsItem::with('website')->orderBy('published_at', 'desc')->paginate(20);
        return view('news.index', compact('newsItems'));
    }

    public function studio($id)
    {
        $newsItem = NewsItem::with('website')->findOrFail($id);
        return view('news.studio', compact('newsItem'));
    }

    public function proxyImage(Request $request)
    {
        $url = $request->query('url');
        if (!$url) abort(404);
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(10)->get($url);
            if ($response->failed()) abort(404);
            return response($response->body())->header('Content-Type', $response->header('Content-Type'));
        } catch (\Exception $e) { abort(404); }
    }

    private function cleanUtf8($string) {
        if (is_string($string)) return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return $string;
    }

    public function postToWordPress($id)
    {
        set_time_limit(300); 
        $news = NewsItem::with('website')->findOrFail($id);

        if ($news->is_posted) {
            return back()->with('error', 'এই নিউজটি ইতিমধ্যে পোস্ট করা হয়েছে!');
        }

        $processLog = [];

        try {
            // ১. কন্টেন্ট ফেচ
            if (empty($news->content) || strlen($news->content) < 150) {
                Log::info("Starting scrape for: " . $news->original_link);

                $fullContent = $this->fetchFullContent($news->original_link, false);
                
                if (!$fullContent) {
                    $processLog[] = "⚠️ সাধারণ ফেচ ব্যর্থ, Stealth Mode চালু হচ্ছে...";
                    $fullContent = $this->fetchFullContent($news->original_link, true);
                }

                if ($fullContent) {
                    $news->update(['content' => $this->cleanUtf8($fullContent)]);
                    $processLog[] = "✅ কন্টেন্ট স্ক্র্যাপ সফল";
                } else {
                    Log::error("Scrape Failed for ID: " . $id);
                    return back()->with('error', 'নিউজটি স্ক্র্যাপ করা যায়নি। লগ চেক করুন।');
                }
            }

            // ২. রিরাইট (DeepSeek)
            $inputText = "HEADLINE: " . $news->title . "\n\nBODY:\n" . strip_tags($news->content);
            $cleanText = $this->cleanUtf8($inputText);
            
            Log::info("Sending to DeepSeek...");

            $aiResponse = $this->rewriteWithDeepSeek($cleanText);
            
            if (!$aiResponse) {
                $processLog[] = "⚠️ AI ফেইল, অরিজিনাল কন্টেন্ট ব্যবহার হচ্ছে";
                $rewrittenContent = $news->content;
                $detectedCategory = 'Others';
            } else {
                $rewrittenContent = $aiResponse['content'];
                $detectedCategory = $aiResponse['category'];
                $processLog[] = "✅ DeepSeek রিরাইট সফল";
            }
            
            $categoryId = $this->wpCategories[$detectedCategory] ?? $this->wpCategories['Others'];

            // ৩. ইমেজ হ্যান্ডলিং
            $imageId = null;
            if ($news->thumbnail_url) {
                $uploadResult = $this->uploadImageToWP($news->thumbnail_url, $news->title);
                if ($uploadResult['success']) {
                    $imageId = $uploadResult['id'];
                } else {
                    $rewrittenContent = '<img src="' . $news->thumbnail_url . '" style="width:100%; margin-bottom:15px; border-radius:5px;"><br>' . $rewrittenContent;
                }
            }

            // ৪. পোস্ট পাবলিশ
            $wpBaseUrl = rtrim(env('WP_SITE_URL'), '/');
            $credit = '<hr><p style="text-align:center; font-size:13px; color:#888;">তথ্যসূত্র: অনলাইন ডেস্ক</p>';

            $postData = [
                'title'   => $this->cleanUtf8($news->title),
                'content' => $this->cleanUtf8($rewrittenContent . $credit),
                'status'  => 'publish',
                'featured_media' => (int) $imageId, 
                'categories' => [$categoryId], 
            ];

            $wpResponse = Http::withBasicAuth(env('WP_USERNAME'), str_replace(' ', '', env('WP_APP_PASSWORD')))
                ->post($wpBaseUrl . '/wp-json/wp/v2/posts', $postData);

            if ($wpResponse->successful()) {
                $wpPost = $wpResponse->json();
                $news->update(['rewritten_content' => $rewrittenContent, 'is_posted' => true, 'wp_post_id' => $wpPost['id']]);
                return back()->with('success', "পোস্ট পাবলিশ হয়েছে! ID: " . $wpPost['id']);
            } else {
                return back()->with('error', 'WP Error: ' . $wpResponse->body());
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function uploadImageToWP($imageUrl, $title)
    {
        try {
            $imageUrl = preg_replace('/\?.*/', '', $imageUrl); 
            $response = Http::withOptions(['verify' => false])->withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(30)->get($imageUrl);
            if ($response->failed()) return ['success' => false, 'error' => 'DL Fail'];
            $imageContent = $response->body();
            $contentType = $response->header('Content-Type') ?: 'image/jpeg';
            $fileName = 'news-' . time() . '.jpg';
            $wpBaseUrl = rtrim(env('WP_SITE_URL'), '/');
            
            $wpResponse = Http::withBasicAuth(env('WP_USERNAME'), str_replace(' ', '', env('WP_APP_PASSWORD')))
                ->withHeaders(['Content-Type' => $contentType, 'Content-Disposition' => 'attachment; filename="' . $fileName . '"'])
                ->withBody($imageContent, $contentType)
                ->post($wpBaseUrl . '/wp-json/wp/v2/media');

            if ($wpResponse->successful()) {
                $mediaId = $wpResponse->json()['id'];
                try {
                    Http::withBasicAuth(env('WP_USERNAME'), str_replace(' ', '', env('WP_APP_PASSWORD')))
                        ->post($wpBaseUrl . '/wp-json/wp/v2/media/' . $mediaId, ['alt_text' => $title, 'title' => $title]);
                } catch (\Exception $e) {}
                return ['success' => true, 'id' => $mediaId];
            }
            return ['success' => false, 'error' => 'WP Reject'];
        } catch (\Exception $e) { return ['success' => false, 'error' => 'Ex']; }
    }

    // ✅✅✅ SMART SCRAPER: FIXING CONTENT MERGING ✅✅✅
    private function fetchFullContent($url, $usePuppeteer = false)
    {
        try {
            $htmlContent = null;
            
            if (!$usePuppeteer) {
                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])->timeout(15)->get($url);
                    if ($response->successful()) $htmlContent = $response->body();
                } catch (\Exception $e) {}
            }

            if (empty($htmlContent)) {
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
    const browser = await puppeteer.launch({headless: "new", args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-blink-features=AutomationControlled', '--window-size=1920,1080']});
    const page = await browser.newPage();
    await page.setExtraHTTPHeaders({'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'});
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await new Promise(r => setTimeout(r, 6000)); 
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
                 if (file_exists($tempFile)) { $htmlContent = file_get_contents($tempFile); unlink($tempFile); }
            }

            if (!$htmlContent) return null;
            if (!str_contains($htmlContent, 'charset')) $htmlContent = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $htmlContent;
            
            $crawler = new Crawler($htmlContent);
            
            // ✅ 1. AGGRESSIVE GARBAGE REMOVAL (সব আবর্জনা সাফ)
            // related-news, most-read, sidebar, comments ইত্যাদি ক্লাস রিমুভ করা
            $crawler->filter('script, style, .advertisement, .ads, .share-buttons, .meta, header, footer, .comments-area, .related-news, .most-read, .sidebar, .widget, .more-news, .suggested-news, .print-section, .tags')->each(function (Crawler $crawler) {
                foreach ($crawler as $node) {
                    if ($node->parentNode) $node->parentNode->removeChild($node);
                }
            });

            // ✅ 2. SMART SELECTOR LOGIC (সবচেয়ে বড় কন্টেন্ট ব্লক খোঁজা)
            // আমরা প্রথম পাওয়া কন্টেন্ট রিটার্ন করব না। আমরা দেখব কোন কন্টেন্টে সবচেয়ে বেশি লেখা আছে।
            // এতে সাইডবারের ছোট নিউজ আসা বন্ধ হবে।
            
            $selectors = [
                '#details', '.details', '.article-details', 'div[itemprop="articleBody"]',
                'article', '#content', '.content-details', '.news-details', '.news-content'
            ];

            $bestContent = "";
            $maxLength = 0;

            foreach ($selectors as $selector) {
                if ($crawler->filter($selector)->count() > 0) {
                    $combinedText = "";
                    
                    // শুধু <p> ট্যাগগুলো নিন
                    $crawler->filter($selector . ' p')->each(function (Crawler $node) use (&$combinedText) {
                        $text = trim($node->text());
                        // প্যারাগ্রাফটি অবশ্যই বাংলা হতে হবে এবং নির্দিষ্ট দৈর্ঘ্যের হতে হবে
                        if (strlen($text) > 20 && !$this->isGarbage($text)) {
                            $combinedText .= "<p>" . $text . "</p>";
                        }
                    });

                    // যদি p ট্যাগ না পাওয়া যায়, পুরো টেক্সট নিন
                    if (strlen($combinedText) < 50) {
                        $rawText = trim($crawler->filter($selector)->html());
                        $rawText = strip_tags($rawText, '<p><br>');
                        if (strlen($rawText) > 50) $combinedText = $rawText;
                    }

                    // তুলনা: যেটা সবচেয়ে বড়, সেটাই আসল নিউজ
                    if (strlen($combinedText) > $maxLength) {
                        $maxLength = strlen($combinedText);
                        $bestContent = $combinedText;
                    }
                }
            }

            if ($maxLength > 150) {
                return $bestContent;
            }

            // ✅ 3. LAST RESORT (Universal Fallback - Only large blocks)
            // যদি কোনো সিলেক্টর কাজ না করে, বডির ভেতর সবচেয়ে বড় প্যারাগ্রাফের গ্রুপটি খুঁজে বের করো
            $universalText = "";
            $crawler->filter('body p')->each(function (Crawler $node) use (&$universalText) {
                $text = trim($node->text());
                // শর্ত: প্যারাগ্রাফটি অবশ্যই নিউজ হতে হবে (লিংক লিস্ট বা মেনু নয়)
                if (strlen($text) > 60 && !$this->isGarbage($text)) {
                    $universalText .= "<p>" . $text . "</p>";
                }
            });

            if (strlen($universalText) > 200) return $universalText;

            return null;
        } catch (\Exception $e) { 
            Log::error("Fetch Exception: " . $e->getMessage());
            return null; 
        }
    }

    private function isGarbage($text) {
        $garbage = ['আরও পড়ুন', 'বিস্তারিত', 'বিজ্ঞাপন', 'Advertisement', 'Click to comment', 'Follow us', 'Google News', 'Share this', 'Read more', 'Latest News', 'Most Popular', 'Trending'];
        foreach ($garbage as $g) {
            if (stripos($text, $g) !== false) return true;
        }
        return false;
    }

    private function rewriteWithDeepSeek($text)
    {
        $apiKey = env('DEEPSEEK_API_KEY');
        if (!$apiKey) return null;

        $url = "https://api.deepseek.com/chat/completions";
        $text = mb_substr($text, 0, 5000, 'UTF-8'); 

        $systemPrompt = <<<EOT
You are a Senior Editor.
**TASK:** Paraphrase the news article professionally.

**STRICT LANGUAGE RULE:**
1. **DETECT LANGUAGE:** If input is BENGALI -> Output BENGALI. If input is ENGLISH -> Output ENGLISH.
2. **NO TRANSLATION.** Maintain the original language.

**OTHER RULES:**
- **NO SUMMARIZATION:** Keep the same paragraph count.
- **QUOTES:** Keep direct speech 100% UNCHANGED.
- **FORMAT:** Use HTML `<p>` tags. Use `<strong>` for key entities.
- **OUTPUT JSON:** { "category": "CategoryName", "content": "<p>...</p>" }
EOT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($url, [
                "model" => "deepseek-chat",
                "messages" => [
                    ["role" => "system", "content" => $systemPrompt], 
                    ["role" => "user", "content" => $text]
                ],
                "temperature" => 0.2, 
                "response_format" => ["type" => "json_object"]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['choices'][0]['message']['content'])) {
                    $json = json_decode($data['choices'][0]['message']['content'], true);
                    return $json ?? ['content' => $data['choices'][0]['message']['content'], 'category' => 'Others'];
                }
            }
        } catch (\Exception $e) {
            Log::error("DeepSeek Error: " . $e->getMessage());
        }
        return null;
    }
}