<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$itv = App\Models\Website::where('url', 'like', '%itv%')->first();
if ($itv) {
    echo "Testing ITVBD... ID: " . $itv->id . "\n";
    $job = new App\Jobs\ScrapeWebsite($itv->id, 1);
    $job->handle(app(App\Services\NewsScraperService::class));
}

$c24 = App\Models\Website::where('url', 'like', '%channel24%')->first();
if ($c24) {
    echo "Testing Channel24BD... ID: " . $c24->id . "\n";
    $job = new App\Jobs\ScrapeWebsite($c24->id, 1);
    $job->handle(app(App\Services\NewsScraperService::class));
}
