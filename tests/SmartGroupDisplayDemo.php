<?php
/**
 * Smart Group Display Demo
 * 
 * Demonstrates the capabilities of the smart group display system
 * with realistic orchestra rehearsal scenarios.
 */

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    
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

// Mock dependencies
class MockDatabase {
    public static function getInstance() { return new self(); }
}
if (!class_exists('App\Core\Database')) {
    class_alias('MockDatabase', 'App\Core\Database');
}

use App\Core\SmartGroupDisplay;
use App\Core\SmartDisplayLanguage;

function displayScenario($title, $groups, $description = "") {
    echo "\n📅 $title\n";
    if ($description) {
        echo "   $description\n";
    }
    
    $display = new SmartGroupDisplay();
    $result = $display->generateDescription($groups);
    
    echo "   Selected: " . implode(", ", $groups) . "\n";
    echo "   Display: \"$result\"\n";
    
    // Show debug info for complex cases
    if (count($groups) > 2) {
        $debug = $display->debugAnalysis($groups);
        echo "   Complexity: " . count($debug['possible_roots']) . " possible roots analyzed\n";
    }
}

echo "🎼 Smart Group Display - Real Orchestra Scenarios\n";
echo "==================================================\n";

// Basic rehearsal types
displayScenario(
    "Full Orchestra Rehearsal", 
    ["tutti"],
    "Everyone is needed for this rehearsal"
);

displayScenario(
    "String Sectional", 
    ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass"],
    "All string players meet separately"
);

displayScenario(
    "Brass Sectional", 
    ["Horn", "Trompete", "Posaune", "Tuba"],
    "Brass section working on difficult passages"
);

displayScenario(
    "Upper Strings Only", 
    ["Violine_1", "Violine_2", "Bratsche"],
    "Working on exposed upper string parts"
);

// "Without" patterns
displayScenario(
    "Full Orchestra without Percussion", 
    ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass", 
     "Flöte", "Oboe", "Klarinette", "Fagott", "Horn", "Trompete", "Posaune", "Tuba"],
    "Orchestra rehearsal where percussion isn't needed"
);

displayScenario(
    "Strings without First Violins", 
    ["Violine_2", "Bratsche", "Cello", "Kontrabass"],
    "First violins have different entrance, rest of strings rehearse"
);

displayScenario(
    "Brass without Low Brass", 
    ["Horn", "Trompete"],
    "High brass sectional"
);

// Complex combinations
displayScenario(
    "Strings and Woodwinds", 
    ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass", 
     "Flöte", "Oboe", "Klarinette", "Fagott"],
    "Working on a movement where brass doesn't play much"
);

displayScenario(
    "Principals Meeting", 
    ["Violine_1", "Flöte", "Oboe", "Klarinette", "Horn", "Trompete"],
    "Section leaders discussing interpretation"
);

displayScenario(
    "Tutti minus Strings", 
    ["Flöte", "Oboe", "Klarinette", "Fagott", "Horn", "Trompete", "Posaune", "Tuba", "Schlagwerk"],
    "Winds and percussion rehearsal"
);

// Mixed scenarios
displayScenario(
    "Strings plus Solo Winds", 
    ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass", "Flöte", "Horn"],
    "String section with featured wind soloists"
);

displayScenario(
    "Chamber Group Rehearsal", 
    ["Violine_1", "Bratsche", "Cello", "Flöte", "Horn"],
    "Small ensemble working on chamber piece"
);

// Edge cases that test the algorithm
displayScenario(
    "Most of Orchestra", 
    ["Violine_1", "Violine_2", "Bratsche", "Cello", 
     "Flöte", "Oboe", "Klarinette", "Fagott", 
     "Horn", "Trompete", "Posaune", "Tuba", "Schlagwerk"],
    "Almost everyone, just missing double bass"
);

displayScenario(
    "Scattered Selection", 
    ["Violine_1", "Horn", "Schlagwerk"],
    "Unusual combination for special piece"
);

// Language demonstration
echo "\n🌍 Multi-Language Examples\n";
echo "===========================\n";

$testGroups = ["Violine_1", "Violine_2", "Bratsche", "Cello"];
$languages = [
    'de' => 'German',
    'en' => 'English', 
    'fr' => 'French',
    'es' => 'Spanish',
    'it' => 'Italian'
];

foreach ($languages as $code => $name) {
    $display = SmartDisplayLanguage::createDisplay($code);
    $result = $display->generateDescription($testGroups);
    echo "🇩🇪 $name: \"$result\"\n";
}

echo "\n✨ The system intelligently analyzes group hierarchies and generates\n";
echo "   natural descriptions that match how conductors actually communicate!\n";

// Performance test
echo "\n⚡ Performance Test\n";
echo "===================\n";

$complexGroups = ["Violine_1", "Violine_2", "Bratsche", "Flöte", "Oboe", "Horn", "Trompete", "Schlagwerk"];
$display = new SmartGroupDisplay();

$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $display->generateDescription($complexGroups);
}
$end = microtime(true);

$avgTime = ($end - $start) / 100;
echo "Average processing time: " . number_format($avgTime * 1000, 2) . " ms\n";
echo "Performance rating: " . ($avgTime < 0.005 ? "🚀 Excellent" : ($avgTime < 0.02 ? "✅ Good" : "⚠️  Needs optimization")) . "\n";

echo "\n🎯 Integration ready! The system can now be used in:\n";
echo "   • Rehearsal scheduling views\n";
echo "   • Promise/attendance tracking\n";
echo "   • Conductor communications\n";
echo "   • Reports and summaries\n";
