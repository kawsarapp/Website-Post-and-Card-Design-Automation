<?php

namespace App\Traits;

use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait NewsDraftsTrait
{
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
        $news->hashtags = $request->hashtags;
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
            'hashtags'     => $news->hashtags,
            'image_url'    => $news->thumbnail_url,
            'extra_images' => $extraImages,
            'location'     => $news->location,
            'original_link'=> $news->original_link,
            'categories'   => $user->settings->category_mapping ?? []
        ]);
    }
}