<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
// লারাভেল ১২-এর জন্য প্রয়োজনীয় ইমপোর্ট
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReporterManagementController extends Controller implements HasMiddleware
{
    /**
     * লারাভেল ১২-এর মিডলওয়্যার লজিক
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (Auth::user()->role === 'reporter') {
                    abort(403, 'রিপোর্টারদের জন্য এই পেজটি অনুমোদিত নয়।');
                }
                return $next($request);
            }),
        ];
    }

    /**
     * রিপোর্টার লিস্ট, ফিল্টারিং এবং সার্চ লজিক
     */
    public function index(Request $request)
    {
        // 🛠️ FIX: withCount('news') এর বদলে সাবকোয়েরি ব্যবহার করা হলো, যাতে User মডেলে হাত দিতে না হয়
        $query = User::where('parent_id', Auth::id())
                     ->where('role', 'reporter')
                     ->addSelect(['news_count' => NewsItem::withoutGlobalScopes()
                         ->selectRaw('count(*)')
                         ->whereColumn('reporter_id', 'users.id')
                     ]); 

        // 🔍 সার্চ লজিক (নাম বা ইমেইল দিয়ে খোঁজার জন্য)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 📊 সর্টিং ও ফিল্টার লজিক
        if ($request->sort === 'oldest') {
            $query->oldest(); // সবচেয়ে পুরনো আগে দেখাবে
        } elseif ($request->sort === 'active') {
            $query->orderByDesc('news_count'); // যে সবচেয়ে বেশি নিউজ দিয়েছে সে আগে থাকবে
        } else {
            $query->latest(); // ডিফল্ট: নতুন যুক্ত হওয়া রিপোর্টার আগে থাকবে
        }

        // পেজিনেশন (সার্চ ও ফিল্টারের প্যারামিটারগুলো লিংকের সাথে ধরে রাখার জন্য)
        $reporters = $query->paginate(20)->appends($request->except('page'));

        return view('manage.reporters.index', compact('reporters'));
    }

    /**
     * নতুন রিপোর্টার তৈরি
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:32',
        ]);

        User::create([
            'name'      => strip_tags($request->name),
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => 'reporter',
            'parent_id' => Auth::id(),
            'is_active' => true,
        ]);

        return back()->with('success', 'নতুন প্রতিনিধি অ্যাকাউন্ট তৈরি হয়েছে।');
    }

    /**
     * রিপোর্টারদের পাঠানো নিউজ রিপোর্ট দেখা
     */
    public function newsReport(Request $request)
    {
        $query = NewsItem::withoutGlobalScopes()
            ->with('reporter')
            ->where('user_id', Auth::id())
            ->whereNotNull('reporter_id');

        // নির্দিষ্ট রিপোর্টারের নিউজ ফিল্টার
        if ($request->filled('reporter_id')) {
            $reporterExists = User::where('id', $request->reporter_id)
                                  ->where('parent_id', Auth::id())
                                  ->exists();
            if ($reporterExists) {
                $query->where('reporter_id', $request->reporter_id);
            }
        }

        // তারিখ অনুযায়ী ফিল্টার
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $news = $query->latest()->paginate(20)->appends($request->except('page'));
        
        $reporters = User::where('parent_id', Auth::id())
                         ->where('role', 'reporter')
                         ->get();

        return view('manage.reporters.news_report', compact('news', 'reporters'));
    }

    /**
     * রিপোর্টার মুছে ফেলা
     */
    public function destroy($id)
    {
        $reporter = User::where('parent_id', Auth::id())
                        ->where('role', 'reporter')
                        ->findOrFail($id);
        
        $reporter->delete();
        
        return back()->with('success', 'প্রতিনিধি অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }
}