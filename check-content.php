<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ek = App\Models\Website::where('url', 'like', '%ekhon.tv%')->first();
if($ek) { 
    $n = App\Models\NewsItem::where('website_id', $ek->id)->orderBy('id', 'desc')->first();
    echo "ID: {$n->id}\nTitle: {$n->title}\nBODY CONTENT PREVIEW:\n";
    echo substr($n->content, 0, 1000) . "...\n";
}
