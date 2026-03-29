<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// 🔥 Traits Import
use App\Traits\ScraperEnginesTrait;
use App\Traits\ScraperHtmlParserTrait;
use App\Traits\ScraperHelperTrait;

class NewsScraperService
{
    use ScraperEnginesTrait, ScraperHtmlParserTrait, ScraperHelperTrait;

    public function scrape($url, $customSelectors = [], $userId = null)
    {
        $proxy = $this->getProxyConfig($userId, $url);
        
        $website = \App\Models\Website::withoutGlobalScopes()->where('url', 'like', '%'.parse_url($url, PHP_URL_HOST).'%')->first();
        $useApi = $website ? $website->use_scraping_api : false;

        // 🔥 STRICT SECURITY ENFORCEMENT
        if (!$proxy && !$useApi) {
            if (config('app.env') === 'local') {
                Log::warning("⚠️ Running on LOCALHOST without Proxy/API. Proceeding directly (DEV MODE).");
            } else {
                Log::error("❌ Security Block [Article]: No Proxy configured AND API disabled. Aborting to protect Hosting Server IP.");
                return null;
            }
        }

        $proxyLog = $proxy ? parse_url($proxy, PHP_URL_HOST) : "Universal API";
        Log::info("🚀 START SCRAPE: $url | via $proxyLog");

        // 🌟 STEP 0: UNIVERSAL SCRAPING API (If enabled)
        $htmlContent = null;
        if ($useApi) {
            Log::info("🔐 Using Universal Scraping API for article body.");
            $htmlContent = $this->fetchWithUniversalScrapingApi($url);
            
            if ($htmlContent && strlen($htmlContent) > 500) {
                $scrapedData = $this->processHtml($htmlContent, $url, $customSelectors);
                
                if (!empty($scrapedData) && !empty($scrapedData['body'])) {
                    // 🔥 Image Cleaned Here
                    if (isset($scrapedData['image'])) {
                        $scrapedData['image'] = $this->fixVendorImages($scrapedData['image']);
                    }
                    if (isset($scrapedData['title'])) {
                        $scrapedData['title'] = $this->cleanTitle($scrapedData['title']);
                    }
                    return $scrapedData;
                }
                Log::warning("⚠️ Universal API fetched HTML, but PHP parser (DOMCrawler) returned empty body. Falling back to Python Scraper/Trafilatura...");
            } else {
                Log::warning("⚠️ Universal API failed for article. Falling back to default proxy...");
            }
        }

        $hardSites = ['jamuna.tv', 'kalerkantho.com', 'somoynews.tv', 'dailyamardesh.com', 'samakal.com', 'bartabazar.com'];
        $isHardSite = false;
        foreach ($hardSites as $site) {
            if (str_contains($url, $site)) {
                $isHardSite = true;
                break;
            }
        }

        if (!$proxy) {
            if (config('app.env') === 'local') {
                // Log::warning("⚠️ Running on LOCALHOST without Proxy/API. Proceeding directly (DEV MODE).");
            } else {
                Log::error("❌ Security Block [Article Fallback]: Universal API failed and NO PROXY available. Aborting instead of leaking Hosting Server IP.");
                return null;
            }
        }

        // 🐍 STEP 1: PYTHON SCRAPER
        $pythonData = $this->runPythonScraper($url, $userId);

        if ($pythonData && !empty($pythonData['body'])) {
            Log::info("✅ Python Scraper Success");
            return [
                'title'      => $this->cleanTitle($pythonData['title'] ?? null), // 🔥 Title Cleaned
                'image'      => $this->fixVendorImages($pythonData['image'] ?? null), // 🔥 Vendor Image Fixed
                'body'       => $this->cleanHtml($pythonData['body']), 
                'source_url' => $url
            ];
        }

        Log::info("⚠️ Python failed. Checking fallback strategy...");

        // 🐘 STEP 2: PHP HTTP REQUEST
        $htmlContent = null;

        if (!$isHardSite) {
            try {
                $htmlContent = retry(2, function () use ($url, $proxy) {
                    $timeout = $proxy ? 20 : 15; 
                    $httpRequest = Http::withHeaders($this->getRealBrowserHeaders())
                        ->timeout($timeout)
                        ->withOptions(['verify' => false, 'connect_timeout' => 10]);

                    if ($proxy) $httpRequest->withOptions(['proxy' => $proxy]);

                    $response = $httpRequest->get($url);
                    if ($response->successful()) return $response->body();
                    
                    throw new \Exception("HTTP Status: " . $response->status());
                }, 3000);
                
            } catch (\Exception $e) {
                Log::warning("PHP HTTP Failed after retries: " . $e->getMessage());
            }
        } else {
            Log::info("🛡️ Skipping PHP fallback for Hard Site (Cloudflare protected).");
        }

        // 🤖 STEP 3: PUPPETEER (Last Resort)
        if (empty($htmlContent) || str_contains($htmlContent, 'Just a moment') || strlen($htmlContent) < 600) {
            Log::info("🔄 All Fast Methods Failed. Engaging Puppeteer Engine...");
            return $this->scrapeWithPuppeteer($url, $customSelectors, $userId);
        }

        // 4️⃣ FINAL PROCESSING
        if ($htmlContent && strlen($htmlContent) > 500) {
            $scrapedData = $this->processHtml($htmlContent, $url, $customSelectors);
            
            // 🔥 Image Cleaned Here
            if (isset($scrapedData['image'])) {
                $scrapedData['image'] = $this->fixVendorImages($scrapedData['image']);
            }
            
            // 🔥 Title Cleaned Here
            if (isset($scrapedData['title'])) {
                $scrapedData['title'] = $this->cleanTitle($scrapedData['title']);
            }
            
            if (empty($scrapedData['title']) || empty($scrapedData['body'])) {
                 Log::warning("⚠️ Content Parsing Failed. Retrying with Puppeteer...");
                 return $this->scrapeWithPuppeteer($url, $customSelectors, $userId);
            }
            return $scrapedData;
        }

        Log::error("❌ CRITICAL: Scrape totally failed for: $url");
        return null;
    }





}