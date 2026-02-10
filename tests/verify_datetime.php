<?php
require_once __DIR__ . '/../src/Core/Config.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Model.php';
require_once __DIR__ . '/../src/Core/ErrorHandler.php';
require_once __DIR__ . '/../src/Core/Helpers.php';
require_once __DIR__ . '/../src/Core/Utilities.php';
require_once __DIR__ . '/../src/Core/RehearsalTypeManager.php';
require_once __DIR__ . '/../src/Core/GroupManager.php';
require_once __DIR__ . '/../src/Models/Rehearsal.php';

use App\Models\Rehearsal;
use App\Core\Database;

// Mock session
$_SESSION['current_orchestra_id'] = 1;

try {
    echo "Initializing Rehearsal Model...\n";
    $rehearsalModel = new Rehearsal();

    // Test Data
    $start = date('Y-m-d H:i:s', strtotime('+1 day 10:00:00'));
    $end = date('Y-m-d H:i:s', strtotime('+1 day 12:00:00'));
    
    $data = [
        'start' => $start,
        'end' => $end,
        'location' => 'Test Location',
        'orchestra_id' => 1,
        'is_small_group' => 0,
        'type' => 'Probe'
    ];
    
    $groups = ['Violine 1', 'Violine 2'];

    echo "Creating Rehearsal...\n";
    $id = $rehearsalModel->create($data, $groups);

    if ($id) {
        echo "Rehearsal created with ID: $id\n";
        
        echo "Reading Rehearsal...\n";
        $rehearsal = $rehearsalModel->findById($id);
        
        if ($rehearsal) {
            echo "Rehearsal found.\n";
            echo "Start: " . $rehearsal['start'] . "\n";
            echo "End: " . $rehearsal['end'] . "\n";
            
            if ($rehearsal['start'] == $start && $rehearsal['end'] == $end) {
                echo "SUCCESS: Start and End match.\n";
            } else {
                echo "FAILURE: Start or End do not match.\n";
            }

            // Test Update
            echo "Updating Rehearsal...\n";
            $newStart = date('Y-m-d H:i:s', strtotime('+2 days 14:00:00'));
            $newEnd = date('Y-m-d H:i:s', strtotime('+2 days 16:00:00'));
            
            $updateData = [
                'start' => $newStart,
                'end' => $newEnd,
                'location' => 'Updated Location'
            ];
            
            $rehearsalModel->updateRehearsal($id, $updateData, $groups);
            
            $updatedRehearsal = $rehearsalModel->findById($id);
            if ($updatedRehearsal['start'] == $newStart && $updatedRehearsal['end'] == $newEnd) {
                echo "SUCCESS: Update verified.\n";
            } else {
                echo "FAILURE: Update failed.\n";
            }
            
            // Cleanup
            echo "Deleting Rehearsal...\n";
            $rehearsalModel->delete($id);
            echo "Rehearsal deleted.\n";
            
        } else {
            echo "FAILURE: Could not find created rehearsal.\n";
        }
    } else {
        echo "FAILURE: Could not create rehearsal.\n";
    }

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
