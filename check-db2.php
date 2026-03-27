<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$itv = App\Models\Website::where('url', 'like', '%itv%')->first();
if($itv) { 
    $items = App\Models\NewsItem::where('website_id', $itv->id)->latest()->take(3)->get();
    echo "--- ITVBD ---\n";
    foreach($items as $n) {
        echo "ID: {$n->id} | Title: {$n->title} | Link: {$n->original_link}\n";
    }
}

$c24 = App\Models\Website::where('url', 'like', '%channel24%')->first();
if($c24) { 
    $items = App\Models\NewsItem::where('website_id', $c24->id)->latest()->take(3)->get();
    echo "--- Channel24BD ---\n";
    foreach($items as $n) {
        echo "ID: {$n->id} | Title: {$n->title} | Link: {$n->original_link}\n";
    }
}
