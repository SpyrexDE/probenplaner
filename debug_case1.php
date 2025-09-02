<?php
/**
 * Debug Test Case 1
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

echo "Debugging Test Case 1: Bratsche with Bläser except Horn\n";
echo "======================================================\n\n";

$display = new SmartGroupDisplay();
$selectedGroups = ['Bratsche', 'Holzbläser', 'Posaune', 'Trompete', 'Tuba'];

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n\n";

// Debug analysis
$analysis = $display->debugAnalysis($selectedGroups);
print_r($analysis);
