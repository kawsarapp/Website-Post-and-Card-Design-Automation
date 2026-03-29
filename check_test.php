<?php
$html = file_get_contents('test_kalerkantho.html');
preg_match_all('/<h2[^>]*><a[^>]+href=[\'\"]([^\'\"]+)[\'\"][^>]*>(.*?)<\/a><\/h2>/is', $html, $matches);
echo "Kalerkantho H2:\n";
print_r(array_slice($matches[1], 0, 5));

$html = file_get_contents('test_bdnews24.html');
preg_match_all('/<a[^>]+href=[\'\"]([^\'\"]+)[\'\"][^>]*>(.*?)<\/a>/is', $html, $matches);
echo "BDNews24 Links:\n";
print_r(array_slice($matches[1], 0, 5));

$html = file_get_contents('test_somoynews.html');
preg_match_all('/<a[^>]+href=[\'\"]([^\'\"]+)[\'\"][^>]*>(.*?)<\/a>/is', $html, $matches);
echo "Somoynews Links:\n";
print_r(array_slice($matches[1], 0, 5));
