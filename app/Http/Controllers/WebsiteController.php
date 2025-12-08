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
            //$websites = Website::withoutGlobalScopes()->get();
			$websites = \App\Models\Website::withoutGlobalScopes()->get();
        } else {
			
			/*$websites = \App\Models\Website::withoutGlobalScopes()
                       ->where(function($q) {
                            $q->where('user_id', auth()->id()) // নিজের তৈরি
                              ->orWhere('is_public', true);    // অথবা পাবলিক (যদি এমন কলাম থাকে)
                        })
                        ->get();
						*/
						
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
    // ১. ওয়েবসাইট ভ্যালিডেশন / লোড
    if (auth()->user()->role === 'super_admin') {
        $website = Website::withoutGlobalScopes()->findOrFail($id);
    } else {
        $website = auth()->user()->accessibleWebsites()
            ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
            ->where('websites.id', $id)
            ->firstOrFail();
    }

    // ২. 🔥 ৫ মিনিটের চেকিং লজিক (Cool-down Check)
    if ($website->last_scraped_at) {
        $lastScraped = \Carbon\Carbon::parse($website->last_scraped_at);
        $diffInSeconds = now()->diffInSeconds($lastScraped);
        $cooldownSeconds = 300; // ৫ মিনিট = ৩০০ সেকেন্ড

        if ($diffInSeconds < $cooldownSeconds) {
            $wait = $cooldownSeconds - $diffInSeconds;
            $minutes = floor($wait / 60);
            $seconds = $wait % 60;
            return back()->with('error', "অনুগ্রহ করে অপেক্ষা করুন: {$minutes} মিনিট {$seconds} সেকেন্ড পর আবার চেষ্টা করুন।");
        }
    }

    // ৩. টাইমস্ট্যাম্প আপডেট করা
    $website->update(['last_scraped_at' => now()]);

    // ৪. জব ডিসপ্যাচ (Redis::rpush এর বদলে সরাসরি Laravel Job ব্যবহার)
    ScrapeWebsite::dispatch($website->id, auth()->id());

    return back()->with('success', '⏳ স্ক্র্যাপিং ব্যাকগ্রাউন্ডে শুরু হয়েছে! বাটনটি ৫ মিনিটের জন্য লক করা হলো। ১-২ মিনিট পর রিফ্রেশ দিন।');
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