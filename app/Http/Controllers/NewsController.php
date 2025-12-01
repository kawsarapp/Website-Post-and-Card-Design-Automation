<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\UserSetting;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use App\Services\TelegramService;
use App\Services\SocialPostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessNewsPost;
use App\Jobs\GenerateAIContent; 

class NewsController extends Controller
{
    private $scraper, $aiWriter, $wpService, $telegram;

    public function __construct(
        NewsScraperService $scraper, 
        AIWriterService $aiWriter, 
        WordPressService $wpService, 
        TelegramService $telegram
    ) {
        $this->scraper = $scraper;
        $this->aiWriter = $aiWriter;
        $this->wpService = $wpService;
        $this->telegram = $telegram;
    }

    public function index()
    {
        $user = Auth::user();
        $settings = $user->settings ?? UserSetting::firstOrCreate(['user_id' => $user->id]);
        
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
        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);

        $allTemplates = [
            ['key' => 'ntv', 'name' => 'NTV News', 'image' => 'templates/ntv.png', 'layout' => 'ntv'],
            ['key' => 'rtv', 'name' => 'RTV News', 'image' => 'templates/rtv.png', 'layout' => 'rtv'],
            ['key' => 'dhakapost', 'name' => 'Dhaka Post', 'image' => 'templates/dhakapost.png', 'layout' => 'dhakapost'],
            ['key' => 'dhakapost_new', 'name' => 'Dhaka Post Dark', 'image' => 'templates/dhakapost-new.png', 'layout' => 'dhakapost_new'],
            ['key' => 'todayevents', 'name' => 'Today Events', 'image' => 'templates/todayevents.png', 'layout' => 'todayevents'],
            ['key' => 'modern_left', 'name' => 'Modern Blue', 'image' => 'templates/blue.png', 'layout' => 'modern_left'],
            ['key' => 'top_heavy', 'name' => 'Sports Style', 'image' => 'templates/sports.png', 'layout' => 'top_heavy'],
        ];

        $allowed = $settings->allowed_templates ?? []; 
        $availableTemplates = [];

        if ($user->role === 'super_admin' || $user->role === 'admin') {
            $availableTemplates = $allTemplates;
        } else {
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
	
	
	// 3. Final Publish (From Draft)
    public function publishDraft(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable'
        ]);

        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        $customData = [
            'title' => $request->title,
            'content' => $request->content,
            'category_id' => $request->category
        ];

        // Status Update
        $news->update(['status' => 'publishing']);

        // Dispatch Job for Final Posting
        ProcessNewsPost::dispatch($news->id, $user->id, $customData);

        return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে! (Publishing Started)']);
    }

    // ==========================================
    // 🔥 NEW: AI FLOW & DRAFTS
    // ==========================================

    // ১. নিউজকে AI প্রসেসিং কিউতে পাঠানো
    public function sendToAiQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'super_admin' && $user->credits <= 0) {
            return back()->with('error', 'আপনার ক্রেডিট শেষ!');
        }

        if ($news->status === 'processing') {
            return back()->with('error', 'এটি ইতিমধ্যেই প্রসেসিং হচ্ছে...');
        }

        // স্ট্যাটাস আপডেট
        $news->update(['status' => 'processing']);

        // জব ডিসপ্যাচ
        GenerateAIContent::dispatch($news->id, $user->id);

        return back()->with('success', 'AI প্রসেসিং শুরু হয়েছে! পেজ রিফ্রেশ করে স্ট্যাটাস দেখুন।');
    }

    // ২. ড্রাফট পেজ (Missing Method Fixed ✅)
    public function drafts()
    {
        $user = Auth::user();
        $settings = $user->settings;

        // শুধু ড্রাফট স্ট্যাটাসের নিউজ ফেচ করা
        $drafts = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes(); 
        }])
        ->where('status', 'draft')
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return view('news.drafts', compact('drafts', 'settings'));
    }

    // ৩. ড্রাফট কন্টেন্ট লোড করা (মডালের জন্য)
    public function getDraftContent($id)
    {
        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        // ড্রাফট না থাকলে অরিজিনাল ডাটা
        $title = !empty($news->ai_title) ? $news->ai_title : $news->title;
        $content = !empty($news->ai_content) ? $news->ai_content : strip_tags($news->content);

        return response()->json([
            'success' => true,
            'title'   => $title,
            'content' => $content,
            'categories' => $user->settings->category_mapping ?? [] 
        ]);
    }

    public function confirmPublish(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable'
        ]);

        $user = Auth::user();

        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) {
                return response()->json(['success' => false, 'message' => '❌ আপনার ক্রেডিট শেষ! দয়া করে ক্রেডিট রিচার্জ করুন।']);
            }

            if (!$user->hasDailyLimitRemaining()) {
                return response()->json(['success' => false, 'message' => "❌ আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ!"]);
            }
        }

        $news = NewsItem::findOrFail($id);

        $customData = [
            'title' => $request->title,
            'content' => $request->content,
            'category_id' => $request->category
        ];

        $news->update(['status' => 'publishing']);

        ProcessNewsPost::dispatch($news->id, $user->id, $customData);

        return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে!']);
    }

    // ==========================================
    // 🔥 SOCIAL & MANUAL POST
    // ==========================================

    public function postToWordPress($id, SocialPostService $socialPoster)
    {
        $user = Auth::user();
        $settings = $user->settings;

        if ($settings && $settings->is_auto_posting) {
            return back()->with('error', 'অটোমেশন চালু আছে! ম্যানুয়াল পোস্ট করতে হলে আগে অটো পোস্ট OFF করুন।');
        }
        
        if (!$settings || !$settings->wp_url || !$settings->wp_username) {
            return back()->with('error', 'দয়া করে সেটিংসে গিয়ে ওয়ার্ডপ্রেস কানেক্ট করুন।');
        }
        
        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) {
                return back()->with('error', 'আপনার রিরাইট ক্রেডিট শেষ!');
            }
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                return back()->with('error', "আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ!");
            }
        }

        $news = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes(); 
        }])->findOrFail($id);

        if ($news->is_posted) {
            return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
        }

        $cardImageUrl = $news->thumbnail_url;
        $newsLink = $news->source_url; 

        try {
            if ($settings->post_to_fb && !empty($settings->fb_page_id)) {
                $socialPoster->postToFacebook($settings, $news->title, $cardImageUrl, $newsLink);
            }
            if ($settings->post_to_telegram && !empty($settings->telegram_channel_id)) {
                $socialPoster->postToTelegram($settings, $news->title, $cardImageUrl, $newsLink);
            }
            if ($settings->post_to_whatsapp && !empty($settings->whatsapp_number_id)) {
                $socialPoster->postToWhatsApp($settings, $news->title, $cardImageUrl, $newsLink);
            }
        } catch (\Exception $e) {
            Log::error("Social Post Error: " . $e->getMessage());
        }

        // ওয়ার্ডপ্রেসে পোস্ট
        ProcessNewsPost::dispatch($news->id, $user->id, []);

        return back()->with('success', 'পোস্ট প্রসেসিং শুরু হয়েছে! (WP, FB, TG & WhatsApp) ⏳');
    }
}