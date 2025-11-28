<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WebsiteController extends Controller
{
    public function index()
    {
        // ১. সুপার এডমিন সব দেখবে
        if (auth()->user()->role === 'super_admin') {
            $websites = Website::withoutGlobalScopes()->get();
        } 
        // ২. সাধারণ ইউজার: পিভট টেবিল থেকে এক্সেস পাওয়া সাইটগুলো দেখবে
        else {
            // withoutGlobalScope ব্যবহার করা হয়েছে কারণ 'websites' টেবিলের user_id এখন এডমিনের
            $websites = auth()->user()->accessibleWebsites()
                        ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                        ->get();
        }
        
        return view('websites.index', compact('websites'));
    }

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
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        Website::create($data);

        return back()->with('success', 'Website added successfully!');
    }

    public function scrape($id)
    {
        if (auth()->user()->role === 'super_admin') {
            $website = Website::withoutGlobalScopes()->findOrFail($id);
        } else {
            $website = auth()->user()->accessibleWebsites()
                ->withoutGlobalScope(\App\Models\Scopes\UserScope::class)
                ->where('websites.id', $id)
                ->firstOrFail();
        }

        \App\Jobs\ScrapeWebsite::dispatch($website->id, auth()->id());

        return back()->with('success', 'স্ক্র্যাপিং ব্যাকগ্রাউন্ডে শুরু হয়েছে! ১-২ মিনিট পর পেজ রিফ্রেশ দিন। ⏳');
    }
    
    // ইমেজ হেল্পার ফাংশন
    private function extractImageSrc($node)
    {
        if ($node->count() === 0) return null;
        $imgTag = ($node->nodeName() === 'img') ? $node : $node->filter('img');
        
        if ($imgTag->count() > 0) {
            // সাধারণ src চেক
            $src = $imgTag->attr('src');
            if ($src && !str_contains($src, 'base64') && strlen($src) > 10) return $src;
            
            // Lazy loading attributes চেক
            $attrs = ['data-original', 'data-src', 'srcset', 'data-srcset'];
            foreach ($attrs as $attr) {
                $val = $imgTag->attr($attr);
                if ($val && !str_contains($val, 'base64')) return $val;
            }
        } else {
            // Background Image চেক
            $style = $node->attr('style');
            if ($style && preg_match('/url\((.*?)\)/', $style, $matches)) return trim($matches[1], "'\" ");
        }
        return null;
    }
}