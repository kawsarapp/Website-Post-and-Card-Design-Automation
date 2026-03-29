<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\User; // 🔥 User মডেল ইমপোর্ট করা হলো
use App\Jobs\ScrapeWebsite; // ✅ Job ক্লাস ইমপোর্ট
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteController extends Controller
{
    // 🔥 হেল্পার ফাংশন: স্টাফ বা রিপোর্টার হলে তার অ্যাডমিনকে বের করবে
    private function getEffectiveAdmin() {
        $user = Auth::user();
        return in_array($user->role, ['staff', 'reporter']) ? User::find($user->parent_id) : $user;
    }

    // ==========================================
    // ১. ওয়েবসাইট লিস্ট দেখা (Role ভিত্তিক)
    // ==========================================
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'super_admin') {
            // সুপার অ্যাডমিন সব ওয়েবসাইট দেখবে
            $websites = Website::withoutGlobalScopes()->get();
        } elseif (in_array($user->role, ['user', 'admin'])) {
            // অ্যাডমিন (Client) তার নিজের তৈরি করা এবং সুপার অ্যাডমিনের দেওয়া সাইটগুলো দেখবে
            $websites = Website::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->orWhereHas('users', function($q) use ($user) {
                    $q->where('users.id', $user->id); // 🔥 Data ambiguity এড়াতে users.id দেওয়া হলো
                })->get();
        } else {
            // Staff বা Reporter: অ্যাডমিন তাদেরকে যে সোর্সগুলো পারমিশন দিয়েছে, শুধু সেগুলো দেখবে
            $websites = $user->accessibleWebsites()
                        ->withoutGlobalScopes()
                        ->get();
        }
        
        return view('websites.index', compact('websites'));
    }

    // ==========================================
    // ২. ওয়েবসাইট যোগ করা (শুধু সুপার অ্যাডমিন)
    // ==========================================
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') {
            return back()->with('error', 'অনুমতি নেই।');
        }

        $request->validate([
            'name' => 'required',
            'url' => 'required|url',
            'selector_container' => 'required',
            'selector_title' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = Auth::id();
        $data['use_scraping_api'] = $request->has('use_scraping_api') ? 1 : 0;

        Website::create($data);

        return back()->with('success', 'Website added successfully!');
    }

    // ==========================================
    // ৩. ওয়েবসাইট স্ক্র্যাপ করা (Observe)
    // ==========================================
    public function scrape($id)
    {
        $user = Auth::user();
        $adminUser = $this->getEffectiveAdmin(); // 🔥 স্টাফের অ্যাডমিনকে কল করা হলো

        // ১. ওয়েবসাইট ভ্যালিডেশন / এক্সেস চেক (Role অনুযায়ী)
        if ($user->role === 'super_admin') {
            $website = Website::withoutGlobalScopes()->findOrFail($id);
        } elseif (in_array($user->role, ['user', 'admin'])) {
            $website = Website::withoutGlobalScopes()
                ->where(function($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->orWhereHas('users', function($q) use ($user) {
                              $q->where('users.id', $user->id); // 🔥 Data ambiguity এড়াতে users.id দেওয়া হলো
                          });
                })->findOrFail($id);
        } else {
            // স্টাফের যদি এই সাইট স্ক্র্যাপ করার অনুমতি থাকে, তবেই সে পারবে
            $website = $user->accessibleWebsites()
                ->withoutGlobalScopes()
                ->where('websites.id', $id)
                ->firstOrFail();
        }

        // ২. 🔥 ৫ মিনিটের চেকিং লজিক (Cool-down Check)
        if ($website->last_scraped_at) {
            $lastScraped = \Carbon\Carbon::parse($website->last_scraped_at);
            $diffInSeconds = now()->diffInSeconds($lastScraped);
            $cooldownSeconds = 300; // ৫ মিনিট = ৩০০ সেকেন্ড

            if ($diffInSeconds < $cooldownSeconds) {
                $wait = $cooldownSeconds - $diffInSeconds;
                $minutes = floor($wait / 60);
                $seconds = $wait % 60;
                return back()->with('error', "অনুগ্রহ করে অপেক্ষা করুন: {$minutes} মিনিট {$seconds} সেকেন্ড পর আবার চেষ্টা করুন।");
            }
        }

        // ৩. টাইমস্ট্যাম্প আপডেট করা
        $website->update(['last_scraped_at' => now()]);

        // ৪. জব ডিসপ্যাচ (🔥 এখানে $adminUser->id দেওয়া হলো, যাতে প্রক্সি এবং লিমিট অ্যাডমিনের প্রোফাইল থেকে নেয়)
        ScrapeWebsite::dispatch($website->id, $adminUser->id);
        
        return redirect()->route('news.index', ['scraping' => 'started'])
            ->with('success', '⏳ স্ক্র্যাপিং শুরু হয়েছে! অনুগ্রহ করে অপেক্ষা করুন...');
    }

    // ==========================================
    // ৪. ওয়েবসাইট আপডেট করা
    // ==========================================
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'super_admin') {
            return back()->with('error', 'Permission Denied');
        }
        
        $website = Website::withoutGlobalScopes()->findOrFail($id);
        
        $data = $request->all();
        $data['use_scraping_api'] = $request->has('use_scraping_api') ? 1 : 0;
        
        $website->update($data);
        
        return back()->with('success', 'Website Updated');
    }
}