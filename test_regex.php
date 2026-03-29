<?php
$url = 'https://ichef.bbci.co.uk/news/1024/branded_bengali/28ca/live/38cad3b0-29fc-11f1-a79a-77e93010d956.png';
echo preg_replace('/news\/\d+\/branded_bengali/', 'ace/ws/800/cpsprodpb', $url);
