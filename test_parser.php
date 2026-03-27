<?php
require 'vendor/autoload.php';
$html = file_get_contents('temp_bd.html');
$crawler = new \Symfony\Component\DomCrawler\Crawler($html);
$links = $crawler->filter('a')->each(function ($node) { 
    $parentClass = '';
    if ($node->getNode(0) && $node->getNode(0)->parentNode) {
        $parentClass = (string) $node->getNode(0)->parentNode->getAttribute('class');
    }
    return "TEXT: " . trim(substr($node->text(), 0, 50)) . " | HREF: " . $node->attr('href') . " | CLASS: " . $node->attr('class') . " | PARENT_CLASS: " . $parentClass;
});
echo implode("\n", array_slice(array_unique($links), 0, 100));
