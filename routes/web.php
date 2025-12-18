<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TelegramBotController;
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

// Test Routes (Settings Test Routes) - এগুলোও অ্যাডমিনের ভেতরে নেওয়া ভালো, তবে বাইরে থাকলেও সমস্যা নেই যদি কন্ট্রোলারে চেক থাকে
Route::post('/settings/test-facebook', [SettingsController::class, 'testFacebookConnection'])->name('settings.test-facebook');
Route::post('/settings/test-telegram', [SettingsController::class, 'testTelegramConnection'])->name('settings.test-telegram');
Route::post('/settings/test-wordpress', [SettingsController::class, 'testWordPressConnection'])->name('settings.test-wordpress');

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);
// Studio Direct Post
Route::post('/news/{id}/publish-studio', [NewsController::class, 'publishStudioDesign'])->name('news.publish-studio');

// --- Guest Routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// --- Authenticated User Routes (সাধারণ ইউজার + অ্যাডমিন সবাই পাবে) ---
Route::middleware(['auth'])->group(function () {
    
    // Notifications
    Route::get('/notifications/read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.read');

    // Status Checks
    Route::get('/news/check-scrape-status', [NewsController::class, 'checkScrapeStatus'])->name('news.check-scrape-status');
    Route::get('/news/check-status', [NewsController::class, 'checkAutoPostStatus'])->name('news.check-auto-status');

    // Design
    Route::post('/settings/save-design', [SettingsController::class, 'saveDesign'])->name('settings.save-design');
    Route::post('/settings/upload-frame', [SettingsController::class, 'uploadFrame'])->name('settings.upload-frame');
    Route::post('/news/{id}/manual-publish', [NewsController::class, 'publishManualFromIndex'])->name('news.manual-publish');
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Website Management
    Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
    Route::put('/websites/{id}', [WebsiteController::class, 'update'])->name('websites.update');
    Route::get('/websites/{id}/scrape', [WebsiteController::class, 'scrape'])->name('websites.scrape');

    // News Management
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::delete('/news/{id}', [NewsController::class, 'destroy'])->name('news.destroy');
    Route::get('/news/create', [NewsController::class, 'create'])->name('news.create');
    Route::post('/news/store', [NewsController::class, 'storeCustom'])->name('news.store-custom');

    // Studio & Proxy
    Route::get('/proxy-image', [NewsController::class, 'proxyImage'])->name('proxy.image');
    Route::get('/news/{id}/studio', [NewsController::class, 'studio'])->name('news.studio');

    // Publishing Actions
    Route::post('/news/{id}/post', [NewsController::class, 'postToWordPress'])->name('news.post');
    Route::post('/news/toggle-automation', [NewsController::class, 'toggleAutomation'])->name('news.toggle-automation');
    Route::post('/news/{id}/queue', [NewsController::class, 'toggleQueue'])->name('news.queue');

    // AI & Drafts
    Route::post('/news/{id}/process-ai', [NewsController::class, 'sendToAiQueue'])->name('news.process-ai');
    Route::get('/news/drafts', [NewsController::class, 'drafts'])->name('news.drafts');                      
    Route::get('/news/{id}/get-draft', [NewsController::class, 'getDraftContent'])->name('news.get-draft'); 
    Route::post('/news/{id}/publish-draft', [NewsController::class, 'publishDraft'])->name('news.publish-draft'); 
    Route::post('/news/{id}/confirm-publish', [NewsController::class, 'confirmPublish'])->name('news.confirm-publish'); 

    // 🔥🔥🔥 Profile Update (এটি সবার জন্য রাখা হলো)
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.update-profile');
    
    // Credit History
    Route::get('/credits', [SettingsController::class, 'credits'])->name('credits.index');

    // Payments
    Route::get('/buy-credits', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/buy-credits', [PaymentController::class, 'store'])->name('payment.store');

    // 🔥🔥 FETCH CATEGORIES (Moved from Admin group)
    // এটি এখন সাধারণ ইউজাররাও এক্সেস করতে পারবে স্টুডিও বা ড্রাফট পেজ থেকে
    Route::get('/settings/fetch-categories', [SettingsController::class, 'fetchCategories'])->name('settings.fetch-categories');
});

// --- 🔥 ADMIN ONLY ROUTES (Settings এখন এখানে) ---
Route::middleware(['auth', AdminMiddleware::class]) // এখানে AdminMiddleware ব্যবহার করা হয়েছে
    ->group(function () {
    
    // 🔥🔥 SETTINGS ROUTES (ONLY ADMIN) 🔥🔥
    // এখন সাধারণ ইউজাররা /settings এ গেলে এক্সেস পাবে না
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
	
    
    // Admin Dashboard Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleStatus'])->name('users.toggle');
        Route::post('/users/{id}/add-credits', [AdminController::class, 'addCredits'])->name('users.credits');
        Route::post('/users/{id}/templates', [AdminController::class, 'updateTemplates'])->name('users.templates');
        Route::post('/users/{id}/limit', [AdminController::class, 'updateLimit'])->name('users.limit');
        Route::post('/users/{id}/websites', [AdminController::class, 'updateWebsiteAccess'])->name('users.websites');
        Route::post('/users/{id}/scraper', [AdminController::class, 'updateScraperSettings'])->name('users.scraper');
        Route::post('/users/create', [AdminController::class, 'store'])->name('users.store');
        Route::put('/users/{id}/update', [AdminController::class, 'updateUser'])->name('users.update');
		Route::get('/post-history', [AdminController::class, 'postHistory'])->name('post-history');
        
        // Payments
        Route::get('/payments', [PaymentController::class, 'adminIndex'])->name('payments.index');
        Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    });
});