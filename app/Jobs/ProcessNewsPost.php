<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Models\User;
use App\Services\WordPressService;
use App\Notifications\PostPublishedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; 
use App\Services\SocialPostService;
use App\Services\NewsCardGeneratorService;

class ProcessNewsPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsId;
    protected $userId;
    protected $customData;
    protected $skipCreditDeduction;

    public $tries = 3; 
    public $backoff = 60; 

    public function __construct($newsId, $userId, $customData = [], $skipCreditDeduction = false)
    {
        $this->newsId = $newsId;
        $this->userId = $userId;
        $this->customData = $customData;
        $this->skipCreditDeduction = $skipCreditDeduction;
    }

    public function handle(
        WordPressService $wpService, 
        SocialPostService $socialPoster, 
        NewsCardGeneratorService $cardGenerator
    ) {
        try {
            Log::info("🚀 Publishing Job Started for News ID: {$this->newsId}");

            $news = NewsItem::withoutGlobalScopes()
                ->with(['website' => function ($query) {
                    $query->withoutGlobalScopes(); 
                }])->find($this->newsId);

            $user = User::find($this->userId);

            if (!$news || !$user) {
                Log::error("Job Failed: News or User not found. ID: {$this->newsId}");
                return;
            }

            $settings = $user->settings;

            // ডাটা প্রিপারেশন
            $finalTitle = $this->customData['title'] ?? $news->ai_title ?? $news->title;
            $finalContent = $this->customData['content'] ?? $news->ai_content ?? $news->content;
            
            // ইমেজ সেপারেশন
            $websiteImage = $this->customData['website_image'] ?? $news->thumbnail_url;
            $socialImage = $this->customData['social_image'] ?? $websiteImage;
            
            // ফ্ল্যাগ চেক
            $socialOnly = $this->customData['social_only'] ?? false;
            $skipSocial = $this->customData['skip_social'] ?? false;
            
            if ($socialOnly) Log::info("🚀 Social Only Mode Activated. Skipping Website Posting.");
            if ($skipSocial) Log::info("⏭️ Manual Publish Mode. Skipping Social Posting for now.");

            $categories = $this->customData['category_ids'] ?? [1];
            
            // OG ক্লিনআপ
            if (!empty($websiteImage) && strpos($websiteImage, '/og/') !== false) {
                $websiteImage = str_replace('/og/', '/', $websiteImage);
            }

            $wpSuccess = false;
            $laravelSuccess = false;
            $wpPostId = null; // এটি আমরা রিমোট আইডি (WP বা Laravel) রাখার জন্য ব্যবহার করব

            // ==========================================
            // ১. ওয়ার্ডপ্রেস পোস্টিং
            // ==========================================
            if (!$socialOnly && $settings && $settings->wp_url && $settings->wp_username) {
                $postResult = $wpService->createPost(
                    $news, $user, $finalTitle, $finalContent, $categories, $websiteImage
                );

                if ($postResult['success']) {
                    $wpSuccess = true;
                    $wpPostId = $postResult['post_id'];
                    Log::info("✅ WP Post Success: ID {$wpPostId}");
                } else {
                    $errorMsg = $postResult['message'] ?? 'Unknown WP Error';
                    Log::error("❌ WP Post Failed: " . $errorMsg);
                    if (!$settings->post_to_laravel) throw new \Exception("WP Failed: " . $errorMsg);
                }
            }

            // ==========================================
            // ২. লারাভেল API পোস্টিং (আপডেটেড)
            // ==========================================
            if (!$socialOnly && $settings && $settings->post_to_laravel && $settings->laravel_site_url) {
                try {
                    $apiUrl = rtrim($settings->laravel_site_url, '/') . '/api/external-news-post';
                    $response = Http::post($apiUrl, [
                        'token' => $settings->laravel_api_token,
                        'title' => $finalTitle,
                        'content' => $finalContent,
                        'image_url' => $websiteImage,
                        'category_name' => $news->category ?? 'General',
                        'category_ids' => $categories, 
                        'original_link' => $news->original_link
                    ]);

                    if ($response->successful()) {
                        $laravelSuccess = true;
                        
                        // 🔥🔥 FIX: লারাভেল থেকে রিটার্ন করা ID ক্যাপচার করা
                        $respData = $response->json();
                        // রেসপন্সে 'id' বা 'post_id' ফিল্ড খুঁজছি
                        $remoteLaravelId = $respData['id'] ?? $respData['post_id'] ?? null;
                        
                        if ($remoteLaravelId) {
                            $wpPostId = $remoteLaravelId; // আমরা wp_post_id কলামেই লারাভেল আইডি রাখছি
                            Log::info("✅ Laravel Post Success. Remote ID: {$remoteLaravelId}");
                        } else {
                            Log::info("✅ Laravel Post Success (No ID returned).");
                        }

                    } else {
                        Log::error("❌ Laravel Post Failed: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Laravel Connection Error: " . $e->getMessage());
                }
            }

            // ==========================================
            // ৩. ফাইনাল আপডেট (DB Save)
            // ==========================================
            if ($wpSuccess || $laravelSuccess || $socialOnly) {

                DB::transaction(function () use ($news, $user, $wpPostId, $websiteImage, $socialOnly) {
                    $updateData = [
                        'is_posted' => true,
                        'posted_at' => now(),
                        'status' => 'published',
                        'error_message' => null
                    ];

                    // 🔥 রিমোট আইডি সেভ করা (WP বা Laravel ID)
                    if ($wpPostId) {
                        $updateData['wp_post_id'] = $wpPostId;
                    }

                    if (!$socialOnly) {
                        $updateData['thumbnail_url'] = $websiteImage;
                    }

                    $news->update($updateData);

                    // ক্রেডিট ডিডাকশন
                    if (!$this->skipCreditDeduction && $user->role !== 'super_admin') {
                        if ($user->credits > 0) {
                            $user->decrement('credits');
                            \App\Models\CreditHistory::create([
                                'user_id' => $user->id,
                                'action_type' => 'auto_post',
                                'description' => 'Auto Published via Job',
                                'credits_change' => -1,
                                'balance_after' => $user->credits
                            ]);
                        }
                    }
                });

                // ==========================================
                // 🔥🔥 NEW: SOCIAL POSTING LOGIC
                // ==========================================
                
                if (!$skipSocial && ($settings->post_to_fb || $settings->post_to_telegram)) {
                    
                    $imageToPost = $socialImage; 
                    $localCardPath = null;

                    // ১. ইমেজ প্রসেসিং
                    if (!isset($this->customData['social_image'])) {
                         Log::info("🎨 Generating Auto News Card...");
                         $localCardPath = $cardGenerator->generate($news, $settings);
                         if ($localCardPath) $imageToPost = $localCardPath;
                    } else {
                        Log::info("✨ Using Studio Designed Image for Social Media.");
                        // পাথ ফাইন্ডার লজিক (যা আগে ফিক্স করা হয়েছিল)
                        $originalUrl = $imageToPost;
                        $foundLocal = false;
                        $appUrl = config('app.url');

                        if (strpos($imageToPost, $appUrl) !== false) {
                            $relativePath = str_replace($appUrl, '', $imageToPost);
                            $relativePath = ltrim(strtok($relativePath, '?'), '/');
                            $checkPath = public_path($relativePath);
                            if (file_exists($checkPath)) { $imageToPost = $checkPath; $foundLocal = true; }
                        }
                        if (!$foundLocal && strpos($originalUrl, '/storage/') !== false) {
                            $parts = explode('/storage/', $originalUrl);
                            if (count($parts) > 1) {
                                $checkPath = storage_path('app/public/' . strtok($parts[1], '?'));
                                if (file_exists($checkPath)) { $imageToPost = $checkPath; $foundLocal = true; }
                            }
                        }
                        if ($foundLocal) Log::info("✅ Local Path Found: $imageToPost");
                    }
                    
                    // ==========================================
                    // 🔗 INTELLIGENT LINK GENERATION (FIXED URL STRUCTURE)
                    // ==========================================
                    
                    $newsLink = $news->original_link; 

                    if ($settings->wp_url && ($wpSuccess || $news->wp_post_id)) {
                        $idToUse = $wpPostId ?? $news->wp_post_id;
                        $newsLink = rtrim($settings->wp_url, '/') . '/?p=' . $idToUse;
                    } 
                    
                    elseif ($settings->post_to_laravel && $settings->laravel_site_url) {
                         if ($laravelSuccess || $news->is_posted) {
                             $idToUse = $wpPostId ?? $news->wp_post_id ?? $news->id;
                             $prefix = $settings->laravel_route_prefix ?? 'news';
                             $prefix = trim($prefix, '/'); 
                             $checkLink = rtrim($settings->laravel_site_url, '/') . '/' . $prefix . '/' . $idToUse;
                             $newsLink = $checkLink;
                             
                             Log::info("🔗 Using Laravel Link ($prefix): $newsLink");
                         }
                    }

                    // ==========================================
                    // 🔥🔥 NEW: SOCIAL CAPTION LOGIC
                    // ==========================================
                    // স্টুডিও থেকে পাঠানো ক্যাপশন থাকলে সেটা নিবে, নাহলে টাইটেল
                    $captionToPost = $this->customData['social_caption'] ?? $finalTitle;

                    if ($settings->post_to_fb) {
                        $socialPoster->postToFacebook($settings, $captionToPost, $imageToPost, $newsLink);
                    }
                    if ($settings->post_to_telegram) {
                        $socialPoster->postToTelegram($settings, $captionToPost, $imageToPost, $newsLink);
                    }

                    // ক্লিনআপ
                    if ($localCardPath && file_exists($localCardPath)) {
                       unlink($localCardPath);
                       Log::info("🧹 Generated card deleted to save space.");
                    }
                    
                    if (isset($this->customData['social_image'])) {
                         $studioImgPath = $imageToPost;

                         if (file_exists($studioImgPath) && strpos($studioImgPath, 'news-cards/studio') !== false) {
                             unlink($studioImgPath);
                             Log::info("🧹 Studio Card deleted from server to save space.");
                         }
                    }
                } 
                else {
                    if ($skipSocial) Log::info("⏭️ Social Posting Skipped (Manual Publish Mode).");
                }

                try {
                    $user->notify(new PostPublishedNotification($finalTitle));
                } catch (\Exception $e) {}

            } else {
                if (!$settings->wp_url && !$settings->post_to_laravel) {
                    throw new \Exception("Settings Error: No WP or Laravel destination configured.");
                } else {
                    throw new \Exception("Posting failed on all configured endpoints.");
                }
            }

        } catch (\Exception $e) {
            Log::error("ProcessNewsPost Job Exception: " . $e->getMessage());
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        if ($news) {
            $news->update([
                'status' => 'failed',
                'error_message' => 'Publish Error: ' . $exception->getMessage() 
            ]);
            Log::error("❌ Job Final Failure for News ID: {$this->newsId}");
        }
    }
}