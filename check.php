<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bb = App\Models\Website::where('url', 'like', '%bartabazar%')->first();
if($bb) { 
    $n = App\Models\NewsItem::where('website_id', $bb->id)->latest()->first(); 
    echo "Bartabazar IMG: " . ($n->thumbnail_url ?? 'NULL') . "\n"; 
} 

$ek = App\Models\Website::where('url', 'like', '%ekhon.tv%')->first();
if($ek) { 
    $n = App\Models\NewsItem::where('website_id', $ek->id)->latest()->first(); 
    echo "Ekhon TV URL: " . ($n->original_link ?? 'NULL') . "\n"; 
}
