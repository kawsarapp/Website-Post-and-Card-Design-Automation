<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIWriterService
{
    /**
     * Rewrite content using DeepSeek API
     * @param string $content
     * @param string $title
     * @return array
     */
    public function rewrite($content, $title)
    {
        if (empty($content) || strlen($content) < 50) {
            return ['title' => $title, 'content' => $content];
        }

        $apiKey = config('services.deepseek.key') ?? env('DEEPSEEK_API_KEY'); 

        if (!$apiKey) {
            Log::error("DeepSeek API Key missing!");
            return ['title' => $title, 'content' => $content];
        }

        $url = "https://api.deepseek.com/chat/completions";
        
        $safeContent = mb_substr($content, 0, 8000, 'UTF-8'); 

				
		$systemPrompt = <<<EOT
You are a Senior Sub-Editor at a leading Bengali News Portal.
**YOUR GOAL:** rewrite the provided text to create a **comprehensive and engaging news report** in professional "Promit Bangla".

**🛡️ CRITICAL RULES:**
1. **NO SUMMARIZATION:** Do not shorten the news. Cover **every single detail** found in the original text.
2. **NO HALLUCINATION:** Do not invent new facts, numbers, or names that are not in the source.
3. **FLOW & EXPANSION:** You can restructure sentences and use professional vocabulary to make the report sound **detailed and authoritative**, rather than just a direct translation. Connect ideas logically.

**✍️ WRITING STYLE:**
- **Tone:** Formal, unbiased, and journalistic.
- **Structure:**
    - Use `<p>` tags for paragraphs. Break long blocks of text into readable paragraphs.
    - **ONLY** use `<h3>` tags if the original text clearly has separate topics/sections. Otherwise, stick to paragraphs.
    - Do not force bullet points (`<ul>`) unless the original text lists items.

**🚫 NEGATIVE CONSTRAINTS:**
- No "In conclusion" (পরিশেষে) type endings.
- No personal opinions.
- Do not make the content shorter than the original meaning requires.

**OUTPUT FORMAT (JSON):**
Return a valid JSON object:
{
    "title": "A catchy, standard news headline in Bengali (approx 6-10 words)",
    "content": "HTML string with <p> tags. Ensure the content feels complete."
}
EOT;			
		

        try {
            $userMessage = "Here is the news to rewrite:\n\n**Title:** $title\n**Content:** $safeContent";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])
            ->timeout(90)
            ->retry(3, 100)
            ->post($url, [
                "model" => "deepseek-chat",
                "messages" => [
                    ["role" => "system", "content" => $systemPrompt], 
                    ["role" => "user", "content" => $userMessage]
                ],
                "temperature" => 0.2,
                "response_format" => ["type" => "json_object"]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawContent = $data['choices'][0]['message']['content'] ?? null;

                if ($rawContent) {
                    $cleanJson = $this->cleanJsonString($rawContent);
                    $json = json_decode($cleanJson, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($json['title'])) {
                        return $json;
                    }
                    
                    Log::warning("AI returned invalid JSON. Using raw output.");
                    return [
                        'title' => $title . ' (AI Revised)',
                        'content' => strip_tags($rawContent)
                    ];
                }
            } else {
                Log::error("DeepSeek API Error: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("DeepSeek Exception: " . $e->getMessage());
        }

        return [
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Helper to clean markdown code blocks from JSON string
     */
    private function cleanJsonString($string)
    {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $string, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/```\s*([\s\S]*?)\s*```/', $string, $matches)) {
            return trim($matches[1]);
        }
        return trim($string);
    }
}