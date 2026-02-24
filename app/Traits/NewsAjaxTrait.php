<?php

namespace App\Traits;

use App\Models\NewsItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

trait NewsAjaxTrait
{
    // 🔥 হেল্পার ফাংশন: স্টাফ বা রিপোর্টার হলে তার অ্যাডমিনকে বের করবে
    private function getEffectiveAdminForAjax() {
        $user = Auth::user();
        return in_array($user->role, ['staff', 'reporter']) ? User::find($user->parent_id) : $user;
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
                ])->timeout(15)->get($url);

                if ($response->successful()) {
                    return ['body' => base64_encode($response->body()), 'type' => $response->header('Content-Type')];
                }
            } catch (\Exception $e) {}
            return null;
        });

        if (!$imageData) abort(404);
        return response(base64_decode($imageData['body']))
            ->header('Content-Type', $imageData['type'])
            ->header('Cache-Control', 'public, max-age=2592000, immutable')
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function suggestLinks(Request $request)
    {
        $keyword = $request->input('keyword');
        if (empty($keyword)) return response()->json([]);

        $adminUser = $this->getEffectiveAdminForAjax();

        // 🔥 অ্যাডমিন এবং স্টাফ উভয়ের নিউজ পুল থেকে লিংক সাজেস্ট করবে
        $relatedNews = \App\Models\NewsItem::withoutGlobalScopes()
            ->whereIn('user_id', [$adminUser->id, Auth::id()])
            ->where('title', 'LIKE', "%{$keyword}%")
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $formattedNews = $relatedNews->map(function ($news) {
            $url = $news->live_url ?? $news->original_link ?? url('/news/' . $news->id);
            return [
                'id' => $news->id,
                'title' => $news->ai_title ?? $news->title ?? 'Untitled News',
                'live_url' => $url
            ];
        });

        $validNews = $formattedNews->filter(function ($item) {
            return !empty($item['live_url']);
        })->values();

        return response()->json($validNews);
    }

    public function toggleQueue($id)
    {
        $adminUser = $this->getEffectiveAdminForAjax();
        $news = NewsItem::withoutGlobalScopes()->whereIn('user_id', [$adminUser->id, Auth::id()])->findOrFail($id);
        
        if ($news->status == 'published') return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');
        
        $news->is_queued = !$news->is_queued;
        $news->save();
        return back()->with('success', $news->is_queued ? '📌 অটো-পোস্ট লিস্টে যুক্ত হয়েছে' : 'লিস্ট থেকে সরানো হয়েছে');
    }

    public function toggleAutomation(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasPermission('can_auto_post') && $user->role !== 'super_admin') {
            return back()->with('error', 'আপনার অটোমেশন ব্যবহার করার অনুমতি নেই।');
        }
        
        $request->validate(['interval' => 'nullable|integer|min:1|max:60']);
        
        $adminUser = $this->getEffectiveAdminForAjax();
        
        // 🔥 অ্যাডমিনের সেটিংসে পরিবর্তন সেভ হবে
        $settings = $adminUser->settings()->firstOrCreate(['user_id' => $adminUser->id]);
        $settings->is_auto_posting = !$settings->is_auto_posting;
        
        if ($request->filled('interval')) $settings->auto_post_interval = $request->interval;
        if ($settings->is_auto_posting) $settings->last_auto_post_at = now();
        
        $settings->save();
        return back()->with('success', "অটোমেশন সফলভাবে " . ($settings->is_auto_posting ? "চালু" : 'বন্ধ') . " করা হয়েছে।");
    }

    public function checkAutoPostStatus()
    {
        $adminUser = $this->getEffectiveAdminForAjax();
        $settings = $adminUser->settings; // 🔥 অ্যাডমিনের সেটিংস
        
        if (!$settings || !$settings->is_auto_posting) return response()->json(['status' => 'off']);
        
        $nextPost = (\Carbon\Carbon::parse($settings->last_auto_post_at ?? now()))->addMinutes($settings->auto_post_interval ?? 10);
        return response()->json(['status' => 'on', 'next_post_time' => $nextPost->format('Y-m-d H:i:s')]);
    }

    public function checkScrapeStatus()
    {
        $adminUser = $this->getEffectiveAdminForAjax();
        
        // 🔥 জবের আইডি যেহেতু অ্যাডমিনের দেওয়া, তাই ক্যাশেও অ্যাডমিনের আইডি চেক করবে
        $isScraping = Cache::has('scraping_user_' . $adminUser->id);
        
        if (!$isScraping && request()->query('force_wait') === 'true') {
            sleep(2); 
            $isScraping = Cache::has('scraping_user_' . $adminUser->id);
        }
        return response()->json(['scraping' => $isScraping]);
    }

    public function checkDraftUpdates(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return response()->json([]);
        
        // শুধু স্ট্যাটাস আপডেট দেখাবে
        return response()->json(NewsItem::withoutGlobalScopes()->whereIn('id', $ids)->get(['id', 'status', 'error_message']));
    }

    public function handlePreviewFeedback(Request $request, $id) 
    {
        $adminUser = $this->getEffectiveAdminForAjax();
        $news = NewsItem::withoutGlobalScopes()->whereIn('user_id', [$adminUser->id, Auth::id()])->findOrFail($id);
        
        if ($request->input('status') == 'approved') {
            $news->status = 'draft'; 
            $news->error_message = '✅ Boss Approved this news.';
        } else {
            $news->status = 'failed';
            $news->error_message = '❌ Rejected: ' . $request->input('note');
        }
        $news->save();
        return back()->with('success', 'মতামত গ্রহণ করা হয়েছে।');
    }
}