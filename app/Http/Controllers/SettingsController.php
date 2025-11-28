<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use App\Services\WordPressService; // সার্ভিস ইমপোর্ট

class SettingsController extends Controller
{
    // ১. সেটিংস পেজ ভিউ
    public function index()
    {
        $settings = UserSetting::firstOrNew(['user_id' => Auth::id()]);
        return view('settings.index', compact('settings'));
    }

    // ২. সেটিংস আপডেট (WordPress Credentials ও Category Mapping সহ)
    public function update(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:50',
            'wp_url' => 'nullable|url',
            'wp_username' => 'nullable|string',
            'wp_app_password' => 'nullable|string',
            'category_mapping' => 'nullable|array', // ম্যাপিং অ্যারে ভ্যালিডেশন
        ]);

        // সেটিংস খুঁজে বের করা অথবা নতুন তৈরি করা
        $settings = UserSetting::firstOrNew(['user_id' => Auth::id()]);

        // বেসিক তথ্য আপডেট
        $settings->brand_name = $request->brand_name;
        $settings->default_theme_color = $request->default_theme_color;
        $settings->wp_url = $request->wp_url;
        $settings->wp_username = $request->wp_username;
        $settings->wp_app_password = $request->wp_app_password;
        $settings->telegram_channel_id = $request->telegram_channel_id;

        // যদি লোগো URL রিকোয়েস্ট থেকে আসে (লোগো আপলোডার ছাড়া ম্যানুয়াল ইনপুট হলে)
        if ($request->filled('logo_url')) {
            $settings->logo_url = $request->logo_url;
        }

        // ক্যাটাগরি ম্যাপিং সেভ (যদি রিকোয়েস্টে থাকে)
        if ($request->has('category_mapping')) {
            $settings->category_mapping = $request->category_mapping;
        }

        $settings->save();

        return back()->with('success', 'সেটিংস এবং ম্যাপিং সফলভাবে আপডেট হয়েছে!');
    }

    // ৩. ক্যাটাগরি ফেচ করার মেথড (AJAX এর জন্য)
    public function fetchCategories(WordPressService $wpService)
    {
        $user = Auth::user();
        
        // রিলেশন থাকলে $user->settings ব্যবহার করতে পারেন, অথবা সরাসরি কুয়েরি:
        $settings = UserSetting::where('user_id', $user->id)->first();

        if (!$settings || !$settings->wp_url || !$settings->wp_username || !$settings->wp_app_password) {
            return response()->json(['error' => 'WordPress settings missing. Please save settings first.'], 400);
        }

        try {
            $categories = $wpService->getCategories(
                $settings->wp_url,
                $settings->wp_username,
                $settings->wp_app_password
            );
            
            return response()->json($categories);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch categories: ' . $e->getMessage()], 500);
        }
    }

    // ৪. লোগো আপলোড মেথড
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $url = asset('storage/' . $path);

            // ইউজারের সেটিংস খুঁজবে অথবা তৈরি করবে
            $settings = UserSetting::firstOrCreate(['user_id' => $user->id]);
            $settings->logo_url = $url;
            $settings->save();

            return response()->json(['success' => true, 'url' => $url]);
        }

        return response()->json(['success' => false], 400);
    }

    // ৫. ক্রেডিট হিস্ট্রি
    public function credits()
    {
        $user = Auth::user();
        $histories = $user->creditHistories()->latest()->paginate(15);
        return view('settings.credits', compact('histories', 'user'));
    }
}