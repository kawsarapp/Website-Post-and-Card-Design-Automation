<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use App\Services\WordPressService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http; // 🔥 এটি নতুন যোগ করা হয়েছে API কল করার জন্য
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    // ১. সেটিংস পেজ ভিউ
    public function index()
    {
        $user = Auth::user();
        $settings = $user->settings ?? new UserSetting(['user_id' => $user->id]);
        
        return view('settings.index', compact('settings'));
    }

    // ২. সেটিংস আপডেট
    public function update(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:50',
            'wp_url' => 'nullable|url',
            'wp_username' => 'nullable|string',
            'wp_app_password' => 'nullable|string',
            'category_mapping' => 'nullable|array',
            'logo_url' => 'nullable|url',
            'telegram_channel_id' => 'nullable|string',
            'default_theme_color' => 'nullable|string',
            'laravel_site_url' => 'nullable|url',
            'laravel_api_token' => 'nullable|string',
            'post_to_laravel' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // সেটিংস খুঁজে বের করা অথবা নতুন তৈরি করা
        $settings = UserSetting::firstOrNew(['user_id' => $user->id]);
        
        // লারাভেল সেটিংস আপডেট
        $settings->laravel_site_url = $request->laravel_site_url;
        $settings->laravel_api_token = $request->laravel_api_token;
        // চেক বক্স টিক দেওয়া থাকলে true, না থাকলে false
        $settings->post_to_laravel = $request->has('post_to_laravel') ? true : false;

        $settings->brand_name = $request->brand_name;
        $settings->default_theme_color = $request->default_theme_color ?? 'red';
        $settings->wp_url = $request->wp_url;
        $settings->wp_username = $request->wp_username;
        $settings->wp_app_password = $request->wp_app_password;
        $settings->telegram_channel_id = $request->telegram_channel_id;

        if ($request->filled('logo_url')) {
            $settings->logo_url = $request->logo_url;
        }

        if ($request->has('category_mapping')) {
            $settings->category_mapping = $request->category_mapping;
        }

        $settings->save();

        return back()->with('success', 'সেটিংস এবং ম্যাপিং সফলভাবে আপডেট হয়েছে!');
    }

    // 🔥 ৩. ক্যাটাগরি ফেচ করার নতুন লজিক (Laravel + WordPress)
    public function fetchCategories(WordPressService $wpService)
    {
        $user = Auth::user();
        $settings = $user->settings;

        if (!$settings) {
            return response()->json(['error' => 'Settings not found'], 400);
        }

        // --- PART A: যদি Laravel Posting অন থাকে, লারাভেল থেকে ক্যাটাগরি আনবো ---
        if ($settings->post_to_laravel && $settings->laravel_site_url && $settings->laravel_api_token) {
            try {
                // URL তৈরি করা (শেষে স্ল্যাশ থাকলে বাদ দেওয়া হচ্ছে)
                $apiUrl = rtrim($settings->laravel_site_url, '/') . '/api/get-categories';
                
                // API কল পাঠানো
                $response = Http::get($apiUrl, [
                    'token' => $settings->laravel_api_token
                ]);

                if ($response->successful()) {
                    return response()->json($response->json());
                } else {
                    return response()->json(['error' => 'Laravel API Error: ' . $response->status() . ' - ' . $response->body()], 400);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Laravel Connection Failed: ' . $e->getMessage()], 500);
            }
        }

        // --- PART B: যদি Laravel অফ থাকে, তবে WordPress চেক করবো ---
        if ($settings->wp_url && $settings->wp_username && $settings->wp_app_password) {
            try {
                $categories = $wpService->getCategories(
                    $settings->wp_url,
                    $settings->wp_username,
                    $settings->wp_app_password
                );
                
                if (empty($categories)) {
                    return response()->json(['error' => 'No categories found in WordPress.'], 404);
                }

                return response()->json($categories);
            } catch (\Exception $e) {
                return response()->json(['error' => 'WordPress Error: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'No Valid Connection (WordPress or Laravel) configured. Please check settings.'], 400);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();
        
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $url = asset('storage/' . $path);

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
        if (method_exists($user, 'creditHistories')) {
            $histories = $user->creditHistories()->latest()->paginate(15);
        } else {
            $histories = collect();
        }
        
        return view('settings.credits', compact('histories', 'user'));
    }
    
    public function saveDesign(Request $request)
    {
        Log::info('Save Design Request Started for User: ' . auth()->id());
        Log::info('Incoming Data:', $request->all());

        try {
            $request->validate([
                'preferences' => 'required|array'
            ]);

            $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);

            $settings->design_preferences = $request->preferences;
            $settings->save();

            Log::info('✅ DB Save Success. Saved Data:', $settings->design_preferences ?? []);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('❌ DB Save Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function uploadFrame(Request $request)
    {
        $request->validate(['frame' => 'required|image|mimes:png|max:2048']); 
        
        if ($request->hasFile('frame')) {
            $path = $request->file('frame')->store('frames', 'public');
            return response()->json(['success' => true, 'url' => asset('storage/' . $path)]);
        }
        return response()->json(['success' => false], 400);
    }
    
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($user->id), 
            ],
            'password' => 'nullable|string|min:6|confirmed', 
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        return back()->with('success', 'প্রোফাইল তথ্য সফলভাবে আপডেট হয়েছে!');
    }
}