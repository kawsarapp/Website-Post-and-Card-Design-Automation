<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use App\Services\WordPressService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * ১. সেটিংস পেজ ভিউ
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'super_admin' && !$user->hasPermission('can_settings')) {
            return redirect()->route('news.index')->with('error', 'আপনার সেটিংস পরিবর্তনের অনুমতি নেই।');
        }

        $settings = $user->settings ?? new UserSetting(['user_id' => $user->id]);
        return view('settings.index', compact('settings'));
    }

    /**
     * ২. সেটিংস আপডেট (FIX: Attempt to assign property on null)
     */
    /**
     * ২. সেটিংস আপডেট (FIXED & DYNAMIC)
     */
    public function update(Request $request)
    {
        // ১. সবার আগে পারমিশন চেক (সিকিউরিটির জন্য)
        if (Auth::user()->role !== 'super_admin' && !Auth::user()->hasPermission('can_settings')) {
            return abort(403, 'Unauthorized');
        }

        // ২. ভ্যালিডেশন
        $request->validate([
            'brand_name' => 'required|string|max:50',
            'wp_url' => 'nullable|url',
            'wp_username' => 'nullable|string',
            'wp_app_password' => 'nullable|string',
            'fb_page_id' => 'nullable|string',
            'fb_access_token' => 'nullable|string',
            'telegram_bot_token' => 'nullable|string',
            'telegram_channel_id' => 'nullable|string',
            'laravel_site_url' => 'nullable|url',
            'laravel_api_token' => 'nullable|string',
            'laravel_route_prefix' => 'nullable|string|max:40',
            // প্রক্সি ভ্যালিডেশন
            'proxy_username' => 'nullable|string',
            'proxy_password' => 'nullable|string',
            'proxy_host' => 'nullable|string',
            'proxy_port' => 'nullable|string',
            // 🔥 ডাইনামিক কাস্টম API ভ্যালিডেশন
            'custom_api_url' => 'nullable|url',
            'custom_category_url' => 'nullable|url',
            'custom_api_mapping' => 'nullable|json',
        ]);
        
        // ৩. সেটিংস লোড করা (একবারই)
        $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);

        // ৪. প্রক্সি সেটিংস অ্যাসাইন করা
        $settings->proxy_username = $request->proxy_username;
        $settings->proxy_password = $request->proxy_password;
        $settings->proxy_host = $request->proxy_host;
        $settings->proxy_port = $request->proxy_port;

        // ৫. সাধারণ সেটিংস
        $settings->brand_name = $request->brand_name;
        $settings->default_theme_color = $request->default_theme_color ?? 'red';
        
        if ($request->filled('logo_url')) {
            $settings->logo_url = $request->logo_url;
        }

        // ওয়ার্ডপ্রেস সেটিংস
        $settings->wp_url = $request->wp_url;
        $settings->wp_username = $request->wp_username;
        $settings->wp_app_password = $request->wp_app_password;

        // ফেসবুক সেটিংস
        $settings->fb_page_id = $request->fb_page_id;
        $settings->fb_access_token = $request->fb_access_token;
        $settings->post_to_fb = $request->has('post_to_fb');
        $settings->fb_comment_link = $request->has('fb_comment_link');

        // টেলিগ্রাম সেটিংস
        $settings->telegram_bot_token = $request->telegram_bot_token;
        $settings->telegram_channel_id = $request->telegram_channel_id;
        $settings->post_to_telegram = $request->has('post_to_telegram');

        // লারাভেল API সেটিংস
        $settings->laravel_site_url = $request->laravel_site_url;
        $settings->laravel_api_token = $request->laravel_api_token;
        $settings->post_to_laravel = $request->has('post_to_laravel');
        $settings->laravel_route_prefix = $request->laravel_route_prefix ?? 'news';

        // ক্যাটাগরি ম্যাপিং
        if ($request->has('category_mapping')) {
            $settings->category_mapping = $request->category_mapping;
        }

        // 🔥 ৬. কাস্টম ডাইনামিক API সেটিংস সেভ করা
        $settings->custom_api_url = $request->custom_api_url;
        $settings->custom_category_url = $request->custom_category_url;
        $settings->custom_api_mapping = $request->custom_api_mapping;

        // ৭. সবশেষে একবারই সেভ করা
        $settings->save();

        return back()->with('success', 'সব সেটিংস (প্রক্সিসহ) সফলভাবে সেভ করা হয়েছে!');
    }

    /**
     * ৩. ফেসবুক কানেকশন টেস্ট
     */
    public function testFacebookConnection(Request $request)
    {
        $pageId = $request->input('fb_page_id');
        $token = $request->input('fb_access_token');

        if (!$pageId || !$token) {
            return response()->json(['success' => false, 'message' => 'Page ID এবং Token দিতে হবে।']);
        }

        try {
            $response = Http::get("https://graph.facebook.com/v19.0/{$pageId}", [
                'fields' => 'id,name',
                'access_token' => $token
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['id'])) {
                return response()->json([
                    'success' => true,
                    'message' => "✅ কানেকশন সফল!\nPage: " . $data['name']
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'message' => "❌ ফেইল্ড: " . ($data['error']['message'] ?? 'Unknown Error')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
        }
    }

    /**
     * ৪. টেলিগ্রাম কানেকশন টেস্ট
     */
    public function testTelegramConnection(Request $request)
    {
        $botToken = $request->input('telegram_bot_token');
        $channelId = $request->input('telegram_channel_id');

        if (!$botToken || !$channelId) {
            return response()->json(['success' => false, 'message' => 'Bot Token এবং Channel ID দিতে হবে।']);
        }

        try {
            $meResponse = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
            if (!$meResponse->successful()) {
                return response()->json(['success' => false, 'message' => '❌ Bot Token ভুল!']);
            }

            $chatResponse = Http::get("https://api.telegram.org/bot{$botToken}/getChat", [
                'chat_id' => $channelId
            ]);

            $chatData = $chatResponse->json();

            if ($chatResponse->successful() && $chatData['ok']) {
                $title = $chatData['result']['title'] ?? 'Unknown Channel';
                return response()->json([
                    'success' => true,
                    'message' => "✅ টেলিগ্রাম কানেক্টেড!\nChannel: $title"
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "❌ চ্যানেল পাওয়া যায়নি বা বট এডমিন নেই।\nError: " . ($chatData['description'] ?? 'Unknown')
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Network Error: ' . $e->getMessage()]);
        }
    }

    /**
     * ৫. ওয়ার্ডপ্রেস কানেকশন টেস্ট
     */
    public function testWordPressConnection(Request $request)
    {
        $url = $request->input('wp_url');
        $username = $request->input('wp_username');
        $password = $request->input('wp_app_password');

        if (!$url || !$username || !$password) {
            return response()->json(['success' => false, 'message' => 'সব ফিল্ড পূরণ করুন।']);
        }

        try {
            $apiUrl = rtrim($url, '/') . '/wp-json/wp/v2/users/me';
            $response = Http::withBasicAuth($username, $password)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => "✅ ওয়ার্ডপ্রেস কানেক্টেড!\nUser: " . ($data['name'] ?? $username)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "❌ কানেকশন ফেইল্ড! স্ট্যাটাস কোড: " . $response->status()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'WP Error: ' . $e->getMessage()]);
        }
    }

    /**
     * ৬. ক্যাটাগরি ফেচ করা (WP/Laravel - 🔥 FULLY DYNAMIC WITH DATA WRAPPER FIX)
     */
    public function fetchCategories(WordPressService $wpService)
    {
        $user = Auth::user();
        $settings = $user->settings;

        if (!$settings) {
            return response()->json(['error' => 'Settings not found'], 400);
        }

        // ইউজার ভিত্তিক আলাদা ক্যাশ কি (Cache Key) তৈরি
        $cacheKey = 'user_categories_' . $user->id;

        // যদি ইউজার 'ফোর্স রিফ্রেশ' করতে চায় (যেমন: নতুন ক্যাটাগরি এড করার পর)
        if (request()->has('refresh')) {
            Cache::forget($cacheKey);
        }

        // ক্যাশ থেকে ডাটা নেওয়া, না থাকলে নতুন করে ফেচ করে ২৪ ঘণ্টার জন্য সেভ করা
        $categories = Cache::remember($cacheKey, now()->addHours(24), function () use ($settings, $wpService) {
            
            // ১. লারাভেল সাইট থেকে ফেচ করা
            if ($settings->post_to_laravel && $settings->laravel_site_url && $settings->laravel_api_token) {
                try {
                    // 🟢 SMART SWITCH: যদি ডাটাবেসে কাস্টম ক্যাটাগরি API দেওয়া থাকে
                    if (!empty($settings->custom_category_url)) {
                        $apiUrl = $settings->custom_category_url;
                        $response = Http::timeout(10)->get($apiUrl);
                        
                        if ($response->successful()) {
                            $resData = $response->json();
                            // 🔥 FIX: ইসলামিক টিভির মত API তে ডাটা "data" key এর ভেতরে থাকে
                            if (isset($resData['data']) && is_array($resData['data'])) {
                                return $resData['data'];
                            }
                            return $resData;
                        }
                    } 
                    // 🔵 DEFAULT UNIVERSAL API FETCH (আমাদের ডিফল্ট সিস্টেম)
                    else {
                        $baseUrl = rtrim($settings->laravel_site_url, '/');
                        $apiUrl = $baseUrl . '/api/get-categories';
                        $response = Http::timeout(10)->get($apiUrl, ['token' => $settings->laravel_api_token]);
                        
                        if ($response->successful()) {
                            return $response->json();
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Laravel Category Fetch Error: " . $e->getMessage());
                }
            }

            // ২. ওয়ার্ডপ্রেস থেকে ফেচ করা
            if ($settings->wp_url && $settings->wp_username && $settings->wp_app_password) {
                try {
                    return $wpService->getCategories(
                        $settings->wp_url,
                        $settings->wp_username,
                        $settings->wp_app_password
                    );
                } catch (\Exception $e) {
                    Log::error("WP Category Fetch Error: " . $e->getMessage());
                }
            }

            return [];
        });

        if (empty($categories)) {
            return response()->json(['error' => 'No Categories Found or Connection Failed'], 400);
        }

        return response()->json($categories);
    }

    /**
     * ৭. লোগো আপলোড
     */
    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => 'required|image|max:2048']);
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);
            $settings->logo_url = asset('storage/' . $path);
            $settings->save();
            return response()->json(['success' => true, 'url' => asset('storage/' . $path)]);
        }
        return response()->json(['success' => false], 400);
    }

    /**
     * ৮. ফ্রেম আপলোড
     */
    public function uploadFrame(Request $request)
    {
        $request->validate(['frame' => 'required|image|mimes:png|max:2048']);
        if ($request->hasFile('frame')) {
            $path = $request->file('frame')->store('frames', 'public');
            return response()->json(['success' => true, 'url' => asset('storage/' . $path)]);
        }
        return response()->json(['success' => false], 400);
    }

    /**
     * ৯. ক্রেডিট হিস্ট্রি
     */
    public function credits()
    {
        $user = Auth::user();
        $histories = method_exists($user, 'creditHistories') ? $user->creditHistories()->latest()->paginate(15) : collect();
        return view('settings.credits', compact('histories', 'user'));
    }

    /**
     * ১০. ডিজাইন প্রেফারেন্স সেভ
     */
    public function saveDesign(Request $request)
    {
        try {
            $settings = UserSetting::firstOrCreate(['user_id' => Auth::id()]);
            $settings->design_preferences = $request->preferences;
            $settings->save();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * ১১. প্রোফাইল আপডেট
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        return back()->with('success', 'প্রোফাইল আপডেট হয়েছে!');
    }
}