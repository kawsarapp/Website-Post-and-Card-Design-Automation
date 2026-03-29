<?php
$html = file_get_contents('test_kalerkantho.html');
preg_match_all('/<a[^>]+href=[\'\"]([^\'\"]+)[^>]*>.*?<img/is', $html, $matches);
echo "Kalerkantho Image Links:\n";
print_r(array_slice($matches[1], 0, 5));
