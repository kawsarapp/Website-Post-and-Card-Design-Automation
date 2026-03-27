<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ap = App\Models\Website::where('url', 'like', '%asia-post%')->first();
if ($ap) {
    echo "Testing Asia Post... ID: " . $ap->id . "\n";
    $job = new App\Jobs\ScrapeWebsite($ap->id, 1);
    $job->handle(app(App\Services\NewsScraperService::class));
}

$pa = App\Models\Website::where('url', 'like', '%prothomalo%')->first();
if ($pa) {
    echo "Testing Prothom Alo... ID: " . $pa->id . "\n";
    $job = new App\Jobs\ScrapeWebsite($pa->id, 1);
    $job->handle(app(App\Services\NewsScraperService::class));
}
