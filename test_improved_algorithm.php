<?php

require_once 'src/bootstrap.php';

use App\Core\SmartGroupDisplay;
use App\Core\GroupManager;

echo "Testing improved SmartGroupDisplay algorithm\n";
echo "===========================================\n\n";

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
$selectedGroups2 = ['Andere', 'Blechbläser', 'Fagott', 'Flöte', 'Klarinette', 'Schlagwerk', 'Streicher'];
$result2 = $display->generateDescription($selectedGroups2);
echo "Result: '$result2'\n";
echo "Pass: " . ($result2 === 'Tutti ohne Oboe' ? "YES" : "NO") . "\n\n";

// Additional test: Just "Tutti"
echo "Test Case 3: Complete Tutti\n";
echo "Expected: 'Tutti'\n";
$selectedGroups3 = ['tutti'];
$result3 = $display->generateDescription($selectedGroups3);
echo "Result: '$result3'\n";
echo "Pass: " . ($result3 === 'Tutti' ? "YES" : "NO") . "\n\n";

// Additional test: "Bläser ohne Horn"
echo "Test Case 4: Bläser ohne Horn\n";
echo "Expected: 'Bläser ohne Horn'\n";
$selectedGroups4 = ['Holzbläser', 'Posaune', 'Trompete', 'Tuba'];
$result4 = $display->generateDescription($selectedGroups4);
echo "Result: '$result4'\n";
echo "Pass: " . ($result4 === 'Bläser ohne Horn' ? "YES" : "NO") . "\n\n";

// Debug output for understanding the algorithm
echo "Debug Analysis for Case 1:\n";
print_r($display->debugAnalysis($selectedGroups1));

echo "\nDebug Analysis for Case 2:\n";
print_r($display->debugAnalysis($selectedGroups2));
