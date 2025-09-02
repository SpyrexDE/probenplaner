<?php
/**
 * Test Improved SmartGroupDisplay Algorithm
 * 
 * Tests the new compression algorithm for complex nested exclusions
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

echo "Testing improved SmartGroupDisplay algorithm\n";
echo "===========================================\n\n";

try {
    $display = new SmartGroupDisplay();
    
    // Test Case 1: "Bratsche with Bläser except horn"
    echo "Test Case 1: Bratsche with Bläser except Horn\n";
    echo "Expected: 'Bratsche und Bläser ohne Horn'\n";
    $selectedGroups1 = ['Bratsche', 'Holzbläser', 'Posaune', 'Trompete', 'Tuba'];
    $result1 = $display->generateDescription($selectedGroups1);
    echo "Result: '$result1'\n";
    echo "Pass: " . ($result1 === 'Bratsche und Bläser ohne Horn' ? "YES" : "NO") . "\n\n";
    
    // Test Case 2: "Tutti except Oboe"  
    echo "Test Case 2: Tutti except Oboe\n";
    echo "Expected: 'Tutti ohne Oboe'\n";
    $selectedGroups2 = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Klarinette', 'Schlagwerk', 'Streicher'];
    $result2 = $display->generateDescription($selectedGroups2);
    echo "Result: '$result2'\n";
    echo "Pass: " . ($result2 === 'Tutti ohne Oboe' ? "YES" : "NO") . "\n\n";
    
    // Additional test: Just "Tutti"
    echo "Test Case 3: Complete Tutti\n";
    echo "Expected: 'Tutti'\n";
    $selectedGroups3 = ['tutti'];
    $result3 = $display->generateDescription($selectedGroups3);
    echo "Result: '$result3'\n";
    echo "Pass: " . ($result3 === 'Tutti' ? "YES" : "NO") . "\n\n";
    
    // Additional test: "Bläser ohne Horn"
    echo "Test Case 4: Bläser ohne Horn\n";
    echo "Expected: 'Bläser ohne Horn'\n";
    $selectedGroups4 = ['Holzbläser', 'Posaune', 'Trompete', 'Tuba'];
    $result4 = $display->generateDescription($selectedGroups4);
    echo "Result: '$result4'\n";
    echo "Pass: " . ($result4 === 'Bläser ohne Horn' ? "YES" : "NO") . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
