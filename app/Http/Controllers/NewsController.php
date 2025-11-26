<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\UserSetting;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class NewsController extends Controller
{
    private $scraper;
    private $aiWriter;
    private $wpService;
    private $telegram;

    private $wpCategories = [
        'Politics' => 14, 'International' => 37, 'Sports' => 15,
        'Entertainment' => 11, 'Technology' => 1, 'Economy' => 1,
        'Bangladesh' => 14, 'Crime' => 1, 'Others' => 1
    ];

    public function __construct(
        NewsScraperService $scraper, 
        AIWriterService $aiWriter, 
        WordPressService $wpService, 
        TelegramService $telegram
    ) {
        $this->scraper   = $scraper;
        $this->aiWriter  = $aiWriter;
        $this->wpService = $wpService;
        $this->telegram  = $telegram;
    }

    public function index()
    {
        $user = Auth::user();
        $settings = $user->settings ?? UserSetting::create(['user_id' => $user->id]);
        
        $newsItems = NewsItem::with('website')->orderBy('published_at', 'desc')->paginate(20);
        
        return view('news.index', compact('newsItems', 'settings'));
    }

    public function studio($id)
    {
        $newsItem = NewsItem::with('website')->findOrFail($id);
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
        } catch (\Exception $e) {
            abort(404);
        }
    }
	
	
	// ✅ কিউ (Queue) টগল করার ফাংশন
    public function toggleQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        
        // স্ট্যাটাস উল্টিয়ে দেওয়া (True থাকলে False, False থাকলে True)
        $news->is_queued = !$news->is_queued;
        $news->save();

        $status = $news->is_queued ? 'অটো-পোস্ট লিস্টে যুক্ত হয়েছে (Priority) 📌' : 'লিস্ট থেকে সরানো হয়েছে';
        
        return back()->with('success', $status);
    }

    // এই মেথডটি NewsController.php তে রিপ্লেস করুন
    public function toggleAutomation(Request $request)
    {
        $request->validate([
            'interval' => 'nullable|integer|min:1|max:60'
        ]);

        $user = Auth::user();
        
        // ইউজারের সেটিংস লোড করা বা তৈরি করা
        $settings = $user->settings ?? UserSetting::firstOrCreate(['user_id' => $user->id]);

        // টগল লজিক (অন/অফ)
        $settings->is_auto_posting = !$settings->is_auto_posting;

        // যদি ইনপুট দেয়, তবে আপডেট হবে
        if ($request->has('interval') && $request->interval > 0) {
            $settings->auto_post_interval = $request->interval;
        }

        // অটোমেশন চালু করলে টাইমার রিসেট করা
        if ($settings->is_auto_posting) {
            $settings->last_auto_post_at = now();
        }

        $settings->save();

        $status = $settings->is_auto_posting ? "চালু (প্রতি {$settings->auto_post_interval} মি. পর পর)" : 'বন্ধ';

        return back()->with('success', "অটোমেশন {$status} করা হয়েছে।");
    }
	
	
	// ✅ AJAX এর জন্য স্ট্যাটাস চেক ফাংশন
    public function checkAutoPostStatus()
    {
        $user = Auth::user();
        $settings = $user->settings;

        if (!$settings || !$settings->is_auto_posting) {
            return response()->json(['status' => 'off']);
        }

        // নেক্সট টাইম ক্যালকুলেশন
        $intervalMinutes = $settings->auto_post_interval ?? 10;
        $lastPost = $settings->last_auto_post_at ? \Carbon\Carbon::parse($settings->last_auto_post_at) : now();
        $nextPost = $lastPost->addMinutes($intervalMinutes);

        return response()->json([
            'status' => 'on',
            'last_posted' => $settings->last_auto_post_at, // ডিবাগিং এর জন্য
            'next_post_time' => $nextPost->format('Y-m-d H:i:s') // নতুন সময়
        ]);
    }
	

    public function postToWordPress($id)
{
    set_time_limit(300);

    $user = Auth::user();
    $settings = $user->settings;

    // --- ✅ অটোমেশন চেক ---
    if ($settings && $settings->is_auto_posting) {
        return back()->with('error', 'অটোমেশন চালু আছে! ম্যানুয়াল পোস্ট করতে হলে আগে অটো পোস্ট OFF করুন।');
    }

    if (!$settings || !$settings->wp_url || !$settings->wp_username) {
        return back()->with('error', 'দয়া করে সেটিংসে গিয়ে ওয়ার্ডপ্রেস কানেক্ট করুন।');
    }

    $news = NewsItem::with('website')->findOrFail($id);

    if ($news->is_posted) return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');

    // প্রাথমিক ক্রেডিট চেক
    if ($user->role !== 'super_admin' && $user->credits <= 0) {
        return back()->with('error', 'আপনার রিরাইট ক্রেডিট শেষ! দয়া করে রিচার্জ করুন।');
    }

    try {
        // ১. স্ক্র্যাপ কন্টেন্ট
        if (empty($news->content) || strlen($news->content) < 150) {
            $content = $this->scraper->scrape($news->original_link);
            if ($content) {
                $news->update(['content' => $this->cleanUtf8($content)]);
            } else {
                return back()->with('error', 'স্ক্র্যাপার কন্টেন্ট পায়নি।');
            }
        }

        // ২. AI রিরাইট
        $inputText = "HEADLINE: " . $news->title . "\n\nBODY:\n" . strip_tags($news->content);
        $cleanText = $this->cleanUtf8($inputText);

        $aiResponse = $this->aiWriter->rewrite($cleanText);

        // ভেরিয়েবল ইনিশিয়ালাইজেশন
        $categoryId = $this->wpCategories['Others'];
        $rewrittenContent = $news->content;

        if (!$aiResponse) {
            // AI ফেইল করলে অরিজিনাল কন্টেন্ট থাকবে (ক্রেডিট কাটবে না)
            $rewrittenContent = $news->content;
        } else {
            $rewrittenContent = $aiResponse['content'];
            $detectedCategory = $aiResponse['category'];
            $categoryId = $this->wpCategories[$detectedCategory] ?? $this->wpCategories['Others'];

            // ==========================================
            // ✅ আপডেটেড ক্রেডিট এবং ডেইলি লিমিট লজিক
            // ==========================================
            if ($user->role !== 'super_admin') {
                
                // ১. ডেইলি লিমিট চেক
                // (User মডেলে hasDailyLimitRemaining ফাংশন থাকতে হবে)
                if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                    return back()->with('error', "আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ! আগামীকাল আবার চেষ্টা করুন।");
                }

                // ২. ক্রেডিট কাটা
                $user->decrement('credits');

                // ৩. ক্রেডিট হিস্ট্রি লগ রাখা
                \App\Models\CreditHistory::create([
                    'user_id' => $user->id,
                    'action_type' => 'manual_post',
                    'description' => 'Post: ' . \Illuminate\Support\Str::limit($news->title, 40),
                    'credits_change' => -1,
                    'balance_after' => $user->credits
                ]);
            }
            // ==========================================
        }

        // ৩. ইমেজ আপলোড
        $imageId = null;
        if ($news->thumbnail_url) {
            $upload = $this->wpService->uploadImage(
                $news->thumbnail_url, 
                $news->title,
                $settings->wp_url,
                $settings->wp_username,
                $settings->wp_app_password
            );

            if ($upload && $upload['success']) {
                $imageId = $upload['id'];
            } else {
                $rewrittenContent = '<img src="' . $news->thumbnail_url . '" style="width:100%; margin-bottom:15px;"><br>' . $rewrittenContent;
            }
        }

        // ৪. ফাইনাল পোস্ট পাবলিশিং
        $credit = '<hr><p style="text-align:center; font-size:13px; color:#888;">তথ্যসূত্র: অনলাইন ডেস্ক</p>';
        $finalContent = $this->cleanUtf8($rewrittenContent . $credit);
        $finalTitle   = $this->cleanUtf8($news->title);

        $wpPost = $this->wpService->publishPost(
            $finalTitle, 
            $finalContent, 
            $settings->wp_url,
            $settings->wp_username,
            $settings->wp_app_password,
            $categoryId,
            $imageId
        );

        if ($wpPost) {
            $news->update([
                'rewritten_content' => $finalContent,
                'is_posted'         => true,
                'wp_post_id'        => $wpPost['id']
            ]);

            if ($settings->telegram_channel_id) {
                $this->telegram->sendToChannel($settings->telegram_channel_id, $finalTitle, $wpPost['link']);
            }

            return back()->with('success', "পোস্ট পাবলিশ হয়েছে! ID: " . $wpPost['id']);
        } else {
            return back()->with('error', 'ওয়ার্ডপ্রেস পোস্ট ফেইল করেছে। ক্রেডেনশিয়াল চেক করুন।');
        }

    } catch (\Exception $e) {
        return back()->with('error', 'System Error: ' . $e->getMessage());
    }
}
	
	
	
	
	

    private function cleanUtf8($string)
    {
        if (is_string($string)) return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return $string;
    }
}