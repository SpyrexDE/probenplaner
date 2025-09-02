<?php
/**
 * Debug Scoring
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\SmartGroupDisplay;

echo "Debug Scoring\n";
echo "=============\n\n";

$display = new SmartGroupDisplay();

$candidates = [
    "Tutti ohne Streicher und Horn",
    "Bratsche und Bläser ohne Horn"
];

foreach ($candidates as $candidate) {
    $score = $display->calculateCompressionScore($candidate);
    echo "Candidate: '$candidate'\n";
    echo "Length: " . strlen($candidate) . "\n";
    echo "Score: $score\n";
    echo "\n";
}
