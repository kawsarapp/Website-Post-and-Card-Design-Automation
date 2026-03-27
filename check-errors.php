<?php
$logPath = 'storage/logs/laravel.log';
if (!file_exists($logPath)) {
    die("Log file not found.\n");
}

$lines = file($logPath);
// Get only today's date (or last 2 hours)
$today = date('Y-m-d');

$currentJob = null;
$currentUrl = null;
$siteErrors = [];
$siteSuccess = [];

foreach ($lines as $line) {
    // Only check lines from today
    if (!str_contains($line, $today)) continue;

    if (preg_match('/JOB STARTED: ([^ ]+) \| URL: ([^\n]+)/', $line, $m)) {
        $currentJob = trim($m[1]);
        $currentUrl = trim($m[2]);
        if (!isset($siteErrors[$currentJob])) {
            $siteErrors[$currentJob] = ['list_failed' => false, 'empty_count' => 0, 'connection_error' => 0, 'success_count' => 0];
        }
    }

    if ($currentJob) {
        if (str_contains($line, 'All strategies failed')) {
            $siteErrors[$currentJob]['list_failed'] = true;
        }
        if (str_contains($line, 'Skipped (Empty Content)')) {
            $siteErrors[$currentJob]['empty_count']++;
        }
        if (str_contains($line, 'This site can’t be r') || str_contains($line, 'Python failed')) {
            $siteErrors[$currentJob]['connection_error']++;
        }
        if (str_contains($line, '✅ Saved:')) {
            $siteErrors[$currentJob]['success_count']++;
        }
    }
}

echo "--- Scraper Health Report ---\n\n";

$failingSites = [];
foreach ($siteErrors as $site => $stats) {
    if ($stats['list_failed'] || $stats['empty_count'] > 0 || ($stats['success_count'] == 0 && $stats['connection_error'] > 0)) {
        $failingSites[] = $site;
        echo "🔴 $site\n";
        if ($stats['list_failed']) echo "   - List completely failed to scrape.\n";
        if ($stats['empty_count'] > 0) echo "   - Content was empty for {$stats['empty_count']} items.\n";
        if ($stats['connection_error'] > 0 && $stats['success_count'] == 0) echo "   - Connection/Python errors preventing scrape.\n";
    } else {
        echo "🟢 $site (Works OK. Saved: {$stats['success_count']})\n";
    }
}

echo "\nFailing Sites List:\n" . implode(', ', $failingSites) . "\n";
