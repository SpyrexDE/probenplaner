<?php
/**
 * Debug Composite Expression for Tutti
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

echo "Debug Composite Expression for Tutti\n";
echo "===================================\n\n";

$display = new SmartGroupDisplay();
$groupManager = new GroupManager();

$selectedGroups = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Horn', 'Klarinette', 'Oboe', 'Posaune', 'Streicher', 'Trompete', 'Tuba'];

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n\n";

// Test the greedy approach
echo "Testing findCompositeGreedy:\n";
$greedyResult = $display->findCompositeGreedy($selectedGroups);
echo "Result: '$greedyResult'\n\n";

// Test the targeted approach
echo "Testing findCompositeTargeted:\n";
$targetResult = $display->findCompositeTargeted($selectedGroups);
echo "Result: '$targetResult'\n\n";

// Test individual units
echo "Testing tryExtractUnitWithExclusions for 'Andere':\n";
$andereUnit = $display->tryExtractUnitWithExclusions($selectedGroups, 'Andere');
if ($andereUnit) {
    echo "Found unit: {$andereUnit['description']} covering: " . implode(', ', $andereUnit['covered_groups']) . "\n";
} else {
    echo "No unit found for Andere\n";
}

echo "\nTesting tryExtractUnitWithExclusions for 'tutti':\n";
$tuttiUnit = $display->tryExtractUnitWithExclusions($selectedGroups, 'tutti');
if ($tuttiUnit) {
    echo "Found unit: {$tuttiUnit['description']} covering: " . implode(', ', $tuttiUnit['covered_groups']) . "\n";
} else {
    echo "No unit found for tutti\n";
}
