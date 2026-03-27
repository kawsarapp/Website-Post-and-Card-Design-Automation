<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ap = App\Models\Website::where('url', 'like', '%asia-post%')->first();
if ($ap) {
    echo "Asia Post Container: " . ($ap->selector_container ?? 'NULL') . "\n";
    echo "Asia Post Title: " . ($ap->selector_title ?? 'NULL') . "\n";
}

$pa = App\Models\Website::where('url', 'like', '%prothomalo%')->first();
if ($pa) {
    echo "Prothom Alo Container: " . ($pa->selector_container ?? 'NULL') . "\n";
    echo "Prothom Alo Title: " . ($pa->selector_title ?? 'NULL') . "\n";
}
