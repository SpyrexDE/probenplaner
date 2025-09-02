<?php
/**
 * Debug findGroupsForInstruments Method
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

echo "Debug findGroupsForInstruments Method\n";
echo "====================================\n\n";

$display = new SmartGroupDisplay();
$groupManager = new GroupManager();

// Test the missing instruments from the debug
$missingInstruments = ['Violine_1', 'Violine_2', 'Cello', 'Kontrabass', 'Horn'];
$withinRootId = 'tutti';

echo "Missing instruments: " . implode(', ', $missingInstruments) . "\n";
echo "Within root: $withinRootId\n\n";

$result = $display->findGroupsForInstruments($missingInstruments, $withinRootId);
echo "Result groups: " . implode(', ', $result) . "\n\n";

// Let's debug what descendants are available
echo "Descendants of tutti:\n";
$descendants = $groupManager->getDescendants($withinRootId);
foreach ($descendants as $desc) {
    $instruments = $groupManager->getInstrumentsByGroup($desc['id']);
    echo "  {$desc['id']}: " . implode(', ', $instruments) . "\n";
}
echo "\n";

// Let's see what groups each missing instrument belongs to
echo "Group membership of missing instruments:\n";
foreach ($missingInstruments as $instrument) {
    echo "$instrument: ";
    foreach ($descendants as $desc) {
        $instruments = $groupManager->getInstrumentsByGroup($desc['id']);
        if (in_array($instrument, $instruments)) {
            echo "{$desc['id']} ";
        }
    }
    echo "\n";
}
