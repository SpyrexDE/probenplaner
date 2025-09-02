<?php
/**
 * Test form rendering with exclusions
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

use App\Core\RehearsalGroupProcessor;

echo "<!DOCTYPE html><html><head><title>Form Rendering Test</title></head><body>";
echo "<h1>Form Rendering Test</h1>";

// Test case: "Bläser ohne Klarinette und Posaune"
$dbGroups = ['Bläser', '!Klarinette', '!Posaune'];
$formData = RehearsalGroupProcessor::generateFormData($dbGroups);

echo "<h2>Test: Bläser ohne Klarinette und Posaune</h2>";
echo "<p>Database groups: " . json_encode($dbGroups) . "</p>";
echo "<p>Form data: " . json_encode($formData) . "</p>";

echo "<form method='post'>";
echo "<h3>Generated Form:</h3>";

// Include the dynamic group selector
include __DIR__ . '/../src/Views/components/dynamic-group-selector.php';

echo "<br><button type='submit'>Submit Test</button>";
echo "</form>";

// Show what would be submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Submitted Data:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    if (isset($_POST['groups'])) {
        $processedGroups = RehearsalGroupProcessor::processGroups($_POST);
        echo "<p>Processed groups: " . json_encode($processedGroups) . "</p>";
        
        $display = new \App\Core\SmartGroupDisplay();
        echo "<p>Description: \"" . $display->generateDescription($processedGroups) . "\"</p>";
    }
}

echo "</body></html>";
