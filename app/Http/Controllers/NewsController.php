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
use Illuminate\Support\Str;

class NewsController extends Controller
{
    private $scraper, $aiWriter, $wpService, $telegram;
    private $wpCategories = ['Politics' => 14, 'International' => 37, 'Sports' => 15, 'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1, 'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1];

    public function __construct(NewsScraperService $scraper, AIWriterService $aiWriter, WordPressService $wpService, TelegramService $telegram) {
        $this->scraper = $scraper; $this->aiWriter = $aiWriter; $this->wpService = $wpService; $this->telegram = $telegram;
    }

    public function index()
    {
        $user = Auth::user();
        $settings = $user->settings ?? UserSetting::firstOrCreate(['user_id' => $user->id]);
        
        // ✅ FIX: ওয়েবসাইট রিলেশনশিপে গ্লোবাল স্কোপ বন্ধ করা হয়েছে
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

        $settings = UserSetting::where('user_id', Auth::id())->first();
        return view('news.studio', compact('newsItem', 'settings'));
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
        $status = $settings->is_auto_posting ? "চালু (প্রতি {$settings->auto_post_interval} মি. পর পর)" : 'বন্ধ';
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
        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) return back()->with('error', 'আপনার রিরাইট ক্রেডিট শেষ!');
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) return back()->with('error', "আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ!");
        }

        $news = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes(); 
        }])->findOrFail($id);

        if ($news->is_posted) return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');

        \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id);
        return back()->with('success', 'পোস্ট প্রসেসিং শুরু হয়েছে! ১-২ মিনিটের মধ্যে সাইটে দেখা যাবে। ⏳');
    }

    private function cleanUtf8($string) { return is_string($string) ? mb_convert_encoding($string, 'UTF-8', 'UTF-8') : $string; }
}