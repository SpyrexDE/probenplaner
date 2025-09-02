<?php
/**
 * Debug Tutti Instruments
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

echo "Debug Tutti Instruments\n";
echo "=======================\n\n";

$display = new SmartGroupDisplay();
$groupManager = new GroupManager();

$selectedGroups = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Horn', 'Klarinette', 'Oboe', 'Posaune', 'Streicher', 'Trompete', 'Tuba'];

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n\n";

// Check what getAllInstrumentsInGroup returns for tutti
$tuttiAllInstruments = $display->getAllInstrumentsInGroup('tutti');
echo "getAllInstrumentsInGroup('tutti'): " . implode(', ', $tuttiAllInstruments) . "\n";

// Check what getAllInstrumentsInGroup returns for Schlagwerk
$schlagwerkAllInstruments = $display->getAllInstrumentsInGroup('Schlagwerk');
echo "getAllInstrumentsInGroup('Schlagwerk'): " . implode(', ', $schlagwerkAllInstruments) . "\n";

// Get selected instruments using getAllInstrumentsInGroup
$selectedInstruments = [];
foreach ($selectedGroups as $groupId) {
    $groupInstruments = $display->getAllInstrumentsInGroup($groupId);
    $selectedInstruments = array_merge($selectedInstruments, $groupInstruments);
}
$selectedInstruments = array_unique($selectedInstruments);
echo "Selected instruments (using getAllInstrumentsInGroup): " . implode(', ', $selectedInstruments) . "\n";

// Find missing instruments
$missingInstruments = array_diff($tuttiAllInstruments, $selectedInstruments);
echo "Missing instruments: " . implode(', ', $missingInstruments) . "\n";
