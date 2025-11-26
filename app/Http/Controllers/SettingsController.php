<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        // বর্তমান ইউজারের সেটিংস অথবা নতুন ইনস্ট্যান্স
        $settings = UserSetting::firstOrNew(['user_id' => Auth::id()]);
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:50',
            'wp_url' => 'nullable|url',
            'wp_username' => 'nullable|string',
            'wp_app_password' => 'nullable|string',
        ]);

        $settings = UserSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'brand_name' => $request->brand_name,
                'logo_url' => $request->logo_url, // নোট: লোগো আপলোডার আলাদা হলে এটি এখানে না রাখলেও চলে, তবে থাকলে সমস্যা নেই
                'default_theme_color' => $request->default_theme_color,
                'wp_url' => $request->wp_url,
                'wp_username' => $request->wp_username,
                'wp_app_password' => $request->wp_app_password,
                'telegram_channel_id' => $request->telegram_channel_id,
            ]
        );

        return back()->with('success', 'সেটিংস আপডেট হয়েছে!');
    } // এখানে update ফাংশন শেষ করা হয়েছে

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
	
	
	
	public function credits()
    {
        $user = Auth::user();
        $histories = $user->creditHistories()->paginate(15);
        return view('settings.credits', compact('histories', 'user'));
    }
	
	
}