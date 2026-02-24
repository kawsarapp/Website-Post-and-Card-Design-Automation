<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NewsItem;
use App\Models\Website;
use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // ==========================================
    // 👑 1. SaaS Dashboard (Super Admin Only)
    // ==========================================
    public function index()
    {
        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized Access!');
        }

        // 🔥 ক্লায়েন্টদের রোল 'admin' হবে
        $totalUsers = User::where('role', 'admin')->count();
        $totalNews = NewsItem::withoutGlobalScopes()->count();
        $totalWebsites = Website::withoutGlobalScopes()->count();
        $allWebsites = Website::withoutGlobalScopes()->get();
        
        $users = User::where('role', 'admin')->with(['accessibleWebsites', 'settings'])->latest()->paginate(20);

        return view('admin.dashboard', compact('users', 'totalUsers', 'totalNews', 'totalWebsites', 'allWebsites'));
    }
    
    // ==========================================
    // ⚙️ Client Management Features
    // ==========================================
    public function updateTemplates(Request $request, $userId)
    {
        $request->validate([
            'templates' => 'required|array',
            'default_template' => 'required|string'
        ]);

        $settings = UserSetting::firstOrCreate(['user_id' => $userId]);
        
        $settings->allowed_templates = $request->templates;
        $settings->default_template = $request->default_template;
        $settings->save();

        return back()->with('success', 'টেমপ্লেট পারমিশন আপডেট করা হয়েছে!');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'অ্যাক্টিভ' : 'নিষ্ক্রিয়';
        return back()->with('success', "ইউজার এখন {$status}!");
    }

    public function addCredits(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|integer|min:1'
        ]);

        $user = User::findOrFail($id);
        $user->increment('credits', $request->amount);
        $user->increment('total_credits_limit', $request->amount);

        \App\Models\CreditHistory::create([
            'user_id' => $user->id,
            'action_type' => 'admin_add',
            'description' => 'Admin added credits',
            'credits_change' => $request->amount,
            'balance_after' => $user->credits
        ]);

        return back()->with('success', "{$request->amount} ক্রেডিট সফলভাবে যোগ করা হয়েছে।");
    }
    
    public function updateLimit(Request $request, $id)
    {
        $request->validate([
            'limit' => 'required|integer|min:1'
        ]);

        $user = User::findOrFail($id);
        $user->daily_post_limit = $request->limit;
        $user->save();

        return back()->with('success', "ডেইলি লিমিট আপডেট করা হয়েছে: {$user->daily_post_limit} টি");
    }
    
    public function updateWebsiteAccess(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $user->accessibleWebsites()->sync($request->websites ?? []);

        return back()->with('success', 'সোর্স পারমিশন আপডেট করা হয়েছে!');
    }
    
    public function updateScraperSettings(Request $request, $id)
    {
        $request->validate(['scraper_method' => 'nullable|in:node,python']);
        
        $settings = UserSetting::firstOrCreate(['user_id' => $id]);
        $settings->scraper_method = $request->scraper_method;
        $settings->save();

        return back()->with('success', 'User scraper preference updated!');
    }
    
    // ==========================================
    // 👑 2. Create New SaaS Client (Admin)
    // ==========================================

    public function store(Request $request)
    {
        // দুটি মেথডের ভ্যালিডেশন একসাথে যুক্ত করা হয়েছে
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'credits' => 'nullable|integer',
            'daily_post_limit' => 'nullable|integer',
            'staff_limit' => 'nullable|integer' // 🔥 নতুন
        ]);

        try {
            DB::transaction(function () use ($request) {
                // নতুন ইউজার তৈরি (Role হবে admin, ক্লায়েন্ট রোল)
                $user = User::create([
                    'name' => strip_tags($request->name),
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'credits' => $request->credits ?? 10,
                    'daily_post_limit' => $request->daily_post_limit ?? 10,
                    'staff_limit' => $request->staff_limit ?? 0, // 🔥 স্টাফ লিমিট সেভ
                    'role' => 'admin', // 🔥 SaaS Client Role (ডুপ্লিকেট Role রিমুভ করে সঠিকটা রাখা হয়েছে)
                    'is_active' => true
                ]);

                // ডিফল্ট সেটিংস তৈরি
                UserSetting::create([
                    'user_id' => $user->id,
                    'daily_post_limit' => $request->daily_post_limit ?? 10,
                    'allowed_templates' => ['ntv', 'rtv', 'dhakapost'], // ডিফল্ট টেমপ্লেট
                ]);
            });

            return back()->with('success', 'নতুন ক্লায়েন্ট সফলভাবে তৈরি করা হয়েছে!');
        } catch (\Exception $e) {
            return back()->with('error', 'অ্যাকাউন্ট তৈরিতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // দুটি মেথডের ভ্যালিডেশন একসাথে যুক্ত করা হয়েছে
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'staff_limit' => 'nullable|integer' // 🔥 নতুন
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('staff_limit')) {
            $user->staff_limit = $request->staff_limit; // 🔥 আপডেট স্টাফ লিমিট
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'ইউজারের তথ্য আপডেট করা হয়েছে!');
    }
    
    public function destroy($id)
    {
        $news = NewsItem::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $news->user_id !== auth()->id()) {
            return back()->with('error', 'আপনার অনুমতি নেই।');
        }
        $news->delete();
        return back()->with('success', 'নিউজটি সফলভাবে মুছে ফেলা হয়েছে।');
    }
            
    // ==========================================
    // 📊 History & Reports
    // ==========================================
    public function postHistory(Request $request)
    {
        // ড্রপডাউনের জন্য লিস্ট (SaaS ক্লায়েন্ট)
        $users = User::where('role', 'admin')->get();
        $websites = Website::withoutGlobalScopes()->get();

        // মেইন কুয়েরি
        $query = NewsItem::withoutGlobalScopes()
            ->with(['user.settings', 'website']) // Eager Loading (Fast Query)
            ->where('is_posted', true);

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('website_id')) {
            $query->where('website_id', $request->website_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('posted_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('posted_at', '<=', $request->date_to);
        }

        // ডাটা ফেচ (Pagination)
        $allPosts = $query->latest('posted_at')->paginate(50)->withQueryString();

        return view('admin.post_history', compact('allPosts', 'users', 'websites'));
    }

    // ==========================================
    // 🕵️ Impersonation (Login As User)
    // ==========================================
    public function loginAsUser($id)
    {
        if (auth()->user()->role !== 'super_admin') {
            return back()->with('error', 'অনুমতি নেই।');
        }

        $originalAdminId = auth()->id(); // অ্যাডমিনের নিজের আইডি
        $user = User::findOrFail($id);

        // সেশনে অ্যাডমিনের আইডি সেভ করে রাখা
        session()->put('admin_impersonator_id', $originalAdminId);

        // ইউজারের আইডিতে লগইন করা
        Auth::login($user);

        return redirect()->route('news.index')->with('success', "Logged in as {$user->name}");
    }

    public function stopImpersonate()
    {
        if (session()->has('admin_impersonator_id')) {
            
            $adminId = session('admin_impersonator_id');
            session()->forget('admin_impersonator_id');

            Auth::loginUsingId($adminId);

            return redirect()->route('admin.dashboard')->with('success', 'স্বাগতম! অ্যাডমিন প্যানেলে ফিরে এসেছেন।');
        }

        return redirect()->route('news.index');
    }
    
    public function updatePermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->permissions = $request->input('permissions', []); // চেক না করলে খালি অ্যারে সেভ হবে
        $user->save();

        return back()->with('success', 'ইউজার পারমিশন সফলভাবে আপডেট করা হয়েছে!');
    }
}