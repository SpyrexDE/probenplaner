<?php
/**
 * Debug Tutti ohne Schlagwerk
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
use App\Core\GroupManager;

echo "Debug Tutti ohne Schlagwerk\n";
echo "===========================\n\n";

$display = new SmartGroupDisplay();
$groupManager = new GroupManager();

// Test case: Tutti ohne Schlagwerk
$selectedGroups = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Horn', 'Klarinette', 'Oboe', 'Posaune', 'Streicher', 'Trompete', 'Tuba'];

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n";
echo "Expected: 'Tutti ohne Schlagwerk'\n\n";

$result = $display->generateDescription($selectedGroups);
echo "Result: '$result'\n";
echo "Pass: " . ($result === 'Tutti ohne Schlagwerk' ? "YES" : "NO") . "\n\n";

// Debug analysis
$analysis = $display->debugAnalysis($selectedGroups);
print_r($analysis);
