<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NewsItem;
use App\Models\Website;
use Illuminate\Http\Request;
use App\Models\UserSetting;

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

    // ইউজার ব্যান/আনব্যান করা
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
	
}