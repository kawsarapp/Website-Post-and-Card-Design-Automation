<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\NewsItem;
use App\Models\User;
use App\Jobs\ProcessNewsPost;
use Carbon\Carbon;

// --- AUTO POST COMMAND ---
Artisan::command('news:autopost', function () {
    
    $this->info("🔄 অটোমেশন চেক শুরু হচ্ছে...");

    // ১. একটিভ অটোমেশন ইউজার খোঁজা
    $users = User::whereHas('settings', function($q) {
        $q->where('is_auto_posting', true);
    })->where('is_active', true)->get();

    $this->info("বোট: মোট " . $users->count() . " জন একটিভ ইউজার পাওয়া গেছে।");

    foreach ($users as $user) {
		
		if (!$user->hasPermission('can_auto_post')) {
        $this->warn("⛔ User {$user->name} does not have 'can_auto_post' permission. Skipping.");
        continue;
    }
        $this->info("--- চেকিং ইউজার: {$user->name} ---");

        // ক্রেডিট চেক (সুপার এডমিন বাদে)
        if ($user->role !== 'super_admin' && $user->credits <= 0) {
            $this->warn("⛔ User {$user->name} has no credits. Skipping.");
            continue;
        }

        $settings = $user->settings;

        // 🔥 সংশোধিত লজিক: WP অথবা Laravel কানেকশন চেক (লুপের ভেতরে)
        if (!$settings) {
            $this->error("❌ সেটিংস পাওয়া যায়নি। স্কিপ করছি।");
            continue;
        }

        $hasWP = $settings->wp_url && $settings->wp_username;
        $hasLaravel = $settings->post_to_laravel && $settings->laravel_site_url && $settings->laravel_api_token;

        // যদি দুটোর কোনোটিই না থাকে, তবে স্কিপ করবে
        if (!$hasWP && !$hasLaravel) {
            $this->error("❌ সেটিংস নেই (WP বা Laravel কোনোটিই সেট করা নেই)। স্কিপ করছি।");
            continue;
        }

        // ২. সময় চেক করা (Interval Check)
        $lastPostTime = $settings->last_auto_post_at ? Carbon::parse($settings->last_auto_post_at) : null;
        $intervalMinutes = $settings->auto_post_interval ?? 10;

        if ($lastPostTime) {
            $diff = abs(now()->diffInMinutes($lastPostTime));
            
            if ($diff < $intervalMinutes) {
                $wait = $intervalMinutes - $diff;
                $this->info("⏳ সময় হয়নি। আরও {$wait} মিনিট অপেক্ষা করতে হবে।");
                continue; 
            }
        }

        // ৩. নিউজ খোঁজা
        $newsToPost = NewsItem::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('is_posted', false)
            ->where('is_queued', true)
            ->oldest()
            ->first();

        // যদি Queue তে না থাকে, সাধারণ পেন্ডিং নিউজ নাও
        if (!$newsToPost) {
            $newsToPost = NewsItem::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('is_posted', false)
                ->oldest() 
                ->first();
        }

        if (!$newsToPost) {
            $this->warn("⚠️ সকল নিউজ পোস্ট করা হয়েছে বা পেন্ডিং নিউজ নাই।");
            continue;
        }

        $this->info("✅ নিউজ পাওয়া গেছে: {$newsToPost->title}");

        try {
            // জব ডিসপ্যাচ করা
            ProcessNewsPost::dispatch($newsToPost->id, $user->id);
            
            // সময় আপডেট করা
            $settings->last_auto_post_at = now();
            $settings->save();

            $this->info("🚀 Job Dispatched successfully!");
        } catch (\Exception $e) {
            $this->error("❌ Job Dispatch Failed: " . $e->getMessage());
        }
    }
    
    $this->info("🏁 চেক শেষ।");

})->purpose('Auto post news via Queue Job');


// শিডিউল সেটআপ
Schedule::command('news:autopost')->everyMinute();

Schedule::call(function () {
    $settingsList = \App\Models\UserSetting::get();
    $totalDeleted = 0;
    
    foreach ($settingsList as $setting) {
        $days = (int) ($setting->auto_clean_days ?? 7);
        $days = $days > 0 ? $days : 7; // Prevent 0/negative day accidental clearouts
        
        $count = NewsItem::where('user_id', $setting->user_id)
            ->where('created_at', '<', now()->subDays($days))
            ->where('is_posted', false) // Only un-posted
            ->where('is_queued', false) // Not currently in queue waiting to be posted
            ->delete();
            
        $totalDeleted += $count;
    }
    
    // Fallback delete un-posted orphaned news (no user attached) after default 7 days
    $orphanedCount = NewsItem::whereNull('user_id')
        ->where('created_at', '<', now()->subDays(7))
        ->where('is_posted', false)
        ->where('is_queued', false)
        ->delete();
        
    $totalDeleted += $orphanedCount;
    
    if ($totalDeleted > 0) {
        Log::info("🧹 Dynamic Auto Clean: {$totalDeleted} garbage (pending) items deleted based on user preferences.");
    }
})->hourly();