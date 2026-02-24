<?php

namespace App\Traits;

use App\Models\NewsItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessNewsPost;
use App\Jobs\GenerateAIContent;
use App\Services\SocialPostService;

trait NewsPublishingTrait
{
    // 🔥 হেল্পার ফাংশন: স্টাফ বা রিপোর্টার হলে তার অ্যাডমিনকে বের করবে
    private function getEffectiveAdmin() {
        $user = Auth::user();
        return in_array($user->role, ['staff', 'reporter']) ? User::find($user->parent_id) : $user;
    }

    public function publishDraft(Request $request, $id)
    {
        $request->validate([
            'title' => 'required', 'content' => 'required', 'category' => 'nullable',
            'extra_categories' => 'nullable|array', 'image_file' => 'nullable|image|max:5120',
            'image_url' => 'nullable|url', 'hashtags' => 'nullable|string'
        ]);

        $news = NewsItem::findOrFail($id);
        $adminUser = $this->getEffectiveAdmin(); // 🔥 অ্যাডমিনের একাউন্ট
        $staffId = Auth::id() !== $adminUser->id ? Auth::id() : null; // 🔥 Staff ID বের করা হলো

        if ($news->status == 'published' && $adminUser->role !== 'super_admin') {
            if($adminUser->credits <= 0) return response()->json(['success' => false, 'message' => '❌ ক্রেডিট নেই!']);
            DB::transaction(function () use ($adminUser, $news, $staffId) {
                $adminUser->decrement('credits', 1);
                \App\Models\CreditHistory::create([
                    'user_id' => $adminUser->id, 
                    'staff_id' => $staffId, // 🔥 স্টাফ আইডি ট্র্যাকিং
                    'action_type' => 'edit_published',
                    'description' => 'Update Published: ' . \Illuminate\Support\Str::limit($news->title, 40),
                    'credits_change' => -1, 'balance_after' => $adminUser->credits
                ]);
            });
        } elseif ($adminUser->role !== 'super_admin') {
            if($adminUser->credits <= 0) return response()->json(['success' => false, 'message' => '❌ ক্রেডিট শেষ!']);
            if (method_exists($adminUser, 'hasDailyLimitRemaining') && !$adminUser->hasDailyLimitRemaining()) {
                 return response()->json(['success' => false, 'message' => '❌ আজকের ডেইলি পোস্ট লিমিট শেষ!']);
            }
        }

        $finalImage = $news->thumbnail_url; 
        if ($request->hasFile('image_file')) $finalImage = asset('storage/' . $request->file('image_file')->store('news-uploads', 'public'));
        elseif ($request->filled('image_url')) $finalImage = $request->image_url;

        $news->update([
            'status' => 'publishing', 
            'staff_id' => $staffId, // 🔥 স্টাফ আইডি সেভ
            'title' => $request->title, 'content' => $request->content,
            'ai_title' => $request->title, 'ai_content' => $request->content, 'thumbnail_url' => $finalImage,
            'hashtags' => $request->hashtags, 'error_message' => null, 'updated_at' => now()
        ]);

        $categories = [];
        if ($request->filled('category')) $categories[] = $request->category;
        if ($request->filled('extra_categories') && is_array($request->extra_categories)) $categories = array_merge($categories, $request->extra_categories);
        $categories = array_values(array_unique($categories));
        if(empty($categories)) $categories = [1];

        // 🔥 Auth::id() পাস করা হচ্ছে যাতে ব্যাকগ্রাউন্ড জব বুঝতে পারে কাজটা কে ট্রিগার করেছে
        ProcessNewsPost::dispatch($news->id, Auth::id(), [
            'title' => $request->title, 'content' => $request->content, 'category_ids' => $categories,
            'featured_image' => $finalImage, 'hashtags' => $request->hashtags
        ], true);

        return response()->json(['success' => true, 'message' => 'আপডেট শুরু হয়েছে! কিছুক্ষণের মধ্যে লাইভ হবে।']);
    }

    public function sendToAiQueue($id)
    {
        $news = NewsItem::findOrFail($id);
        $adminUser = $this->getEffectiveAdmin();
        $staffId = Auth::id() !== $adminUser->id ? Auth::id() : null; // 🔥 Staff ID

        if ($adminUser->role !== 'super_admin') {
             if($adminUser->credits <= 0) return back()->with('error', 'আপনার ক্রেডিট শেষ!');
             if (method_exists($adminUser, 'hasDailyLimitRemaining') && !$adminUser->hasDailyLimitRemaining()) return back()->with('error', 'আজকের ডেইলি লিমিট শেষ!');
             try {
                 DB::transaction(function () use ($adminUser, $news, $staffId) {
                     $adminUser->decrement('credits', 1);
                     \App\Models\CreditHistory::create([
                         'user_id' => $adminUser->id, 
                         'staff_id' => $staffId, // 🔥 স্টাফ আইডি ট্র্যাকিং
                         'action_type' => 'ai_rewrite',
                         'description' => 'AI Processing: ' . \Illuminate\Support\Str::limit($news->title, 40),
                         'credits_change' => -1, 'balance_after' => $adminUser->credits
                     ]);
                 });
             } catch (\Exception $e) { return back()->with('error', 'ক্রেডিট কাটা সম্ভব হয়নি।'); }
        }

        if ($news->status === 'processing') return back()->with('error', 'এটি ইতিমধ্যেই প্রসেসিং হচ্ছে...');
        $news->update([
            'status' => 'processing', 
            'staff_id' => $staffId, // 🔥 স্টাফ আইডি সেভ
            'error_message' => null, 'ai_title' => 'Writing...', 'ai_content' => null
        ]);
        
        GenerateAIContent::dispatch($news->id, Auth::id()); // 🔥 Auth::id() পাস করা হলো
        return back()->with('success', 'Processing Start!');
    }

    public function confirmPublish(Request $request, $id)
    {
        $request->validate(['title' => 'required', 'content' => 'required', 'category' => 'nullable']);
        $adminUser = $this->getEffectiveAdmin();
        $staffId = Auth::id() !== $adminUser->id ? Auth::id() : null; // 🔥 Staff ID
        
        if ($adminUser->role !== 'super_admin' && method_exists($adminUser, 'hasDailyLimitRemaining') && !$adminUser->hasDailyLimitRemaining()) {
             return response()->json(['success' => false, 'message' => '❌ আজকের ডেইলি লিমিট শেষ!']);
        }
        $news = NewsItem::findOrFail($id);
        $news->update(['status' => 'publishing', 'staff_id' => $staffId]); // 🔥 স্টাফ আইডি সেভ
        
        ProcessNewsPost::dispatch($news->id, Auth::id(), [
            'title' => $request->title, 'content' => $request->content, 
            'category_ids' => $request->category ? [$request->category] : [1]
        ]);
        
        return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে!']);
    }

    public function publishManualFromIndex(Request $request, $id)
    {
        $request->validate(['title' => 'required', 'content' => 'required', 'image_file' => 'nullable|image|max:5120', 'image_url' => 'nullable|url', 'category' => 'nullable']);
        $news = NewsItem::findOrFail($id);
        $adminUser = $this->getEffectiveAdmin();
        $staffId = Auth::id() !== $adminUser->id ? Auth::id() : null; // 🔥 Staff ID

        if ($news->status === 'published' || $news->status === 'publishing') return response()->json(['success' => false, 'message' => '⚠️ এটি ইতিমধ্যেই প্রসেসিং বা পাবলিশড!']);

        $finalImage = $news->thumbnail_url; 
        if ($request->hasFile('image_file')) $finalImage = asset('storage/' . $request->file('image_file')->store('news-uploads', 'public'));
        elseif ($request->filled('image_url')) $finalImage = $request->image_url;

        $news->update([
            'title' => $request->title, 'content' => $request->content, 
            'ai_title' => $request->title, 'ai_content' => $request->content, 
            'thumbnail_url' => $finalImage, 'status' => 'publishing', 
            'staff_id' => $staffId, // 🔥 স্টাফ আইডি সেভ
            'is_rewritten' => 1, 'updated_at' => now()
        ]);

        ProcessNewsPost::dispatch($news->id, Auth::id(), [
            'title' => $news->title, 'content' => $news->content, 
            'category_ids' => [$request->category ?? 1], 'featured_image' => $finalImage
        ], true);
        
        return response()->json(['success' => true, 'message' => 'পাবলিশিং কিউতে পাঠানো হয়েছে!']);
    }

    public function publishStudioDesign(Request $request, $id)
    {
        $request->validate(['design_image' => 'required|image|max:20480', 'category_id' => 'nullable', 'social_caption' => 'nullable|string']);
        $news = NewsItem::findOrFail($id);
        $adminUser = $this->getEffectiveAdmin();
        $staffId = Auth::id() !== $adminUser->id ? Auth::id() : null; // 🔥 Staff ID

        if ($adminUser->role !== 'super_admin') {
            if ($adminUser->credits <= 0) return response()->json(['success' => false, 'message' => 'ক্রেডিট শেষ!']);
            if (method_exists($adminUser, 'hasDailyLimitRemaining') && !$adminUser->hasDailyLimitRemaining()) return response()->json(['success' => false, 'message' => 'ডেইলি লিমিট শেষ!']);
        }

        $isSocialOnly = $request->has('social_only') && $request->social_only == '1';
        if ($news->status == 'published' && !$isSocialOnly) return response()->json(['success' => false, 'message' => '⚠️ এটি ইতিমধ্যেই পাবলিশড!']);
        if ($news->status != 'published' && $isSocialOnly) return response()->json(['success' => false, 'message' => '⚠️ আগে ওয়েবসাইটে পাবলিশ করুন।']);

        try {
            if ($request->hasFile('design_image')) {
                $studioImageUrl = asset('storage/' . $request->file('design_image')->store('news-cards/studio', 'public'));
                if (!$isSocialOnly) $news->update(['status' => 'publishing', 'staff_id' => $staffId, 'updated_at' => now()]); // 🔥 স্টাফ আইডি সেভ
                ProcessNewsPost::dispatch($news->id, Auth::id(), [
                    'title' => $news->title, 'content' => $news->content, 'social_only' => $isSocialOnly,
                    'website_image' => $news->thumbnail_url, 'social_image' => $studioImageUrl,
                    'category_ids' => [$request->category_id ?? 1], 'social_caption' => $request->social_caption ?? ($news->ai_title ?? $news->title)
                ], true);
                return response()->json(['success' => true, 'message' => 'পাবলিশিং শুরু হয়েছে!']);
            }
            return response()->json(['success' => false, 'message' => 'ইমেজ নেই।']);
        } catch (\Exception $e) { return response()->json(['success' => false, 'message' => 'সার্ভার এরর।']); }
    }
    
    public function postToWordPress($id, SocialPostService $socialPoster)
    {
        $adminUser = $this->getEffectiveAdmin();
        $staffId = Auth::id() !== $adminUser->id ? Auth::id() : null; // 🔥 Staff ID
        $settings = $adminUser->settings; 

        if ($settings && $settings->is_auto_posting) return back()->with('error', 'অটোমেশন OFF করুন।');

        $news = NewsItem::with(['website' => function ($query) { $query->withoutGlobalScopes(); }])->findOrFail($id);
        if ($news->status == 'published') return back()->with('error', 'ইতিমধ্যে পোস্ট করা হয়েছে!');

        if ($adminUser->role !== 'super_admin') {
            if ($adminUser->credits <= 0) return back()->with('error', 'ক্রেডিট শেষ!');
            if (method_exists($adminUser, 'hasDailyLimitRemaining') && !$adminUser->hasDailyLimitRemaining()) return back()->with('error', "ডেইলি লিমিট সীমায় পৌঁছেছেন!");
            try {
                DB::transaction(function () use ($adminUser, $news, $staffId) {
                    $adminUser->decrement('credits', 1);
                    \App\Models\CreditHistory::create([
                        'user_id' => $adminUser->id, 
                        'staff_id' => $staffId, // 🔥 স্টাফ আইডি ট্র্যাকিং
                        'action_type' => 'manual_post',
                        'description' => 'Manual Post', 'credits_change' => -1, 'balance_after' => $adminUser->credits
                    ]);
                });
            } catch (\Exception $e) { return back()->with('error', 'ক্রেডিট সিস্টেমে সমস্যা।'); }
        }

        $news->update(['status' => 'publishing', 'staff_id' => $staffId]); // 🔥 স্টাফ আইডি সেভ
        
        ProcessNewsPost::dispatch($news->id, Auth::id(), ['category_ids' => [1]], true);
        
        return back()->with('success', 'প্রসেসিং শুরু হয়েছে! কিছুক্ষণের মধ্যে ওয়েবসাইটের লিংক সহ সোশ্যাল মিডিয়ায় পোস্ট হয়ে যাবে।');
    }
}