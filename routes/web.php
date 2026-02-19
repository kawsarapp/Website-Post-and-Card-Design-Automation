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
    ReporterManagementController,
    AdminTemplateController
};
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| Web Routes - SubEditorBD Full Routing System
|--------------------------------------------------------------------------
*/

// --- ১. পাবলিক এবং গেস্ট রুটস (লগইন ছাড়া এক্সেস পাবে) ---
Route::get('/', function () {
    return redirect()->route('login');
});

// 🔥 বসের জন্য পাবলিক প্রিভিউ এবং ফিডব্যাক রুটস
Route::get('/preview/{id}', [NewsController::class, 'publicPreview'])->name('news.public-preview');
Route::post('/preview/{id}/feedback', [NewsController::class, 'handlePreviewFeedback'])->name('news.preview-feedback');

// টেলিগ্রাম ওয়েবহুক
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});


// --- ২. লগইন করা সকল ইউজারের জন্য কমন রুটস (Auth & NoCache Middleware) ---
// 🔥 এখানে 'nocache' যোগ করা হয়েছে যাতে ব্রাউজার ড্যাশবোর্ডের ডাটা ক্যাশ না করে
Route::middleware(['auth', 'nocache'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); // ডুপ্লিকেট রুট মুছে ফেলা হয়েছে
    Route::get('/stop-impersonate', [AdminController::class, 'stopImpersonate'])->name('stop.impersonate');

    // নোটিফিকেশন রিড
    Route::get('/notifications/read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.read');

    // প্রোফাইল ও ক্রেডিট
    Route::get('/credits', [SettingsController::class, 'credits'])->name('credits.index');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.update-profile');

    // --- সেটিংস ম্যানেজমেন্ট ---
    Route::middleware(['permission:can_settings'])->group(function () {
        Route::get('/admin/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/admin/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/admin/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
    });

    // রিপোর্টার সেকশন
    Route::prefix('reporter')->name('reporter.')->group(function () {
        Route::get('/news/create', [ReporterController::class, 'create'])->name('news.create');
        Route::post('/news/store', [ReporterController::class, 'store'])->name('news.store');
        Route::get('/my-news', [ReporterController::class, 'index'])->name('news.index');
    });

    // প্রতিনিধি ম্যানেজমেন্ট
    Route::middleware(['permission:manage_reporters'])->group(function () {
        Route::prefix('manage')->name('manage.')->group(function () {
            Route::get('/reporters', [ReporterManagementController::class, 'index'])->name('reporters.index');
            Route::post('/reporters/store', [ReporterManagementController::class, 'store'])->name('reporters.store');
            Route::delete('/reporters/{id}', [ReporterManagementController::class, 'destroy'])->name('reporters.destroy');
            Route::get('/reporter-news', [ReporterManagementController::class, 'newsReport'])->name('reporters.news');
        });
    });

    // ৪. নিউজ কোর সেকশন
    Route::controller(NewsController::class)->prefix('news')->name('news.')->group(function () {
        
        Route::get('/', 'index')->name('index');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::post('/{id}/post', 'postToWordPress')->name('post');
        Route::post('/{id}/manual-publish', 'publishManualFromIndex')->name('manual-publish');
        Route::post('/{id}/queue', 'toggleQueue')->name('queue');
        Route::get('/check-status', 'checkAutoPostStatus')->name('check-auto-status');
        Route::get('/check-scrape-status', 'checkScrapeStatus')->name('check-scrape-status');
        Route::post('/toggle-automation', 'toggleAutomation')->name('toggle-automation');
        Route::post('/check-draft-updates', 'checkDraftUpdates')->name('check-draft-updates');
        Route::get('/published', 'published')->name('published');

        Route::get('/{id}/unlock', 'unlockNews')->name('unlock');
        Route::get('/{id}/get-draft', 'getDraftContent')->name('get-draft');
        Route::post('/{id}/update-draft', 'updateDraft')->name('update-draft');
        Route::post('/{id}/process-ai', 'sendToAiQueue')->name('process-ai');

        Route::middleware(['permission:can_direct_publish'])->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/store-custom', 'storeCustom')->name('store-custom');
        });

        Route::middleware(['permission:can_ai'])->group(function () {
            Route::get('/drafts', 'drafts')->name('drafts');
            Route::post('/{id}/publish-draft', 'publishDraft')->name('publish-draft');
            Route::post('/{id}/confirm-publish', 'confirmPublish')->name('confirm-publish');
        });

        Route::middleware(['permission:can_studio'])->group(function () {
            Route::get('/{id}/studio', 'studio')->name('studio');
            Route::post('/{id}/publish-studio', 'publishStudioDesign')->name('publish-studio');
        });
    });

    // নিউজ স্ক্র্যাপিং
    Route::middleware(['permission:can_scrape'])->group(function () {
        Route::resource('websites', WebsiteController::class)->only(['index', 'store', 'update']);
        Route::get('/websites/{id}/scrape', [WebsiteController::class, 'scrape'])->name('websites.scrape');
    });
    
    Route::get('/proxy-image', [NewsController::class, 'proxyImage'])->name('proxy.image');

    // কানেকশন টেস্ট
    Route::prefix('settings/test')->name('settings.')->group(function () {
        Route::post('/facebook', [SettingsController::class, 'testFacebookConnection'])->name('test-facebook');
        Route::post('/telegram', [SettingsController::class, 'testTelegramConnection'])->name('test-telegram');
        Route::post('/wordpress', [SettingsController::class, 'testWordPressConnection'])->name('test-wordpress');
    });

    Route::resource('buy-credits', PaymentController::class)->names('payment')->only(['create', 'store']);
    Route::get('/settings/fetch-categories', [SettingsController::class, 'fetchCategories'])->name('settings.fetch-categories');
    Route::post('/settings/save-design', [SettingsController::class, 'saveDesign'])->name('settings.save-design');
    Route::post('/settings/upload-frame', [SettingsController::class, 'uploadFrame'])->name('settings.upload-frame');
});


// --- ৫. সুপার অ্যাডমিন রুটস (nocache যোগ করা হয়েছে) ---
Route::middleware(['auth', 'nocache', AdminMiddleware::class])->group(function () {
    
    Route::prefix('admin/templates')->name('admin.templates.')->group(function () {
        Route::get('/', [AdminTemplateController::class, 'index'])->name('index');
        Route::get('/builder', [AdminTemplateController::class, 'builder'])->name('builder');
        Route::post('/store', [AdminTemplateController::class, 'store'])->name('store');
        Route::delete('/{id}', [AdminTemplateController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/users/{id}')->name('admin.users.')->group(function () {
        Route::post('/permissions', [AdminController::class, 'updatePermissions'])->name('permissions');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/post-history', [AdminController::class, 'postHistory'])->name('post-history');

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

        Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
            Route::get('/', 'adminIndex')->name('index');
            Route::post('/{id}/approve', 'approve')->name('approve');
            Route::post('/{id}/reject', 'reject')->name('reject');
        });
    });
});