<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use App\Models\UserSetting;
use App\Models\Template;
use App\Services\NewsScraperService;
use App\Services\AIWriterService;
use App\Services\WordPressService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ProcessNewsPost;
use App\Jobs\GenerateAIContent;
use App\Traits\NewsDraftsTrait;
use App\Traits\NewsPublishingTrait;
use App\Traits\NewsAjaxTrait;

class NewsController extends Controller
{
    // 🔥 Traits ব্যবহার করে বিশাল কোডকে সুন্দরভাবে কল করা হলো
    use NewsDraftsTrait, NewsPublishingTrait, NewsAjaxTrait;

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

        $query = NewsItem::with(['website' => function ($q) { $q->withoutGlobalScopes(); }])
            ->where('user_id', $user->id)
            ->where('is_rewritten', 0)
            ->whereNotNull('website_id')
            ->where('status', '!=', 'processing'); 

        if ($search) $query->where('title', 'like', "%{$search}%");
        if ($websiteId) $query->where('website_id', $websiteId);

        $newsItems = $query->orderBy('id', 'desc')->paginate(20);
        $websites = \App\Models\Website::withoutGlobalScopes()->where('user_id', $user->id)->get();

        return view('news.index', compact('newsItems', 'websites'));
    }

    public function studio($id)
    {
        $newsItem = NewsItem::with(['website' => function ($query) { $query->withoutGlobalScopes(); }])->findOrFail($id);
        $user = Auth::user();
        $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);

        // 🔥 আগের সব টেমপ্লেট হুবহু ফিরিয়ে আনা হয়েছে
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
            ['key' => 'todayeventsSingle', 'name' => 'todayeventsSingle', 'image' => 'templates/todayeventsSingle.png', 'layout' => 'todayeventsSingle'],
            ['key' => 'todayeventsSingle1', 'name' => 'todayeventsSingle1', 'image' => 'templates/todayeventsSingle1.png', 'layout' => 'todayeventsSingle1'],
            ['key' => 'WatchBangladesh', 'name' => 'WatchBangladesh', 'image' => 'templates/WatchBangladesh.png', 'layout' => 'WatchBangladesh'],
            ['key' => 'TodayEventsDualFrame', 'name' => 'TodayEventsDualFrame', 'image' => 'templates/TodayEventsDualFrame.png', 'layout' => 'TodayEventsDualFrame'],
        ];

        try {
            $dbTemplates = Template::where('is_active', true)->latest()->get()->map(function($t) {
                return [ 'key' => 'custom_db_' . $t->id, 'name' => $t->name, 'image' => $t->thumbnail_url, 'layout' => 'dynamic', 'layout_data' => $t->layout_data, 'frame_url' => $t->frame_url ];
            })->toArray();
            $allTemplates = array_merge($dbTemplates, $allTemplates); 
        } catch (\Exception $e) {}

        $allowed = $settings->allowed_templates ?? [];
        $availableTemplates = [];

        if ($user->role === 'super_admin' || $user->role === 'admin') {
            $availableTemplates = $allTemplates;
        } else {
            foreach ($allTemplates as $template) {
                if ($template['layout'] === 'dynamic' || in_array($template['key'], $allowed)) $availableTemplates[] = $template;
            }
        }
        
        $categories = $settings->category_mapping ?? [];
        return view('news.studio', compact('newsItem', 'settings', 'availableTemplates', 'categories'));
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
                ProcessNewsPost::dispatch($news->id, auth()->id(), ['category_ids' => $request->filled('category') ? [$request->category] : [1]], true);
                return redirect()->route('news.index')->with('success', 'পাবলিশিং শুরু!');
            }

            return redirect()->route('news.drafts')->with('success', 'ড্রাফটে সেভ হয়েছে!');
        } catch (\Exception $e) { return back()->with('error', 'সেভ করতে সমস্যা।')->withInput(); }
    }

    public function publicPreview($id) {
        $news = NewsItem::findOrFail($id); 
        return view('news.public_preview', compact('news'));
    }

    public function destroy($id)
    {
        $news = NewsItem::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $news->user_id !== auth()->id()) return back()->with('error', 'অনুমতি নেই।');
        $news->delete();
        return back()->with('success', 'মুছে ফেলা হয়েছে।');
    }


    // AI Status Checking for Smart Polling
    public function checkStatus(\Illuminate\Http\Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json([]);
        }

        $items = \App\Models\NewsItem::withoutGlobalScopes()
                    ->whereIn('id', $ids)
                    ->get(['id', 'status', 'error_message', 'ai_title', 'title']);

        return response()->json($items);
    }


}