<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScraperService
{
    protected $proxyUrl;

    public function __construct()
    {
        // .env থেকে ডেটা নিয়ে প্রক্সি ইউআরএল তৈরি
        $user = env('SMARTPROXY_USER');
        $pass = env('SMARTPROXY_PASS');
        $host = env('SMARTPROXY_HOST');
        $port = env('SMARTPROXY_PORT');

        $this->proxyUrl = "http://{$user}:{$pass}@{$host}:{$port}";
    }

    public function scrape(string $url)
    {
        try {
            $response = Http::withOptions([
                'proxy' => $this->proxyUrl,
                'verify' => false, // SSL ইস্যু এড়াতে
                'timeout' => 30,   // ৩০ সেকেন্ডের বেশি অপেক্ষা করবে না
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error("Scraping failed for {$url}: Status " . $response->status());
            return null;

        } catch (\Exception $e) {
            Log::error("Scraping Error: " . $e->getMessage());
            return null;
        }
    }
}