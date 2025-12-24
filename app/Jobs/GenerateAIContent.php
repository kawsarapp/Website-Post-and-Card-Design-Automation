<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Services\AIWriterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Notifications\AIRewriteCompletedNotification;

class GenerateAIContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsId;
    protected $userId;

    public $tries = 1;
    public $timeout = 120;    

    public function __construct($newsId, $userId)
    {
        $this->newsId = $newsId;
        $this->userId = $userId;
    }

    public function handle(AIWriterService $aiWriter)
    {
        Log::info("🚀 AI Job Started for News ID: {$this->newsId}");

        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);

        if (!$news) {
            Log::error("❌ News not found ID: {$this->newsId}");
            return;
        }

        $news->update(['status' => 'processing']);

        try {
            $title = $news->title ?? '';
            $body = $news->content ?? '';

            $textOnly = strip_tags(str_replace(['</p>', '<br>', '</div>'], ["\n\n", "\n", "\n"], $body));
            $cleanBody = trim($textOnly);
            
            $fullContext = "Headline: " . $title . "\n\nDetails: " . $cleanBody;

            // 🔥 পরিবর্তন: চেক করা হচ্ছে নিউজটি আগে রি-রাইট করা হয়েছে কি না
            $isRetry = (bool) $news->is_rewritten;

            // 🔥 পরিবর্তন: rewrite মেথডে $isRetry প্যারামিটারটি পাস করা হচ্ছে
            $aiResponse = $aiWriter->rewrite($fullContext, $title, $isRetry);

            $news->update([
                'ai_title' => $aiResponse['title'] ?? $news->title,
                'ai_content' => $aiResponse['content'],
                'status' => 'draft',
                'is_rewritten' => true,
                'error_message' => null 
            ]);

            Log::info("✅ AI Job Completed. ID: {$this->newsId}");

            $user = User::find($this->userId);
            if ($user) {
                $safeTitle = mb_convert_encoding($news->ai_title, 'UTF-8', 'UTF-8');
                $user->notify(new AIRewriteCompletedNotification($safeTitle, $news->id));
            }

        } catch (\Exception $e) {
            
            $msg = $e->getMessage();
            $userMessage = "Rewrite Failed. Try Again."; // ডিফল্ট মেসেজ

            if ($msg === "SHORT_CONTENT") {
                $userMessage = "News too short. Please scrape again.";
            } 
            elseif ($msg === "API_KEY_MISSING" || $msg === "API_KEY_INVALID") {
                $userMessage = "System Error: API Key Missing/Invalid.";
            }
            elseif ($msg === "SERVER_BUSY" || $msg === "RATE_LIMIT_EXCEEDED") {
                $userMessage = "AI Server Busy. Please try again in 5 mins.";
            }

            elseif ($msg === "SERVER_ERROR") {
                $userMessage = "AI Provider Error. Try again later.";
            }
			
            elseif ($msg === "INSUFFICIENT_BALANCE") {
                $userMessage = "AI Credits Finished (Contact Admin).";
            }
			
            elseif ($msg === "ALL_AI_FAILED") {
                $userMessage = "All AI Services are currently down. Try again later.";
            }


            Log::error("🔥 AI Job Failed: $msg");

            $news->update([
                'status' => 'failed',
                'error_message' => $userMessage,
                'ai_content' => null
            ]);
            
            $this->fail($e);
        }
    }
}