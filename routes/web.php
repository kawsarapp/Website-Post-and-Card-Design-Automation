<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    AdminController,
    WebsiteController,
    NewsController,
    SettingsController,
    PaymentController,
    TelegramBotController,
    ReporterController,
    ReporterManagementController
};
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes - SubEditorBD Full Routing System
|--------------------------------------------------------------------------
*/

// --- ১. পাবলিক এবং গেস্ট রুটস ---
Route::get('/', function () {
    return redirect()->route('login');
});

// টেলিগ্রাম ওয়েবহুক
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});


// --- ২. লগইন করা সকল ইউজারের জন্য কমন রুটস (Auth Middleware) ---
Route::middleware(['auth'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/stop-impersonate', [AdminController::class, 'stopImpersonate'])->name('stop.impersonate');

    // নোটিফিকেশন রিড
    Route::get('/notifications/read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.read');

    // প্রোফাইল ও ক্রেডিট
    Route::get('/credits', [SettingsController::class, 'credits'])->name('credits.index');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.update-profile');


    // ============================================================
    // ৩. রিপোর্টার সেকশন 
    // ============================================================
    Route::prefix('reporter')->name('reporter.')->group(function () {
        Route::get('/news/create', [ReporterController::class, 'create'])->name('news.create');
        Route::post('/news/store', [ReporterController::class, 'store'])->name('news.store');
        Route::get('/my-news', [ReporterController::class, 'index'])->name('news.index');
    });


    // ============================================================
    // ৪. ম্যানেজমেন্ট ও নিউজ কোর সেকশন
    // ============================================================
    
    // ৫. প্রতিনিধি ম্যানেজমেন্ট (manage_reporters)
    Route::middleware(['permission:manage_reporters'])->group(function () {
        Route::prefix('manage')->name('manage.')->group(function () {
            Route::get('/reporters', [ReporterManagementController::class, 'index'])->name('reporters.index');
            Route::post('/reporters/store', [ReporterManagementController::class, 'store'])->name('reporters.store');
            Route::delete('/reporters/{id}', [ReporterManagementController::class, 'destroy'])->name('reporters.destroy');
            Route::get('/reporter-news', [ReporterManagementController::class, 'newsReport'])->name('reporters.news');
        });
    });

    // নিউজ কোর রুটস
    Route::controller(NewsController::class)->prefix('news')->name('news.')->group(function () {
        
        // কমন নিউজ রুটস
        Route::get('/', 'index')->name('index');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/post', 'postToWordPress')->name('post');
        Route::post('/{id}/manual-publish', 'publishManualFromIndex')->name('manual-publish');
        Route::post('/{id}/queue', 'toggleQueue')->name('queue');
        Route::get('/check-status', 'checkAutoPostStatus')->name('check-auto-status');
        Route::get('/check-scrape-status', 'checkScrapeStatus')->name('check-scrape-status');
        Route::post('/toggle-automation', 'toggleAutomation')->name('toggle-automation');
        
        // --- 🔐 লকিং এবং আনলকিং রুটস ---
        Route::get('/{id}/unlock', 'unlockNews')->name('unlock');
        Route::get('/{id}/get-draft', 'getDraftContent')->name('get-draft');

        // --- 📝 ড্রাফট এবং AI রিরাইট রুটস ---
        // ফিক্স: এখানে শুধুমাত্র একটি রুট থাকবে যা updateDraft কে কল করবে
        Route::post('/{id}/update-draft', 'updateDraft')->name('update-draft');
        Route::post('/{id}/process-ai', 'sendToAiQueue')->name('process-ai');

        // ৪. ম্যানুয়াল পাবলিশ (can_direct_publish)
        Route::middleware(['permission:can_direct_publish'])->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store-custom', 'storeCustom')->name('store-custom');
        });

        // ২. AI ড্রাফট (can_ai)
        Route::middleware(['permission:can_ai'])->group(function () {
            Route::get('/drafts', 'drafts')->name('drafts');
            Route::post('/{id}/publish-draft', 'publishDraft')->name('publish-draft');
            Route::post('/{id}/confirm-publish', 'confirmPublish')->name('confirm-publish');
        });

        // ৩. স্টুডিও ডিজাইন (can_studio)
        Route::middleware(['permission:can_studio'])->group(function () {
            Route::get('/{id}/studio', 'studio')->name('studio');
            Route::post('/{id}/publish-studio', 'publishStudioDesign')->name('publish-studio');
        });
    });

    // ১. নিউজ স্ক্র্যাপিং (can_scrape)
    Route::middleware(['permission:can_scrape'])->group(function () {
        Route::resource('websites', WebsiteController::class)->only(['index', 'store', 'update']);
        Route::get('/websites/{id}/scrape', [WebsiteController::class, 'scrape'])->name('websites.scrape');
    });
    
    // ইমেজ প্রক্সি
    Route::get('/proxy-image', [NewsController::class, 'proxyImage'])->name('proxy.image');

    // কানেকশন টেস্ট
    Route::prefix('settings/test')->name('settings.')->group(function () {
        Route::post('/facebook', [SettingsController::class, 'testFacebookConnection'])->name('test-facebook');
        Route::post('/telegram', [SettingsController::class, 'testTelegramConnection'])->name('test-telegram');
        Route::post('/wordpress', [SettingsController::class, 'testWordPressConnection'])->name('test-wordpress');
    });

    // পেমেন্ট ও ডিজাইন সেটিংস
    Route::resource('buy-credits', PaymentController::class)->names('payment')->only(['create', 'store']);
    Route::get('/settings/fetch-categories', [SettingsController::class, 'fetchCategories'])->name('settings.fetch-categories');
    Route::post('/settings/save-design', [SettingsController::class, 'saveDesign'])->name('settings.save-design');
    Route::post('/settings/upload-frame', [SettingsController::class, 'uploadFrame'])->name('settings.upload-frame');
});


// --- ৫. সুপার অ্যাডমিন রুটস ---
Route::middleware(['auth', AdminMiddleware::class])->group(function () {
    
    Route::prefix('admin/users/{id}')->name('admin.users.')->group(function () {
        Route::post('/permissions', [AdminController::class, 'updatePermissions'])->name('permissions');
    });
    
    Route::get('/admin/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/admin/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/admin/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/post-history', [AdminController::class, 'postHistory'])->name('post-history');

        // ইউজার ম্যানেজমেন্ট
        Route::prefix('users/{id}')->name('users.')->group(function () {
            Route::post('/toggle-status', [AdminController::class, 'toggleStatus'])->name('toggle');
            Route::post('/add-credits', [AdminController::class, 'addCredits'])->name('credits');
            Route::post('/templates', [AdminController::class, 'updateTemplates'])->name('templates');
            Route::post('/limit', [AdminController::class, 'updateLimit'])->name('limit');
            Route::post('/websites', [AdminController::class, 'updateWebsiteAccess'])->name('websites');
            Route::post('/scraper', [AdminController::class, 'updateScraperSettings'])->name('scraper');
            Route::get('/login-as', [AdminController::class, 'loginAsUser'])->name('login-as');
            Route::put('/update', [AdminController::class, 'updateUser'])->name('update');
        });
        Route::post('/users/create', [AdminController::class, 'store'])->name('users.store');

        // পেমেন্ট অ্যাডমিন
        Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
            Route::get('/', 'adminIndex')->name('index');
            Route::post('/{id}/approve', 'approve')->name('approve');
            Route::post('/{id}/reject', 'reject')->name('reject');
        });
    });
});