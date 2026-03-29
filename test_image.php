<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$scraper = app(\App\Services\NewsScraperService::class);
$data = $scraper->scrape('https://www.jagonews24.com/national/news/929948', [], 1);
echo 'JAGO IMAGE: ' . ($data['image'] ?? 'NULL') . "\n";

$data2 = $scraper->scrape('https://www.bbc.com/bengali/articles/cx2y15720n2o', [], 1);
echo 'BBC IMAGE: ' . ($data2['image'] ?? 'NULL') . "\n";
