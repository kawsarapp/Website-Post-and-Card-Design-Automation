<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class WebsiteController extends Controller
{
    public function index()
    {
        $websites = Website::all();
        return view('websites.index', compact('websites'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'url' => 'required|url',
            'selector_container' => 'required',
            'selector_title' => 'required',
        ]);

        Website::create($request->all());

        return back()->with('success', 'Website added successfully!');
    }

    public function scrape($id)
    {
        // ✅ ১. হিউম্যান ডিলে (Human Delay): 
        // রোবট ডিটেকশন এড়াতে শুরুতে র‍্যান্ডম অপেক্ষা।
        sleep(rand(2, 5));

        $website = Website::findOrFail($id);
        
        // ফাইল পাথ কনফিগারেশন
        $fileName = "scrape_" . time() . "_{$website->id}.html";
        $tempFile = storage_path("app/public/{$fileName}");
        $scriptPath = base_path("scraper-engine.js");

        // ✅ JS আপডেট: Cloudflare Stealth Mode + Advanced Fingerprinting Spoofing
        // এটি সাধারণ Puppeteer এর বদলে puppeteer-extra ব্যবহার করবে।
        $jsCode = <<<'JS'
import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs';

// ১. স্টেলথ প্লাগিন সক্রিয় করা (Cloudflare এর যম)
puppeteer.use(StealthPlugin());

const url = process.argv[2];
const outputFile = process.argv[3];

if (!url || !outputFile) process.exit(1);

(async () => {
  const browser = await puppeteer.launch({
    headless: "new",
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu',
      '--disable-blink-features=AutomationControlled', // খুব গুরুত্বপূর্ণ: অটোমেশন ফ্ল্যাগ লুকানো
      '--window-size=1920,1080',
      '--disable-infobars',
      '--exclude-switches=enable-automation'
    ]
  });

  try {
    const page = await browser.newPage();
    
    // ২. রিয়েলিস্টিক ইউজার এজেন্ট সেট করা
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36');

    // ৩. ভিউপোর্ট সেট করা
    await page.setViewport({ width: 1366, height: 768 });

    // ৪. কাস্টম হেডার (ভাষা এবং সিকিউরিটি)
    await page.setExtraHTTPHeaders({
        'Accept-Language': 'en-US,en;q=0.9,bn;q=0.8',
        'Upgrade-Insecure-Requests': '1',
        'Sec-Ch-Ua-Platform': '"Windows"',
        'Sec-Fetch-Site': 'none',
        'Sec-Fetch-User': '?1',
    });

    // ৫. রিসোর্স অপটিমাইজেশন (দ্রুত লোড হওয়ার জন্য ফন্ট ও মিডিয়া ব্লক)
    // তবে স্ক্রিপ্ট ব্লক করা যাবে না কারণ Cloudflare চেক স্ক্রিপ্টের মাধ্যমে হয়।
    await page.setRequestInterception(true);
    page.on('request', (req) => {
        const resourceType = req.resourceType();
        if (['font', 'media', 'stylesheet'].includes(resourceType)) {
            req.abort();
        } else {
            req.continue();
        }
    });

    // ৬. পেজ লোড (টাইমআউট বাড়িয়ে ৬০ সেকেন্ড)
    try {
        // networkidle2 মানে অন্তত ২টা কানেকশন বাকি থাকা পর্যন্ত অপেক্ষা (Kaler Kantho এর মতো ভারী সাইটের জন্য ভালো)
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    } catch (error) {
        console.log("Nav timeout, continuing anyway...");
    }

    // ✅ ৭. Cloudflare/Turnstile বাইপাস অপেক্ষা (সবচেয়ে গুরুত্বপূর্ণ ধাপ)
    // পেজ লোড হওয়ার পর ৫ সেকেন্ড অপেক্ষা করবে যাতে চেকিং শেষ হয়।
    await new Promise(r => setTimeout(r, 5000));

    // ৮. মাউস মুভমেন্ট (হিউম্যান বিহেভিয়ার সিমুলেশন)
    try {
        await page.mouse.move(100, 100);
        await page.mouse.move(200, 200, { steps: 10 });
        await page.mouse.move(Math.floor(Math.random() * 500), Math.floor(Math.random() * 500));
    } catch(e) {}

    // ৯. স্মার্ট স্ক্রলিং (Lazy Load ইমেজ লোড করার জন্য)
    await page.evaluate(async () => {
        await new Promise((resolve) => {
            let totalHeight = 0;
            const distance = 200;
            const timer = setInterval(() => {
                const scrollHeight = document.body.scrollHeight;
                window.scrollBy(0, distance);
                totalHeight += distance;

                // ৩০০০ পিক্সেল বা পেজের শেষ পর্যন্ত স্ক্রল করবে
                if (totalHeight >= scrollHeight || totalHeight > 3000) {
                    clearInterval(timer);
                    resolve();
                }
            }, 200);
        });
    });

    // ১০. ইমেজ অ্যাট্রিবিউট ফিক্স (Lazy Loading এর data-src বের করা)
    await page.evaluate(() => {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            const hiddenSrc = img.getAttribute('data-original') || img.getAttribute('data-src') || img.getAttribute('data-srcset');
            if (hiddenSrc) img.setAttribute('src', hiddenSrc);
        });
    });
    
    // সবশেষে ২ সেকেন্ড বিশ্রাম
    await new Promise(r => setTimeout(r, 2000));

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

        try {
            $command = "node \"$scriptPath\" \"{$website->url}\" \"$tempFile\" \"{$website->selector_container}\" 2>&1";
            shell_exec($command);

            if (!file_exists($tempFile)) {
                return back()->with('error', "স্ক্র্যাপার ফাইল তৈরি হয়নি। Node.js ইন্সটল আছে কিনা চেক করুন।");
            }

            $html = file_get_contents($tempFile);
            unlink($tempFile); // টেম্প ফাইল ডিলিট

            $crawler = new Crawler($html);
            
            // Cloudflare ব্লক চেক
            if (str_contains($html, 'Attention Required') || str_contains($html, 'Just a moment...')) {
                return back()->with('error', "Cloudflare ব্লক দিয়েছে। ২ মিনিট পর আবার চেষ্টা করুন।");
            }

            $containers = $crawler->filter($website->selector_container);

            if ($containers->count() === 0) {
                // ডিবাগিং-এর জন্য (অপশনাল)
                // Log::error("HTML Dump: " . substr($html, 0, 500)); 
                return back()->with('error', "সিলেক্টর মিলেনি! ক্লাস নেম চেক করুন অথবা সাইট লোড হয়নি।");
            }

            $baseTime = now(); 
            $count = 0;
            
            // নিউজ লিমিট (৫-১০ টি)
            $limit = rand(5, 10);

            $containers->each(function (Crawler $node) use ($website, &$count, $baseTime, $limit) {
                
                if ($count >= $limit) return false;

                try {
                    // টাইটেল এক্সট্রাকশন
                    $titleNode = $node->filter($website->selector_title);
                    if ($titleNode->count() === 0) return;
                    $title = trim($titleNode->text());

                    // লিংক এক্সট্রাকশন
                    $link = null;
                    $anchor = $node->filter('a');
                    if ($anchor->count() > 0) $link = $anchor->first()->attr('href');
                    else {
                        $titleLink = $node->filter($website->selector_title)->filter('a');
                        if ($titleLink->count() > 0) $link = $titleLink->attr('href');
                    }
                    if (!$link) return;

                    // লিংক ফিক্সিং (Relative to Absolute)
                    if (!str_starts_with($link, 'http')) {
                        $parsedUrl = parse_url($website->url);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $link = $baseUrl . '/' . ltrim($link, '/');
                    }

                    // ইমেজ এক্সট্রাকশন
                    $image = null;
                    // ১. নির্দিষ্ট সিলেক্টর থাকলে
                    if ($website->selector_image) {
                        try {
                            $targetNode = $node->filter($website->selector_image);
                            $image = $this->extractImageSrc($targetNode);
                        } catch (\Exception $e) {}
                    }
                    // ২. অটোমেটিক ফলব্যাক
                    if (!$image) {
                        try {
                            $fallbackImg = $node->filter('img');
                            if ($fallbackImg->count() > 0) {
                                $image = $this->extractImageSrc($fallbackImg->first());
                            }
                        } catch (\Exception $e) {}
                    }
                    
                    // ইমেজ URL ক্লিনআপ
                    if ($image) {
                        if (str_contains($image, ',')) $image = trim(explode(',', $image)[0]);
                        if (str_contains($image, ' ')) $image = trim(explode(' ', $image)[0]);
                        if (str_starts_with($image, '//')) $image = 'https:' . $image;
                        elseif (!str_starts_with($image, 'http')) {
                            $parsedUrl = parse_url($website->url);
                            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                            $image = $baseUrl . '/' . ltrim($image, '/');
                        }
                    }

                    // ডাটাবেস সেভ
                    NewsItem::updateOrCreate(
                        ['original_link' => $link],
                        [
                            'website_id' => $website->id,
                            'title' => $title,
                            'thumbnail_url' => $image,
                            'published_at' => $baseTime->copy()->subSeconds($count), 
                        ]
                    );
                    $count++;

                } catch (\Exception $e) {}
            });

            if ($count === 0) return back()->with('error', "কোনো নিউজ পাওয়া যায়নি।");
            
            return back()->with('success', "✅ {$count}টি নিউজ স্ক্র্যাপ হয়েছে! (Stealth Mode Active)");

        } catch (\Exception $e) {
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }
    
    // ইমেজ হেল্পার ফাংশন
    private function extractImageSrc($node)
    {
        if ($node->count() === 0) return null;
        $imgTag = ($node->nodeName() === 'img') ? $node : $node->filter('img');
        
        if ($imgTag->count() > 0) {
            // সাধারণ src চেক
            $src = $imgTag->attr('src');
            if ($src && !str_contains($src, 'base64') && strlen($src) > 10) return $src;
            
            // Lazy loading attributes চেক
            $attrs = ['data-original', 'data-src', 'srcset', 'data-srcset'];
            foreach ($attrs as $attr) {
                $val = $imgTag->attr($attr);
                if ($val && !str_contains($val, 'base64')) return $val;
            }
        } else {
            // Background Image চেক
            $style = $node->attr('style');
            if ($style && preg_match('/url\((.*?)\)/', $style, $matches)) return trim($matches[1], "'\" ");
        }
        return null;
    }
}