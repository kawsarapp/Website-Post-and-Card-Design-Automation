<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIWriterService
{
    public function rewrite($text)
    {
        $apiKey = env('DEEPSEEK_API_KEY');
        if (!$apiKey) return null;

        $url = "https://api.deepseek.com/chat/completions";
        $text = mb_substr($text, 0, 5000, 'UTF-8'); 

        // 🔥 আপডেটেড প্রম্পট: কম বোল্ড + তারিখ রিমুভ 🔥
        $systemPrompt = <<<EOT
You are a Senior Editor.
**TASK:** Paraphrase the news article professionally.

**STRICT RULES:**
1. **REMOVE METADATA:** Do NOT include publish dates, print times, or reporter names (e.g., "প্রকাশ: ..."). Start directly with the news.
2. **LANGUAGE:** If input is BENGALI -> Output BENGALI. If ENGLISH -> Output ENGLISH. Do NOT translate.
3. **QUOTES:** Keep direct speech 100% UNCHANGED.

**FORMATTING RULES (Follow Strictly):**
- **BOLDING:** Use `<strong>` tags VERY SPARINGLY.
   - ✅ BOLD ONLY: Names of important People, Team Names, or Countries (e.g., <strong>ম্যানচেস্টার ইউনাইটেড</strong>, <strong>লিওনেল মেসি</strong>).
   - ❌ DO NOT BOLD: Verbs, Adjectives, Dates, or common words.
- **PARAGRAPHS:** Use HTML `<p>` tags. Keep the flow natural.

**OUTPUT:** JSON format: { "category": "CategoryName", "content": "<p>...</p>" }
EOT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($url, [
                "model" => "deepseek-chat",
                "messages" => [
                    ["role" => "system", "content" => $systemPrompt], 
                    ["role" => "user", "content" => $text]
                ],
                "temperature" => 0.2, 
                "response_format" => ["type" => "json_object"]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['choices'][0]['message']['content'])) {
                    $json = json_decode($data['choices'][0]['message']['content'], true);
                    return $json ?? ['content' => $data['choices'][0]['message']['content'], 'category' => 'Others'];
                }
            }
        } catch (\Exception $e) {
            Log::error("DeepSeek Error: " . $e->getMessage());
        }
        return null;
    }
}