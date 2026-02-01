<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\UserSetting;
use App\Models\Template;
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
use Illuminate\Support\Facades\Cache;


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
            ->where('is_rewritten', 0)
            ->whereNotNull('website_id')
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
        ['key' => 'Bangladeshmail24', 'name' => 'Bangladeshmail24', 'image' => 'templates/Bangladeshmail24.png', 'layout' => 'Bangladeshmail24'],
    ];

        // ডাইনামিক টেমপ্লেট লোড
        try {
            $dbTemplates = Template::where('is_active', true)->latest()->get()->map(function($t) {
                return [
                    'key' => 'custom_db_' . $t->id,
                    'name' => $t->name,
                    'image' => $t->thumbnail_url,
                    'layout' => 'dynamic', 
                    'layout_data' => $t->layout_data,
                    'frame_url' => $t->frame_url
                ];
            })->toArray();

            $allTemplates = array_merge($dbTemplates, $allTemplates); 
        } catch (\Exception $e) {
            Log::error("Template Fetch Error: " . $e->getMessage());
        }

        $allowed = $settings->allowed_templates ?? [];
        $availableTemplates = [];

        if ($user->role === 'super_admin' || $user->role === 'admin') {
            $availableTemplates = $allTemplates;
        } else {
            foreach ($allTemplates as $template) {
                if ($template['layout'] === 'dynamic' || in_array($template['key'], $allowed)) {
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

			$cacheKey = 'proxy_img_' . md5($url);

			$imageData = Cache::remember($cacheKey, now()->addDays(7), function () use ($url) {
				try {
					$response = Http::withHeaders([
						'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
					])->timeout(15)->get($url); // টাইমআউট ১৫ সেকেন্ড করা হলো

					if ($response->successful()) {
						return [
							'body' => base64_encode($response->body()), // বাইনারি ডাটা সেফলি রাখার জন্য এনকোড
							'type' => $response->header('Content-Type')
						];
					}
				} catch (\Exception $e) {
					\Log::error("Proxy Image Error for URL [{$url}]: " . $e->getMessage());
				}
				return null;
			});

			if (!$imageData) abort(404);

			// ৩. রেসপন্স পাঠানো এবং ব্রাউজারকেও ৩০ দিন ক্যাশ করতে বলা
			return response(base64_decode($imageData['body']))
				->header('Content-Type', $imageData['type'])
				->header('Cache-Control', 'public, max-age=2592000, immutable')
				->header('Access-Control-Allow-Origin', '*'); // ক্যানভাসে ড্র করার জন্য দরকার
		}
    
    public function toggleQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        if ($news->status == 'published') return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
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
    
    // ==========================================
    // 🔥 FIXED: Publish Draft with Hashtags & Images
    // ==========================================
    public function publishDraft(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category' => 'nullable',
            'extra_categories' => 'nullable|array',
            'image_file' => 'nullable|image|max:5120',
            'image_url' => 'nullable|url',
            'hashtags' => 'nullable|string' // ✅ ভ্যালিডেশন অ্যাড করা হলো
        ]);

        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        if ($news->status == 'published' && $user->role !== 'super_admin') {
            if($user->credits <= 0) {
                return response()->json(['success' => false, 'message' => '❌ ক্রেডিট নেই!']);
            }
            DB::transaction(function () use ($user, $news) {
                $user->decrement('credits', 1);
                \App\Models\CreditHistory::create([
                    'user_id' => $user->id,
                    'action_type' => 'edit_published',
                    'description' => 'Update Published: ' . \Illuminate\Support\Str::limit($news->title, 40),
                    'credits_change' => -1,
                    'balance_after' => $user->credits
                ]);
            });
        } 
        elseif ($user->role !== 'super_admin') {
            if($user->credits <= 0) return response()->json(['success' => false, 'message' => '❌ ক্রেডিট শেষ!']);
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

        // ✅ ডাটাবেসে আপডেট (হ্যাসট্যাগ সহ)
        $news->update([
            'status'        => 'publishing',
            'title'         => $request->title,
            'content'       => $request->content,
            'ai_title'      => $request->title,
            'ai_content'    => $request->content,
            'thumbnail_url' => $finalImage,
            'hashtags'      => $request->hashtags, // 🔥 এটি মিসিং ছিল
            'error_message' => null,
            'updated_at'    => now()
        ]);

        $categories = [];
        if ($request->filled('category')) $categories[] = $request->category;
        if ($request->filled('extra_categories') && is_array($request->extra_categories)) {
            $categories = array_merge($categories, $request->extra_categories);
        }
        $categories = array_values(array_unique($categories));
        if(empty($categories)) $categories = [1];

        // ✅ জবের জন্য কাস্টম ডাটা
        $customData = [
            'title'          => $request->title,
            'content'        => $request->content,
            'category_ids'   => $categories,
            'featured_image' => $finalImage,
            'hashtags'       => $request->hashtags, // 🔥 জবে পাঠানো হচ্ছে
            'skip_social'    => true
        ];

        \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id, $customData, true);

        return response()->json(['success' => true, 'message' => 'আপডেট শুরু হয়েছে! কিছুক্ষণের মধ্যে লাইভ হবে।']);
    }

    public function sendToAiQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'super_admin') {
             if($user->credits <= 0) return back()->with('error', 'আপনার ক্রেডিট শেষ!');
             if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                 return back()->with('error', 'আজকের ডেইলি লিমিট শেষ!');
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
                 return back()->with('error', 'ক্রেডিট কাটা সম্ভব হয়নি।');
             }
        }

        if ($news->status === 'processing') return back()->with('error', 'এটি ইতিমধ্যেই প্রসেসিং হচ্ছে...');

        $news->update([
            'status' => 'processing', 
            'error_message' => null,
            'ai_title' => 'AI লিখছে...',
            'ai_content' => null
        ]);

        \App\Jobs\GenerateAIContent::dispatch($news->id, $user->id);
        return back()->with('success', 'AI প্রসেসিং শুরু হয়েছে!');
    }
    
    public function drafts()
    {
        $user = Auth::user();
        $settings = $user->settings;

        $drafts = NewsItem::with(['website' => function ($q) {
                $q->withoutGlobalScopes();
            }])
            ->where('user_id', $user->id)
            ->where(function($q) {
                $q->where('is_rewritten', 1) 
                  ->orWhere(function($subQ) {
                      $subQ->whereNull('website_id')->whereNull('reporter_id'); 
                  })
                  ->orWhereIn('status', ['processing', 'publishing', 'failed']);
            })
            ->where('status', '!=', 'published') 
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('news.drafts', compact('drafts', 'settings'));
    }

    public function published()
    {
        $user = Auth::user();
        $settings = $user->settings;

        $published = NewsItem::with(['website' => function ($q) {
            $q->withoutGlobalScopes();
        }])
        ->where('user_id', $user->id)
        ->where('status', 'published')
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return view('news.published', compact('published', 'settings'));
    }

    public function updateDraft(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'image_url' => 'nullable|url',
            'hashtags' => 'nullable|string'
        ]);

        $news = auth()->user()->newsItems()->findOrFail($id);
        
        if ($request->hasFile('image_file')) {
            try {
                $file = $request->file('image_file');
                $filename = 'news_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('news-images', $filename, 'public');
                $news->thumbnail_url = asset('storage/' . $path);
            } catch (\Exception $e) {
                Log::error("Image Upload Failed: " . $e->getMessage());
            }
        } 
        elseif ($request->filled('image_url')) {
            $news->thumbnail_url = $request->image_url;
        }

        $news->title = $request->title;
        $news->ai_title = $request->title; 
        $news->content = $request->content;
        $news->ai_content = $request->content;
        $news->hashtags = $request->hashtags; // ✅ ড্রাফটে সেভ
        $news->is_rewritten = 1;
        $news->status = 'draft';
        $news->updated_at = now();
        
        $news->save();

        return response()->json(['success' => true, 'message' => 'ড্রাফট এবং ইমেজ সফলভাবে সেভ হয়েছে।']);
    }
    
    public function getDraftContent($id)
    {
        $news = NewsItem::with('lockedBy')->findOrFail($id);
        $user = Auth::user();

        if ($news->locked_by_user_id && $news->locked_by_user_id !== $user->id) {
            return response()->json([
                'success' => false, 
                'message' => '⚠️ এটি বর্তমানে ' . ($news->lockedBy->name ?? 'অন্য একজন') . ' এডিট করছেন।'
            ]);
        }

        $news->update(['locked_by_user_id' => $user->id, 'locked_at' => now()]);

        $title = !empty($news->ai_title) ? $news->ai_title : $news->title;
        $content = !empty($news->ai_content) ? $news->ai_content : $news->content;

        $extraImages = [];
        if (!empty($news->tags)) {
            $decodedTags = json_decode($news->tags, true);
            if (is_array($decodedTags)) $extraImages = $decodedTags;
        }

        return response()->json([
            'success'      => true,
            'title'        => $title,
            'content'      => $content,
            'hashtags'     => $news->hashtags, // ✅ ড্রাফট লোড করার সময় হ্যাসট্যাগ পাঠানো
            'image_url'    => $news->thumbnail_url,
            'extra_images' => $extraImages,
            'location'     => $news->location,
            'original_link'=> $news->original_link,
            'categories'   => $user->settings->category_mapping ?? []
        ]);
    }

    public function confirmPublish(Request $request, $id)
    {
        $request->validate(['title' => 'required', 'content' => 'required', 'category' => 'nullable']);
        $user = Auth::user();

        if ($user->role !== 'super_admin') {
             if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) {
                 return response()->json(['success' => false, 'message' => '❌ আজকের ডেইলি লিমিট শেষ!']);
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
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'image_file' => 'nullable|image|max:5120',
            'image_url' => 'nullable|url',
            'category' => 'nullable'
        ]);

        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        if ($news->status === 'published' || $news->status === 'publishing') {
            return response()->json(['success' => false, 'message' => '⚠️ এটি ইতিমধ্যেই প্রসেসিং বা পাবলিশড!']);
        }

        $finalImage = $news->thumbnail_url; 
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('news-uploads', 'public');
            $finalImage = asset('storage/' . $path);
        } elseif ($request->filled('image_url')) {
            $finalImage = $request->image_url;
        }

        $news->update([
            'title'         => $request->title,
            'content'       => $request->content,
            'ai_title'      => $request->title,   
            'ai_content'    => $request->content, 
            'thumbnail_url' => $finalImage,
            'status'        => 'publishing',
            'is_rewritten'  => 1,
            'updated_at'    => now()
        ]);

        $customData = [
            'title'          => $news->title,
            'content'        => $news->content,
            'category_ids'   => [$request->category ?? 1],
            'featured_image' => $finalImage,
            'skip_social'    => true 
        ];

        \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id, $customData, true);
        return response()->json(['success' => true, 'message' => 'পাবলিশিং কিউতে পাঠানো হয়েছে!']);
    }

    public function postToWordPress($id, SocialPostService $socialPoster)
    {
        $user = Auth::user();
        $settings = $user->settings;

        if ($settings && $settings->is_auto_posting) return back()->with('error', 'অটোমেশন OFF করুন।');

        $hasWP = $settings->wp_url && $settings->wp_username;
        $hasLaravel = $settings->post_to_laravel && $settings->laravel_site_url && $settings->laravel_api_token;

        if (!$settings || (!$hasWP && !$hasLaravel)) return back()->with('error', 'সেটিংসে গিয়ে কানেক্ট করুন।');

        $news = NewsItem::with(['website' => function ($query) { $query->withoutGlobalScopes(); }])->findOrFail($id);
        if ($news->status == 'published') return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');

        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) return back()->with('error', 'ক্রেডিট শেষ!');
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) return back()->with('error', "ডেইলি লিমিট শেষ!");

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
            } catch (\Exception $e) { return back()->with('error', 'ক্রেডিট সিস্টেমে সমস্যা।'); }
        }

        try {
            if ($settings->post_to_fb && !empty($settings->fb_page_id)) $socialPoster->postToFacebook($settings, $news->title, $news->thumbnail_url, $news->source_url);
            if ($settings->post_to_telegram && !empty($settings->telegram_channel_id)) $socialPoster->postToTelegram($settings, $news->title, $news->thumbnail_url, $news->source_url);
        } catch (\Exception $e) { Log::error("Social Error: " . $e->getMessage()); }

        $news->update(['status' => 'publishing']);
        ProcessNewsPost::dispatch($news->id, $user->id, [], true);
        return back()->with('success', 'প্রসেসিং শুরু হয়েছে!');
    }
    
    public function destroy($id)
    {
        $news = NewsItem::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $news->user_id !== auth()->id()) return back()->with('error', 'অনুমতি নেই।');
        $news->delete();
        return back()->with('success', 'মুছে ফেলা হয়েছে।');
    }

    public function create() { return view('news.create'); }

    public function storeCustom(Request $request)
    {
        $request->validate(['title' => 'required|max:255', 'content' => 'required', 'image_file' => 'nullable|image|max:5120', 'image_url' => 'nullable|url']);
        try {
            $finalImage = null;
            if ($request->hasFile('image_file')) $finalImage = asset('storage/' . $request->file('image_file')->store('news-uploads', 'public'));
            elseif ($request->filled('image_url')) $finalImage = $request->image_url;

            $news = NewsItem::create([
                'user_id' => auth()->id(), 'title' => $request->title, 'content' => $request->content,
                'thumbnail_url' => $finalImage, 'original_link' => '#custom-' . uniqid(), 'status' => 'draft',
                'published_at' => now()
            ]);

            if ($request->has('process_ai')) {
                $news->update(['status' => 'processing']);
                GenerateAIContent::dispatch($news->id, auth()->id());
                return redirect()->route('news.drafts')->with('success', 'AI প্রসেসিং শুরু!');
            }

            if ($request->has('direct_publish')) {
                $news->update(['status' => 'publishing']);
                ProcessNewsPost::dispatch($news->id, auth()->id(), [], true);
                return redirect()->route('news.index')->with('success', 'পাবলিশিং শুরু!');
            }

            return redirect()->route('news.drafts')->with('success', 'ড্রাফটে সেভ হয়েছে!');
        } catch (\Exception $e) { return back()->with('error', 'সেভ করতে সমস্যা।')->withInput(); }
    }

    public function checkScrapeStatus()
    {
        $isScraping = Cache::has('scraping_user_' . auth()->id());
        if (!$isScraping && request()->query('force_wait') === 'true') {
            sleep(2); $isScraping = Cache::has('scraping_user_' . auth()->id());
        }
        return response()->json(['scraping' => $isScraping]);
    }

    public function publishStudioDesign(Request $request, $id)
    {
        $request->validate(['design_image' => 'required|image|max:20480', 'category_id' => 'nullable', 'social_caption' => 'nullable|string']);
        $news = NewsItem::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'super_admin') {
            if ($user->credits <= 0) return response()->json(['success' => false, 'message' => 'ক্রেডিট শেষ!']);
            if (method_exists($user, 'hasDailyLimitRemaining') && !$user->hasDailyLimitRemaining()) return response()->json(['success' => false, 'message' => 'ডেইলি লিমিট শেষ!']);
        }

        $isSocialOnly = $request->has('social_only') && $request->social_only == '1';
        if ($news->status == 'published' && !$isSocialOnly) return response()->json(['success' => false, 'message' => '⚠️ এটি ইতিমধ্যেই পাবলিশড! রিশেয়ারের জন্য Only Social ব্যবহার করুন।']);
        if ($news->status != 'published' && $isSocialOnly) return response()->json(['success' => false, 'message' => '⚠️ আগে ওয়েবসাইটে পাবলিশ করুন।']);

        try {
            if ($request->hasFile('design_image')) {
                $studioImageUrl = asset('storage/' . $request->file('design_image')->store('news-cards/studio', 'public'));
                if (!$isSocialOnly) $news->update(['status' => 'publishing', 'updated_at' => now()]);
                
                $socialCaption = $request->filled('social_caption') ? $request->social_caption : ($news->ai_title ?? $news->title);
                $customData = [
                    'title' => $news->title, 'content' => $news->content, 'social_only' => $isSocialOnly,
                    'website_image' => $news->thumbnail_url, 'social_image' => $studioImageUrl,
                    'category_ids' => [$request->category_id ?? 1], 'social_caption' => $socialCaption 
                ];

                \App\Jobs\ProcessNewsPost::dispatch($news->id, $user->id, $customData, true);
                return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে!']);
            }
            return response()->json(['success' => false, 'message' => 'ইমেজ নেই।']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'সার্ভার এরর।']); }
    }

    public function publicPreview($id) {
        $news = NewsItem::findOrFail($id); 
        return view('news.public_preview', compact('news'));
    }

    public function handlePreviewFeedback(Request $request, $id) {
        $news = NewsItem::findOrFail($id);
        $status = $request->input('status');

        if ($status == 'approved') {
            $news->status = 'draft'; 
            $news->error_message = '✅ Boss Approved this news.';
        } else {
            $news->status = 'failed';
            $news->error_message = '❌ Boss Rejected: ' . $request->input('note');
        }
        
        $news->save();
        return back()->with('success', 'মতামত গ্রহণ করা হয়েছে।');
    }

    public function checkDraftUpdates(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return response()->json([]);
        $updates = NewsItem::whereIn('id', $ids)->get(['id', 'status', 'error_message']);
        return response()->json($updates);
    }
}