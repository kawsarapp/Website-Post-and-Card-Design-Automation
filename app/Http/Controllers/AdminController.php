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
        // ড্যাশবোর্ড স্ট্যাটস
        $totalUsers = User::where('role', 'user')->count();
        $totalNews = NewsItem::withoutGlobalScopes()->count(); // সব নিউজ (গ্লোবাল স্কোপ ছাড়া)
        $totalWebsites = Website::withoutGlobalScopes()->count();
        
        // ইউজার লিস্ট (লেটেস্ট আগে)
        $users = User::where('role', 'user')->latest()->paginate(20);

        return view('admin.dashboard', compact('users', 'totalUsers', 'totalNews', 'totalWebsites'));
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

    // ক্রেডিট রিচার্জ করা
    public function addCredits(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|integer|min:1'
        ]);

        $user = User::findOrFail($id);
        $user->increment('credits', $request->amount);
        $user->increment('total_credits_limit', $request->amount); // চাইলে লিমিটও বাড়াতে পারো

        return back()->with('success', "{$request->amount} ক্রেডিট সফলভাবে যোগ করা হয়েছে।");
    }
}