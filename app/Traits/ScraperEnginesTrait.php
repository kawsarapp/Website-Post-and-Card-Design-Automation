<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait ScraperEnginesTrait
{
    public function getProxyConfig($userId = null)
    {
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
            $auth = "";
            if ($settings->proxy_username && $settings->proxy_password) {
                $sessionId = date('Hi'); 
                $rotatingUser = $settings->proxy_username . "-session-" . $sessionId;
                $auth = "{$rotatingUser}:{$settings->proxy_password}@";
            }
            return "http://{$auth}{$settings->proxy_host}:{$settings->proxy_port}";
        }
        return null;
    }

    public function runPythonScraper($url, $userId = null)
    {
        $proxy = $this->getProxyConfig($userId);
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
        $proxy = $this->getProxyConfig($userId);
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
}