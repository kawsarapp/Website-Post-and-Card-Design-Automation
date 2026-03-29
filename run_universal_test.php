<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$scraper = app(\App\Services\NewsScraperService::class);
$k_html = $scraper->fetchWithUniversalScrapingApi('https://www.kalerkantho.com/special/recent');
file_put_contents('test_kalerkantho.html', $k_html);

$b_html = $scraper->fetchWithUniversalScrapingApi('https://bangla.bdnews24.com/archive');
file_put_contents('test_bdnews24.html', $b_html);

$s_html = $scraper->fetchWithUniversalScrapingApi('https://www.somoynews.tv/read/recent');
file_put_contents('test_somoynews.html', $s_html);

echo "Done!";
