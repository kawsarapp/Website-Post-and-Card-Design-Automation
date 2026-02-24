<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * স্টাফ লিস্ট এবং অ্যাডমিনের ডাটা লোড করা
     */
    public function index()
    {
        $admin = Auth::user();

        // ১. সিকিউরিটি চেক: শুধু অ্যাডমিন বা সুপার অ্যাডমিন স্টাফ ম্যানেজ করতে পারবে
        if (!in_array($admin->role, ['admin', 'super_admin'])) {
            abort(403, 'Unauthorized action.');
        }

        // ২. পারমিশন চেক (যদি আপনার সিস্টেম অ্যাডমিনদের জন্য এই পারমিশন সিস্টেম রাখে)
        $permissions = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];
        if ($admin->role !== 'super_admin' && !in_array('can_manage_staff', $permissions)) {
            return back()->with('error', 'আপনার স্টাফ তৈরি করার অনুমতি নেই।');
        }

        // ৩. স্টাফদের ডাটা এবং অ্যানালিটিক্স (Analytics) একসাথে আনা
        $staffs = User::where('parent_id', $admin->id)
            ->whereIn('role', ['staff', 'reporter'])
            ->with(['accessibleWebsites', 'settings'])
            ->get()
            ->map(function($staff) {
                // স্টাফের কাজের হিসাব ও পারফরম্যান্স যুক্ত করা হচ্ছে
                $staff->total_published = \App\Models\NewsItem::withoutGlobalScopes()->where('staff_id', $staff->id)->where('status', 'published')->count();
                $staff->total_drafts = \App\Models\NewsItem::withoutGlobalScopes()->where('staff_id', $staff->id)->where('status', '!=', 'published')->count();
                $staff->credits_used = \App\Models\CreditHistory::where('staff_id', $staff->id)->where('credits_change', '<', 0)->sum('credits_change') * -1;
                $staff->ai_rewrites = \App\Models\CreditHistory::where('staff_id', $staff->id)->where('action_type', 'ai_rewrite')->count();
                
                return $staff;
            });
                      
        // ৪. অ্যাডমিনের নিজের তৈরি করা সোর্স এবং সুপার অ্যাডমিনের দেওয়া সোর্স একসাথে আনা (ফিক্সড)
        $adminWebsites = Website::withoutGlobalScopes()
            ->where(function($query) use ($admin) {
                $query->where('user_id', $admin->id)
                      ->orWhereHas('users', function($q) use ($admin) {
                          $q->where('users.id', $admin->id); // টেবিল নেম উল্লেখ করে ফিক্স করা হয়েছে
                      });
            })->get();
            
        $adminTemplates = $admin->settings->allowed_templates ?? [];

        return view('client.staff.index', compact('staffs', 'admin', 'adminWebsites', 'adminTemplates'));
    }

    /**
     * নতুন স্টাফ তৈরি করা
     */
    public function store(Request $request)
    {
        $admin = Auth::user();

        // লিমিট চেক
        $currentStaffCount = User::where('parent_id', $admin->id)->where('role', 'staff')->count();
        if ($admin->role !== 'super_admin' && $currentStaffCount >= $admin->staff_limit) {
            return back()->with('error', "❌ আপনার লিমিট শেষ!");
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        // 🔥 অ্যাডমিনের বর্তমান পারমিশনগুলো নেওয়া (যাতে স্টাফকেও সেইম পারমিশন দেওয়া যায়)
        $adminPermissions = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];

        $staff = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff',
            'parent_id' => $admin->id,
            'is_active' => true,
            'permissions' => $adminPermissions // 👈 ডিফল্টভাবে অ্যাডমিনের সব পারমিশন দিয়ে দেওয়া হলো
        ]);

        // স্টাফের জন্য সেটিংস তৈরি
        UserSetting::create(['user_id' => $staff->id]);

        // 🔥 অ্যাডমিনের সব ওয়েবসাইট অটোমেটিক স্টাফকে এক্সেস দেওয়া (ঐচ্ছিক কিন্তু সুবিধাজনক)
        $adminWebsiteIds = \App\Models\Website::withoutGlobalScopes()
            ->where('user_id', $admin->id)
            ->pluck('id')->toArray();
            
        $staff->accessibleWebsites()->sync($adminWebsiteIds);

        return back()->with('success', 'নতুন স্টাফ অ্যাকাউন্ট তৈরি হয়েছে এবং সব পারমিশন ডিফল্ট করা হয়েছে!');
    }

    /**
     * স্টাফের পারমিশন আপডেট (অ্যাডমিনের নিজের পারমিশনের বাইরে দিতে পারবে না)
     */
    public function updatePermissions(Request $request, $id)
    {
        $admin = Auth::user();
        $staff = User::where('parent_id', $admin->id)->findOrFail($id);

        $requestedPermissions = $request->input('permissions', []);
        
        // অ্যাডমিনের নিজের যা পারমিশন আছে, স্টাফকে তার বেশি দিতে পারবে না
        if ($admin->role !== 'super_admin') {
            $adminPermissions = is_array($admin->permissions) ? $admin->permissions : json_decode($admin->permissions, true) ?? [];
            $finalPermissions = array_intersect($requestedPermissions, $adminPermissions);
        } else {
            $finalPermissions = $requestedPermissions; // সুপার অ্যাডমিন সব দিতে পারবে
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
        
        // অ্যাডমিনের এক্সেসে থাকা ওয়েবসাইট আইডিগুলো ফিল্টার করা
        $adminWebsiteIds = Website::withoutGlobalScopes()
            ->where(function($query) use ($admin) {
                $query->where('user_id', $admin->id)
                      ->orWhereHas('users', function($q) use ($admin) {
                          $q->where('users.id', $admin->id);
                      });
            })->pluck('id')->toArray();
        
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
        $staff = User::where('parent_id', $adminId)->findOrFail($id);
        
        $staff->delete();
        
        return back()->with('success', 'স্টাফ মুছে ফেলা হয়েছে।');
    }
}