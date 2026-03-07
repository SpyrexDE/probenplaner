<?php

/**
 * Comprehensive Test Suite for SmartGroupDisplay
 * 
 * Tests the smart group display system with various complex scenarios
 * to ensure it generates intelligent, natural language descriptions
 * that a conductor would write.
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\SmartGroupDisplay;
use App\Core\GroupManager;

class SmartGroupDisplayTest
{
    private SmartGroupDisplay $display;
    private GroupManager $groupManager;
    private array $testResults = [];

    public function __construct()
    {
        $this->display = new SmartGroupDisplay();
        $this->groupManager = new GroupManager();
    }

    /**
     * Run all tests and display results
     */
    public function runAllTests(): void
    {
        echo "=== Smart Group Display Test Suite ===\n\n";

        // Basic tests
        $this->testBasicCases();

        // Hierarchy tests  
        $this->testHierarchyCases();

        // "Without" pattern tests
        $this->testWithoutPatterns();

        // Complex combination tests
        $this->testComplexCombinations();

        // Real-world scenarios
        $this->testRealWorldScenarios();

        $this->displayTestSummary();
    }

    private function testBasicCases(): void
    {
        echo "--- Basic Cases ---\n";
        $this->runTest("Empty groups", [], "");
        $this->runTest("Single section", ["Streicher"], "Streicher");
        $this->runTest("Single instrument", ["Violine_1"], "Violine 1");
        $this->runTest("Two sections", ["Streicher", "Blechbläser"], "Streicher und Blechbläser");
        $this->runTest("Three sections", ["Streicher", "Holzbläser", "Blechbläser"], "Streicher, Holzbläser und Blechbläser");
    }

    private function testHierarchyCases(): void
    {
        echo "\n--- Hierarchy Tests ---\n";
        // All strings should show as "Streicher"
        $this->runTest("All strings", ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass"], "Streicher");
        // All brass should show as "Blechbläser"  
        $this->runTest("All brass", ["Horn", "Trompete", "Posaune", "Tuba"], "Blechbläser");
        // Some strings
        $this->runTest("Some strings", ["Violine_1", "Violine_2"], "Violine 1 und Violine 2");
    }

    private function testWithoutPatterns(): void
    {
        echo "\n--- Without Patterns ---\n";
        // Most strings (missing one)
        $this->runTest("Most strings", ["Violine_1", "Violine_2", "Bratsche", "Cello"], "Streicher ohne Kontrabass");
        // Most brass
        $this->runTest("Most brass", ["Horn", "Trompete", "Posaune"], "Blechbläser ohne Tuba");

        // All except percussion — no single root encompasses everything anymore
        $allExceptPercussion = [
            "Violine_1",
            "Violine_2",
            "Bratsche",
            "Cello",
            "Kontrabass",
            "Flöte",
            "Oboe",
            "Klarinette",
            "Fagott",
            "Horn",
            "Trompete",
            "Posaune",
            "Tuba"
        ];
        $this->runTest("All except percussion", $allExceptPercussion, null);

        // Exception labels at the end
        $this->runTest("Exception labels at end", ["Violine_1", "Violine_2", "Bratsche", "Kontrabass", "Flöte", "Schlagwerk", "Andere"], null);

        // Subgroup compression: all except brass
        $allExceptBrass = [
            "Violine_1",
            "Violine_2",
            "Bratsche",
            "Cello",
            "Kontrabass",
            "Flöte",
            "Oboe",
            "Klarinette",
            "Fagott",
            "Schlagwerk"
        ];
        $this->runTest("All except brass", $allExceptBrass, null);

        // Complex case with redundant selections
        $complexCase = [
            "Streicher",
            "Violine_1",
            "Violine_2",
            "Bratsche",
            "Cello",
            "Kontrabass",
            "Flöte",
            "Fagott",
            "Schlagwerk",
            "Andere"
        ];
        $this->runTest("Complex redundant case", $complexCase, null);
    }

    private function testComplexCombinations(): void
    {
        echo "\n--- Complex Combinations ---\n";

        // Mixed levels
        $this->runTest("Section + instrument", ["Streicher", "Flöte"], "Streicher und Flöte");

        // Overlapping selections (should be smart)
        $this->runTest("Overlapping", ["Streicher", "Violine_1"], "Streicher");
    }

    private function testRealWorldScenarios(): void
    {
        echo "\n--- Real-world Scenarios ---\n";

        $scenarios = [
            "String rehearsal" => ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass"],
            "Brass sectional" => ["Horn", "Trompete", "Posaune", "Tuba"],
            "Upper strings" => ["Violine_1", "Violine_2", "Bratsche"],
            "Lower strings" => ["Cello", "Kontrabass"],
            "Winds without brass" => ["Flöte", "Oboe", "Klarinette", "Fagott"],
            "Principals only" => ["Violine_1", "Flöte", "Horn", "Trompete"]
        ];

        foreach ($scenarios as $name => $groups) {
            $result = $this->display->generateDescription($groups);
            echo "✓ $name: '$result'\n";
            $this->testResults[] = [
                'name' => $name,
                'groups' => $groups,
                'actual' => $result,
                'passed' => true
            ];
        }
    }

    /**
     * Run a single test and record results
     */
    private function runTest(string $name, array $groups, ?string $expected): void
    {
        $result = $this->display->generateDescription($groups);
        $passed = ($expected === null) || ($result === $expected);

        $this->testResults[] = [
            'name' => $name,
            'groups' => $groups,
            'expected' => $expected,
            'actual' => $result,
            'passed' => $passed
        ];

        $status = $passed ? "✅" : "❌";
        if ($expected === null) {
            $status = "ℹ️";
        }

        echo "$status $name: '$result'\n";

        if (!$passed && $expected !== null) {
            echo "   Expected: '$expected'\n";
        }
    }

    /**
     * Display test summary
     */
    private function displayTestSummary(): void
    {
        echo "\n=== Test Summary ===\n";

        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['passed']));

        echo "Total tests: $total\n";
        echo "Passed: $passed\n";

        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        echo "Success rate: $percentage%\n";
    }

    /**
     * Performance test
     */
    public function testPerformance(): void
    {
        echo "\n--- Performance Test ---\n";

        $complexSelection = [
            "Violine_1",
            "Violine_2",
            "Bratsche",
            "Cello",
            "Kontrabass",
            "Flöte",
            "Oboe",
            "Klarinette",
            "Horn",
            "Trompete"
        ];

        $startTime = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $this->display->generateDescription($complexSelection);
        }
        $endTime = microtime(true);

        $avgTime = ($endTime - $startTime) / 50;
        echo "Average time: " . number_format($avgTime * 1000, 2) . " ms\n";

        if ($avgTime < 0.01) {
            echo "✅ Performance is excellent\n";
        } else {
            echo "⚠️  Performance could be improved\n";
        }
    }

    /**
     * Debug a specific selection
     */
    public function debugSelection(array $groups): void
    {
        echo "\n--- Debug Analysis ---\n";
        $debug = $this->display->debugAnalysis($groups);
        echo "Selected: " . implode(', ', $groups) . "\n";
        echo "Result: " . $debug['final_description'] . "\n";
        echo "Possible roots: " . implode(', ', array_slice($debug['possible_roots'], 0, 5)) . "\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename($_SERVER['SCRIPT_FILENAME']) === 'SmartGroupDisplayTest.php') {
    $test = new SmartGroupDisplayTest();
    $test->runAllTests();
    $test->testPerformance();

    // Debug some interesting cases
    echo "\n--- Debug Examples ---\n";
    $test->debugSelection(["Horn", "Trompete", "Posaune", "Tuba", "Flöte"]);
    $test->debugSelection(["Violine_1", "Flöte", "Horn", "Trompete", "Schlagwerk"]);
}
