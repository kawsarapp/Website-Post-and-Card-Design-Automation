<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ek = App\Models\Website::where('url', 'like', '%ekhon.tv%')->first();
if ($ek) {
    echo "Testing Ekhon TV... ID: " . $ek->id . "\n";
    $job = new App\Jobs\ScrapeWebsite($ek->id, 1);
    $job->handle(app(App\Services\NewsScraperService::class));
}

$bb = App\Models\Website::where('url', 'like', '%bartabazar%')->first();
if ($bb) {
    echo "Testing Bartabazar... ID: " . $bb->id . "\n";
    $job = new App\Jobs\ScrapeWebsite($bb->id, 1);
    $job->handle(app(App\Services\NewsScraperService::class));
}
