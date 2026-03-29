<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Symfony\Component\DomCrawler\Crawler;

function testClasses($file) {
    echo "Testing $file:\n";
    $html = file_get_contents($file);
    $crawler = new Crawler($html);
    
    // Find all links and their parent class
    $links = $crawler->filter('a');
    $results = [];
    $links->each(function (Crawler $node) use (&$results) {
        $href = $node->attr('href');
        $parentClass = null;
        try {
            if ($node->parents()->count() > 0) {
                $parentClass = $node->parents()->first()->attr('class');
            }
        } catch (\Exception $e) {}
        
        $results[] = [
            'href' => $href,
            'class' => $node->attr('class'),
            'parent_class' => $parentClass
        ];
    });

    foreach(array_slice($results, 20, 10) as $r) {
        if ($r['href'] && strpos($r['href'], 'http') === false && strlen($r['href']) > 1) {
            echo "Href: {$r['href']} | Class: {$r['class']} | Parent: {$r['parent_class']}\n";
        }
    }
}

testClasses('test_bdnews24.html');
testClasses('test_somoynews.html');
