<?php
/**
 * Test Users Generator Action
 */

// Check if form was submitted
if (!isset($_POST['action']) || $_POST['action'] !== 'generate_users') {
    return ['message' => 'No action specified', 'messageType' => 'error'];
}

// Validate inputs
if (empty($_POST['orchestra_id']) || !is_numeric($_POST['orchestra_id'])) {
    return ['message' => 'Please select a valid orchestra', 'messageType' => 'error'];
}

if (empty($_POST['username_prefix'])) {
    return ['message' => 'Username prefix is required', 'messageType' => 'error'];
}

if (empty($_POST['max_users']) || !is_numeric($_POST['max_users']) || $_POST['max_users'] < 1) {
    return ['message' => 'Please enter a valid maximum number of users', 'messageType' => 'error'];
}

// Get input values
$orchestraId = (int)$_POST['orchestra_id'];
$usernamePrefix = $_POST['username_prefix'];
$maxUsers = min((int)$_POST['max_users'], 20); // Cap at 20 for safety

// Verify orchestra exists
$orchestraResult = $conn->query("SELECT id, name FROM orchestras WHERE id = {$orchestraId}");
if ($orchestraResult->num_rows === 0) {
    return ['message' => 'Selected orchestra does not exist', 'messageType' => 'error'];
}
$orchestra = $orchestraResult->fetch_assoc();

// Define user sections
$sections = [
    // Strings
    'Violine_1', 'Violine_2', 'Bratsche', 'Cello', 'Kontrabass',
    // Woodwinds
    'Flöte', 'Oboe', 'Klarinette', 'Fagott',
    // Brass
    'Trompete', 'Posaune', 'Tuba', 'Horn',
    // Other
    'Schlagwerk', 'Andere'
];

// For storing generated users
$generatedUsers = [];
$totalGenerated = 0;
$usernameCounter = 1;

// Begin transaction
$conn->begin_transaction();

try {
    // Loop through each section
    foreach ($sections as $section) {
        // Generate a random number of users for this section (0-10)
        $numUsers = rand(0, min(10, $maxUsers));
        
        // Create the users for this section
        for ($i = 0; $i < $numUsers; $i++) {
            $username = $usernamePrefix . $usernameCounter;
            $usernameCounter++;
            
            // Check if user with this username already exists in this orchestra
            $checkResult = $conn->query("SELECT id FROM users WHERE username = '{$username}' AND orchestra_id = {$orchestraId}");
            if ($checkResult->num_rows > 0) {
                // Skip this username
                continue;
            }
            
            // Hash the password (same as username)
            $hashedPassword = password_hash($username, PASSWORD_DEFAULT);
            
            // Create the user
            $sql = "INSERT INTO users (username, password, type, orchestra_id, role) 
                    VALUES ('{$username}', '{$hashedPassword}', '{$section}', {$orchestraId}, 'member')";
            
            if ($conn->query($sql)) {
                $generatedUsers[] = [
                    'username' => $username,
                    'password' => $username, // Username is the password
                    'type' => $section
                ];
                $totalGenerated++;
            }
        }
    }
    
    // If successful, commit the transaction
    $conn->commit();
    
    return [
        'message' => "Successfully generated {$totalGenerated} test users for orchestra '{$orchestra['name']}'",
        'messageType' => 'success',
        'data' => [
            'users_generated' => $totalGenerated,
            'users' => $generatedUsers,
            'orchestra' => $orchestra
        ]
    ];
    
} catch (\Exception $e) {
    // If there's an error, roll back the transaction
    $conn->rollback();
    
    return [
        'message' => 'Error generating users: ' . $e->getMessage(),
        'messageType' => 'error'
    ];
} 