<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ek = App\Models\Website::where('url', 'like', '%ekhon.tv%')->first();
if($ek) { 
    $items = App\Models\NewsItem::where('website_id', $ek->id)->orderBy('id', 'desc')->take(5)->get();
    foreach($items as $n) {
        $bodyLength = strlen($n->content ?? '');
        echo "ID: {$n->id} | Title: {$n->title} | Body Len: {$bodyLength}\n";
    }
}
