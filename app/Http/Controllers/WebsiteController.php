<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Jobs\ScrapeWebsite; // ✅ Job ক্লাস ইমপোর্ট
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'super_admin') {
            $websites = Website::withoutGlobalScopes()->get();
        } else {
            $websites = auth()->user()->accessibleWebsites()
                        ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                        ->get();
        }
        return view('websites.index', compact('websites'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            return back()->with('error', 'অনুমতি নেই।');
        }

        $request->validate([
            'name' => 'required',
            'url' => 'required|url',
            'selector_container' => 'required',
            'selector_title' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        Website::create($data);

        return back()->with('success', 'Website added successfully!');
    }

    public function scrape($id)
    {
        // ১. ওয়েবসাইট ভ্যালিডেশন
        if (auth()->user()->role === 'super_admin') {
            $website = Website::withoutGlobalScopes()->findOrFail($id);
        } else {
            $website = auth()->user()->accessibleWebsites()
                ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                ->where('websites.id', $id)
                ->firstOrFail();
        }

        // ✅ FIX: Redis::rpush এর বদলে সরাসরি Laravel Job ডিসপ্যাচ করা হচ্ছে
        // এটি আপনার রানিং 'queue:work' প্রসেস ব্যবহার করবে
        ScrapeWebsite::dispatch($website->id, auth()->id());

        return back()->with('success', '⏳ স্ক্র্যাপিং ব্যাকগ্রাউন্ডে শুরু হয়েছে! ১-২ মিনিট পর পেজ রিফ্রেশ দিন।');
    }

    // Update Method (Optional)
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'super_admin') return back()->with('error', 'Permission Denied');
        $website = Website::withoutGlobalScopes()->findOrFail($id);
        $website->update($request->all());
        return back()->with('success', 'Website Updated');
    }
}