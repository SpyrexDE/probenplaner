<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'src/bootstrap.php';

use App\Core\SmartGroupDisplay;

try {
    echo "Creating SmartGroupDisplay instance...\n";
    $display = new SmartGroupDisplay();
    echo "Success!\n";
    
    echo "Testing simple case...\n";
    $result = $display->generateDescription(['Bratsche']);
    echo "Result for ['Bratsche']: '$result'\n";
    
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
