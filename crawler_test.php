<?php
require __DIR__.'/vendor/autoload.php';

$url = 'https://cdn.ntvbd.com/ntvbd/assets/images/logo.png';
$proxyAuth = base64_encode('smart-qyr2zzuo8gp2_area-BD:bzvtmlwHNYRAQmn6');

$context = stream_context_create([
    'http' => [
        'proxy' => 'tcp://proxy.smartproxy.net:3121',
        'request_fulluri' => true,
        'header' => "Proxy-Authorization: Basic $proxyAuth\r\n" .
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0\r\n".
                    "Accept: image/webp,*/*\r\n",
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$img = @file_get_contents($url, false, $context);
echo "Length: " . strlen($img) . "\n";
