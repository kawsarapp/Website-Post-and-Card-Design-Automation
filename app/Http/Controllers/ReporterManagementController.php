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

    public function index()
    {
        $reporters = User::where('parent_id', Auth::id())
                         ->where('role', 'reporter')
                         ->get();
        return view('manage.reporters.index', compact('reporters'));
    }

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

    public function newsReport(Request $request)
    {
        $query = NewsItem::withoutGlobalScopes()
            ->with('reporter')
            ->where('user_id', Auth::id())
            ->whereNotNull('reporter_id');

        if ($request->filled('reporter_id')) {
            $reporterExists = User::where('id', $request->reporter_id)
                                  ->where('parent_id', Auth::id())
                                  ->exists();
            if ($reporterExists) {
                $query->where('reporter_id', $request->reporter_id);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $news = $query->latest()->paginate(20);
        $reporters = User::where('parent_id', Auth::id())->get();

        return view('manage.reporters.news_report', compact('news', 'reporters'));
    }

    public function destroy($id)
    {
        $reporter = User::where('parent_id', Auth::id())
                        ->where('role', 'reporter')
                        ->findOrFail($id);
        
        $reporter->delete();
        return back()->with('success', 'প্রতিনিধি অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }
}