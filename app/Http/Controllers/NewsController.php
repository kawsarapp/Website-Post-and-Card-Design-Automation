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
use Illuminate\Support\Facades\DB;

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

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) return redirect()->route('login');

        $search = $request->input('search');
        $websiteId = $request->input('website');

        $query = NewsItem::with(['website' => function ($q) {
                $q->withoutGlobalScopes();
            }])
            ->where('user_id', $user->id)
            ->where('is_rewritten', 0)      // এখনো AI হাত দেয়নি
            ->whereNotNull('website_id')    // 🔥 ম্যানুয়াল পোস্ট বাদ (শুধুমাত্র ওয়েবসাইট থেকে আসা)
            ->where('status', '!=', 'processing'); 

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($websiteId) {
            $query->where('website_id', $websiteId);
        }

        $newsItems = $query->orderBy('id', 'desc')->paginate(20);
        
        $websites = \App\Models\Website::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->get();

        return view('news.index', compact('newsItems', 'websites'));
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
            ['key' => 'jonomot', 'name' => 'jonomot', 'image' => 'templates/jonomot.png', 'layout' => 'jonomot'],
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
		
		$categories = $settings->category_mapping ?? [];
        return view('news.studio', compact('newsItem', 'settings', 'availableTemplates', 'categories'));
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
			if (!auth()->user()->hasPermission('can_auto_post')) {
				return back()->with('error', 'আপনার অটোমেশন ব্যবহার করার অনুমতি নেই।');
			}

			$request->validate([
				'interval' => 'nullable|integer|min:1|max:60'
			]);

			$user = auth()->user();

			$settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);

			$settings->is_auto_posting = !$settings->is_auto_posting;

			if ($request->filled('interval')) {
				$settings->auto_post_interval = $request->interval;
			}

			if ($settings->is_auto_posting) {
				$settings->last_auto_post_at = now();
			}

			$settings->save();

			$status = $settings->is_auto_posting ? "চালু" : 'বন্ধ';
			return back()->with('success', "অটোমেশন সফলভাবে {$status} করা হয়েছে।");
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
            'category' => 'nullable',
            'extra_categories' => 'nullable|array',
            'image_file' => 'nullable|image|max:5120', // 5MB Max
            'image_url' => 'nullable|url'
        ]);

        $news = NewsItem::findOrFail($id);
        $user = Auth::user();
		
		
		// 🔥🔥🔥 FIX: ডেইলি লিমিট চেক যোগ করা হলো
        if ($user->role !== 'super_admin') {
             // ১. ক্রেডিট চেক
             if($user->credits <= 0) {
                return response()->json(['success' => false, 'message' => '❌ আপনার ক্রেডিট শেষ!']);
             }

             if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                 return response()->json(['success' => false, 'message' => '❌ আজকের ডেইলি পোস্ট লিমিট শেষ!']);
             }
        }

        $finalImage = $news->thumbnail_url; 
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('news-uploads', 'public');
            $finalImage = asset('storage/' . $path);
        } elseif ($request->filled('image_url')) {
            $finalImage = $request->image_url;
        }

        // ডাটাবেস আপডেট
        $news->update([
            'status'        => 'publishing',
            'title'         => $request->title,
            'content'       => $request->content,
			'ai_title'      => $request->title,
            'ai_content'    => $request->content,
            'thumbnail_url' => $finalImage,
            'error_message' => null,
            'updated_at'    => now()
        ]);

        // ক্যাটাগরি প্রসেসিং
        $categories = [];
        if ($request->filled('category')) $categories[] = $request->category;
        if ($request->filled('extra_categories') && is_array($request->extra_categories)) {
            $categories = array_merge($categories, $request->extra_categories);
        }
        $categories = array_values(array_unique($categories));
        if(empty($categories)) $categories = [1];

        // জবের জন্য কাস্টম ডাটা
        $customData = [
            'title'          => $request->title,
            'content'        => $request->content,
            'category_ids'   => $categories,
            'featured_image' => $finalImage,
			'skip_social'    => true
        ];

        \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id, $customData, true);

        return response()->json(['success' => true, 'message' => 'পরিবর্তন সেভ করা হয়েছে এবং পাবলিশিং শুরু হয়েছে!']);
    }
    // ==========================================
    // 🔥 NEW: AI FLOW & DRAFTS
    // ==========================================

   
   public function sendToAiQueue($id)
		{
			$news = NewsItem::findOrFail($id);
			$user = Auth::user();

			// ১. সুপার অ্যাডমিন না হলে ক্রেডিট এবং ডেইলি লিমিট চেক
			if ($user->role !== 'super_admin') {
				 if($user->credits <= 0) {
					return back()->with('error', 'আপনার ক্রেডিট শেষ!');
				 }

				 if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
					 return back()->with('error', 'আজকের ডেইলি লিমিট শেষ! আগামীকাল আবার চেষ্টা করুন।');
				 }
				 
				 try {
					 DB::transaction(function () use ($user, $news) {
						 $user->decrement('credits', 1);

						 \App\Models\CreditHistory::create([
							 'user_id' => $user->id,
							 'action_type' => 'ai_rewrite',
							 'description' => 'AI Processing: ' . \Illuminate\Support\Str::limit($news->title, 40),
							 'credits_change' => -1,
							 'balance_after' => $user->credits
						 ]);
					 });
				 } catch (\Exception $e) {
					 Log::error("Credit Deduction Failed: " . $e->getMessage());
					 return back()->with('error', 'সিস্টেম এরর! ক্রেডিট কাটা সম্ভব হয়নি।');
				 }
			}

			// ২. ডাবল প্রসেসিং প্রোটেকশন
			if ($news->status === 'processing') {
				return back()->with('error', 'এটি ইতিমধ্যেই প্রসেসিং হচ্ছে...');
			}

			// 🔥 পরিবর্তন: পুরোনো ডাটা মুছে ফেলা (যাতে ইউজার স্ক্রিনে পরিবর্তন বুঝতে পারে)
			$news->update([
				'status' => 'processing', 
				'error_message' => null,
				'ai_title' => 'AI লিখছে...', // কার্ডে তাৎক্ষণিক 'AI লিখছে' মেসেজ দেখাবে
				'ai_content' => null         // পুরোনো কন্টেন্ট ক্লিয়ার করে দেওয়া হলো
			]);

			// ৩. জব ডিসপ্যাচ করা
			\App\Jobs\GenerateAIContent::dispatch($news->id, $user->id);

			return back()->with('success', 'AI প্রসেসিং শুরু হয়েছে!');
		}
	
	
	public function drafts()
{
    $user = Auth::user();
    $settings = $user->settings;

    $query = NewsItem::with(['website' => function ($q) {
        $q->withoutGlobalScopes();
    }])
    ->where('user_id', $user->id)
    ->where(function($q) {
        // ১. যেগুলোর কাজ শুরু হয়েছে (Edited or AI rewritten)
        $q->where('is_rewritten', 1) 
          // ২. অথবা যেগুলো সাধারণ ম্যানুয়াল পোস্ট (অ্যাডমিন নিজে তৈরি করেছে)
          ->orWhere(function($subQ) {
              $subQ->whereNull('website_id')
                   ->whereNull('reporter_id'); 
          })
          // ৩. অথবা যেকোনো নিউজ যা বর্তমানে প্রসেসিং/পাবলিশিং অবস্থায় আছে
          ->orWhereIn('status', ['processing', 'publishing', 'published', 'failed']);
    });

    $drafts = $query->orderBy('updated_at', 'desc')->paginate(20);
    return view('news.drafts', compact('drafts', 'settings'));
}

public function updateDraft(Request $request, $id)
{
    $request->validate([
        'title' => 'required',
        'content' => 'required',
    ]);

    $news = NewsItem::findOrFail($id);
    
    $news->update([
        'title'         => $request->title,
        'content'       => $request->content,
        'ai_title'      => $request->title,
        'ai_content'    => $request->content,
		'is_posted'     => true,
        'status'        => 'draft',       // এখানে স্ট্যাটাস ড্রাফট থাকবে
        'is_rewritten'  => 1,             // এটি যোগ করুন যাতে ড্রাফট পেজে নিউজটি দেখা যায়
        'updated_at'    => now()
    ]);

    return response()->json(['success' => true, 'message' => 'ড্রাফট সফলভাবে সেভ হয়েছে।']);
}
		


    
    
	public function getDraftContent($id)
{
    // ১. নিউজটি খুঁজে বের করা এবং প্রয়োজনীয় রিলেশন লোড করা
    $news = NewsItem::with('lockedBy')->findOrFail($id);
    $user = Auth::user();

    // ২. লকিং সিস্টেম চেক (যাতে একই নিউজ একাধিক ব্যক্তি এডিট না করে)
    if ($news->locked_by_user_id && $news->locked_by_user_id !== $user->id) {
        return response()->json([
            'success' => false, 
            'message' => '⚠️ এটি বর্তমানে ' . ($news->lockedBy->name ?? 'অন্য একজন') . ' এডিট করছেন।'
        ]);
    }

    // ৩. নিউজটি বর্তমান ইউজারের জন্য লক করা
    $news->update([
        'locked_by_user_id' => $user->id,
        'locked_at' => now()
    ]);

    // ৪. কন্টেন্ট ও টাইটেল নির্ধারণ (AI কন্টেন্ট থাকলে সেটি অগ্রাধিকার পাবে)
    $title = !empty($news->ai_title) ? $news->ai_title : $news->title;
    $content = !empty($news->ai_content) ? $news->ai_content : $news->content;

    // ৫. অতিরিক্ত ছবি প্রসেসিং (tags কলামে JSON ডাটা ডিকোড করা)
    $extraImages = [];
    if (!empty($news->tags)) {
        $decodedTags = json_decode($news->tags, true);
        // যদি এটি একটি বৈধ অ্যারে হয়, তবে সেটি অতিরিক্ত ছবির লিস্ট
        if (is_array($decodedTags)) {
            $extraImages = $decodedTags;
        }
    }

    // ৬. রেসপন্স পাঠানো (মোডালে যা যা দরকার)
    return response()->json([
        'success'      => true,
        'title'        => $title,
        'content'      => $content,
        'image_url'    => $news->thumbnail_url,   // প্রধান ছবি
        'extra_images' => $extraImages,           // অতিরিক্ত ৪টি ছবির অ্যারে
        'location'     => $news->location,         // লোকেশন
        'original_link'=> $news->original_link,   // সোর্স লিংক
        'tags_string'  => is_array($extraImages) ? '' : $news->tags, // যদি ট্যাগস সাধারণ টেক্সট হয়
        'categories'   => $user->settings->category_mapping ?? [] // ক্যাটাগরি ম্যাপিং
    ]);
}
	
	/*
	public function unlockNews($id)
		{
			$news = NewsItem::withoutGlobalScopes()->findOrFail($id);
			
			// শুধুমাত্র যিনি লক করেছেন তিনিই আনলক করতে পারবেন
			if ($news->locked_by_user_id === auth()->id()) {
				$news->update([
					'locked_by_user_id' => null,
					'locked_at' => null
				]);
			}
			return response()->json(['success' => true]);
		}
		*/

    public function confirmPublish(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable'
        ]);

        $user = Auth::user();

        if ($user->role !== 'super_admin') {
             if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                 return response()->json(['success' => false, 'message' => '❌ আজকের ডেইলি পোস্ট লিমিট শেষ!']);
             }
        }
		

        $news = NewsItem::findOrFail($id);

        $customData = [
            'title' => $request->title,
            'content' => $request->content,
            'category_id' => $request->category,
			'skip_social' => true
        ];

        $news->update(['status' => 'publishing']);

        ProcessNewsPost::dispatch($news->id, $user->id, $customData);

        return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে!']);
    }
	
	public function publishManualFromIndex(Request $request, $id)
{
    // ১. ভ্যালিডেশন
    $request->validate([
        'title' => 'required',
        'content' => 'required',
        'image_file' => 'nullable|image|max:5120',
        'image_url' => 'nullable|url',
        'category' => 'nullable'
    ]);

    $news = NewsItem::findOrFail($id);
    $user = Auth::user();

    // ২. ডুপ্লিকেট পাবলিশ চেক (আপনার নতুন লজিক অনুযায়ী)
    if ($news->is_posted || $news->status === 'publishing') {
        return response()->json([
            'success' => false, 
            'message' => '⚠️ এই নিউজটি ইতিমধ্যেই পাবলিশ করা হয়েছে বা বর্তমানে পাবলিশিং প্রসেসে আছে!'
        ]);
    }

    // ৩. ইমেজ প্রসেসিং
    $finalImage = $news->thumbnail_url; 
    if ($request->hasFile('image_file')) {
        $path = $request->file('image_file')->store('news-uploads', 'public');
        $finalImage = asset('storage/' . $path);
    } elseif ($request->filled('image_url')) {
        $finalImage = $request->image_url;
    }

    // ৪. ক্যাটাগরি প্রসেসিং
    $categoryIds = $request->filled('category') ? [$request->category] : [1];

    // ৫. ডাটাবেস আপডেট (পাবলিশিং শুরু করার আগে স্টেট পরিবর্তন)
    $news->update([
        'title'         => $request->title,
        'content'       => $request->content,
        'ai_title'      => $request->title,   
        'ai_content'    => $request->content, 
        'thumbnail_url' => $finalImage,
        'status'        => 'publishing',
        'is_posted'     => true, // অ্যাকশন নেওয়া হয়েছে বুঝাতে true করে দেওয়া হলো
        'is_rewritten'  => 1,
        'updated_at'    => now()
    ]);

    // ৬. জবের জন্য ডাটা রেডি এবং ডিসপ্যাচ
    $customData = [
        'title'          => $news->title,
        'content'        => $news->content,
        'category_ids'   => $categoryIds,
        'featured_image' => $finalImage,
        'skip_social'    => true // ম্যানুয়াল পাবলিশে সোশ্যাল স্কিপ হবে
    ];

    \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id, $customData, true);

    return response()->json([
        'success' => true, 
        'message' => 'নিউজটি সফলভাবে পাবলিশিং কিউতে পাঠানো হয়েছে!'
    ]);
}

    // ==========================================
    // 🔥 SOCIAL & MANUAL POST
    // ==========================================

	public function postToWordPress($id, SocialPostService $socialPoster)
{
    $user = Auth::user();
    $settings = $user->settings;

    // ১. অটোমেশন চেক
    if ($settings && $settings->is_auto_posting) {
        return back()->with('error', 'অটোমেশন চালু আছে! ম্যানুয়াল পোস্ট করতে হলে আগে অটো পোস্ট OFF করুন।');
    }

    // 🔥 ফিক্স: WP অথবা Laravel যেকোনো একটা থাকলেই হবে
    $hasWP = $settings->wp_url && $settings->wp_username;
    $hasLaravel = $settings->post_to_laravel && $settings->laravel_site_url && $settings->laravel_api_token;

    if (!$settings || (!$hasWP && !$hasLaravel)) {
        return back()->with('error', 'দয়া করে সেটিংসে গিয়ে WordPress অথবা Laravel কানেক্ট করুন।');
    }

    $news = NewsItem::with(['website' => function ($query) {
        $query->withoutGlobalScopes();
    }])->findOrFail($id);

    if ($news->is_posted) {
        return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
    }

    // ২. ক্রেডিট ও লিমিট চেক (আপনার আগের লজিকই থাকছে)
    if ($user->role !== 'super_admin') {
        if ($user->credits <= 0) {
            return back()->with('error', 'আপনার ক্রেডিট শেষ!');
        }
        if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
            return back()->with('error', "আজকের ডেইলি লিমিট ({$user->daily_post_limit}টি) শেষ!");
        }

        try {
            DB::transaction(function () use ($user, $news) {
                $user->decrement('credits', 1);
                \App\Models\CreditHistory::create([
                    'user_id' => $user->id,
                    'action_type' => 'manual_post',
                    'description' => 'Manual Post: ' . \Illuminate\Support\Str::limit($news->title, 40),
                    'credits_change' => -1,
                    'balance_after' => $user->credits
                ]);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'ক্রেডিট সিস্টেমে সমস্যা হয়েছে। আবার চেষ্টা করুন।');
        }
    }

    // ৩. সোশ্যাল মিডিয়া পোস্টিং
    $cardImageUrl = $news->thumbnail_url;
    $newsLink     = $news->source_url;

    try {
        if ($settings->post_to_fb && !empty($settings->fb_page_id)) {
            $socialPoster->postToFacebook($settings, $news->title, $cardImageUrl, $newsLink);
        }
        if ($settings->post_to_telegram && !empty($settings->telegram_channel_id)) {
            $socialPoster->postToTelegram($settings, $news->title, $cardImageUrl, $newsLink);
        }
    } catch (\Exception $e) {
        Log::error("Social Post Error: " . $e->getMessage());
    }

    // ৪. জব ডিসপ্যাচ
    $news->update(['status' => 'publishing']);
    
    // লজিক ঠিক আছে, জবের ভেতরেই WP/Laravel হ্যান্ডেল হবে
    ProcessNewsPost::dispatch($news->id, $user->id, [], true);

    return back()->with('success', 'পোস্ট প্রসেসিং শুরু হয়েছে! ⏳ (Laravel/WP)');
}
    
    
    public function destroy($id)
    {
        $news = NewsItem::findOrFail($id);
        
        // 🔥 ফিক্স: ইউজার ভ্যালিডেশন (অন্যের নিউজ ডিলিট করতে পারবে না)
        if (auth()->user()->role !== 'super_admin' && $news->user_id !== auth()->id()) {
            return back()->with('error', 'আপনার অনুমতি নেই (Unauthorized Action)।');
        }

        $news->delete();
        return back()->with('success', 'নিউজটি সফলভাবে মুছে ফেলা হয়েছে।');
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

            $news->update(['status' => 'publishing']);

            // জবে 'true' পাঠানো হচ্ছে
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
	
	
	
		// NewsController.php এর ভেতরে যেকোনো জায়গায় যোগ করুন

		public function checkScrapeStatus()
		{
			$isScraping = \Illuminate\Support\Facades\Cache::has('scraping_user_' . auth()->id());
			
			if (!$isScraping && request()->query('force_wait') === 'true') {
				sleep(2); // ২ সেকেন্ড ওয়েট
				$isScraping = \Illuminate\Support\Facades\Cache::has('scraping_user_' . auth()->id());
			}
			
			return response()->json([
				'scraping' => $isScraping
			]);
		}
		
		
		
		
		
		
		// ==========================================
    // 🔥 STUDIO DIRECT PUBLISH METHOD
    // ==========================================
   
   
   
   public function publishStudioDesign(Request $request, $id)
    {
        $request->validate([
            'design_image' => 'required|image|max:20480',
            'category_id'  => 'nullable',
            'social_caption' => 'nullable|string'
        ]);

        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        // ১. সাধারণ চেক (User & Credit)
        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) return response()->json(['success' => false, 'message' => 'ক্রেডিট শেষ!']);
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                return response()->json(['success' => false, 'message' => 'ডেইলি লিমিট শেষ!']);
            }
        }

        // 🔥🔥 NEW: পাবলিশিং লজিক চেক (আপনার রিকোয়ারমেন্ট অনুযায়ী)
        $isSocialOnly = $request->has('social_only') && $request->social_only == '1';

        if ($news->is_posted) {
            // কেস ১: নিউজটি অলরেডি ওয়েবসাইটে পাবলিশ করা আছে
            if (!$isSocialOnly) {
                // ইউজার আবারও ওয়েবসাইটে পাবলিশ করতে চাচ্ছে -> এটা বন্ধ করতে হবে
                return response()->json([
                    'success' => false, 
                    'message' => '⚠️ এই নিউজটি ইতিমধ্যেই ওয়েবসাইটে পাবলিশ করা হয়েছে! আপনি চাইলে "Only Social" সিলেক্ট করে রিশেয়ার করতে পারেন।'
                ]);
            }
            // যদি "Only Social" হয়, তবে আমরা এলাউ করব (রিশেয়ার করার জন্য)
        } else {
            // কেস ২: নিউজটি এখনো ড্রাফট (পাবলিশ হয়নি)
            if ($isSocialOnly) {
                // ইউজার ওয়েবসাইট বাদ দিয়ে শুধু সোশ্যাল করতে চাচ্ছে -> এটা বন্ধ করতে হবে (আপনার রিকোয়ারমেন্ট)
                return response()->json([
                    'success' => false, 
                    'message' => '⚠️ নিউজটি এখনো ওয়েবসাইটে পাবলিশ হয়নি! "Only Social" পোস্ট করার আগে অবশ্যই ওয়েবসাইটে পাবলিশ করতে হবে।'
                ]);
            }
        }

        try {
            if ($request->hasFile('design_image')) {
                
                $path = $request->file('design_image')->store('news-cards/studio', 'public');
                $studioImageUrl = asset('storage/' . $path);
                
                // যদি ওয়েবসাইটের পোস্ট না হয় (Only Social), তবে স্ট্যাটাস চেইঞ্জ করার দরকার নেই
                if (!$isSocialOnly) {
                    $news->update([
                        'status' => 'publishing',
                        'updated_at' => now()
                    ]);
                }
                
                // ক্যাপশন এবং ক্যাটাগরি প্রসেসিং
                $socialCaption = $request->filled('social_caption') 
                                ? $request->social_caption 
                                : (!empty($news->ai_title) ? $news->ai_title : $news->title);

                $categoryIds = $request->filled('category_id') ? [$request->category_id] : [1];

                // জবে ডাটা পাঠানো
                $customData = [
                    'title'          => $news->title, 
                    'content'        => $news->content,
                    'social_only'    => $isSocialOnly,
                    'website_image'  => $news->thumbnail_url,
                    'social_image'   => $studioImageUrl,
                    'category_ids'   => $categoryIds,
                    'social_caption' => $socialCaption 
                ];

                \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id, $customData, true);

                return response()->json(['success' => true, 'message' => 'পাবলিশিং প্রসেস শুরু হয়েছে!']);
            }

            return response()->json(['success' => false, 'message' => 'ইমেজ পাওয়া যায়নি।']);

        } catch (\Exception $e) {
            Log::error("Studio Publish Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'সার্ভার এরর: ' . $e->getMessage()]);
        }
    }
	
	
	public function getGithubVersion()
	{
		return Cache::remember('github_version', 3600, function () {
			try {
				$response = Http::get('https://api.github.com/repos/আপনার_ইউজারনেম/Website-Post-and-Card-Design-Automation/releases/latest');
				
				if ($response->successful()) {
					return $response->json()['tag_name']; // যেমন: v1.0.1
				}
				return 'v1.0.0';
			} catch (\Exception $e) {
				return 'v1.0.0';
			}
		});
	}
	
	
	
	
	
	
	
}