<?php

namespace App\Http\Controllers;

use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// লারাভেল ১২-এর জন্য প্রয়োজনীয় ইমপোর্ট
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReporterController extends Controller implements HasMiddleware
{
    /**
     * লারাভেল ১১ ও ১২-তে মিডলওয়্যার ডিফাইন করার নতুন পদ্ধতি
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (Auth::user()->role !== 'reporter') {
                    abort(403, 'আপনার এই প্যানেলে প্রবেশের অনুমতি নেই।');
                }
                return $next($request);
            }),
        ];
    }

    public function index()
    {
        $news = NewsItem::withoutGlobalScopes()
            ->where('reporter_id', Auth::id())
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('reporter.index', compact('news'));
    }

    public function create()
    {
        $parent = Auth::user()->parent;
        $categories = $parent && $parent->settings ? $parent->settings->category_mapping : [];
        return view('reporter.create', compact('categories'));
    }

 
 
		public function store(Request $request)
{
    // ১. ভ্যালিডেশন (৩ মেগাবাইট লিমিটসহ)
    $request->validate([
        'title'         => 'required|string|max:255',
        'content'       => 'required|string',
        'image_file'    => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
        'extra_image_1' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        'extra_image_2' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        'extra_image_3' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        'extra_image_4' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        'location'      => 'nullable|string|max:100',
    ]);

    try {
        $user = Auth::user();

        // ২. প্রধান ছবি আপলোড
        $path = $request->file('image_file')->store('news-uploads/reporters', 'public');
        $mainImage = asset('storage/' . $path);

        // ৩. অতিরিক্ত ৪টি ছবি প্রসেসিং
        $additionalImages = [];
        for ($i = 1; $i <= 4; $i++) {
            if ($request->hasFile("extra_image_$i")) {
                $extraPath = $request->file("extra_image_$i")->store('news-uploads/reporters/extra', 'public');
                $additionalImages[] = asset('storage/' . $extraPath);
            }
        }

        // ৪. ডাটাবেসে সেভ (অতিরিক্ত ছবিগুলো 'tags' কলামে JSON হিসেবে যাচ্ছে)
        NewsItem::create([
            'user_id'              => $user->parent_id ?? $user->id, 
            'reporter_id'          => $user->id,
            'title'                => $request->title,
            'content'              => $request->content,
            'thumbnail_url'        => $mainImage,
            'location'             => $request->location,
            'short_summary'        => $request->short_summary,
            'image_caption'        => $request->image_caption,
            'original_link'        => $request->source_url ?? '#reporter-' . uniqid(),
            'tags'                 => json_encode($additionalImages), // ছবিগুলো এখানে সেভ হচ্ছে
            'reporter_name_manual' => $user->name,
            'status'               => 'draft',
            'published_at'         => now(),
            'is_posted'            => false
        ]);

        return redirect()->route('reporter.news.index')->with('success', '✅ নিউজটি সফলভাবে পাঠানো হয়েছে।');

    } catch (\Exception $e) {
        Log::error("Reporter News Submission Error: " . $e->getMessage());
        return back()->with('error', 'একটি সমস্যা হয়েছে। ' . $e->getMessage())->withInput();
    }
}
	
	
	
	
}