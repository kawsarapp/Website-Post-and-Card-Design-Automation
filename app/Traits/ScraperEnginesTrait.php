<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait ScraperEnginesTrait
{
    public function getProxyConfig($userId = null, $url = null)
    {
        // User requested: real IP should never be used, so removed the proxy bypass for jamuna.tv and bdnews24.com.
        // 🔥 ফিক্স: স্টাফ হলে তার অ্যাডমিনের আইডি বের করবে, নতুবা দেওয়া আইডি ব্যবহার করবে
        if ($userId) {
            $uid = $userId;
        } elseif (Auth::check()) {
            $user = Auth::user();
            $uid = in_array($user->role, ['staff', 'reporter']) ? $user->parent_id : $user->id;
        } else {
            return null;
        }

        if (!$uid) return null;

        $settings = \App\Models\UserSetting::where('user_id', $uid)->first();

        if ($settings && $settings->proxy_host && $settings->proxy_port) {
            if ($settings->proxy_username && $settings->proxy_password) {
                $auth = "{$settings->proxy_username}:{$settings->proxy_password}@";
            }
            return "http://{$auth}{$settings->proxy_host}:{$settings->proxy_port}";
        }
        return null;
    }

    public function runPythonScraper($url, $userId = null)
    {
        $proxy = $this->getProxyConfig($userId, $url);
        $scriptPath = base_path("scraper.py"); 
        if (!file_exists($scriptPath)) return null;

        $pythonCmd = env('PYTHON_PATH'); 
        if (!$pythonCmd) {
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        }

        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url);
        if ($proxy) $command .= " " . escapeshellarg($proxy);
        $command .= " 2>&1";

        $output = shell_exec($command);
        $data = json_decode($output, true);
        
        return (json_last_error() === JSON_ERROR_NONE && isset($data['body'])) ? $data : null;
    }

    public function fetchHtmlWithPython($url, $userId = null)
    {
        $proxy = $this->getProxyConfig($userId, $url);
        $scriptPath = base_path("fetch_list.py"); 
        if (!file_exists($scriptPath)) return null;

        $pythonCmd = env('PYTHON_PATH'); 
        if (!$pythonCmd) {
            $pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
        }

        $command = "$pythonCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url);
        if ($proxy) $command .= " " . escapeshellarg($proxy);
        $command .= " 2>&1";

        Log::info("🔄 Fetching List HTML with Python curl_cffi...");
        $output = shell_exec($command);
        
        return (strlen($output) > 500) ? $output : null;
    }

    protected function scrapeWithPuppeteer($url, $customSelectors, $userId)
    {
        $htmlContent = $this->runPuppeteer($url, $userId);

        if ($htmlContent && strlen($htmlContent) > 500) {
            $scrapedData = $this->processHtml($htmlContent, $url, $customSelectors);
            if (isset($scrapedData['image'])) {
                $scrapedData['image'] = $this->fixVendorImages($scrapedData['image']);
            }
            return $scrapedData;
        }
        return null;
    }

    public function runPuppeteer($url, $userId = null)
    {
        $proxy = $this->getProxyConfig($userId, $url);
        $scriptPath = base_path("scraper-engine.js");
        if (!file_exists($scriptPath)) return null;

        $tempFile = storage_path("app/public/temp_" . uniqid() . "_" . rand(1000,9999) . ".html");
        $nodeCmd = env('NODE_PATH') ?: ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'node' : 'node');

        $command = "$nodeCmd " . escapeshellarg($scriptPath) . " " . escapeshellarg($url) . " " . escapeshellarg($tempFile) . " " . escapeshellarg($proxy ?? '') . " 2>&1";
        
        Log::info("🔄 Engaging Node (Puppeteer) Engine...");
        shell_exec($command);
        
        $htmlContent = null;
        if (file_exists($tempFile)) {
            $htmlContent = file_get_contents($tempFile);
            unlink($tempFile);
        }
        
        return (strlen($htmlContent) > 500) ? $htmlContent : null;
    }

    /**
     * 🚀 SmartProxy Universal Scraping API
     * Used for hard-blocked sites like Jamuna TV (Datadome).
     * Offloads all rendering, proxy rotation, and CAPTCHA solving to their cloud.
     */
    public function fetchWithUniversalScrapingApi($url)
    {
        $token = env('SMARTPROXY_SCRAPING_API_TOKEN');
        if (!$token) {
            Log::warning("⚠️ SMARTPROXY_SCRAPING_API_TOKEN not set in .env — skipping Universal API.");
            return null;
        }

        Log::info("🌐 Calling SmartProxy Universal Scraping API for: $url");

        try {
            $payload = json_encode([
                'geo'       => 'BD',
                'locale'    => 'en-US',
                'js_render' => true,
                'format'    => ['html'],
                'context'   => ['url' => $url, 'screenshot_type' => 1],
                'source'    => 'uni_scraper'
            ]);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Basic ' . $token,
                'Content-Type'  => 'application/json'
            ])->withBody($payload, 'application/json')
              ->timeout(60)
              ->post('https://scraper.smartproxy.org/v1/query');

            if ($response->successful()) {
                $html = $response->json('results.0.content');
                if ($html && strlen($html) > 500) {
                    Log::info("✅ Universal Scraping API Success. HTML length: " . strlen($html));
                    return $html;
                }
            }

            Log::warning("⚠️ Universal Scraping API failed: " . $response->status() . " — " . $response->body());
        } catch (\Exception $e) {
            Log::warning("⚠️ Universal Scraping API exception: " . $e->getMessage());
        }

        return null;
    }
}