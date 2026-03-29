<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIWriterService
{
    private $systemPrompt;

    public function __construct()
    {
        // আপনার হুবহু বাংলাদেশী সাব-এডিটর স্টাইল প্রম্পট
        $this->systemPrompt = <<<EOT
        You are a **Senior Sub-Editor** at a top-tier Bangladeshi Daily (like Prothom Alo or The Daily Star).
        **YOUR GOAL:** Rewrite the raw input into a **crisp, factual, and professional news report** in standard "Promit Bangla".

        **🧹 STEP 1: GARBAGE REMOVAL (CRITICAL)**
        Before rewriting, mentally remove all "Garbage Information":
        - **REMOVE:** Promotional text ("Click here", "Subscribe", "Follow us", "Share this").
        - **REMOVE:** Social media jargon ("Viral video", "Netizens say", Hashtags).
        - **REMOVE:** Redundant adjectives (e.g., "Shocking", "Unbelievable", "Mind-blowing").
        - **REMOVE:** Repetitive sentences that say the same thing twice.

        **🧠 STEP 2: CONTEXT & TONE**
        - **Identify the Core News:** What actually happened? (Who, What, When, Where, Why).
        - **Tone:** - If **Politics/Govt**: Formal, serious, neutral. Use words like 'প্রজ্ঞাপন', 'নির্দেশনা', 'জানানো হয়েছে'.
          - If **Crime/Accident**: Factual, concise. No sensationalism.
          - If **General**: Informative and direct.
        - **Fact Preservation:** NEVER change Quotes ("..."), Names, Dates, Numbers, or Locations.

        **✍️ STEP 3: WRITING RULES (HUMAN TOUCH)**
        1. **NO BOLDING:** Do NOT use `<b>`, `<strong>`, or markdown bold. Real news reports are plain text.
        2. **NO HEADINGS:** Do NOT use `<h3>` or `<h4>` inside the body unless it is a very long feature article. Use paragraph breaks instead.
        3. **INVERTED PYRAMID:** - **Lead Paragraph:** Start directly with the main news. (e.g., "আগামীকাল থেকে স্কুল বন্ধ ঘোষণা করেছে শিক্ষা মন্ত্রণালয়।"). Avoid starting with "It has been reported that...".
           - **Body:** Provide supporting details and quotes.
           - **Background:** Context or previous events (if necessary) at the end.

        **📏 STEP 4: LENGTH & COMPLETENESS (STRICT)**
        - **NO SUMMARIZATION:** Do not summarize or abridge the news. You are a Sub-Editor, not a Summarizer. If the input contains 5 detailed points, your output must cover all 5 points.
        - **NO FABRICATION:** Do not add filler sentences just to make it look long. Stick strictly to the information provided in the source.
        - **Maintain Depth:** The output length should be proportional to the factual content of the input.

        **FORMATTING:**
        - Use ONLY `<p>` tags for paragraphs.
        - Keep paragraphs comprised of 3-4 sentences for readability on mobile screens.

        **OUTPUT FORMAT (JSON):**
        Return ONLY a valid JSON object.
        {
            "title": "A professional, catchy news headline in Bengali (Max 10-12 words)",
            "content": "HTML string with <p> tags only. No bold, no headings."
        }
        EOT;
    }

    // 🔥 আপডেট: $isRetry এবং $userId প্যারামিটার যুক্ত করা হয়েছে
    public function rewrite($content, $title, $isRetry = false, $userId = null)
    {
        if (empty($content) || strlen(strip_tags($content)) < 100) {
            throw new \Exception("SHORT_CONTENT");
        }

        $safeContent = mb_substr($content, 0, 8000, 'UTF-8'); 

        // 🔥 আপডেট: পুনরায় লেখার জন্য বিশেষ নির্দেশিকা
        $retryInstruction = $isRetry 
            ? "\n\n⚠️ NOTE: This is a RE-WRITE request. Your previous version was not satisfactory. Please use DIFFERENT vocabulary, change the sentence structure, and try a MORE ENGAGING lead paragraph while maintaining the same facts."
            : "";

        $finalInput = "Title: $title\n\nContent: $safeContent" . $retryInstruction;

        // 1️⃣ TRY DEEPSEEK FIRST
        try {
            return $this->callDeepSeek($finalInput, $title, $isRetry, $userId);
        } catch (\Exception $e) {
            Log::warning("⚠️ DeepSeek Failed: " . $e->getMessage() . ". Switching to OpenAI...");
        }

        // 2️⃣ THEN TRY OPENAI
        try {
            return $this->callOpenAI($finalInput, $title, $isRetry, $userId);
        } catch (\Exception $e) {
            Log::warning("⚠️ OpenAI Failed: " . $e->getMessage() . ". Switching to Gemini...");
        }

        // 3️⃣ FINALLY TRY GEMINI
        try {
            return $this->callGemini($finalInput, $title, $isRetry, $userId);
        } catch (\Exception $e) {
            Log::error("❌ ALL AI SERVICES FAILED: " . $e->getMessage());
            throw new \Exception("ALL_AI_FAILED");
        }
    }

    private function callDeepSeek($content, $title, $isRetry, $userId)
    {
        $settings = $userId ? \App\Models\UserSetting::where('user_id', $userId)->first() : null;
        $apiKey = ($settings && $settings->deepseek_api_key) ? $settings->deepseek_api_key : (config('services.deepseek.key') ?? env('DEEPSEEK_API_KEY'));
        $model = ($settings && $settings->deepseek_model) ? $settings->deepseek_model : "deepseek-chat";

        if (!$apiKey) throw new \Exception("DeepSeek API Key Missing");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(40)->post("https://api.deepseek.com/chat/completions", [
            "model" => $model,
            "messages" => [
                ["role" => "system", "content" => $this->systemPrompt], 
                ["role" => "user", "content" => $content]
            ],
            "response_format" => ["type" => "json_object"],
            "temperature" => $isRetry ? 0.85 : 0.7
        ]);

        return $this->parseResponse($response, 'DeepSeek');
    }

    private function callGemini($content, $title, $isRetry, $userId)
    {
        $settings = $userId ? \App\Models\UserSetting::where('user_id', $userId)->first() : null;
        $apiKey = ($settings && $settings->gemini_api_key) ? $settings->gemini_api_key : (config('services.gemini.key') ?? env('GEMINI_API_KEY'));
        
        if (!$apiKey) throw new \Exception("Gemini API Key Missing");

        // Use custom model if available, else standard fallback array
        if ($settings && $settings->gemini_model) {
            $modelsToTry = [$settings->gemini_model];
        } else {
            $modelsToTry = ["gemini-1.5-flash", "gemini-1.5-pro"];
        }

        foreach ($modelsToTry as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                
                $config = [
                    "responseMimeType" => "application/json",
                    "temperature" => $isRetry ? 0.8 : 0.6
                ];

                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(40)
                    ->post($url, [
                        "contents" => [[
                            "parts" => [["text" => $this->systemPrompt . "\n\n" . $content]]
                        ]],
                        "generationConfig" => $config
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    Log::info("✅ Rewritten by: Gemini ($model)");
                    return $this->processRawJson($rawText, 'Gemini');
                }
            } catch (\Exception $e) { continue; }
        }
        throw new \Exception("Gemini Failed.");
    }

    private function callOpenAI($content, $title, $isRetry, $userId)
    {
        $settings = $userId ? \App\Models\UserSetting::where('user_id', $userId)->first() : null;
        $apiKey = ($settings && $settings->openai_api_key) ? $settings->openai_api_key : (config('services.openai.key') ?? env('OPENAI_API_KEY'));
        $model = ($settings && $settings->openai_model) ? $settings->openai_model : "gpt-4o-mini";

        if (!$apiKey) throw new \Exception("OpenAI API Key Missing");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(40)->post("https://api.openai.com/v1/chat/completions", [
            "model" => $model, 
            "messages" => [
                ["role" => "system", "content" => $this->systemPrompt], 
                ["role" => "user", "content" => $content]
            ],
            "response_format" => ["type" => "json_object"],
            "temperature" => $isRetry ? 0.85 : 0.7
        ]);

        return $this->parseResponse($response, 'OpenAI');
    }

    private function parseResponse($response, $providerName)
    {
        if ($response->successful()) {
            $data = $response->json();
            $rawContent = $data['choices'][0]['message']['content'] ?? null;
            Log::info("✅ Rewritten by: $providerName");
            return $this->processRawJson($rawContent, $providerName);
        }
        throw new \Exception("$providerName API Error: " . $response->status());
    }

    private function processRawJson($rawContent, $providerName)
    {
        if (!$rawContent) throw new \Exception("$providerName returned empty content");

        $cleanJson = $this->cleanJsonString($rawContent);
        $json = json_decode($cleanJson, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($json['title'])) {
            $json['content'] = strip_tags($json['content'], '<p>'); 
            return $json;
        }

        throw new \Exception("$providerName returned invalid format");
    }

    private function cleanJsonString($string)
    {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $string, $matches)) return trim($matches[1]);
        if (preg_match('/```\s*([\s\S]*?)\s*```/', $string, $matches)) return trim($matches[1]);
        return trim($string);
    }
}