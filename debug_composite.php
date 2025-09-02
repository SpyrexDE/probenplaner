<?php
/**
 * Debug Composite Expression Method
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

echo "Debugging findCompositeExpression method\n";
echo "========================================\n\n";

$display = new SmartGroupDisplay();
$groupManager = new GroupManager();
$selectedGroups = ['Bratsche', 'Holzbläser', 'Posaune', 'Trompete', 'Tuba'];

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n\n";

// Let's manually test what tryExtractUnitWithExclusions does for 'Bläser'
echo "Testing tryExtractUnitWithExclusions for 'Bläser':\n";

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

echo "\nCoverage check:\n";
echo "Selected: " . count($selectedInstruments) . "\n";
echo "Total Bläser: " . count($bläserInstruments) . "\n";
echo "Coverage: " . (count($selectedInstruments) / count($bläserInstruments)) . "\n";
echo "Missing: " . count($missingInstruments) . "\n";
echo "Coverage >= 40%: " . ((count($selectedInstruments) / count($bläserInstruments)) >= 0.4 ? "YES" : "NO") . "\n";
echo "Missing <= 60% of total: " . (count($missingInstruments) <= count($bläserInstruments) * 0.6 ? "YES" : "NO") . "\n";
echo "Covered groups >= 2: " . (count($coveredGroups) >= 2 ? "YES" : "NO") . "\n";
