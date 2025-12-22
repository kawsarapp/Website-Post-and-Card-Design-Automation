<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // ইউজারের রোল যদি প্যারামিটারে পাঠানো রোলের সাথে না মিলে তবে ৪০৩ এরর
        if (auth()->check() && auth()->user()->role !== $role) {
            abort(403, 'আপনার এই সেকশনে প্রবেশের অনুমতি নেই।');
        }

        return $next($request);
    }
}