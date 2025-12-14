<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // 🔥 এটি ইমপোর্ট করতে হবে

class NewsCardGeneratorService
{
    protected $manager;

    public function __construct()
    {
        // Intervention Image V3 Setup (GD Driver)
        $this->manager = new ImageManager(new Driver());
    }

    public function generate($news, $settings)
    {
        try {
            // ১. ডিফল্ট টেমপ্লেট পাথ সেট করা
            $templateName = $settings->default_template ?? 'default'; 
            $templatePath = public_path("templates/{$templateName}.png");

            // টেমপ্লেট না পেলে ডিফল্ট-এ ফলব্যাক করবে
            if (!file_exists($templatePath)) {
                $templatePath = public_path("templates/default.png");
                if (!file_exists($templatePath)) {
                    Log::error("❌ Card Gen Error: Default Template not found.");
                    return null; 
                }
            }
            
            // টেমপ্লেট রিড করা
            $img = $this->manager->read($templatePath);

            // ২. নিউজের মেইন ইমেজ প্রসেস করা
            if ($news->thumbnail_url) {
                try {
                    $newsImage = null;

                    // ক) প্রথমে দেখব এটি লোকাল ফাইল কিনা
                    $localPath = $this->getImageSystemPath($news->thumbnail_url);
                    
                    if ($localPath && file_exists($localPath)) {
                        $newsImage = $this->manager->read($localPath);
                    } else {
                        // খ) যদি রিমোট URL হয়, তবে HTTP Client দিয়ে ডাউনলোড করব (নিরাপদ পদ্ধতি)
                        try {
                            $response = Http::timeout(10)
                                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36')
                                ->get($news->thumbnail_url);

                            if ($response->successful()) {
                                // বাইনারি ডাটা থেকে ইমেজ তৈরি
                                $newsImage = $this->manager->read($response->body());
                            } else {
                                Log::warning("⚠️ Image Download Failed: " . $response->status());
                            }
                        } catch (\Exception $e) {
                             Log::warning("⚠️ HTTP Image Fetch Error: " . $e->getMessage());
                        }
                    }

                    // যদি ইমেজ সফলভাবে লোড হয়, তবেই প্রসেস করব
                    if ($newsImage) {
                        // ইমেজের সাইজ এবং পজিশন (টেমপ্লেট অনুযায়ী 1140x450)
                        $newsImage->cover(1140, 450); 
                        $img->place($newsImage, 'top-center', 0, 20); 
                    }

                } catch (\Exception $e) {
                    Log::error("❌ Card Gen Image Error: " . $e->getMessage() . " | URL: " . $news->thumbnail_url);
                }
            }

            // ৩. টাইটেল লেখা (বাংলা ফন্ট সাপোর্ট)
            $fontPath = public_path('fonts/SolaimanLipi.ttf'); 
            
            if (file_exists($fontPath)) {
                $titleText = $this->wrapText($news->title, 40);
                
                $img->text($titleText, 600, 500, function($font) use ($fontPath) {
                    $font->file($fontPath);
                    $font->size(35);
                    $font->color('#000000');
                    $font->align('center');
                    $font->valign('top');
                });
            }

            // ৪. লোগো বসানো
            if (!empty($settings->logo_url)) {
                try {
                    // লোগোর জন্যও একই সেফ পদ্ধতি ব্যবহার করা ভালো
                    $logoContent = Http::get($settings->logo_url)->body();
                    $logo = $this->manager->read($logoContent);
                    $logo->scale(height: 100); 
                    $img->place($logo, 'top-right', 20, 20);
                } catch (\Exception $e) {}
            }

            // ৫. ফাইল সেভ করা
            $fileName = 'card_' . $news->id . '_' . time() . '.jpg';
            $savePath = storage_path('app/public/generated-cards/' . $fileName);
            
            if (!file_exists(dirname($savePath))) {
                mkdir(dirname($savePath), 0755, true);
            }

            $img->toJpeg(90)->save($savePath);

            return $savePath;

        } catch (\Exception $e) {
            Log::error("🔥 Critical Card Gen Error: " . $e->getMessage());
            return null;
        }
    }

    // হেল্পার: URL থেকে লোকাল পাথ বের করা
    private function getImageSystemPath($url)
    {
        $appUrl = config('app.url');
        if (strpos($url, $appUrl) !== false) {
            $relativePath = str_replace($appUrl, '', $url);
            return public_path($relativePath);
        }
        if (str_starts_with($url, '/')) {
            return public_path($url);
        }
        return null;
    }

    // টেক্সট র‍্যাপার
    private function wrapText($text, $limit = 40)
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (mb_strlen($currentLine . $word) > $limit) {
                $lines[] = $currentLine;
                $currentLine = $word . ' ';
            } else {
                $currentLine .= $word . ' ';
            }
        }
        $lines[] = $currentLine;

        return implode("\n", $lines);
    }
}