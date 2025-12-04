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
        
        // সব নিউজ দেখানোর জন্য কোড:
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
            ['key' => 'BanglaLiveNews', 'name' => 'Bangla Live News', 'image' => 'templates/BanglaLiveNews.png', 'layout' => 'BanglaLiveNews'],
            ['key' => 'BanglaLiveNews1', 'name' => 'Bangla Live News 1', 'image' => 'templates/BanglaLiveNews1.png', 'layout' => 'BanglaLiveNews1'],
            ['key' => 'ShotterKhoje', 'name' => 'Shotter Khoje', 'image' => 'templates/ShotterKhoje.png', 'layout' => 'ShotterKhoje'],
            ['key' => 'Jaijaidin1', 'name' => 'Jaijaidin 1', 'image' => 'templates/Jaijaidin1.png', 'layout' => 'Jaijaidin1'],
            ['key' => 'Jaijaidin2', 'name' => 'Jaijaidin 2', 'image' => 'templates/Jaijaidin2.png', 'layout' => 'Jaijaidin2'],
            ['key' => 'Jaijaidin3', 'name' => 'Jaijaidin 3', 'image' => 'templates/Jaijaidin3.png', 'layout' => 'Jaijaidin3'],
            ['key' => 'Jaijaidin4', 'name' => 'Jaijaidin 4', 'image' => 'templates/Jaijaidin4.png', 'layout' => 'Jaijaidin4'],
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
        if ($news->is_posted) return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
        $news->is_queued = !$news->is_queued;
        $news->save();
        return back()->with('success', $news->is_queued ? '📌 অটো-পোস্ট লিস্টে যুক্ত হয়েছে' : 'লিস্ট থেকে সরানো হয়েছে');
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
        return back()->with('success', "অটোমেশন {$status} করা হয়েছে।");
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

        return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে! (Publishing Started)']);
    }

    // ==========================================
    // 🔥 NEW: AI FLOW & DRAFTS
    // ==========================================

    public function sendToAiQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        
        if ($user->role !== 'super_admin') {
             if($user->credits <= 0) {
                return back()->with('error', 'আপনার ক্রেডিট শেষ!');
             }
             
             // 🔥 ফিক্স: ক্রেডিট কাটা + হিস্ট্রি সেভ
             //$user->decrement('credits', 0);
             //$user->decrement('credits', 1);

             \App\Models\CreditHistory::create([
                 'user_id' => $user->id,
                 'action_type' => 'auto_post',
                 'description' => 'AI Processing: ' . \Illuminate\Support\Str::limit($news->title, 40),
                 'credits_change' => -1,
                 'balance_after' => $user->credits
             ]);
        }
		
        

        if ($news->status === 'processing') {
            return back()->with('error', 'এটি ইতিমধ্যেই প্রসেসিং হচ্ছে...');
        }

        $news->update(['status' => 'processing']);
        GenerateAIContent::dispatch($news->id, $user->id);

        return back()->with('success', 'AI প্রসেসিং শুরু হয়েছে! পেজ রিফ্রেশ করে স্ট্যাটাস দেখুন।');
    }

    // ২. ড্রাফট পেজ
    public function drafts()
    {
        $user = Auth::user();
        $settings = $user->settings;

        $drafts = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes();
        }])
        ->whereIn('status', ['draft', 'processing', 'publishing', 'published', 'failed'])
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
            'title'    => $title,
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

        /*
        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) {
                return response()->json(['success' => false, 'message' => '❌ আপনার ক্রেডিট শেষ!']);
            }

            if (!$user->hasDailyLimitRemaining()) {
                return response()->json(['success' => false, 'message' => "❌ আজকের ডেইলি লিমিট শেষ!"]);
            }

            $user->decrement('credits', 1);

            \App\Models\CreditHistory::create([
                'user_id' => $user->id,
                'action_type' => 'manual_post',
                'description' => 'Published Draft: ' . \Illuminate\Support\Str::limit($request->title, 40),
                'credits_change' => -1,
                'balance_after' => $user->credits
            ]);
        }
        */

        $news = NewsItem::findOrFail($id);

        $customData = [
            'title' => $request->title,
            'content' => $request->content,
            'category_id' => $request->category
        ];

        $news->update(['status' => 'publishing']);

        ProcessNewsPost::dispatch($news->id, $user->id, $customData);

        return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে!']);
    }

    // ==========================================
    // 🔥 SOCIAL & MANUAL POST
    // ==========================================

    public function postToWordPress($id, SocialPostService $socialPoster)
    {
        $user = Auth::user();
        $settings = $user->settings;

        if ($settings && $settings->is_auto_posting) {
            return back()->with('error', 'অটোমেশন চালু আছে! ম্যানুয়াল পোস্ট করতে হলে আগে অটো পোস্ট OFF করুন।');
        }
        
        if (!$settings || !$settings->wp_url || !$settings->wp_username) {
            return back()->with('error', 'দয়া করে সেটিংসে গিয়ে ওয়ার্ডপ্রেস কানেক্ট করুন।');
        }

        $news = NewsItem::with(['website' => function ($query) {
            $query->withoutGlobalScopes();
        }])->findOrFail($id);

        if ($news->is_posted) {
            return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
        }

        
        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) {
                return back()->with('error', 'আপনার রিরাইট ক্রেডিট শেষ!');
            }
            
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                return back()->with('error', "আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ!");
            }

            $user->decrement('credits', 1);
            
            \App\Models\CreditHistory::create([
                'user_id' => $user->id,
                'action_type' => 'manual_post',
                'description' => 'Direct Post: ' . \Illuminate\Support\Str::limit($news->title, 40),
                'credits_change' => -1,
                'balance_after' => $user->credits // আপডেটেড ব্যালেন্স
            ]);
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

        ProcessNewsPost::dispatch($news->id, $user->id, []);

        return back()->with('success', 'পোস্ট প্রসেসিং শুরু হয়েছে! (WP, FB, TG & WhatsApp) ⏳');
    }
    
    public function destroy($id)
    {
        $news = NewsItem::findOrFail($id);
        
        // পারমিশন চেক (অপশনাল)
        if (auth()->user()->role !== 'super_admin' && $news->user_id !== auth()->id()) {
            return back()->with('error', 'আপনার অনুমতি নেই।');
        }

        $news->delete();
        return back()->with('success', 'নিউজটি সফলভাবে মুছে ফেলা হয়েছে।');
    }

    // ফর্ম দেখানোর জন্য
    public function create()
    {
        return view('news.create');
    }

    public function storeCustom(Request $request)
    {
        // ১. রিকোয়েস্ট আসার সাথে সাথে লগ রাখা
        Log::info('StoreCustom: New request received', [
            'user_id' => auth()->id(),
            'title'    => $request->title,
            'has_ai'   => $request->has('process_ai'),
            'has_direct' => $request->has('direct_publish'),
            'has_file' => $request->hasFile('image_file')
        ]);

        // ভ্যালিডেশন
        $request->validate([
            'title'       => 'required|max:255',
            'content'     => 'required',
            'image_file'  => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url'   => 'nullable|url'
        ]);

        try {
            // --- ইমেজ আপলোড লজিক শুরু ---
            $finalImage = null;

            if ($request->hasFile('image_file')) {
                $file = $request->file('image_file');
                $path = $file->store('news-uploads', 'public');
                $finalImage = asset('storage/' . $path);
            } 
            elseif ($request->filled('image_url')) {
                $finalImage = $request->image_url;
            }
            // --- ইমেজ আপলোড লজিক শেষ ---

            // ২. নিউজ আইটেম তৈরি করা
            $news = NewsItem::create([
                'user_id'        => auth()->id(),
                'website_id'     => null,
                'title'          => $request->title,
                'content'        => $request->content,
                'thumbnail_url'  => $finalImage,
                'original_link'  => '#custom-' . uniqid(),
                'status'         => 'draft',
                'published_at'   => now(),
                'is_posted'      => false
            ]);

            // ৩. ডেটাবেসে সফলভাবে সেভ হওয়ার লগ
            Log::info('StoreCustom: News created successfully', [
                'news_id' => $news->id,
                'image'   => $finalImage
            ]);

            // ====================================================
            // 🔥 নতুন লজিক: বাটন অনুযায়ী অ্যাকশন
            // ====================================================

            // ১. যদি AI বাটনে ক্লিক করা হয়
            if ($request->has('process_ai')) {
                Log::info('StoreCustom: AI Processing requested', ['news_id' => $news->id]);
                
                $news->update(['status' => 'processing']);
                
                GenerateAIContent::dispatch($news->id, auth()->id());

                return redirect()->route('news.drafts')
                    ->with('success', 'AI প্রসেসিং শুরু হয়েছে!');
            }

            // ২. 🔥 যদি Direct Publish বাটনে ক্লিক করা হয়
            if ($request->has('direct_publish')) {
                Log::info('StoreCustom: Direct Publish requested', ['news_id' => $news->id]);

                // স্ট্যাটাস আপডেট
                $news->update(['status' => 'publishing']);

                // সরাসরি পাবলিশ জব কল করা (ক্রেডিট চেক স্কিপ করার জন্য true পাঠানো হলো)
                ProcessNewsPost::dispatch($news->id, auth()->id(), [], true);

                return redirect()->route('news.index') 
                    ->with('success', '🚀 পাবলিশিং শুরু হয়েছে! কিছুক্ষণের মধ্যে লাইভ হবে।');
            }

            // ৩. যদি শুধু সেভ বাটনে ক্লিক করা হয় (ডিফল্ট)
            Log::info('StoreCustom: News saved manually (Draft)', ['news_id' => $news->id]);
            
            return redirect()->route('news.drafts')
                ->with('success', 'নিউজ ড্রাফটে সেভ হয়েছে!');

        } catch (\Exception $e) {
            // ৪. এরর লগ
            Log::error('StoreCustom: Error creating news', [
                'user_id' => auth()->id(),
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString()
            ]);

            return back()->with('error', 'নিউজ সেভ করতে সমস্যা হয়েছে। লগ চেক করুন।')->withInput();
        }
    }
}