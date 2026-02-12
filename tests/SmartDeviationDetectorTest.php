<?php

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Core/SmartDeviationDetector.php';

class SmartDeviationDetectorTest {
    private $db;
    private $detector;
    
    public function __construct() {
        $this->db = \App\Core\Database::getInstance();
        $this->detector = new SmartDeviationDetector($this->db);
    }
    
    public function runTests() {
        echo "Running SmartDeviationDetector Tests...\n\n";
        
        $this->testBasicInitialization();
        $this->testRehearsalAnalysis();
        
        echo "\nAll tests completed!\n";
    }
    
    private function testBasicInitialization() {
        echo "✓ Testing basic initialization...\n";
        
        if ($this->detector instanceof SmartDeviationDetector) {
            echo "  ✓ Detector initialized successfully\n";
        } else {
            echo "  ✗ Detector initialization failed\n";
        }
    }
    
    private function testRehearsalAnalysis() {
        echo "✓ Testing rehearsal analysis...\n";
        
        // Get a rehearsal ID for testing
        $stmt = $this->db->prepare("SELECT id FROM rehearsals ORDER BY start DESC LIMIT 1");
        $stmt->execute();
        $rehearsal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rehearsal) {
            $analysis = $this->detector->analyzeRehearsal($rehearsal['id']);
            
            echo "  ✓ Analysis completed for rehearsal ID: {$rehearsal['id']}\n";
            echo "  ✓ Found " . count($analysis['deviations']) . " deviations\n";
            echo "  ✓ Found " . count($analysis['insufficient_data']) . " sections with insufficient data\n";
            
            if (!empty($analysis['deviations'])) {
                echo "  ✓ Sample deviation: " . $analysis['deviations'][0]['message'] . "\n";
            }
        } else {
            echo "  ✗ No rehearsals found for testing\n";
        }
    }
}

// Run the tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $test = new SmartDeviationDetectorTest();
    $test->runTests();
}
