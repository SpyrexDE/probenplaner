<?php
namespace App\Models {
    class Orchestra {
        public function findById($id) { return null; }
    }
}

namespace {
    define('APP_ROOT', __DIR__ . '/../src');
    require_once APP_ROOT . '/Core/SmartGroupDisplay.php';
    require_once APP_ROOT . '/Core/GroupManager.php';

    use App\Core\SmartGroupDisplay;

    session_start();

    $display = new SmartGroupDisplay();

    // 1. "Schlagwerk und Blechbläser" (Should NOT be "Schlagwerk und Bläser ohne Holzbläser")
    $groups1 = ["Schlagwerk", "Blechbläser"];

    // 2. "Tutti ohne Bläser" (Should NOT be "Streicher, Schlagwerk und Harfe")
    // Assuming Tutti contains: Streicher, Bläser, Schlagwerk, Harfe
    $groups2 = ["Streicher", "Schlagwerk", "Harfe"];

    // 3. "Holzbläser ohne Klarinette" (Should NOT be "Flöte, Oboe und Fagott")
    $groups3 = ["Flöte", "Oboe", "Fagott"];

    echo "--- Case 1 (Blech + Schlagwerk) ---\n";
    $debug1 = $display->debugAnalysis($groups1);
    echo "Expected: Schlagwerk und Blechbläser\nActual: " . $debug1['final_description'] . "\n\n";

    echo "--- Case 2 (Streicher, Schlagwerk, Harfe) ---\n";
    $debug2 = $display->debugAnalysis($groups2);
    echo "Expected: Tutti ohne Bläser\nActual: " . $debug2['final_description'] . "\n\n";

    echo "--- Case 3 (Flöte, Oboe, Fagott) ---\n";
    $debug3 = $display->debugAnalysis($groups3);
    echo "Expected: Holzbläser ohne Klarinette\nActual: " . $debug3['final_description'] . "\n\n";
    
    // Output Candidate array for Case 2
    echo "--- Case 2 Score Breakdown ---\n";
    print_r(array_map(function($info) {
        return ['desc' => $info['description'], 'score' => $info['score']];
    }, $debug2['coverage_analysis']));
}
