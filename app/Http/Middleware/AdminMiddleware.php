<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
	
	
	public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            if (Auth::user()->role === 'super_admin' || session()->has('admin_impersonator_id')) {
                return $next($request);
            }
        }
        return redirect('/')->with('error', 'অ্যাক্সেস ডিনাইড!');
    }
	
	
	
}