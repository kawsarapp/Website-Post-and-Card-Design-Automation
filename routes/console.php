<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use App\Models\NewsItem;
use App\Models\User;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Str; // Str ক্লাসের জন্য

Artisan::command('news:autopost', function (
    NewsScraperService $scraper, 
    AIWriterService $aiWriter, 
    WordPressService $wpService,
    TelegramService $telegram
) {
    $this->info("🔄 অটোমেশন চেক শুরু হচ্ছে...");

    // ১. একটিভ অটোমেশন ইউজার খোঁজা
    $users = User::whereHas('settings', function($q) {
        $q->where('is_auto_posting', true);
    })->where('credits', '>', 0)->where('is_active', true)->get();

    $this->info("বোট: মোট " . $users->count() . " জন একটিভ ইউজার পাওয়া গেছে।");

    foreach ($users as $user) {
        $this->info("--- চেকিং ইউজার: {$user->name} ---");

        $settings = $user->settings;

        if (!$settings || !$settings->wp_url || !$settings->wp_username) {
            $this->error("❌ সেটিংস নেই। স্কিপ করছি।");
            continue;
        }

        // ২. সময় চেক করা
        $lastPostTime = $settings->last_auto_post_at ? Carbon::parse($settings->last_auto_post_at) : null;
        $intervalMinutes = $settings->auto_post_interval ?? 10;

        if ($lastPostTime) {
            $diff = abs(now()->diffInMinutes($lastPostTime));
            
            $this->info("ℹ️ শেষ পোস্ট: {$diff} মিনিট আগে। ইন্টারভাল: {$intervalMinutes} মিনিট।");
            
            if ($diff < $intervalMinutes) {
                $wait = $intervalMinutes - $diff;
                $this->warn("⏳ সময় হয়নি। আরও {$wait} মিনিট অপেক্ষা করতে হবে।");
                continue; 
            }
        }

        // ৩. পেন্ডিং নিউজ খোঁজা (স্মার্ট প্রায়োরিটি লজিক)
        
        // স্টেপ A: প্রথমে দেখবে ইউজার কোনো নিউজ 'Select' করেছে কিনা
        $newsToPost = NewsItem::withoutGlobalScope(\App\Models\Scopes\UserScope::class)
            ->where('user_id', $user->id)
            ->where('is_posted', false)
            ->where('is_queued', true)
            ->oldest()
            ->first();

        // স্টেপ B: না থাকলে সাধারণ পুরানো নিউজ
        if (!$newsToPost) {
            $this->info("ℹ️ কোনো সিলেক্ট করা নিউজ নেই, ডিফল্ট মুডে যাচ্ছে...");
            
            $newsToPost = NewsItem::withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                ->where('user_id', $user->id)
                ->where('is_posted', false)
                ->oldest()
                ->first();
        }

        // স্টেপ C: এরপরও না থাকলে স্কিপ
        if (!$newsToPost) {
            $this->warn("⚠️ সকল নিউজ পোস্ট করা হয়েছে বা পেন্ডিং নিউজ নাই।");
            continue;
        }

        $this->info("✅ নিউজ পাওয়া গেছে: {$newsToPost->title}");

        try {
            // স্ক্র্যাপ (যদি কন্টেন্ট না থাকে)
            if (empty($newsToPost->content) || strlen($newsToPost->content) < 150) {
                $this->info("content স্ক্র্যাপ করা হচ্ছে...");
                $content = $scraper->scrape($newsToPost->original_link);
                
                if ($content) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                    $newsToPost->update(['content' => $content]);
                } else {
                    $this->error("❌ স্ক্র্যাপ ফেইল (কন্টেন্ট নেই)। স্কিপ করছি...");
                    continue;
                }
            }

            // AI রিরাইট
            $this->info("🤖 AI রিরাইট হচ্ছে...");
            $inputText = "HEADLINE: " . $newsToPost->title . "\n\nBODY:\n" . strip_tags($newsToPost->content);
            $inputText = mb_convert_encoding($inputText, 'UTF-8', 'UTF-8');
            
            $aiResponse = $aiWriter->rewrite($inputText);

            if ($aiResponse) {
                
                // ==========================================
                // ✅ ১. ডেইলি লিমিট চেক
                // ==========================================
                if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                    $this->warn("⛔ User {$user->name} daily limit exceeded. Skipping.");
                    continue; 
                }

                // ==========================================
                // ✅ ২. ক্রেডিট কাটা এবং লগ রাখা
                // ==========================================
                $user->decrement('credits');
                
                \App\Models\CreditHistory::create([
                    'user_id' => $user->id,
                    'action_type' => 'auto_post',
                    'description' => 'Auto: ' . Str::limit($newsToPost->title, 40),
                    'credits_change' => -1,
                    'balance_after' => $user->credits
                ]);
                
                // ==========================================
                // বাকি প্রসেস (ক্যাটাগরি, ইমেজ, পোস্ট)
                // ==========================================

                // ক্যাটাগরি ডিটেকশন
                $wpCategories = [
                    'Politics' => 14, 'International' => 37, 'Sports' => 15,
                    'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1,
                    'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1
                ];
                $detectedCategory = $aiResponse['category'] ?? 'Others';
                $categoryId = $wpCategories[$detectedCategory] ?? 1;

                // ইমেজ আপলোড
                $imageId = null;
                if ($newsToPost->thumbnail_url) {
                    $this->info("🖼️ ইমেজ আপলোড হচ্ছে...");
                    $upload = $wpService->uploadImage(
                        $newsToPost->thumbnail_url, 
                        $newsToPost->title,
                        $settings->wp_url,            
                        $settings->wp_username,       
                        $settings->wp_app_password 
                    );

                    if ($upload && $upload['success']) {
                        $imageId = $upload['id'];
                    } else {
                        // আপলোড ফেইল হলে কন্টেন্টে এমবেড
                        $aiResponse['content'] = '<img src="' . $newsToPost->thumbnail_url . '" style="width:100%; margin-bottom:15px;"><br>' . $aiResponse['content'];
                    }
                }

                // WP পোস্ট
                $credit = '<hr><p style="text-align:center; font-size:13px; color:#888;">তথ্যসূত্র: অনলাইন ডেস্ক</p>';
                $finalContent = $aiResponse['content'] . $credit;

                $wpPost = $wpService->publishPost(
                    $newsToPost->title, 
                    $finalContent, 
                    $settings->wp_url,       
                    $settings->wp_username, 
                    $settings->wp_app_password,
                    $categoryId,
                    $imageId
                );

                if ($wpPost) {
                    // সফল হলে নিউজ আপডেট (ক্রেডিট আগেই কাটা হয়েছে)
                    $newsToPost->update([
                        'rewritten_content' => $finalContent, 
                        'is_posted' => true, 
                        'is_queued' => false,
                        'wp_post_id' => $wpPost['id']
                    ]);

                    $settings->update(['last_auto_post_at' => now()]);

                    if ($settings->telegram_channel_id) {
                        $telegram->sendToChannel($settings->telegram_channel_id, $newsToPost->title, $wpPost['link']);
                    }
                    
                    $this->info("🚀 সফল! Post ID: {$wpPost['id']}");
                } else {
                    $this->error("❌ ওয়ার্ডপ্রেস পোস্ট ফেইল করেছে। (ক্রেডিট রিফান্ড করা যেতে পারে)");
                    // অপশনাল: ফেইল করলে ক্রেডিট ফেরত দেওয়ার লজিক এখানে যুক্ত করতে পারেন
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ এরর: " . $e->getMessage());
        }
    }
    $this->info("🏁 চেক শেষ।");

})->purpose('Auto post news with interval check');

Schedule::command('news:autopost')->everyMinute();