<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Public Routes ---
Route::get('/', function () {
    return redirect()->route('websites.index');
});

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);

// --- Guest Routes (Only for non-logged in users) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// --- Authenticated User Routes (Require Login) ---
Route::middleware(['auth'])->group(function () {
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Website Management
    Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
    Route::get('/websites/{id}/scrape', [WebsiteController::class, 'scrape'])->name('websites.scrape');

    // News & Card Maker
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/check-status', [NewsController::class, 'checkAutoPostStatus'])->name('news.check-status');
    
    // Image Proxy
    Route::get('/proxy-image', [NewsController::class, 'proxyImage'])->name('proxy.image');
    
    // News Automation & Studio
    Route::post('/news/{id}/post', [NewsController::class, 'postToWordPress'])->name('news.post');
    Route::get('/news/{id}/studio', [NewsController::class, 'studio'])->name('news.studio');
    Route::post('/news/toggle-automation', [NewsController::class, 'toggleAutomation'])->name('news.toggle-automation');
    Route::post('/news/{id}/queue', [NewsController::class, 'toggleQueue'])->name('news.queue');

    // User Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // ✅ লোগো আপলোড রাউটটি এখানে (সাধারণ ইউজারদের জন্য) নিয়ে আসা হয়েছে
    Route::post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
});

// --- Admin Routes (Require Login + Admin Middleware) ---
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    
    // User Management Actions
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/users/{id}/add-credits', [AdminController::class, 'addCredits'])->name('users.credits');
    
    // Template Management (এটি আগের ধাপের সাথে মিল আছে)
    Route::post('/users/{id}/templates', [AdminController::class, 'updateTemplates'])->name('users.templates');
});