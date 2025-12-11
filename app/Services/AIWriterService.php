<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIWriterService
{
    private $systemPrompt;

    public function __construct()
    {
        // 🔥 UPDATE: প্রম্পটে ভাষার ব্যাপারে কড়া নির্দেশ দেওয়া হয়েছে
        $this->systemPrompt = <<<EOT
You are a Senior Sub-Editor at a leading Bengali News Portal.
**YOUR GOAL:** Rewrite the provided text into a **detailed, professional news report** in standard "Promit Bangla".

**🛑 STRICT LANGUAGE RULES:**
1. **OUTPUT MUST BE BENGALI:** The entire output (Title and Content) MUST be in **Standard Bengali (Bangla)**.
2. **NO HINDI/ENGLISH:** Do NOT use Hindi words (e.g., करेंगे, देंगे) or English sentences.
3. **TRANSLATION:** If the source text is in Hindi or English, **TRANSLATE** it completely into professional Bengali before rewriting.

**FORMATTING IS MANDATORY:**
- **Must use HTML tags.**
- Wrap EVERY paragraph in `<p>` tags.
- Use `<h3>` for subheadings.
- Use `<b>` to highlight key info.

**OUTPUT FORMAT (JSON):**
Return ONLY a valid JSON object.
{
    "title": "A catchy news headline in Bengali",
    "content": "HTML string with <p> tags."
}
EOT;
    }

    public function rewrite($content, $title)
    {
        if (empty($content) || strlen(strip_tags($content)) < 100) {
            throw new \Exception("SHORT_CONTENT");
        }

        $safeContent = mb_substr($content, 0, 8000, 'UTF-8'); 

        // 1️⃣ TRY DEEPSEEK
        try {
            return $this->callDeepSeek($safeContent, $title);
        } catch (\Exception $e) {
            Log::warning("⚠️ DeepSeek Failed: " . $e->getMessage() . ". Switching to Gemini...");
        }

        // 2️⃣ TRY GEMINI
        try {
            return $this->callGemini($safeContent, $title);
        } catch (\Exception $e) {
            Log::warning("⚠️ Gemini Failed: " . $e->getMessage() . ". Switching to OpenAI...");
        }

        // 3️⃣ TRY OPENAI
        try {
            return $this->callOpenAI($safeContent, $title);
        } catch (\Exception $e) {
            Log::error("❌ ALL AI SERVICES FAILED: " . $e->getMessage());
            throw new \Exception("ALL_AI_FAILED");
        }
    }

    // ======================================================
    // 🧠 PRIVATE DRIVERS
    // ======================================================

    private function callDeepSeek($content, $title)
    {
        $apiKey = config('services.deepseek.key');
        if (empty($apiKey)) $apiKey = env('DEEPSEEK_API_KEY');
        if (!$apiKey) throw new \Exception("DeepSeek API Key Missing");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(40)->post("https://api.deepseek.com/chat/completions", [
            "model" => "deepseek-chat",
            "messages" => [
                ["role" => "system", "content" => $this->systemPrompt], 
                ["role" => "user", "content" => "Title: $title\n\nContent: $content"]
            ],
            "response_format" => ["type" => "json_object"]
        ]);

        return $this->parseResponse($response, 'DeepSeek');
    }

    private function callGemini($content, $title)
    {
        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) throw new \Exception("Gemini API Key Missing");

        // স্মার্ট লুপ: একটার পর একটা মডেল ট্রাই করবে
        $modelsToTry = [
            "gemini-1.5-flash", 
            "gemini-1.5-pro", 
            "gemini-1.0-pro",
            "gemini-pro"
        ];

        foreach ($modelsToTry as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                
                // Gemini-এর জন্য প্রম্পটে JSON নির্দেশনা দেওয়া
                $geminiPrompt = $this->systemPrompt . "\n\nProvide output in strictly valid JSON format.";

                // কিছু মডেলে responseMimeType সাপোর্ট করে না, তাই সেফটির জন্য শুধু flash এ দেওয়া হলো
                $config = [];
                if (str_contains($model, 'flash') || str_contains($model, '1.5')) {
                    $config = ["responseMimeType" => "application/json"];
                }

                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(40)
                    ->post($url, [
                        "contents" => [[
                            "parts" => [["text" => $geminiPrompt . "\n\nTitle: $title\nContent: $content"]]
                        ]],
                        "generationConfig" => $config
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    Log::info("✅ Rewritten by: Gemini ($model)");
                    return $this->processRawJson($rawText, 'Gemini');
                }
                
                if ($response->status() >= 400 && $response->status() < 500) {
                    Log::warning("⚠️ Gemini Model $model failed ({$response->status()}). Trying next...");
                    continue; 
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        throw new \Exception("Gemini Failed with ALL models.");
    }

    private function callOpenAI($content, $title)
    {
        $apiKey = config('services.openai.key');
        if (empty($apiKey)) $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) throw new \Exception("OpenAI API Key Missing");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(40)->post("https://api.openai.com/v1/chat/completions", [
            "model" => "gpt-4o-mini", 
            "messages" => [
                ["role" => "system", "content" => $this->systemPrompt], 
                ["role" => "user", "content" => "Title: $title\n\nContent: $content"]
            ],
            "response_format" => ["type" => "json_object"]
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
            return $json;
        }

        $cleanRaw = preg_replace('/^```json\s*|```\s*$/', '', $rawContent);
        if (strlen($cleanRaw) > 50) {
            return [
                'title' => 'AI News (' . $providerName . ')',
                'content' => $cleanRaw 
            ];
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