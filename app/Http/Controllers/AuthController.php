<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // লগইন পেজ দেখানো
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // লগইন প্রসেস করা
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // যদি সুপার অ্যাডমিন হয়, অ্যাডমিন প্যানেলে পাঠাবে
            if (Auth::user()->role === 'super_admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('news.index');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    // লগআউট করা
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}