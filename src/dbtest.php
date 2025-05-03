<?php
// Debug script to analyze database structure

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
$host = '172.28.0.2';  // Use the fixed IP from docker-compose.yml
$user = 'probenplaner';
$password = 'kDo1#a43';
$dbname = 'probenplaner';

$mysqli = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

echo "<h1>Database Debug Information</h1>";

// Get rehearsals table structure
$result = $mysqli->query("DESCRIBE rehearsals");
if ($result) {
    echo "<h2>Rehearsals Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    $result->free();
} else {
    echo "<p>Error fetching rehearsals table structure: " . $mysqli->error . "</p>";
}

// Try to insert a test record
echo "<h2>Test Insert</h2>";

$stmt = $mysqli->prepare("
    INSERT INTO rehearsals 
    (date, time, location, description, color, groups_data, orchestra_id, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo "<p>Prepare failed: " . $mysqli->error . "</p>";
} else {
    $date = date('Y-m-d');
    $time = '19:00';
    $location = 'Test Location';
    $description = 'Test Description';
    $color = 'white';
    $groups_data = '{"Tutti":0}';
    $orchestra_id = 1;
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');
    
    $stmt->bind_param('ssssssis', $date, $time, $location, $description, $color, $groups_data, $orchestra_id, $created_at, $updated_at);
    
    echo "<p>Executing statement with parameters:</p>";
    echo "<ul>";
    echo "<li>Date: $date</li>";
    echo "<li>Time: $time</li>";
    echo "<li>Location: $location</li>";
    echo "<li>Description: $description</li>";
    echo "<li>Color: $color</li>";
    echo "<li>Groups Data: $groups_data</li>";
    echo "<li>Orchestra ID: $orchestra_id</li>";
    echo "<li>Created At: $created_at</li>";
    echo "<li>Updated At: $updated_at</li>";
    echo "</ul>";
    
    if ($stmt->execute()) {
        echo "<p>Test insert successful!</p>";
    } else {
        echo "<p>Test insert failed: " . $stmt->error . " (Error #" . $stmt->errno . ")</p>";
    }
    
    $stmt->close();
}

$mysqli->close(); 