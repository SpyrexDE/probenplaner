<?php
/**
 * Rehearsal Testing Action
 */

// Check if form was submitted
if (!isset($_POST['action']) || $_POST['action'] !== 'generate_rehearsals') {
    return ['message' => 'No action specified', 'messageType' => 'error'];
}

// Validate inputs
if (empty($_POST['orchestra_id']) || !is_numeric($_POST['orchestra_id'])) {
    return ['message' => 'Please select a valid orchestra', 'messageType' => 'error'];
}

if (empty($_POST['pattern'])) {
    return ['message' => 'Please select a test pattern', 'messageType' => 'error'];
}

if (empty($_POST['num_rehearsals']) || !is_numeric($_POST['num_rehearsals']) || $_POST['num_rehearsals'] < 1) {
    return ['message' => 'Please enter a valid number of rehearsals (minimum 1)', 'messageType' => 'error'];
}

if (empty($_POST['start_date'])) {
    return ['message' => 'Please select a start date', 'messageType' => 'error'];
}

if (empty($_POST['days_between']) || !is_numeric($_POST['days_between']) || $_POST['days_between'] < 1) {
    return ['message' => 'Please enter valid days between rehearsals', 'messageType' => 'error'];
}

// Get input values
$orchestraId = (int)$_POST['orchestra_id'];
$pattern = $_POST['pattern'];
$numRehearsals = min((int)$_POST['num_rehearsals'], 20); // Cap at 20 for safety
$startDate = $_POST['start_date'];
$daysBetween = (int)$_POST['days_between'];

// Verify orchestra exists
$stmt = $conn->prepare("SELECT id, name FROM orchestras WHERE id = ?");
$stmt->bind_param('i', $orchestraId);
$stmt->execute();
$orchestraResult = $stmt->get_result();
if ($orchestraResult->num_rows === 0) {
    return ['message' => 'Selected orchestra does not exist', 'messageType' => 'error'];
}
$orchestra = $orchestraResult->fetch_assoc();
$stmt->close();

// Get users in the orchestra
$stmt = $conn->prepare("SELECT id, type FROM users WHERE orchestra_id = ? AND role = 'member'");
$stmt->bind_param('i', $orchestraId);
$stmt->execute();
$usersResult = $stmt->get_result();
$users = [];
while ($row = $usersResult->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

if (empty($users)) {
    return ['message' => 'No users found in the selected orchestra. Please generate test users first.', 'messageType' => 'error'];
}

// Define test patterns
$testPatterns = [
    'normal' => [
        'name' => 'Normal Distribution',
        'description' => 'Random attendance with normal distribution (70-90% average)',
        'attendance_range' => [70, 90],
        'std_dev' => 10
    ],
    'low_attendance' => [
        'name' => 'Low Attendance',
        'description' => 'Consistently low attendance across all sections (30-50% average)',
        'attendance_range' => [30, 50],
        'std_dev' => 8
    ],
    'high_attendance' => [
        'name' => 'High Attendance',
        'description' => 'Consistently high attendance across all sections (85-95% average)',
        'attendance_range' => [85, 95],
        'std_dev' => 5
    ],
    'string_problem' => [
        'name' => 'String Section Problem',
        'description' => 'String sections have very low attendance while others are normal',
        'attendance_range' => [70, 90],
        'std_dev' => 10,
        'problem_sections' => ['Violine_1', 'Violine_2', 'Bratsche', 'Cello', 'Kontrabass'],
        'problem_range' => [20, 40]
    ],
    'trend_decline' => [
        'name' => 'Declining Trend',
        'description' => 'Attendance gradually declining over time (90% → 60%)',
        'attendance_range' => [60, 90],
        'std_dev' => 8,
        'trend' => 'decline'
    ],
    'trend_improve' => [
        'name' => 'Improving Trend',
        'description' => 'Attendance gradually improving over time (50% → 85%)',
        'attendance_range' => [50, 85],
        'std_dev' => 8,
        'trend' => 'improve'
    ],
    'volatile' => [
        'name' => 'Volatile Attendance',
        'description' => 'High variance in attendance (30-95% with high standard deviation)',
        'attendance_range' => [30, 95],
        'std_dev' => 25
    ],
    'no_response' => [
        'name' => 'Low Response Rate',
        'description' => 'Many users not responding to rehearsals (high "maybe" rate)',
        'attendance_range' => [60, 80],
        'std_dev' => 10,
        'no_response_rate' => 0.4
    ]
];

if (!isset($testPatterns[$pattern])) {
    return ['message' => 'Invalid test pattern selected', 'messageType' => 'error'];
}

$selectedPattern = $testPatterns[$pattern];

// Group users by type
$usersByType = [];
foreach ($users as $user) {
    if (!isset($usersByType[$user['type']])) {
        $usersByType[$user['type']] = [];
    }
    $usersByType[$user['type']][] = $user;
}

// Generate rehearsals
$generatedRehearsals = [];
$currentDate = new DateTime($startDate);

// Begin transaction
$conn->begin_transaction();

try {
    for ($i = 0; $i < $numRehearsals; $i++) {
        $rehearsalDate = clone $currentDate;
        $rehearsalDate->add(new DateInterval('P' . ($i * $daysBetween) . 'D'));
        
        // Create rehearsal
        $stmt = $conn->prepare("INSERT INTO rehearsals (date, type, start_time, end_time, location, orchestra_id, is_small_group) VALUES (?, 'Probe', ?, ?, ?, ?, 0)");
        if (!$stmt) {
            throw new Exception("Failed to prepare rehearsal insert statement: " . $conn->error);
        }
        $startTime = '19:00:00';
        $endTime = '20:00:00';
        $location = 'Proberaum ' . ($i + 1);
        $rehearsalDateStr = $rehearsalDate->format('Y-m-d');
        $stmt->bind_param('ssssi', $rehearsalDateStr, $startTime, $endTime, $location, $orchestraId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create rehearsal: " . $stmt->error);
        }
        
        $rehearsalId = $conn->insert_id;
        $stmt->close();
        
        // Add "tutti" group to make this a tutti rehearsal
        $stmt = $conn->prepare("INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, 'tutti')");
        if (!$stmt) {
            throw new Exception("Failed to prepare rehearsal group insert statement: " . $conn->error);
        }
        $stmt->bind_param('i', $rehearsalId);
        if (!$stmt->execute()) {
            throw new Exception("Failed to add tutti group to rehearsal: " . $stmt->error);
        }
        $stmt->close();
        
        // Calculate base attendance rate for this rehearsal
        $baseRate = $selectedPattern['attendance_range'][0] + 
                   (rand(0, 100) / 100) * ($selectedPattern['attendance_range'][1] - $selectedPattern['attendance_range'][0]);
        
        // Apply trend if specified
        if (isset($selectedPattern['trend'])) {
            $trendProgress = $i / ($numRehearsals - 1); // 0 to 1
            if ($selectedPattern['trend'] === 'decline') {
                $baseRate = $selectedPattern['attendance_range'][1] - 
                           $trendProgress * ($selectedPattern['attendance_range'][1] - $selectedPattern['attendance_range'][0]);
            } elseif ($selectedPattern['trend'] === 'improve') {
                $baseRate = $selectedPattern['attendance_range'][0] + 
                           $trendProgress * ($selectedPattern['attendance_range'][1] - $selectedPattern['attendance_range'][0]);
            }
        }
        
        // Generate promises for each user
        $rehearsalStats = [
            'total_users' => 0,
            'attending' => 0,
            'not_attending' => 0,
            'no_response' => 0
        ];
        
        foreach ($usersByType as $userType => $typeUsers) {
            // Determine attendance rate for this section
            $sectionRate = $baseRate;
            
            // Apply problem section logic
            if (isset($selectedPattern['problem_sections']) && in_array($userType, $selectedPattern['problem_sections'])) {
                $sectionRate = $selectedPattern['problem_range'][0] + 
                              (rand(0, 100) / 100) * ($selectedPattern['problem_range'][1] - $selectedPattern['problem_range'][0]);
            }
            
            // Add some variance
            $variance = (rand(-100, 100) / 100) * $selectedPattern['std_dev'];
            $sectionRate = max(0, min(100, $sectionRate + $variance));
            
            foreach ($typeUsers as $user) {
                $rehearsalStats['total_users']++;
                
                // Randomly decide if this user responds at all (5-15% chance of no response)
                $noResponseChance = rand(5, 15); // 5-15% chance
                if (rand(1, 100) <= $noResponseChance) {
                    // User doesn't respond at all - no promise record created
                    $rehearsalStats['no_response']++;
                    continue; // Skip to next user
                }
                
                // Determine user's response
                $rand = rand(1, 100);
                
                // Handle no_response pattern
                if (isset($selectedPattern['no_response_rate']) && $rand <= $selectedPattern['no_response_rate'] * 100) {
                    $status = 'maybe';
                    $rehearsalStats['no_response']++;
                } elseif ($rand <= $sectionRate) {
                    $status = 'yes';
                    $rehearsalStats['attending']++;
                } else {
                    $status = 'no';
                    $rehearsalStats['not_attending']++;
                }
                
                // Insert promise
                $stmt = $conn->prepare("INSERT INTO user_promises (user_id, rehearsal_id, status) VALUES (?, ?, ?)");
                $stmt->bind_param('iis', $user['id'], $rehearsalId, $status);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Calculate attendance rate
        $attendanceRate = $rehearsalStats['total_users'] > 0 ? 
                        ($rehearsalStats['attending'] / $rehearsalStats['total_users']) * 100 : 0;
        
        $generatedRehearsals[] = [
            'date' => $rehearsalDate->format('Y-m-d'),
            'start_time' => $startTime,
            'location' => $location,
            'total_users' => $rehearsalStats['total_users'],
            'attending' => $rehearsalStats['attending'],
            'not_attending' => $rehearsalStats['not_attending'],
            'no_response' => $rehearsalStats['no_response'],
            'attendance_rate' => $attendanceRate
        ];
    }
    
    $conn->commit();
    
    return [
        'message' => "Successfully generated {$numRehearsals} rehearsals with '{$selectedPattern['name']}' pattern",
        'messageType' => 'success',
        'data' => [
            'rehearsals_generated' => $numRehearsals,
            'rehearsals' => $generatedRehearsals
        ]
    ];
    
} catch (Exception $e) {
    $conn->rollback();
    return [
        'message' => 'Error generating rehearsals: ' . $e->getMessage(),
        'messageType' => 'error'
    ];
}
