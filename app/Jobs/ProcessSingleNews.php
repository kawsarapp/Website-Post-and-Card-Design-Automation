<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Models\Website;
use App\Services\NewsScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

class ProcessSingleNews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $link;
    protected $title;
    protected $userId;
    protected $websiteId;
    protected $listImage;

    // 🔥 ULTRA SETTINGS
    public $timeout = 180; // ৩ মিনিট ম্যাক্সিমাম
    public $tries = 1;     // রিট্রাই করার দরকার নেই, ফেইল হলে বাদ (সার্ভার লোড কমাতে)

    public function __construct($link, $title, $userId, $websiteId, $listImage = null)
    {
        $this->link = $link;
        $this->title = $title;
        $this->userId = $userId;
        $this->websiteId = $websiteId;
        $this->listImage = $listImage;
    }

    public function handle(NewsScraperService $scraper)
    {
        try {
            // ১. 🛑 FAST DUPLICATE CHECK
            // DB কুয়েরি অপ্টিমাইজ করার জন্য exist() ব্যবহার করা হয়েছে
            if (NewsItem::where('original_link', $this->link)
                        ->where('user_id', $this->userId)
                        ->exists()) {
                return;
            }

            // ২. ⚙️ SETUP
            $website = Website::find($this->websiteId);
            $customSelectors = $website ? ['content' => $website->selector_content] : [];

            // ৩. 🕷️ SCRAPING (Calling Ultra Scraper)
            $scrapedData = $scraper->scrape($this->link, $customSelectors, $this->userId);

            if (!$scrapedData || empty($scrapedData['body'])) {
                Log::warning("⚠️ Skipped (Empty Content): {$this->link}");
                return;
            }

            // ৪. 🖼️ IMAGE PROCESSING (SMART HANDLING)
            $finalImage = $this->processImage($scrapedData['image'] ?? $this->listImage, $website);

            // ৫. 📝 TITLE CLEANUP
            $finalTitle = !empty($scrapedData['title']) && strlen($scrapedData['title']) > 10 
                          ? trim($scrapedData['title']) 
                          : trim($this->title);

            // ৬. 💾 SAVE TO DATABASE
            $this->saveNews($finalTitle, $scrapedData['body'], $finalImage);

        } catch (\Exception $e) {
            Log::error("🔥 Job Error ({$this->link}): " . $e->getMessage());
        }
    }

    /**
     * ==========================================
     * 🛠️ HELPER: IMAGE PROCESSOR
     * ==========================================
     */
    private function processImage($imageUrl, $website)
    {
        if (!$imageUrl) return null;

        // A. Relative URL Fix
        if (!str_starts_with($imageUrl, 'http') && $website) {
            $parsedUrl = parse_url($website->url);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $imageUrl = $baseUrl . '/' . ltrim($imageUrl, '/');
        }

        // B. Clean OG Path
        if (strpos($imageUrl, '/og/') !== false) {
            $imageUrl = str_replace('/og/', '/', $imageUrl);
        }

        // C. 🔥 RTV / SPECIAL CROP LOGIC
        // শুধুমাত্র নির্দিষ্ট ডোমেইনের জন্য এই ভারী কাজটি হবে
        if (str_contains($imageUrl, 'rtvonline.com')) {
            return $this->cropImage($imageUrl);
        }

        return $imageUrl;
    }

    /**
     * ==========================================
     * 🛠️ HELPER: SMART CROPPER
     * ==========================================
     */
    private function cropImage($url)
    {
        try {
            // 🚀 Fast Download using Laravel HTTP (Timeout 10s)
            // file_get_contents ব্যবহার করবেন না, এটি সার্ভার ঝুলিয়ে দেয়
            $response = Http::timeout(10)->get($url);

            if ($response->failed()) return $url; // ডাউনলোড না হলে অরিজিনাল ইউআরএল রিটার্ন

            $manager = new ImageManager(new Driver());
            $image = $manager->read($response->body());

            // ✂️ Cropping Logic (Bottom 10% Cut)
            $newHeight = (int) ($image->height() * 0.90);
            $image->crop($image->width(), $newHeight, 0, 0);

            // 💾 Save to Storage
            $filename = 'cropped_' . time() . '_' . Str::random(8) . '.jpg';
            $savePath = 'news_images/' . $filename;
            
            // Encode & Save (Quality 80 for speed)
            Storage::disk('public')->put($savePath, (string) $image->toJpeg(80));

            return asset('storage/' . $savePath);

        } catch (\Exception $e) {
            Log::warning("⚠️ Image Crop Failed (Using Original): " . $e->getMessage());
            return $url; // ফেইল করলে অরিজিনাল ইমেজ ফেরত যাবে, নিউজ আটকাবে না
        }
    }

    /**
     * ==========================================
     * 🛠️ HELPER: DATABASE SAVE
     * ==========================================
     */
    private function saveNews($title, $content, $image)
    {
        try {
            NewsItem::create([
                'user_id'       => $this->userId,
                'website_id'    => $this->websiteId,
                'title'         => $title,
                'original_link' => $this->link,
                'thumbnail_url' => $image,
                'content'       => $content,
                'published_at'  => now(),
                'status'        => 'draft', // ডিফল্ট স্ট্যাটাস
            ]);
            
            // লগ ছোট করা হয়েছে যাতে ডিস্ক স্পেস বাঁচে
            Log::info("✅ Saved: " . Str::limit($title, 20));

        } catch (QueryException $e) {
            // Duplicate Entry Error Code: 1062
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                // সাইলেন্টলি ইগনোর করুন (লগ ফ্লাড না করার জন্য)
                return;
            }
            Log::error("🔥 DB Error: " . $e->getMessage());
        }
    }
}