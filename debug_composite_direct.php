<?php
/**
 * Debug Composite Expression Method Directly
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

// Extend SmartGroupDisplay to access private method
class DebugSmartGroupDisplay extends SmartGroupDisplay {
    public function debugFindCompositeExpression(array $selectedGroups) {
        // Access groupManager through a public method
        $groupManager = new \App\Core\GroupManager();
        $allGroups = $groupManager->getAllGroups();
        
        // Sort groups by potential (larger groups first as they may form better units)
        $sortedRoots = [];
        foreach ($allGroups as $rootId => $rootData) {
            $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
            if (count($rootInstruments) >= 2) { // Only consider groups with multiple instruments
                $sortedRoots[] = $rootId;
            }
        }
        
        // Sort by size (larger groups first)
        usort($sortedRoots, function($a, $b) {
            $countA = count($this->getAllInstrumentsInGroup($a));
            $countB = count($this->getAllInstrumentsInGroup($b));
            return $countB <=> $countA;
        });
        
        echo "Sorted roots by size:\n";
        foreach ($sortedRoots as $rootId) {
            $count = count($this->getAllInstrumentsInGroup($rootId));
            echo "  $rootId: $count instruments\n";
        }
        echo "\n";
        
        // Find all possible group-with-exclusions units
        $units = [];
        $remainingGroups = $selectedGroups;
        
        echo "Starting with remaining groups: " . implode(', ', $remainingGroups) . "\n\n";
        
        foreach ($sortedRoots as $rootId) {
            if (empty($remainingGroups)) break;
            
            echo "Testing root: $rootId\n";
            
            $unit = $this->debugTryExtractUnitWithExclusions($remainingGroups, $rootId);
            if ($unit && count($unit['covered_groups']) >= 2) { // Only use units that cover multiple groups
                echo "  ✓ Found unit: {$unit['description']} covering: " . implode(', ', $unit['covered_groups']) . "\n";
                $units[] = $unit['description'];
                $remainingGroups = array_diff($remainingGroups, $unit['covered_groups']);
                echo "  Remaining groups after extraction: " . implode(', ', $remainingGroups) . "\n";
            } else {
                if ($unit) {
                    echo "  ✗ Unit rejected (covers " . count($unit['covered_groups']) . " groups, need >= 2)\n";
                } else {
                    echo "  ✗ No unit found\n";
                }
            }
            echo "\n";
        }
        
        // Add any remaining individual groups
        echo "Adding remaining groups as individual units:\n";
        foreach ($remainingGroups as $groupId) {
            $displayName = $groupManager->getDisplayName($groupId);
            echo "  + $displayName\n";
            $units[] = $displayName;
        }
        
        echo "\nFinal units: " . implode(', ', $units) . "\n";
        echo "Units count: " . count($units) . ", Original groups count: " . count($selectedGroups) . "\n";
        
        // Only return if we found at least one meaningful unit and have fewer units than original groups
        if (count($units) < count($selectedGroups) && count($units) > 0) {
            $result = $this->combineUnits($units);
            echo "Result: '$result'\n";
            return $result;
        }
        
        echo "No compression achieved\n";
        return null;
    }
    
    public function debugTryExtractUnitWithExclusions(array $remainingGroups, string $rootId): ?array {
        $rootInstruments = $this->getAllInstrumentsInGroup($rootId);
        if (empty($rootInstruments) || count($rootInstruments) < 2) {
            echo "    Skipping $rootId (too few instruments: " . count($rootInstruments) . ")\n";
            return null; // Not worth considering small groups
        }
        
        $selectedInstruments = [];
        $coveredGroups = [];
        
        foreach ($remainingGroups as $groupId) {
            $groupInstruments = $this->getAllInstrumentsInGroup($groupId);
            $intersection = array_intersect($groupInstruments, $rootInstruments);
            if (!empty($intersection)) {
                $selectedInstruments = array_merge($selectedInstruments, $intersection);
                $coveredGroups[] = $groupId;
                echo "    Group $groupId intersects: " . implode(', ', $intersection) . "\n";
            }
        }
        
        $selectedInstruments = array_unique($selectedInstruments);
        $missingInstruments = array_diff($rootInstruments, $selectedInstruments);
        
        echo "    Selected instruments: " . implode(', ', $selectedInstruments) . "\n";
        echo "    Missing instruments: " . implode(', ', $missingInstruments) . "\n";
        echo "    Covered groups: " . implode(', ', $coveredGroups) . "\n";
        echo "    Coverage: " . count($selectedInstruments) . "/" . count($rootInstruments) . " = " . (count($selectedInstruments) / count($rootInstruments)) . "\n";
        
        // Must have some coverage of the root (lowered threshold)
        if (count($selectedInstruments) < count($rootInstruments) * 0.4) {
            echo "    ✗ Coverage too low\n";
            return null;
        }
        
        // Check for meaningful patterns
        if (!empty($missingInstruments) && count($missingInstruments) <= count($rootInstruments) * 0.6) {
            $missingGroups = $this->findGroupsForInstruments($missingInstruments, $rootId);
            if (!empty($missingGroups) && count($coveredGroups) >= 2) { // Must cover at least 2 original groups
                $groupManager = new \App\Core\GroupManager();
                $rootName = $groupManager->getDisplayName($rootId);
                $missingDescription = $this->generateSimpleList($missingGroups);
                $description = $rootName . ' ' . $this->language['without'] . ' ' . $missingDescription;
                
                echo "    ✓ Found 'without' pattern: $description\n";
                return [
                    'description' => $description,
                    'covered_groups' => $coveredGroups
                ];
            } else {
                echo "    ✗ Missing groups empty or not enough covered groups\n";
            }
        } elseif (empty($missingInstruments) && count($coveredGroups) >= 2) {
            // Perfect match - covers at least 2 groups
            $groupManager = new \App\Core\GroupManager();
            $description = $groupManager->getDisplayName($rootId);
            echo "    ✓ Found perfect match: $description\n";
            return [
                'description' => $description,
                'covered_groups' => $coveredGroups
            ];
        } else {
            echo "    ✗ Pattern doesn't match criteria\n";
        }
        
        return null;
    }
}

echo "Debug Composite Expression Method\n";
echo "==================================\n\n";

$display = new DebugSmartGroupDisplay();
$selectedGroups = ['Bratsche', 'Holzbläser', 'Posaune', 'Trompete', 'Tuba'];

echo "Selected groups: " . implode(', ', $selectedGroups) . "\n\n";

$result = $display->debugFindCompositeExpression($selectedGroups);
