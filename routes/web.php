<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\NewsController; // ✅ এই লাইনটি থাকতে হবে
use App\Http\Controllers\TelegramBotController;

Route::get('/', function () {
    return redirect()->route('websites.index');
});

// Website Management
Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
Route::get('/websites/{id}/scrape', [WebsiteController::class, 'scrape'])->name('websites.scrape');

// News & Card Maker
Route::get('/news', [NewsController::class, 'index'])->name('news.index');

// ✅ Image Proxy (ইমেজ ডাউনলোডের জন্য)
Route::get('/proxy-image', [NewsController::class, 'proxyImage'])->name('proxy.image');

// ✅ WordPress Auto Post (এই লাইনটি মিসিং ছিল!)
Route::post('/news/{id}/post', [NewsController::class, 'postToWordPress'])->name('news.post');

Route::get('/news/{id}/studio', [App\Http\Controllers\NewsController::class, 'studio'])->name('news.studio');

Route::post('/news/toggle-automation', [App\Http\Controllers\NewsController::class, 'toggleAutomation'])->name('news.toggle-automation');

Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);