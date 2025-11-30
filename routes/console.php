<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\NewsItem;
use App\Models\User;
use App\Jobs\ProcessNewsPost; // ✅ Job ইমপোর্ট করা হয়েছে
use Carbon\Carbon;

// --- AUTO POST COMMAND ---
Artisan::command('news:autopost', function () {
    
    $this->info("🔄 অটোমেশন চেক শুরু হচ্ছে...");

    // ১. একটিভ অটোমেশন ইউজার খোঁজা
    $users = User::whereHas('settings', function($q) {
        $q->where('is_auto_posting', true);
    })->where('is_active', true)->get();

    $this->info("বোট: মোট " . $users->count() . " জন একটিভ ইউজার পাওয়া গেছে।");

    foreach ($users as $user) {
        $this->info("--- চেকিং ইউজার: {$user->name} ---");

        // ক্রেডিট চেক (সুপার এডমিন বাদে)
        if ($user->role !== 'super_admin' && $user->credits <= 0) {
            $this->warn("⛔ User {$user->name} has no credits. Skipping.");
            continue;
        }

        $settings = $user->settings;

        if (!$settings || !$settings->wp_url || !$settings->wp_username) {
            $this->error("❌ সেটিংস নেই। স্কিপ করছি।");
            continue;
        }

        // ২. সময় চেক করা (Interval Check)
        $lastPostTime = $settings->last_auto_post_at ? Carbon::parse($settings->last_auto_post_at) : null;
        $intervalMinutes = $settings->auto_post_interval ?? 10;

        if ($lastPostTime) {
            $diff = abs(now()->diffInMinutes($lastPostTime));
            
            if ($diff < $intervalMinutes) {
                $wait = $intervalMinutes - $diff;
                $this->info("⏳ সময় হয়নি। আরও {$wait} মিনিট অপেক্ষা করতে হবে।");
                continue; 
            }
        }

        // ৩. পেন্ডিং নিউজ খোঁজা (Priority Logic)
        
        // A. প্রথমে দেখবে Queue তে কোনো নিউজ আছে কিনা (is_queued = 1)
        $newsToPost = NewsItem::withoutGlobalScope(\App\Models\Scopes\UserScope::class)
            ->where('user_id', $user->id)
            ->where('is_posted', false)
            ->where('is_queued', true)
            ->oldest()
            ->first();

        // B. যদি Queue তে না থাকে, তবে সাধারণ পুরানো নিউজ
        if (!$newsToPost) {
            $newsToPost = NewsItem::withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                ->where('user_id', $user->id)
                ->where('is_posted', false)
                ->oldest()
                ->first();
        }

        // C. নিউজ না থাকলে স্কিপ
        if (!$newsToPost) {
            $this->warn("⚠️ সকল নিউজ পোস্ট করা হয়েছে বা পেন্ডিং নিউজ নাই।");
            continue;
        }

        $this->info("✅ নিউজ পাওয়া গেছে: {$newsToPost->title}");

        // ✅ ৪. জব কিউতে পাঠানো (সব ভারী কাজ ProcessNewsPost জবে হবে)
        try {
            ProcessNewsPost::dispatch($newsToPost->id, $user->id);
            $this->info("🚀 Job Dispatched successfully!");
        } catch (\Exception $e) {
            $this->error("❌ Job Dispatch Failed: " . $e->getMessage());
        }
    }
    
    $this->info("🏁 চেক শেষ।");

})->purpose('Auto post news via Queue Job');

// শিডিউল রানার (প্রতি মিনিটে)
Schedule::command('news:autopost')->everyMinute();

// অটো ক্লিনআপ (দিনে ২ বার)
Schedule::call(function () {
    $days = 7;
    $count = NewsItem::where('created_at', '<', now()->subDays($days))->delete();
    if ($count > 0) Log::info("🧹 Auto Clean: {$count} items deleted.");
})->twiceDaily(0, 12);