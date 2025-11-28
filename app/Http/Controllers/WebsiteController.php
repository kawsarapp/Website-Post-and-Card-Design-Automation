<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ScrapeWebsite; // ✅ জব ক্লাস ইমপোর্ট করা জরুরি

class WebsiteController extends Controller
{
    // ১. ওয়েবসাইট লিস্ট (User Role অনুযায়ী)
    public function index()
    {
        if (auth()->user()->role === 'super_admin') {
            $websites = Website::withoutGlobalScopes()->get();
        } else {
            // সাধারণ ইউজার: পিভট টেবিল থেকে এক্সেস পাওয়া সাইটগুলো দেখবে
            $websites = auth()->user()->accessibleWebsites()
                        ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                        ->get();
        }
        return view('websites.index', compact('websites'));
    }

    // ২. নতুন ওয়েবসাইট অ্যাড (Only Super Admin)
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'super_admin') {
            return back()->with('error', 'আপনার ওয়েবসাইট অ্যাড করার অনুমতি নেই।');
        }

        $request->validate([
            'name' => 'required',
            'url' => 'required|url',
            'selector_container' => 'required',
            'selector_title' => 'required',
            'scraper_method' => 'nullable|in:node,python'
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        Website::create($data);

        return back()->with('success', 'Website added successfully!');
    }

    // ৩. ✅ আপডেট মেথড (নতুন যোগ করা হয়েছে)
    public function update(Request $request, $id)
    {
        // সিকিউরিটি চেক: শুধুমাত্র সুপার অ্যাডমিন এডিট করতে পারবে
        if (auth()->user()->role !== 'super_admin') {
            return back()->with('error', 'আপনার ওয়েবসাইট এডিট করার অনুমতি নেই।');
        }

        $website = Website::withoutGlobalScopes()->findOrFail($id);

        $request->validate([
            'name' => 'required',
            'url' => 'required|url',
            'selector_container' => 'required',
            'selector_title' => 'required',
            'scraper_method' => 'nullable|in:node,python'
        ]);

        $website->update($request->all());

        return back()->with('success', 'ওয়েবসাইট সফলভাবে আপডেট করা হয়েছে!');
    }

    // ৪. ✅ স্ক্র্যাপ ফাংশন (Queue ব্যবহার করবে)
    public function scrape($id)
    {
        // A. ওয়েবসাইট ভ্যালিডেশন ও এক্সেস চেক
        if (auth()->user()->role === 'super_admin') {
            $website = Website::withoutGlobalScopes()->findOrFail($id);
        } else {
            $website = auth()->user()->accessibleWebsites()
                ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                ->where('websites.id', $id)
                ->firstOrFail();
        }

        // B. জব কিউতে পাঠানো (Background Process)
        // আমরা ইউজার আইডি সাথে পাঠিয়ে দিচ্ছি যাতে নিউজটি এই ইউজারের নামে সেভ হয়
        ScrapeWebsite::dispatch($website, auth()->id());

        // C. সাথে সাথে ইউজারকে রেসপন্স দেওয়া (ইউজারকে আর লোডিংয়ে আটকে থাকতে হবে না)
        return back()->with('success', '⏳ স্ক্র্যাপিং রিকোয়েস্ট গ্রহণ করা হয়েছে! ১-২ মিনিট পর পেজ রিফ্রেশ দিন।');
    }
}