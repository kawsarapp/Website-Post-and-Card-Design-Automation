<?php

namespace App\Jobs;

use App\Models\NewsItem;
use App\Models\User;
use App\Services\AIWriterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAIContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $newsId;
    protected $userId;

    public function __construct($newsId, $userId)
    {
        $this->newsId = $newsId;
        $this->userId = $userId;
    }

    public function handle(AIWriterService $aiWriter)
    {
        Log::info("🚀 AI Job Started for News ID: {$this->newsId}");

        $news = NewsItem::find($this->newsId);
        if (!$news) {
            Log::error("❌ News not found ID: {$this->newsId}");
            return;
        }

        $news->update(['status' => 'processing']);

        try {
            // ==================================================
            // 🔥 SMART CONTENT MERGING (যাতে নিউজ বড় হয়)
            // ==================================================
            
            // ১. সব সোর্স থেকে তথ্য নেওয়া
            $title = $news->title ?? '';
            $desc = $news->description ?? $news->summary ?? '';
            $body = $news->content ?? '';

            // ২. HTML ট্যাগ ক্লিন করা
            $cleanBody = trim(strip_tags($body));
            $cleanDesc = trim(strip_tags($desc));

            // ৩. AI-কে পাঠানোর জন্য পূর্ণাঙ্গ তথ্য তৈরি করা
            // আমরা টাইটেল + ডেসক্রিপশন + বডি সব একসাথে জোড়া দিচ্ছি
            $fullContext = "Headline: " . $title . "\n\n";
            
            if (!empty($cleanDesc)) {
                $fullContext .= "Summary/Intro: " . $cleanDesc . "\n\n";
            }
            
            if (!empty($cleanBody)) {
                $fullContext .= "Details: " . $cleanBody;
            } else {
                // যদি বডি না থাকে, তবে ডেসক্রিপশন দুইবার রিপিট করা হচ্ছে না, 
                // বরং AI কে বলা হবে এর ওপর ভিত্তি করে লিখতে।
                $fullContext .= "Details: (Not available, please expand based on Headline and Summary)";
            }

            // ==================================================

            // ৪. AI কল করা (বড় কনটেক্সট পাঠানো হচ্ছে)
            $aiResponse = $aiWriter->rewrite($fullContext, $title);

            // ৫. ডাটাবেসে সেভ করা
            $finalContent = $aiResponse['content'] ?? $news->content ?? 'Content generation failed.';

            $news->update([
                'ai_title' => $aiResponse['title'] ?? $news->title,
                'ai_content' => $finalContent,
                'status' => 'draft'
            ]);

            Log::info("✅ AI Job Completed. ID: {$this->newsId}");

        } catch (\Exception $e) {
            Log::error("🔥 AI Job Failed for ID {$this->newsId}: " . $e->getMessage());
            $news->update(['status' => 'failed']);
        }
    }
}