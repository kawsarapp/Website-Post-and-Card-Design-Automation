<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class TestParser {
    use App\Traits\ScraperHtmlParserTrait, App\Traits\ScraperHelperTrait;
    
    public function test() {
        $html = file_get_contents('https://ekhon.tv/economy/export-import/69c6a25ea98154ff24f41aea');
        // Let's also test via Puppeteer since JS rendered
        // Actually, we can use the NewsScraperService to fetch the HTML!
        $scraper = app(App\Services\NewsScraperService::class);
        $scrapedData = $scraper->scrape('https://ekhon.tv/economy/export-import/69c6a25ea98154ff24f41aea', []);
        
        echo "TITLE: " . ($scrapedData['title'] ?? 'NULL') . "\n";
        echo "BODY LENGTH: " . strlen($scrapedData['body'] ?? '') . "\n";
        if (!empty($scrapedData['body'])) {
            echo "BODY PREVIEW:\n" . substr($scrapedData['body'], 0, 500) . "\n";
        } else {
            echo "BODY IS EMPTY!\n";
        }
    }
}

(new TestParser())->test();
