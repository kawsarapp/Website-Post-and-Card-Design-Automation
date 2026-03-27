<?php
namespace App\Http\Controllers;

use App\Traits\ScraperEnginesTrait;

$scraper = new class {
    use ScraperEnginesTrait;
};

$html = $scraper->runPuppeteer('https://www.banglatribune.com/latest-news', 1);
file_put_contents('bt_puppeteer.html', $html);
echo "Saved " . strlen($html) . " bytes\n";
