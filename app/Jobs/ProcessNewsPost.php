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
            Log::info("🚀 Publishing/Updating Job Started for News ID: {$this->newsId}");

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
            
            $websiteImage = $this->customData['website_image'] ?? $news->thumbnail_url;
            $socialImage = $this->customData['social_image'] ?? $websiteImage;
            
            $socialOnly = $this->customData['social_only'] ?? false;
            $skipSocial = $this->customData['skip_social'] ?? false;
            
            if ($socialOnly) Log::info("🚀 Social Only Mode Activated. Skipping Website Posting.");
            if ($skipSocial) Log::info("⏭️ Manual Publish Mode. Skipping Social Posting for now.");

            $categories = $this->customData['category_ids'] ?? [1];
            
            if (!empty($websiteImage) && strpos($websiteImage, '/og/') !== false) {
                $websiteImage = str_replace('/og/', '/', $websiteImage);
            }

            $wpSuccess = false;
            $laravelSuccess = false;
            $remotePostId = $news->wp_post_id; 
            
            // ডিফল্ট লিংক (যদি পোস্ট ফেইল করে তবে সোর্স লিংক থাকবে)
            $publishedUrl = $news->live_url; 

            // ==========================================
            // ১. ওয়ার্ডপ্রেস পোস্টিং
            // ==========================================
            if (!$socialOnly && $settings && $settings->wp_url && $settings->wp_username) {
                if ($news->wp_post_id) {
                    Log::info("🔄 Updating existing WordPress post: ID {$news->wp_post_id}");
                    $postResult = $wpService->updatePost(
                        $news->wp_post_id, $news, $user, $finalTitle, $finalContent, $categories, $websiteImage
                    );
                } else {
                    Log::info("🆕 Creating new WordPress post");
                    $postResult = $wpService->createPost(
                        $news, $user, $finalTitle, $finalContent, $categories, $websiteImage
                    );
                }

                if ($postResult['success']) {
                    $wpSuccess = true;
                    $remotePostId = $postResult['post_id'];
                    $publishedUrl = $postResult['link'] ?? $publishedUrl; // WP লিংক সেট
                    Log::info("✅ WP Action Success: ID {$remotePostId} | Link: {$publishedUrl}");
                } else {
                    $errorMsg = $postResult['message'] ?? 'Unknown WP Error';
                    Log::error("❌ WP Action Failed: " . $errorMsg);
                    if (!$settings->post_to_laravel) throw new \Exception("WP Failed: " . $errorMsg);
                }
            }

            // ==========================================
            // ২. লারাভেল / নোড / এপিআই পোস্টিং (Fixed Logic)
            // ==========================================
            if (!$socialOnly && $settings && $settings->post_to_laravel && $settings->laravel_site_url) {
                try {
                    $apiUrl = rtrim($settings->laravel_site_url, '/') . '/api/external-news-post';
                    
                    $payload = [
                        'token' => $settings->laravel_api_token,
                        'title' => $finalTitle,
                        'content' => $finalContent,
                        'image_url' => $websiteImage,
                        'category_name' => $news->category ?? 'General',
                        'category_ids' => $categories, 
                        'original_link' => $news->original_link
                    ];

                    if ($news->wp_post_id) {
                        $payload['remote_id'] = $news->wp_post_id;
                        Log::info("🔄 Sending Update Request to API for ID: {$news->wp_post_id}");
                    }

                    $response = Http::post($apiUrl, $payload);

                    if ($response->successful()) {
                        $laravelSuccess = true;
                        $respData = $response->json();
                        
                        $remotePostId = $respData['post_id'] ?? $respData['id'] ?? $remotePostId;
                        
                        // 🔥🔥 FIX: API থেকে আসা 'live_url' বা 'link' বা 'url' চেক করা হচ্ছে
                        if (!empty($respData['live_url'])) {
                            $publishedUrl = $respData['live_url'];
                        } elseif (!empty($respData['link'])) {
                            $publishedUrl = $respData['link'];
                        } elseif (!empty($respData['url'])) {
                            $publishedUrl = $respData['url'];
                        } else {
                            // যদি API লিংক না দেয়, তবে ম্যানুয়ালি তৈরি করা হবে
                            $prefix = trim($settings->laravel_route_prefix ?? 'news', '/');
                            $publishedUrl = rtrim($settings->laravel_site_url, '/') . '/' . $prefix . '/' . $remotePostId;
                        }
                        
                        Log::info("✅ API Action Success. Remote ID: {$remotePostId} | Link: {$publishedUrl}");
                    } else {
                        Log::error("❌ API Action Failed: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("❌ API Connection Error: " . $e->getMessage());
                }
            }

            // ==========================================
            // ৩. ফাইনাল আপডেট & সোশ্যাল পোস্টিং
            // ==========================================
            if ($wpSuccess || $laravelSuccess || $socialOnly) {

                DB::transaction(function () use ($news, $user, $remotePostId, $publishedUrl, $websiteImage, $socialOnly) {
                    $updateData = [
                        'is_posted' => true,
                        'posted_at' => now(),
                        'status' => 'published',
                        'live_url' => $publishedUrl, // ডাটাবেসে সঠিক লিংক সেভ হবে
                        'error_message' => null
                    ];

                    if ($remotePostId) $updateData['wp_post_id'] = $remotePostId;
                    if (!$socialOnly) $updateData['thumbnail_url'] = $websiteImage;

                    $news->update($updateData);

                    // ক্রেডিট লজিক
                    if (!$this->skipCreditDeduction && $user->role !== 'super_admin') {
                        if ($user->credits > 0) {
                            $user->decrement('credits');
                            \App\Models\CreditHistory::create([
                                'user_id' => $user->id,
                                'action_type' => 'auto_post',
                                'description' => 'Published/Updated via Job',
                                'credits_change' => -1,
                                'balance_after' => $user->credits
                            ]);
                        }
                    }
                });

                // ==========================================
                // 🔥 ফিক্সড সোশ্যাল মিডিয়া পোস্টিং
                // ==========================================
                
                if (!$skipSocial && ($settings->post_to_fb || $settings->post_to_telegram)) {
                    
                    $imageToPost = $socialImage; 
                    $localCardPath = null;

                    if (!isset($this->customData['social_image'])) {
                         Log::info("🎨 Generating Auto News Card...");
                         $localCardPath = $cardGenerator->generate($news, $settings);
                         if ($localCardPath) $imageToPost = $localCardPath;
                    } else {
                        Log::info("✨ Using Studio Designed Image.");
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
                    }
                    
                    // 🔥 LINK SELECTION LOGIC 🔥
                    $newsLink = $publishedUrl; // এখানে এখন সঠিক লিংক থাকার কথা

                    // যদি কোনো কারণে লিংক না থাকে, তবে ম্যানুয়াল ফলব্যাক
                    if (empty($newsLink) && $remotePostId) {
                        if ($settings->wp_url) {
                            $newsLink = rtrim($settings->wp_url, '/') . '/?p=' . $remotePostId;
                        } elseif ($settings->laravel_site_url) {
                             $prefix = trim($settings->laravel_route_prefix ?? 'news', '/');
                             $newsLink = rtrim($settings->laravel_site_url, '/') . '/' . $prefix . '/' . $remotePostId;
                        }
                    }
                    
                    // যদি তাও না থাকে, তবে সোর্স লিংক (কাস্টম সাইটের ক্ষেত্রে এটা এড়ানো উচিত)
                    if (empty($newsLink)) {
                        $newsLink = $news->original_link;
                    }

                    $captionToPost = $this->customData['social_caption'] ?? $finalTitle;

                    if ($settings->post_to_fb) {
                        $fbResult = $socialPoster->postToFacebook($settings, $captionToPost, $imageToPost, $newsLink);
                        $news->update(['fb_status' => $fbResult['success'] ? 'success' : 'failed', 'fb_error' => $fbResult['message'] ?? null]);
                    }
                    if ($settings->post_to_telegram) {
                        $tgResult = $socialPoster->postToTelegram($settings, $captionToPost, $imageToPost, $newsLink);
                        $news->update(['tg_status' => $tgResult['success'] ? 'success' : 'failed', 'tg_error' => $tgResult['message'] ?? null]);
                    }

                    if ($localCardPath && file_exists($localCardPath)) unlink($localCardPath);
                    if (isset($this->customData['social_image']) && file_exists($imageToPost) && strpos($imageToPost, 'news-cards/studio') !== false) unlink($imageToPost);
                }

                try {
                    $user->notify(new PostPublishedNotification($finalTitle));
                } catch (\Exception $e) {}

            } else {
                throw new \Exception("Posting failed on all configured endpoints.");
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
                'error_message' => 'Action Error: ' . $exception->getMessage() 
            ]);
            Log::error("❌ Job Final Failure for News ID: {$this->newsId}");
        }
    }
}