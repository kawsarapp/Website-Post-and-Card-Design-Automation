<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Prothomalo URL
    $url = 'https://www.prothomalo.com/latest';
    
    // We fetch HTML securely using python curl_cffi wrapper (very fast and bypasses Cloudflare)
    $output = shell_exec('python python_scripts/scraper.py ' . escapeshellarg($url));
    $html = json_decode($output, true)['html'] ?? '';

    $crawler = new \Symfony\Component\DomCrawler\Crawler($html);
    
    echo "Total Links: " . $crawler->filter('a')->count() . PHP_EOL;
    
    $selectors = [
        '.story-card a', 
        'h2 a', 
        'article a', 
        'h3 a',
        '.bn-story-card a',
        '[data-testid="story-card"] a',
        '.card a'
    ];
    
    foreach($selectors as $s) {
        echo "Selector [{$s}]: " . $crawler->filter($s)->count() . PHP_EOL;
    }

    echo "Sample H2s:" . PHP_EOL;
    $crawler->filter('h2')->each(function($node){ 
        echo trim($node->text()) . PHP_EOL;
    });

} catch(\Exception $e) {
    echo "Error: " . $e->getMessage();
}
