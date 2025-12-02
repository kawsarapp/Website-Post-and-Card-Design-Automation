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

// Telegram Webhook
Route::post('/telegram/webhook', [TelegramBotController::class, 'handle']);

// --- Guest Routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// --- Authenticated User Routes ---
Route::middleware(['auth'])->group(function () {

    // Design Save
    Route::post('/settings/save-design', [SettingsController::class, 'saveDesign'])->name('settings.save-design');
    Route::post('/settings/upload-frame', [SettingsController::class, 'uploadFrame'])->name('settings.upload-frame');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Website Management
    Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
    Route::post('/websites', [WebsiteController::class, 'store'])->name('websites.store');
    Route::put('/websites/{id}', [WebsiteController::class, 'update'])->name('websites.update');
    Route::get('/websites/{id}/scrape', [WebsiteController::class, 'scrape'])->name('websites.scrape');

    // News & Studio
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/check-status', [NewsController::class, 'checkAutoPostStatus'])->name('news.check-status');
    Route::get('/proxy-image', [NewsController::class, 'proxyImage'])->name('proxy.image');
    Route::get('/news/{id}/studio', [NewsController::class, 'studio'])->name('news.studio');

    // WordPress Posting
    Route::post('/news/{id}/post', [NewsController::class, 'postToWordPress'])->name('news.post');
    Route::post('/news/toggle-automation', [NewsController::class, 'toggleAutomation'])->name('news.toggle-automation');
    Route::post('/news/{id}/queue', [NewsController::class, 'toggleQueue'])->name('news.queue');

    // 🔥 AI Queue / Draft Flow
    Route::post('/news/{id}/process-ai', [NewsController::class, 'sendToAiQueue'])->name('news.process-ai'); // 1. Start AI Job
    Route::get('/news/drafts', [NewsController::class, 'drafts'])->name('news.drafts');                      // View All Drafts
    Route::get('/news/{id}/get-draft', [NewsController::class, 'getDraftContent'])->name('news.get-draft'); // Fetch Draft Content
    Route::post('/news/{id}/publish-draft', [NewsController::class, 'publishDraft'])->name('news.publish-draft'); // Publish Draft
    Route::post('/news/{id}/generate-rewrite', [NewsController::class, 'generateRewrite'])->name('news.generate-rewrite'); // Preview Rewrite
    Route::post('/news/{id}/confirm-publish', [NewsController::class, 'confirmPublish'])->name('news.confirm-publish'); // Final Publish

    // User Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');
    Route::get('/settings/fetch-categories', [SettingsController::class, 'fetchCategories'])->name('settings.fetch-categories');
	Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.update-profile');
	Route::post('/settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('settings.upload-logo');


    // Credit History
    Route::get('/credits', [SettingsController::class, 'credits'])->name('credits.index');

    // Payment Routes (User)
    Route::get('/buy-credits', [PaymentController::class, 'create'])->name('payment.create');
    Route::post('/buy-credits', [PaymentController::class, 'store'])->name('payment.store');
});

// --- Admin Routes ---
Route::middleware(['auth', AdminMiddleware::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // User Management
    Route::post('/users/{id}/toggle-status', [AdminController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/users/{id}/add-credits', [AdminController::class, 'addCredits'])->name('users.credits');
    Route::post('/users/{id}/templates', [AdminController::class, 'updateTemplates'])->name('users.templates');
    Route::post('/users/{id}/limit', [AdminController::class, 'updateLimit'])->name('users.limit');
    Route::post('/users/{id}/websites', [AdminController::class, 'updateWebsiteAccess'])->name('users.websites');
    Route::post('/users/{id}/scraper', [AdminController::class, 'updateScraperSettings'])->name('users.scraper');
	Route::post('/users/create', [AdminController::class, 'store'])->name('users.store');
	Route::put('/users/{id}/update', [AdminController::class, 'updateUser'])->name('users.update');

    // Payment Management (Admin)
    Route::get('/payments', [PaymentController::class, 'adminIndex'])->name('payments.index');
    Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
});
