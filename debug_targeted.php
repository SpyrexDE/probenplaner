<?php
/**
 * Debug Targeted Composite Expression
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

// Test the targeted approach specifically for "Bläser"
echo "Testing targeted approach for Bläser\n";
echo "===================================\n\n";

$display = new SmartGroupDisplay();
$groupManager = new GroupManager();

$selectedGroups = ['Bratsche', 'Holzbläser', 'Posaune', 'Trompete', 'Tuba'];
$rootId = 'Bläser';

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n";
echo "Testing root: $rootId\n\n";

// Manually test what happens with Bläser
$bläserInstruments = $groupManager->getInstrumentsByGroup('Bläser');
echo "Bläser instruments: " . implode(', ', $bläserInstruments) . "\n";

$selectedInstruments = [];
$coveredGroups = [];

foreach ($selectedGroups as $groupId) {
    $groupInstruments = $groupManager->getInstrumentsByGroup($groupId);
    echo "Group $groupId instruments: " . implode(', ', $groupInstruments) . "\n";
    $intersection = array_intersect($groupInstruments, $bläserInstruments);
    if (!empty($intersection)) {
        echo "  -> Intersects with Bläser: " . implode(', ', $intersection) . "\n";
        $selectedInstruments = array_merge($selectedInstruments, $intersection);
        $coveredGroups[] = $groupId;
    }
}

$selectedInstruments = array_unique($selectedInstruments);
echo "\nTotal selected instruments within Bläser: " . implode(', ', $selectedInstruments) . "\n";
echo "Covered groups: " . implode(', ', $coveredGroups) . "\n";

$missingInstruments = array_diff($bläserInstruments, $selectedInstruments);
echo "Missing instruments from Bläser: " . implode(', ', $missingInstruments) . "\n";

echo "\nWould this create a valid unit?\n";
echo "Coverage: " . count($selectedInstruments) . "/" . count($bläserInstruments) . " = " . (count($selectedInstruments) / count($bläserInstruments)) . "\n";
echo "Missing: " . count($missingInstruments) . "\n";
echo "Coverage >= 40%: " . ((count($selectedInstruments) / count($bläserInstruments)) >= 0.4 ? "YES" : "NO") . "\n";
echo "Missing <= 60% of total: " . (count($missingInstruments) <= count($bläserInstruments) * 0.6 ? "YES" : "NO") . "\n";
echo "Covered groups >= 2: " . (count($coveredGroups) >= 2 ? "YES" : "NO") . "\n";

if (!empty($missingInstruments) && count($missingInstruments) <= count($bläserInstruments) * 0.6) {
    $missingGroups = $display->findGroupsForInstruments($missingInstruments, 'Bläser');
    echo "Missing groups representation: " . implode(', ', $missingGroups) . "\n";
    
    $missingDescription = $display->generateSimpleList($missingGroups);
    echo "Missing description: '$missingDescription'\n";
    
    if (!empty($missingGroups) && count($coveredGroups) >= 2) {
        $description = "Bläser ohne " . $missingDescription;
        echo "Would create unit: '$description'\n";
        echo "Remaining groups after this unit: " . implode(', ', array_diff($selectedGroups, $coveredGroups)) . "\n";
    }
}
