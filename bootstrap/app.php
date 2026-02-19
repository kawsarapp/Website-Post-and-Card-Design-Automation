<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request; // 🔥 এটি ইম্পোর্ট করা জরুরি

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
			'nocache' => \App\Http\Middleware\NoCacheMiddleware::class, // 🔥 Notun add holo
            //'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            '/telegram/webhook', 
            '/news/*/post'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 🔥 লাইভ সার্ভারে এরর হ্যান্ডলিং
        $exceptions->render(function (Throwable $e, Request $request) {
            // যদি APP_DEBUG=true থাকে, তবে ডিফল্ট এরর পেজ দেখাবে
            if (config('app.debug')) {
                return null; 
            }

            // লাইভ সার্ভারে (Debug False থাকলে) কাস্টম পেজে রিডাইরেক্ট করবে
            return response()->view('errors.custom', [], 500);
        });
    }) // 👈 এখানে ক্লোজিং ব্র্যাকেট ভুল ছিল, ঠিক করা হয়েছে
    ->create();