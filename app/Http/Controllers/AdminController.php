<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NewsItem;
use App\Models\Website;
use Illuminate\Http\Request;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::where('role', 'user')->count();
        $totalNews = NewsItem::withoutGlobalScopes()->count();
        $totalWebsites = Website::withoutGlobalScopes()->count();
		$allWebsites = Website::withoutGlobalScopes()->get();
        
		$users = User::where('role', 'user')->with('accessibleWebsites')->latest()->paginate(20);

		return view('admin.dashboard', compact('users', 'totalUsers', 'totalNews', 'totalWebsites', 'allWebsites'));
    }
	
	
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

        return back()->with('success', 'টেমপ্লেট পারমিশন আপডেট করা হয়েছে!');
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'অ্যাক্টিভ' : 'নিষ্ক্রিয়';
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

        return back()->with('success', "{$request->amount} ক্রেডিট সফলভাবে যোগ করা হয়েছে।");
    }
	
    public function updateLimit(Request $request, $id)
    {
        $request->validate([
            'limit' => 'required|integer|min:1'
        ]);

        $user = User::findOrFail($id);
        $user->daily_post_limit = $request->limit;
        $user->save();

        return back()->with('success', "ডেইলি লিমিট আপডেট করা হয়েছে: {$user->daily_post_limit} টি");
    }
	
    public function updateWebsiteAccess(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $user->accessibleWebsites()->sync($request->websites ?? []);

        return back()->with('success', 'সোর্স পারমিশন আপডেট করা হয়েছে!');
    }
	
    public function updateScraperSettings(Request $request, $id)
    {
        $request->validate(['scraper_method' => 'nullable|in:node,python']);
        
        $settings = UserSetting::firstOrCreate(['user_id' => $id]);
        $settings->scraper_method = $request->scraper_method;
        $settings->save();

        return back()->with('success', 'User scraper preference updated!');
    }
	
	
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'credits' => 'nullable|integer',
            'daily_post_limit' => 'nullable|integer'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'credits' => $request->credits ?? 0,
            'daily_post_limit' => $request->daily_post_limit ?? 10,
            'role' => 'user',
            'is_active' => true
        ]);

        UserSetting::create(['user_id' => $user->id]);

        return back()->with('success', 'নতুন ইউজার সফলভাবে তৈরি করা হয়েছে!');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6'
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'ইউজারের তথ্য আপডেট করা হয়েছে!');
    }
	
	
			public function destroy($id)
			{
				$news = NewsItem::findOrFail($id);
				if (auth()->user()->role !== 'super_admin' && $news->user_id !== auth()->id()) {
					return back()->with('error', 'আপনার অনুমতি নেই।');
				}
				$news->delete();
				return back()->with('success', 'নিউজটি সফলভাবে মুছে ফেলা হয়েছে।');
			}
			
			
	public function postHistory(Request $request)
    {
        // ড্রপডাউনের জন্য লিস্ট
        $users = User::where('role', 'user')->get();
        $websites = Website::withoutGlobalScopes()->get();

        // মেইন কুয়েরি
        $query = NewsItem::withoutGlobalScopes()
            ->with(['user.settings', 'website']) // Eager Loading (Fast Query)
            ->where('is_posted', true);

        // ১. সার্চ ফিল্টার (Title)
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // ২. ইউজার ফিল্টার
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // ৩. পোর্টাল/ওয়েবসাইট ফিল্টার
        if ($request->filled('website_id')) {
            $query->where('website_id', $request->website_id);
        }

        // ৪. তারিখ ফিল্টার (From - To)
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
			
			
			
			
			
			
}
