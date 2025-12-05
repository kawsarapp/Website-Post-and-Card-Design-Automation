<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ ১. এখানে আমরা 'admin' নাম দিয়ে মিডলওয়্যারটি চিনিয়ে দিচ্ছি
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        
        // CSRF টোকেন ভেরিফিকেশন বাদ দেওয়া (Webhook এর জন্য)
        $middleware->validateCsrfTokens(except: [
            '/telegram/webhook', 
            '/news/*/post' // যদি বাইরে থেকে পোস্ট রিকোয়েস্ট আসে
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
