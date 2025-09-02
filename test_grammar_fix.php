<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple autoloader for testing
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load config
$config = require 'src/config/orchestra_groups.php';

// Test case that should produce "Bratsche, Schlagwerk und Cello"
$selectedGroups = [
    'Bratsche',        // Individual instrument
    'Schlagwerk',      // Section group
    'Cello'           // Individual instrument
];

try {
    $smartDisplay = new App\Core\SmartGroupDisplay();
    
    $description = $smartDisplay->generateDescription($selectedGroups);
    
    echo "=== GERMAN GRAMMAR FIX TEST ===\n\n";
    echo "Selected groups: " . implode(', ', $selectedGroups) . "\n\n";
    
    echo "ALGORITHM OUTPUT:\n";
    echo "  \"" . $description . "\"\n\n";
    
    echo "EXPECTED OUTPUT:\n";
    echo "  \"Bratsche, Schlagwerk und Cello\"\n\n";
    
    echo "ANALYSIS:\n";
    echo "- Should use proper German grammar: comma-separated list with 'und' only before last item\n";
    echo "- Should NOT be: \"Bratsche und Schlagwerk und Cello\" (incorrect)\n";
    echo "- Should be: \"Bratsche, Schlagwerk und Cello\" (correct)\n";
    
    if (strpos($description, 'und Schlagwerk und') !== false) {
        echo "\n❌ FAILED: Still has incorrect grammar with multiple 'und'\n";
    } else {
        echo "\n✅ SUCCESS: Grammar looks correct!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
