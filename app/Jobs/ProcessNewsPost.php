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

            // ==========================================
            // 🔥 DATA PREPARATION
            // ==========================================
            $finalTitle   = $this->customData['title']   ?? $news->ai_title   ?? $news->title;
            $finalContent = $this->customData['content']  ?? $news->ai_content ?? $news->content;
            
            $websiteImage = $this->customData['website_image'] 
                            ?? $this->customData['featured_image'] 
                            ?? $news->thumbnail_url;

            $socialImage = $this->customData['social_image'] ?? $websiteImage;
            $hashtags    = $this->customData['hashtags'] ?? $news->hashtags ?? '';

            $socialOnly = $this->customData['social_only'] ?? false;
            $skipSocial = $this->customData['skip_social'] ?? false;
            
            if ($socialOnly) Log::info("🚀 Social Only Mode Activated. Skipping Website Posting.");
            if ($skipSocial) Log::info("⏭️ Manual Publish Mode. Skipping Social Posting for now.");

            // ==========================================
            // 🔥 ক্যাটাগরি — সব ধরনের input handle করে
            // category_ids (array) অথবা category_id (single) যেকোনোটাই কাজ করবে
            // ==========================================
            $categories = $this->customData['category_ids'] 
                          ?? (isset($this->customData['category_id']) ? [$this->customData['category_id']] : [1]);
            
            if (!is_array($categories)) {
                $categories = [$categories];
            }
            $categories = array_values(array_filter(array_unique(array_map('intval', $categories))));
            if (empty($categories)) {
                $categories = [1];
            }
            
            if (!empty($websiteImage) && strpos($websiteImage, '/og/') !== false) {
                $websiteImage = str_replace('/og/', '/', $websiteImage);
            }

            $wpSuccess     = false;
            $laravelSuccess = false;
            $remotePostId  = $news->wp_post_id; 
            $publishedUrl  = $news->live_url; 

            // ==========================================
            // ১. ওয়ার্ডপ্রেস পোস্টিং 
            // ==========================================
            if (!$socialOnly && $settings && $settings->wp_url && $settings->wp_username) {
                if ($news->wp_post_id) {
                    Log::info("🔄 Updating existing WordPress post: ID {$news->wp_post_id}");
                    $postResult = $wpService->updatePost(
                        $news->wp_post_id, $news, $user, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags
                    );
                } else {
                    Log::info("🆕 Creating new WordPress post");
                    $postResult = $wpService->createPost(
                        $news, $user, $finalTitle, $finalContent, $categories, $websiteImage, $hashtags
                    );
                }

                if ($postResult['success']) {
                    $wpSuccess    = true;
                    $remotePostId = $postResult['post_id'];
                    $publishedUrl = $postResult['link'] ?? $publishedUrl;
                    Log::info("✅ WP Action Success: ID {$remotePostId} | Link: {$publishedUrl}");
                } else {
                    $errorMsg = $postResult['message'] ?? 'Unknown WP Error';
                    Log::error("❌ WP Action Failed: " . $errorMsg);
                    if (!$settings->post_to_laravel) throw new \Exception("WP Failed: " . $errorMsg);
                }
            }

            // ==========================================
            // ২. লারাভেল / নোড / এপিআই পোস্টিং
            // ==========================================
            if (!$socialOnly && $settings && ($settings->post_to_laravel || !empty($settings->custom_api_url)) && $settings->laravel_site_url) {
                try {
                    $baseUrl  = rtrim($settings->laravel_site_url, '/');
                    $response = null;

                    // ============================================================
                    // 🟢 CUSTOM API LOGIC — Guzzle Multipart (bulletproof)
                    // ============================================================
                    if (!empty($settings->custom_api_url) && !empty($settings->custom_api_mapping)) {
                        $apiUrl  = $settings->custom_api_url;
                        $mapping = json_decode($settings->custom_api_mapping, true);

                        Log::info("🟢 Sending Dynamic Custom Request to: " . $apiUrl);

                        // Guzzle multipart array — এই format-এ পাঠালে
                        // "A 'contents' key is required" error আসবেই না
                        $multipart = [];

                        $addPart = function(string $name, $val) use (&$multipart) {
                            $multipart[] = [
                                'name'     => $name,
                                'contents' => (string) ($val ?? '')
                            ];
                        };

                        // ── সাধারণ text fields ─────────────────────────────────
                        if (isset($mapping['title']))   $addPart($mapping['title'],   $finalTitle);
                        if (isset($mapping['content'])) $addPart($mapping['content'], $finalContent);
                        if (isset($mapping['tags']))    $addPart($mapping['tags'],    $hashtags);
                        if (isset($mapping['token']))   $addPart($mapping['token'],   $settings->laravel_api_token);
                        if (isset($mapping['date']))    $addPart($mapping['date'],    now()->format('Y-m-d'));

                        // ── extra static fields (approved_status, priority ইত্যাদি) ──
                        if (isset($mapping['extra']) && is_array($mapping['extra'])) {
                            foreach ($mapping['extra'] as $key => $val) {
                                $addPart((string) $key, $val);
                            }
                        }

                        // ── category array পাঠানো: news_category[] => 4 ────────
                        if (isset($mapping['category'])) {
                            $catKey = str_replace('[]', '', $mapping['category']);
                            foreach ($categories as $cat) {
                                $multipart[] = [
                                    'name'     => $catKey . '[]',
                                    'contents' => (string) $cat
                                ];
                            }
                        }

                        // ── image file upload ───────────────────────────────────
                        if (isset($mapping['image']) && !empty($websiteImage)) {
                            try {
                                $imgResponse = Http::timeout(15)->get($websiteImage);
                                if ($imgResponse->successful()) {
                                    $multipart[] = [
                                        'name'     => (string) $mapping['image'],
                                        'contents' => $imgResponse->body(),
                                        'filename' => basename(parse_url($websiteImage, PHP_URL_PATH)) ?: 'news_image.jpg'
                                    ];
                                    Log::info("🖼️ Image attached: " . basename(parse_url($websiteImage, PHP_URL_PATH)));
                                } else {
                                    Log::warning("⚠️ Image fetch returned non-200: " . $imgResponse->status());
                                }
                            } catch (\Exception $e) {
                                Log::warning("⚠️ Image Fetch Failed: " . $e->getMessage());
                            }
                        }

                        // debug log — কী পাঠাচ্ছি
                        $debugPayload = collect($multipart)->mapWithKeys(function($p) {
                            return [$p['name'] => isset($p['filename']) ? "[FILE: {$p['filename']}]" : $p['contents']];
                        })->toArray();
                        //Log::info("📤 [DEBUG] Sending to API: ", $debugPayload);
                        Log::info("📤 [DEBUG] Sending to API: " . json_encode(
                            collect($multipart)->map(fn($p) => [
                                'name' => $p['name'],
                                'value' => isset($p['filename']) ? "[FILE: {$p['filename']}]" : $p['contents']
                            ])->toArray()
                        ));

                        // ── Guzzle দিয়ে সরাসরি পাঠানো ─────────────────────────
                        $client = new \GuzzleHttp\Client(['timeout' => 30, 'verify' => false]);
                        $guzzleResponse = $client->post($apiUrl, [
                            'multipart'   => $multipart,
                            'headers'     => ['Accept' => 'application/json'],
                            'http_errors' => false // 4xx/5xx তেও exception হবে না
                        ]);

                        $responseBody = $guzzleResponse->getBody()->getContents();
                        $statusCode   = $guzzleResponse->getStatusCode();
                        Log::info("🔍 Custom API Response (HTTP {$statusCode}): " . $responseBody);

                        if ($statusCode >= 200 && $statusCode < 300) {
                            $laravelSuccess = true;
                            $respData = json_decode($responseBody, true) ?? [];

                            $idKey        = $mapping['response_id_key'] ?? 'post_id';
                            $remotePostId = $respData[$idKey] ?? $respData['id'] ?? $respData['news_id'] ?? $remotePostId;

                            if (!empty($respData['live_url'])) {
                                $publishedUrl = $respData['live_url'];
                            } elseif (!empty($respData['link'])) {
                                $publishedUrl = $respData['link'];
                            } elseif (!empty($respData['url'])) {
                                $publishedUrl = $respData['url'];
                            } else {
                                $siteBase     = rtrim($settings->laravel_site_url, '/');
                                $prefix       = trim($settings->laravel_route_prefix ?? 'news', '/');
                                $publishedUrl = $siteBase . '/' . $prefix . '/' . $remotePostId;
                            }

                            Log::info("✅ Custom API Success. Remote ID: {$remotePostId} | Link: {$publishedUrl}");
                        } else {
                            Log::error("❌ Custom API Failed (HTTP {$statusCode}): " . $responseBody);
                        }

                    }
                    // ============================================================
                    // 🔵 DEFAULT UNIVERSAL API LOGIC
                    // ============================================================
                    else {
                        $apiUrl  = $baseUrl . '/api/external-news-post';
                        $payload = [
                            'token'         => $settings->laravel_api_token,
                            'title'         => $finalTitle,
                            'content'       => $finalContent,
                            'image_url'     => $websiteImage, 
                            'hashtags'      => $hashtags,     
                            'category_name' => $news->category ?? 'General',
                            'category_ids'  => $categories, 
                            'original_link' => $news->original_link
                        ];

                        if ($news->wp_post_id) {
                            $payload['remote_id'] = $news->wp_post_id;
                            Log::info("🔄 Sending Update Request to API for ID: {$news->wp_post_id}");
                        }

                        $response = Http::post($apiUrl, $payload);

                        if ($response && $response->successful()) {
                            $laravelSuccess = true;
                            $respData       = $response->json();
                            $remotePostId   = $respData['post_id'] ?? $respData['id'] ?? $remotePostId;
                            $siteBase       = rtrim($settings->laravel_site_url, '/');
                            $prefix         = trim($settings->laravel_route_prefix ?? 'news', '/');
                            $publishedUrl   = $respData['live_url'] ?? $respData['link'] ?? $respData['url'] ?? ($siteBase . '/' . $prefix . '/' . $remotePostId);
                            Log::info("✅ Default API Success. Remote ID: {$remotePostId} | Link: {$publishedUrl}");
                        } else {
                            Log::error("❌ Default API Failed: " . ($response ? $response->body() : 'No Response'));
                        }
                    }

                } catch (\Exception $e) {
                    Log::error("❌ API Connection Error: " . $e->getMessage());
                }
            }

            // ==========================================
            // ৩. ফাইনাল আপডেট & সোশ্যাল পোস্টিং
            // ==========================================
            if ($wpSuccess || $laravelSuccess || $socialOnly) {

                DB::transaction(function () use ($news, $user, $remotePostId, $publishedUrl, $websiteImage, $socialOnly, $hashtags) {
                    $updateData = [
                        'is_posted'     => true,
                        'posted_at'     => now(),
                        'status'        => 'published',
                        'live_url'      => $publishedUrl,
                        'error_message' => null,
                        'hashtags'      => $hashtags 
                    ];

                    if ($remotePostId) $updateData['wp_post_id'] = $remotePostId;
                    if (!$socialOnly)  $updateData['thumbnail_url'] = $websiteImage;

                    $news->update($updateData);

                    if (!$this->skipCreditDeduction && $user->role !== 'super_admin') {
                        if ($user->credits > 0) {
                            $user->decrement('credits');
                            \App\Models\CreditHistory::create([
                                'user_id'        => $user->id,
                                'action_type'    => 'auto_post',
                                'description'    => 'Published/Updated via Job',
                                'credits_change' => -1,
                                'balance_after'  => $user->credits
                            ]);
                        }
                    }
                });

                if (!$skipSocial && ($settings->post_to_fb || $settings->post_to_telegram)) {
                    
                    $imageToPost  = $socialImage; 
                    $localCardPath = null;

                    if (!isset($this->customData['social_image'])) {
                        Log::info("🎨 Generating Auto News Card...");
                        $localCardPath = $cardGenerator->generate($news, $settings);
                        if ($localCardPath) $imageToPost = $localCardPath;
                    } else {
                        Log::info("✨ Using Studio Designed Image.");
                        $originalUrl = $imageToPost;
                        $foundLocal  = false;
                        $appUrl      = config('app.url');
                        if (strpos($imageToPost, $appUrl) !== false) {
                            $relativePath = str_replace($appUrl, '', $imageToPost);
                            $relativePath = ltrim(strtok($relativePath, '?'), '/');
                            $checkPath    = public_path($relativePath);
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
                    
                    $newsLink     = $publishedUrl ?: $news->original_link;
                    $captionToPost = $this->customData['social_caption'] ?? $finalTitle;
                    if (!empty($hashtags)) {
                        $captionToPost .= "\n\n" . $hashtags;
                    }

                    if ($settings->post_to_fb) {
                        $fbResult = $socialPoster->postToFacebook($settings, $captionToPost, $imageToPost, $newsLink);
                        $news->update(['fb_status' => $fbResult['success'] ? 'success' : 'failed', 'fb_error' => $fbResult['message'] ?? null]);
                    }
                    if ($settings->post_to_telegram) {
                        $tgResult = $socialPoster->postToTelegram($settings, $captionToPost, $imageToPost, $newsLink);
                        $news->update(['tg_status' => $tgResult['success'] ? 'success' : 'failed', 'tg_error' => $tgResult['message'] ?? null]);
                    }

                    if ($localCardPath && file_exists($localCardPath)) unlink($localCardPath);
                    if (isset($this->customData['social_image']) && strpos($imageToPost, 'news-cards/studio') !== false) {
                        if (file_exists($imageToPost) && is_file($imageToPost)) {
                            unlink($imageToPost);
                        }
                    }
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
                'status'        => 'failed',
                'error_message' => 'Action Error: ' . $exception->getMessage() 
            ]);
        }
    }
}