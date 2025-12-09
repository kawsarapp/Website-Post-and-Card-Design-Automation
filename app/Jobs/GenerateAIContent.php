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

    public $tries = 2;        
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
            $desc = $news->description ?? $news->summary ?? '';
            $body = $news->content ?? '';

            $cleanBody = trim(strip_tags($body));
            $cleanDesc = trim(strip_tags($desc));

            $fullContext = "Headline: " . $title . "\n\n";
            
            if (!empty($cleanDesc)) {
                $fullContext .= "Summary/Intro: " . $cleanDesc . "\n\n";
            }
            
            if (!empty($cleanBody)) {
                $fullContext .= "Details: " . $cleanBody;
            } else {
                $fullContext .= "Details: (Full body missing, please verify facts and write a complete report based on the Headline and Summary provided above)";
            }

            // AI কল করা
            $aiResponse = $aiWriter->rewrite($fullContext, $title);

            if (empty($aiResponse) || empty($aiResponse['content'])) {
                throw new \Exception("AI Service returned empty content.");
            }

            $news->update([
                'ai_title' => $aiResponse['title'] ?? $news->title,
                'ai_content' => $aiResponse['content'],
                'status' => 'draft',
                'is_rewritten' => true,
                'error_message' => null 
            ]);

            Log::info("✅ AI Job Completed. ID: {$this->newsId}");

            // 🔥 Notification Logic (Updated for UTF-8 Safety)
            $user = \App\Models\User::find($this->userId);
            if ($user) {
                // ১. টাইটেল নির্ধারণ
                $rawTitle = $news->ai_title ?? $news->title;
                
                // ২. বাংলা ক্যারেক্টার যাতে ভেঙে না যায়, তাই এনকোডিং ফিক্স করা
                $safeTitle = mb_convert_encoding($rawTitle, 'UTF-8', 'UTF-8');

                // ৩. নোটিফিকেশন পাঠানো
                $user->notify(new \App\Notifications\AIRewriteCompletedNotification($safeTitle, $news->id));
            }

        } catch (\Exception $e) {
            Log::error("🔥 AI Job Exception for ID {$this->newsId}: " . $e->getMessage());
            $this->fail($e); 
        }
    }

    public function failed(\Throwable $exception)
    {
        $news = NewsItem::withoutGlobalScopes()->find($this->newsId);
        
        if ($news) {
            $news->update([
                'status' => 'failed',
                'error_message' => 'AI Error: ' . $exception->getMessage()
            ]);
            
            Log::error("❌ AI Job Officially Failed for News ID: {$this->newsId}. Error saved to DB.");
        }
    }
}