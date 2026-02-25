<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use App\Models\Website;
use App\Models\NewsItem;
use App\Models\CreditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    /**
     * স্টাফ লিস্ট, ফিল্টারিং এবং অ্যাডমিনের ডাটা লোড করা
     */
    public function index(Request $request)
    {
        $admin = Auth::user();

        // ১. সিকিউরিটি চেক: শুধু অ্যাডমিন বা সুপার অ্যাডমিন স্টাফ ম্যানেজ করতে পারবে
        if (!in_array($admin->role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized action.');
        }

        // ২. পারমিশন চেক
        $permissions = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];
        if ($admin->role !== 'super_admin' && !in_array('can_manage_staff', $permissions)) {
            return back()->with('error', 'আপনার স্টাফ তৈরি করার অনুমতি নেই।');
        }

        // 🔍 ফিল্টার প্যারামিটারগুলো নেওয়া
        $search = $request->input('search');
        $dateFilter = $request->input('date_filter', 'all');

        // ৩. স্টাফদের ডাটা এবং অ্যানালিটিক্স (Analytics) কোয়েরি
        $staffQuery = User::where('parent_id', $admin->id)
            ->whereIn('role', ['staff', 'reporter'])
            ->with(['accessibleWebsites', 'settings']);

        // 🔍 সার্চ লজিক অ্যাপ্লাই
        if (!empty($search)) {
            $staffQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staffs = $staffQuery->get()->map(function($staff) use ($dateFilter) {
            
            // বেইস কোয়েরি তৈরি (যাতে ফিল্টার করা সহজ হয়)
            $newsQuery = NewsItem::withoutGlobalScopes()->where('staff_id', $staff->id);
            $creditQuery = CreditHistory::where('staff_id', $staff->id);

            // 📅 ডেট ফিল্টার অ্যাপ্লাই
            if ($dateFilter === 'today') {
                $newsQuery->where('created_at', '>=', now()->subHours(24));
                $creditQuery->where('created_at', '>=', now()->subHours(24));
            } elseif ($dateFilter === '7days') {
                $newsQuery->where('created_at', '>=', now()->subDays(7));
                $creditQuery->where('created_at', '>=', now()->subDays(7));
            } elseif ($dateFilter === 'month') {
                $newsQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                $creditQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            }

            // ডাইনামিক ফিল্টার্ড ডাটা
            $staff->total_published = (clone $newsQuery)->where('status', 'published')->count();
            $staff->total_drafts    = (clone $newsQuery)->where('status', '!=', 'published')->count();
            $staff->custom_news     = (clone $newsQuery)->whereNull('website_id')->count();
            $staff->reporter_news   = (clone $newsQuery)->whereNotNull('reporter_id')->count();
            
            $staff->credits_used    = (clone $creditQuery)->where('credits_change', '<', 0)->sum('credits_change') * -1;
            $staff->ai_rewrites     = (clone $creditQuery)->where('action_type', 'ai_rewrite')->count();

            // ⏳ ২৪ ঘণ্টার ডাটা (এটি ফিল্টার ছাড়া সবসময় ফিক্সড ২৪ ঘণ্টার দেখাবে)
            $staff->published_24h = NewsItem::withoutGlobalScopes()
                                        ->where('staff_id', $staff->id)
                                        ->where('status', 'published')
                                        ->where('created_at', '>=', now()->subHours(24))
                                        ->count();

            return $staff;
        });
                      
        // ৪. অ্যাডমিনের নিজের তৈরি করা সোর্স এবং সুপার অ্যাডমিনের দেওয়া সোর্স একসাথে আনা
        $adminWebsites = Website::withoutGlobalScopes()
            ->where(function($query) use ($admin) {
                $query->where('user_id', $admin->id)
                      ->orWhereHas('users', function($q) use ($admin) {
                          $q->where('users.id', $admin->id); 
                      });
            })->get();
            
        $adminTemplates = $admin->settings->allowed_templates ?? [];

        return view('client.staff.index', compact('staffs', 'admin', 'adminWebsites', 'adminTemplates'));
    }

    /**
     * নতুন স্টাফ তৈরি করা (With Transaction Security)
     */
    public function store(Request $request)
    {
        $admin = Auth::user();

        // লিমিট চেক
        $currentStaffCount = User::where('parent_id', $admin->id)->where('role', 'staff')->count();
        if ($admin->role !== 'super_admin' && $currentStaffCount >= $admin->staff_limit) {
            return back()->with('error', "❌ আপনার স্টাফ লিমিট শেষ!");
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        $adminPermissions = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];
        $adminTemplates = $admin->settings->allowed_templates ?? [];

        try {
            DB::beginTransaction(); // 🛡️ ডাটাবেস ট্রানজেকশন শুরু

            $staff = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'staff',
                'parent_id' => $admin->id,
                'is_active' => true,
                'permissions' => $adminPermissions // ডিফল্টভাবে অ্যাডমিনের পারমিশন
            ]);

            // স্টাফের জন্য সেটিংস তৈরি (অ্যাডমিনের টেমপ্লেট দিয়ে)
            UserSetting::create([
                'user_id' => $staff->id,
                'allowed_templates' => $adminTemplates,
                'default_template' => $admin->settings->default_template ?? 'default'
            ]);

            // অ্যাডমিনের সব ওয়েবসাইট অটোমেটিক স্টাফকে এক্সেস দেওয়া
            $adminWebsiteIds = Website::withoutGlobalScopes()
                ->where('user_id', $admin->id)
                ->pluck('id')->toArray();
                
            $staff->accessibleWebsites()->sync($adminWebsiteIds);

            DB::commit(); // 🛡️ ডাটা সেভ সফল
            return back()->with('success', 'নতুন স্টাফ অ্যাকাউন্ট তৈরি হয়েছে এবং পারমিশন সেট করা হয়েছে!');

        } catch (\Exception $e) {
            DB::rollBack(); // 🛡️ এরর হলে সব বাতিল
            return back()->with('error', 'অ্যাকাউন্ট তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }

    /**
     * স্টাফের পারমিশন আপডেট
     */
    public function updatePermissions(Request $request, $id)
    {
        $admin = Auth::user();
        $staff = User::where('parent_id', $admin->id)->findOrFail($id); // সিকিউরিটি: শুধু নিজের স্টাফ

        $requestedPermissions = $request->input('permissions', []);
        
        if ($admin->role !== 'super_admin') {
            $adminPermissions = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];
            $finalPermissions = array_intersect($requestedPermissions, $adminPermissions); // অ্যাডমিনের বাইরে দিতে পারবে না
        } else {
            $finalPermissions = $requestedPermissions;
        }
        
        $staff->permissions = $finalPermissions;
        $staff->save();

        return back()->with('success', 'পারমিশন আপডেট করা হয়েছে!');
    }

    /**
     * সোর্স (ওয়েবসাইট) এক্সেস আপডেট
     */
    public function updateWebsites(Request $request, $id)
    {
        $admin = Auth::user();
        $staff = User::where('parent_id', $admin->id)->findOrFail($id);
        
        $requestedWebsites = $request->input('websites', []);
        
        $adminWebsiteIds = Website::withoutGlobalScopes()
            ->where(function($query) use ($admin) {
                $query->where('user_id', $admin->id)
                      ->orWhereHas('users', function($q) use ($admin) {
                          $q->where('users.id', $admin->id);
                      });
            })->pluck('id')->toArray();
        
        // ভ্যালিডেশন: শুধুমাত্র অ্যাডমিনের এক্সেসে থাকা ওয়েবসাইট দিতে পারবে
        $validWebsites = array_intersect($requestedWebsites, $adminWebsiteIds);
        $staff->accessibleWebsites()->sync($validWebsites);
        
        return back()->with('success', 'সোর্স এক্সেস আপডেট করা হয়েছে!');
    }

    /**
     * টেমপ্লেট এক্সেস আপডেট
     */
    public function updateTemplates(Request $request, $id)
    {
        $admin = Auth::user();
        $staff = User::where('parent_id', $admin->id)->findOrFail($id);
        
        $requestedTemplates = $request->input('templates', []);
        $adminTemplates = $admin->settings->allowed_templates ?? [];
        
        $validTemplates = array_intersect($requestedTemplates, $adminTemplates);
        
        $settings = UserSetting::firstOrCreate(['user_id' => $staff->id]);
        $settings->allowed_templates = $validTemplates;
        
        if (in_array($request->input('default_template'), $validTemplates)) {
            $settings->default_template = $request->input('default_template');
        }
        
        $settings->save();
        
        return back()->with('success', 'টেমপ্লেট এক্সেস আপডেট করা হয়েছে!');
    }

    /**
     * স্টাফ ডিলিট
     */
    public function destroy($id)
    {
        $adminId = Auth::id();
        $staff = User::where('parent_id', $adminId)->findOrFail($id); // সিকিউরিটি: শুধু নিজের স্টাফ ডিলিট করতে পারবে
        
        $staff->delete();
        
        return back()->with('success', 'স্টাফ অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }
}