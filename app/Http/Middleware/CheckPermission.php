<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // ১. ইউজার লগইন করা আছে কি না চেক
        if (!auth()->check()) {
            return redirect('login');
        }

        // ২. ইউজারের ওই পারমিশন আছে কি না চেক (সুপার এডমিন হলে অটো এক্সেস পাবে)
        if (!auth()->user()->hasPermission($permission)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'আপনার এই কাজের অনুমতি নেই।'], 403);
            }
            abort(403, 'আপনার এই সেকশনে প্রবেশের অনুমতি নেই।');
        }

        return $next($request);
    }
}