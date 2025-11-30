<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\UserSetting;
use App\Models\CreditHistory;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Jobs\ProcessNewsPost; // ✅ Job Import

class NewsController extends Controller
{
    private $scraper, $aiWriter, $wpService, $telegram;

    public function __construct(NewsScraperService $scraper, AIWriterService $aiWriter, WordPressService $wpService, TelegramService $telegram) {
        $this->scraper = $scraper; $this->aiWriter = $aiWriter; $this->wpService = $wpService; $this->telegram = $telegram;
    }

    public function index()
    {
        $user = Auth::user();
        $settings = $user->settings ?? UserSetting::firstOrCreate(['user_id' => $user->id]);
        
        // ✅ Website Name দেখার জন্য withoutGlobalScope ব্যবহার করা হয়েছে
        $newsItems = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes(); 
        }])
        ->orderBy('published_at', 'desc')
        ->paginate(20);
        
        return view('news.index', compact('newsItems', 'settings'));
    }

    public function studio($id)
    {
        $newsItem = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes(); 
        }])->findOrFail($id);

        $user = Auth::user();
        $settings = UserSetting::where('user_id', $user->id)->first();

        // ১. সব কার্ডের মাস্টার ডাটা (ইমেজ পাথ সহ)
        $allTemplates = [
            [
                'key' => 'ntv', 
                'name' => 'NTV News', 
                'image' => 'templates/ntv.png', 
                'layout' => 'ntv'
            ],
            [
                'key' => 'rtv', 
                'name' => 'RTV News', 
                'image' => 'templates/rtv.png', 
                'layout' => 'rtv'
            ],
            [
                'key' => 'dhakapost', 
                'name' => 'Dhaka Post', 
                'image' => 'templates/dhakapost.png', 
                'layout' => 'dhakapost'
            ],
            [
                'key' => 'dhakapost_new', 
                'name' => 'Dhaka Post Dark', 
                'image' => 'templates/dhakapost-new.png', 
                'layout' => 'dhakapost_new'
            ],
            [
                'key' => 'todayevents', 
                'name' => 'Today Events', 
                'image' => 'templates/todayevents.png', 
                'layout' => 'todayevents'
            ],
            // বাকি ওল্ড টেমপ্লেট
            [
                'key' => 'modern_left', 
                'name' => 'Modern Blue', 
                'image' => 'templates/blue.png', 
                'layout' => 'modern_left'
            ],
            [
                'key' => 'top_heavy', 
                'name' => 'Sports Style', 
                'image' => 'templates/sports.png', 
                'layout' => 'top_heavy'
            ],
        ];

        // ২. ইউজারের পারমিশন চেক করা
        $allowed = $settings->allowed_templates ?? []; // ডাটাবেস থেকে পাওয়া অ্যারে (JSON Cast করা আছে)
        $availableTemplates = [];

        // ৩. ফিল্টারিং লজিক
        if ($user->role === 'super_admin' || $user->role === 'admin') {
            // এডমিন হলে সব পাবে
            $availableTemplates = $allTemplates;
        } else {
            // সাধারণ ইউজার হলে শুধু সিলেক্ট করা গুলো পাবে
            // যদি কোনো পারমিশন সেট করা না থাকে, তবে ডিফল্ট হিসেবে সব বা নির্দিষ্ট কিছু দেখাতে পারেন
            if(empty($allowed)) {
                 // অপশনাল: একদম নতুন ইউজারের জন্য ডিফল্ট ১-২টা দিতে পারেন
                 // $allowed = ['ntv', 'rtv']; 
            }

            foreach ($allTemplates as $template) {
                if (in_array($template['key'], $allowed)) {
                    $availableTemplates[] = $template;
                }
            }
        }

        return view('news.studio', compact('newsItem', 'settings', 'availableTemplates'));
    }

    public function proxyImage(Request $request)
    {
        $url = $request->query('url');
        if (!$url) abort(404);
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(10)->get($url);
            return response($response->body())->header('Content-Type', $response->header('Content-Type'));
        } catch (\Exception $e) { abort(404); }
    }
    
    public function toggleQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        if ($news->is_posted) return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
        $news->is_queued = !$news->is_queued;
        $news->save();
        return back()->with('success', $news->is_queued ? '📌 অটো-পোস্ট লিস্টে যুক্ত হয়েছে' : 'লিস্ট থেকে সরানো হয়েছে');
    }

    public function toggleAutomation(Request $request)
    {
        $request->validate(['interval' => 'nullable|integer|min:1|max:60']);
        $user = Auth::user();
        $settings = $user->settings ?? UserSetting::firstOrCreate(['user_id' => $user->id]);
        $settings->is_auto_posting = !$settings->is_auto_posting;
        if ($request->has('interval') && $request->interval > 0) $settings->auto_post_interval = $request->interval;
        if ($settings->is_auto_posting) $settings->last_auto_post_at = now();
        $settings->save();
        $status = $settings->is_auto_posting ? "চালু" : 'বন্ধ';
        return back()->with('success', "অটোমেশন {$status} করা হয়েছে।");
    }
    
    public function checkAutoPostStatus()
    {
        $user = Auth::user();
        $settings = $user->settings;
        if (!$settings || !$settings->is_auto_posting) return response()->json(['status' => 'off']);
        $intervalMinutes = $settings->auto_post_interval ?? 10;
        $lastPost = $settings->last_auto_post_at ? \Carbon\Carbon::parse($settings->last_auto_post_at) : now();
        $nextPost = $lastPost->addMinutes($intervalMinutes);
        return response()->json(['status' => 'on', 'next_post_time' => $nextPost->format('Y-m-d H:i:s')]);
    }

    public function postToWordPress($id)
    {
        $user = Auth::user();
        $settings = $user->settings;

        if ($settings && $settings->is_auto_posting) return back()->with('error', 'অটোমেশন চালু আছে! ম্যানুয়াল পোস্ট করতে হলে আগে অটো পোস্ট OFF করুন।');
        if (!$settings || !$settings->wp_url || !$settings->wp_username) return back()->with('error', 'দয়া করে সেটিংসে গিয়ে ওয়ার্ডপ্রেস কানেক্ট করুন।');
        
        // প্রাথমিক ক্রেডিট চেক
        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) return back()->with('error', 'আপনার রিরাইট ক্রেডিট শেষ!');
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) return back()->with('error', "আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ!");
        }

        $news = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes(); 
        }])->findOrFail($id);

        if ($news->is_posted) return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');

        // ✅ Job Dispatch (Queue)
        ProcessNewsPost::dispatch($news->id, $user->id);

        return back()->with('success', 'পোস্ট প্রসেসিং শুরু হয়েছে! ১-২ মিনিটের মধ্যে সাইটে দেখা যাবে। ⏳');
    }
}