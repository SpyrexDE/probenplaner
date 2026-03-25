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

    $groups = ["Schlagwerk", "Horn", "Trompete", "Posaune", "Tuba"]; 
    $groups2 = ["Schlagwerk", "Blechbläser"];

    echo "--- Debug Analysis (Instruments) ---\n";
    $debug1 = $display->debugAnalysis($groups);
    echo json_encode($debug1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    echo "\n--- Debug Analysis (Sections) ---\n";
    $debug2 = $display->debugAnalysis($groups2);
    echo json_encode($debug2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
