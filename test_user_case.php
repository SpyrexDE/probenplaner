<?php
require_once __DIR__ . '/src/Core/GroupManager.php';
require_once __DIR__ . '/src/Core/SmartGroupDisplay.php';
require_once __DIR__ . '/src/Core/SmartDisplayLanguage.php';

use App\Core\GroupManager;
use App\Core\SmartGroupDisplay;

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

class MockDatabase {
    public static function getInstance() { return new self(); }
}
class_alias('MockDatabase', 'App\Core\Database');

$display = new SmartGroupDisplay();

// The user selection:
$groups = [
  // Bratsche, Cello
  "Bratsche", "Cello", 
  // Schlagwerk
  "Schlagwerk", 
  // Holzbläser ohne Oboe
  "Flöte", "Klarinette", "Fagott", 
  // Blechbläser ohne Trompete
  "Horn", "Posaune", "Tuba"
];

$res = $display->generateDescription($groups);
echo "Final string: " . $res . "\n";

$debug = $display->debugAnalysis($groups);
print_r($debug['possible_roots']);
echo "Candidate check: \n";
foreach ($debug['coverage_analysis'] ?? [] as $id => $val) {
   echo "$id: " . $val['description'] . " (Score: " . $val['score'] . ")\n";
}

