<?php
/**
 * Final Comprehensive Test
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

echo "Final Comprehensive Test\n";
echo "=======================\n\n";

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
$selectedGroups2 = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Horn', 'Klarinette', 'Posaune', 'Schlagwerk', 'Streicher', 'Trompete', 'Tuba'];
$result2 = $display->generateDescription($selectedGroups2);
echo "Result: '$result2'\n";
echo "Pass: " . ($result2 === 'Tutti ohne Oboe' ? "YES" : "NO") . "\n\n";

// Test Case 3: "Tutti except Schlagwerk"
echo "Test Case 3: Tutti except Schlagwerk\n";
echo "Expected: 'Tutti ohne Schlagwerk'\n";
$selectedGroups3 = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Horn', 'Klarinette', 'Oboe', 'Posaune', 'Streicher', 'Trompete', 'Tuba'];
$result3 = $display->generateDescription($selectedGroups3);
echo "Result: '$result3'\n";
echo "Pass: " . ($result3 === 'Tutti ohne Schlagwerk' ? "YES" : "NO") . "\n\n";

// Test Case 4: Complete Tutti
echo "Test Case 4: Complete Tutti\n";
echo "Expected: 'Tutti'\n";
$selectedGroups4 = ['tutti'];
$result4 = $display->generateDescription($selectedGroups4);
echo "Result: '$result4'\n";
echo "Pass: " . ($result4 === 'Tutti' ? "YES" : "NO") . "\n\n";

// Test Case 5: "Bläser ohne Horn"
echo "Test Case 5: Bläser ohne Horn\n";
echo "Expected: 'Bläser ohne Horn'\n";
$selectedGroups5 = ['Holzbläser', 'Posaune', 'Trompete', 'Tuba'];
$result5 = $display->generateDescription($selectedGroups5);
echo "Result: '$result5'\n";
echo "Pass: " . ($result5 === 'Bläser ohne Horn' ? "YES" : "NO") . "\n\n";

echo "Summary:\n";
$passed = 0;
$total = 5;
if ($result1 === 'Bratsche und Bläser ohne Horn') $passed++;
if ($result2 === 'Tutti ohne Oboe') $passed++;
if ($result3 === 'Tutti ohne Schlagwerk') $passed++;
if ($result4 === 'Tutti') $passed++;
if ($result5 === 'Bläser ohne Horn') $passed++;

echo "Passed: $passed/$total tests\n";
if ($passed === $total) {
    echo "🎉 ALL TESTS PASSED! 🎉\n";
} else {
    echo "❌ Some tests failed\n";
}
