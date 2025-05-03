<?php
/**
 * Comprehensive Debug Dashboard
 * Combines all debug tools in one place
 */

// Include bootstrap for database access
require_once '../../bootstrap.php';

// Security check - only allow admin access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = false;

// Check for admin session or local IP
if (isset($_SESSION['role']) && $_SESSION['role'] === 'conductor') {
    $isAdmin = true;
}
// Also allow local access and Docker/container IPs
elseif (in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', '172.28.0.1', '172.17.0.1']) || 
        // Add a parameter to bypass the check for testing
        (isset($_GET['force_access']) && $_GET['force_access'] === 'true')) {
    $isAdmin = true;
}

if (!$isAdmin) {
    $message = "Access denied. You must be logged in as a conductor or access from localhost.";
    $additionalInfo = "Your IP: " . $_SERVER['REMOTE_ADDR'];
    
    // Add session info for debugging
    if (isset($_SESSION['role'])) {
        $additionalInfo .= "<br>Your role: " . $_SESSION['role'];
    } else {
        $additionalInfo .= "<br>No role set in session";
    }
    
    // Create a proper absolute URL for forcing access
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    // Add force_access parameter or replace it if it already exists
    if (strpos($uri, '?') !== false) {
        // If URL already has parameters
        if (strpos($uri, 'force_access=') !== false) {
            // Replace the existing force_access parameter
            $uri = preg_replace('/force_access=[^&]*/', 'force_access=true', $uri);
        } else {
            // Add force_access parameter
            $uri .= '&force_access=true';
        }
    } else {
        // No parameters yet
        $uri .= '?force_access=true';
    }
    
    $forceAccessUrl = $protocol . $host . $uri;
    
    die("<h1>$message</h1><p>$additionalInfo</p><p>You can try <a href=\"$forceAccessUrl\">forcing access</a> for testing.</p>");
}

// Force access parameter for links
$forceParam = isset($_GET['force_access']) && $_GET['force_access'] === 'true' ? '&force_access=true' : '';

// Helper function to output formatted results
function outputSection($title, $content) {
    echo "<div class='section'>";
    echo "<h2>$title</h2>";
    echo "<pre>";
    print_r($content);
    echo "</pre>";
    echo "</div>";
}

// Handle form submissions
$message = '';
$messageType = '';

// DATABASE TEST
$dbInfo = [];
$dbConnection = false;
$dbError = '';

try {
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();
    
    if ($conn) {
        $dbConnection = true;
        $dbInfo = [
            'Server version' => $conn->server_info,
            'Host info' => $conn->host_info,
            'Character set' => $conn->character_set_name(),
        ];
    }
} catch (\Exception $e) {
    $dbError = $e->getMessage();
}

// DATABASE MIGRATION
if (isset($_POST['run_migration'])) {
    try {
        $tables = ['orchestras', 'users', 'rehearsals', 'rehearsal_groups', 'user_promises'];
        $migrations = [];
        
        foreach ($tables as $table) {
            $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
            $migrations[$table] = ['exists' => $exists];
            
            if (!$exists) {
                // Create the table based on schema
                $sqlFile = file_get_contents('../../../database/init/01-schema.sql');
                
                // Find the CREATE TABLE statement for this table
                if (preg_match('/CREATE TABLE ' . $table . '\s*\((.*?)\)\s*ENGINE/s', $sqlFile, $matches)) {
                    $createTable = "CREATE TABLE IF NOT EXISTS $table (" . $matches[1] . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                    
                    // Execute the CREATE TABLE statement
                    $result = $conn->query($createTable);
                    $migrations[$table]['created'] = $result ? true : false;
                    $migrations[$table]['error'] = $result ? '' : $conn->error;
                } else {
                    $migrations[$table]['error'] = "Could not find CREATE TABLE statement for $table";
                }
            }
        }
        
        $message = "Migration executed. Check results below.";
        $messageType = "success";
    } catch (\Exception $e) {
        $message = "Migration failed: " . $e->getMessage();
        $messageType = "error";
    }
}

// CREATE ORCHESTRA
if (isset($_POST['create_orchestra'])) {
    try {
        $name = trim($_POST['orchestra_name']);
        $token = trim($_POST['orchestra_token']);
        $leaderPw = trim($_POST['leader_password']);
        $conductorUsername = trim($_POST['conductor_username']);
        $conductorPassword = trim($_POST['conductor_password']);
        
        if (empty($name) || empty($token) || empty($leaderPw) || empty($conductorUsername) || empty($conductorPassword)) {
            throw new \Exception("All fields are required");
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Insert orchestra
        $stmt = $conn->prepare("INSERT INTO orchestras (name, token, leader_pw) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $token, $leaderPw);
        $stmt->execute();
        $orchestraId = $conn->insert_id;
        
        // Insert conductor user
        $hashedPassword = password_hash($conductorPassword, PASSWORD_DEFAULT);
        $role = 'conductor';
        $type = 'Dirigent';
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, type, orchestra_id, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $conductorUsername, $hashedPassword, $type, $orchestraId, $role);
        $stmt->execute();
        $userId = $conn->insert_id;
        
        // Update orchestra with conductor ID
        $stmt = $conn->prepare("UPDATE orchestras SET conductor_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $userId, $orchestraId);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $message = "Orchestra created successfully! Orchestra ID: $orchestraId, Conductor ID: $userId";
        $messageType = "success";
    } catch (\Exception $e) {
        if (isset($conn) && $conn->connect_errno === 0) {
            $conn->rollback();
        }
        $message = "Failed to create orchestra: " . $e->getMessage();
        $messageType = "error";
    }
}

// TEST REHEARSAL CREATION
if (isset($_POST['test_rehearsal'])) {
    try {
        $orchestraId = (int)$_POST['orchestra_id'];
        
        if ($orchestraId <= 0) {
            throw new \Exception("Invalid orchestra ID");
        }
        
        // Prepare data
        $date = date('Y-m-d');
        $time = '19:00';
        $location = 'Test Location';
        $description = 'Test Description';
        $color = 'white';
        $groups_data = '{"Tutti":0}';
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');
        
        // Insert rehearsal
        $stmt = $conn->prepare("
            INSERT INTO rehearsals 
            (date, time, location, description, color, groups_data, orchestra_id, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('ssssssiss', $date, $time, $location, $description, $color, $groups_data, $orchestraId, $created_at, $updated_at);
        
        if ($stmt->execute()) {
            $rehearsalId = $conn->insert_id;
            
            // Insert rehearsal group
            $group = 'Tutti';
            $stmt = $conn->prepare("INSERT INTO rehearsal_groups (rehearsal_id, group_name) VALUES (?, ?)");
            $stmt->bind_param('is', $rehearsalId, $group);
            $stmt->execute();
            
            $message = "Test rehearsal created successfully! Rehearsal ID: $rehearsalId";
            $messageType = "success";
        } else {
            $message = "Failed to create test rehearsal: " . $stmt->error;
            $messageType = "error";
        }
    } catch (\Exception $e) {
        $message = "Failed to create test rehearsal: " . $e->getMessage();
        $messageType = "error";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #d9534f;
            margin-bottom: 20px;
        }
        h2 {
            color: #0275d8;
            margin-top: 30px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        h3 {
            color: #5cb85c;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background-color: #eee;
            border: 1px solid #ddd;
            cursor: pointer;
            margin-right: 5px;
        }
        .tab.active {
            background-color: #0275d8;
            color: white;
            border-color: #0275d8;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        button {
            padding: 10px 15px;
            background-color: #0275d8;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background-color: #025aa5;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .message.success {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
        }
        .message.error {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            color: #a94442;
        }
        .status-ok {
            color: #5cb85c;
        }
        .status-error {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Dashboard</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="db-info">Database Info</div>
            <div class="tab" data-tab="db-migration">Database Migration</div>
            <div class="tab" data-tab="create-orchestra">Create Orchestra</div>
            <div class="tab" data-tab="test-rehearsal">Test Rehearsal</div>
            <div class="tab" data-tab="table-schema">Table Schema</div>
        </div>
        
        <!-- Database Info Tab -->
        <div class="tab-content active" id="db-info">
            <div class="section">
                <h2>Database Connection</h2>
                <?php if ($dbConnection): ?>
                    <p class="status-ok">✅ Database connection is working properly!</p>
                    <h3>Database Information</h3>
                    <table>
                        <?php foreach ($dbInfo as $key => $value): ?>
                            <tr>
                                <th><?= htmlspecialchars($key) ?></th>
                                <td><?= htmlspecialchars($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="status-error">❌ Database connection failed: <?= htmlspecialchars($dbError) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Database Tables</h2>
                <?php if ($dbConnection): ?>
                    <?php 
                    $tables = ['orchestras', 'users', 'rehearsals', 'rehearsal_groups', 'user_promises'];
                    $allTablesExist = true;
                    ?>
                    <table>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                            <th>Row Count</th>
                        </tr>
                        <?php foreach ($tables as $table): ?>
                            <?php 
                            $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
                            $rowCount = $exists ? $conn->query("SELECT COUNT(*) AS count FROM $table")->fetch_assoc()['count'] : 0;
                            
                            if (!$exists) {
                                $allTablesExist = false;
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($table) ?></td>
                                <td class="<?= $exists ? 'status-ok' : 'status-error' ?>">
                                    <?= $exists ? '✅ Exists' : '❌ Missing' ?>
                                </td>
                                <td><?= $exists ? $rowCount : 'N/A' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <?php if (!$allTablesExist): ?>
                        <p class="status-error">❌ Some tables are missing. Go to the Database Migration tab to create them.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="status-error">❌ Cannot check tables: Database connection failed</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Database Migration Tab -->
        <div class="tab-content" id="db-migration">
            <div class="section">
                <h2>Database Migration</h2>
                
                <?php if (isset($migrations)): ?>
                    <h3>Migration Results</h3>
                    <table>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Result</th>
                        </tr>
                        <?php foreach ($migrations as $table => $info): ?>
                            <tr>
                                <td><?= htmlspecialchars($table) ?></td>
                                <td>
                                    <?php if ($info['exists']): ?>
                                        <span class="status-ok">Already exists</span>
                                    <?php elseif (isset($info['created']) && $info['created']): ?>
                                        <span class="status-ok">Created successfully</span>
                                    <?php else: ?>
                                        <span class="status-error">Creation failed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$info['exists'] && isset($info['error']) && !empty($info['error'])): ?>
                                        <span class="status-error"><?= htmlspecialchars($info['error']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
                
                <form method="post">
                    <p>This will create any missing database tables from the schema.</p>
                    <button type="submit" name="run_migration">Run Migration</button>
                </form>
            </div>
        </div>
        
        <!-- Create Orchestra Tab -->
        <div class="tab-content" id="create-orchestra">
            <div class="section">
                <h2>Create Orchestra</h2>
                <p>Use this form to create a new orchestra and conductor account directly in the database.</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="orchestra_name">Orchestra Name:</label>
                        <input type="text" id="orchestra_name" name="orchestra_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="orchestra_token">Orchestra Token (for registration):</label>
                        <input type="text" id="orchestra_token" name="orchestra_token" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="leader_password">Leader Password:</label>
                        <input type="password" id="leader_password" name="leader_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="conductor_username">Conductor Username:</label>
                        <input type="text" id="conductor_username" name="conductor_username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="conductor_password">Conductor Password:</label>
                        <input type="password" id="conductor_password" name="conductor_password" required>
                    </div>
                    
                    <button type="submit" name="create_orchestra">Create Orchestra</button>
                </form>
            </div>
        </div>
        
        <!-- Test Rehearsal Tab -->
        <div class="tab-content" id="test-rehearsal">
            <div class="section">
                <h2>Test Rehearsal Creation</h2>
                <p>Create a test rehearsal to check if the rehearsal creation functionality is working.</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="orchestra_id">Orchestra ID:</label>
                        <input type="text" id="orchestra_id" name="orchestra_id" required>
                    </div>
                    
                    <button type="submit" name="test_rehearsal">Create Test Rehearsal</button>
                </form>
            </div>
        </div>
        
        <!-- Table Schema Tab -->
        <div class="tab-content" id="table-schema">
            <div class="section">
                <h2>Table Schema</h2>
                <?php if ($dbConnection): ?>
                    <?php 
                    $tables = ['orchestras', 'users', 'rehearsals', 'rehearsal_groups', 'user_promises'];
                    ?>
                    
                    <?php foreach ($tables as $table): ?>
                        <?php 
                        $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
                        if (!$exists) continue;
                        
                        $result = $conn->query("DESCRIBE $table");
                        ?>
                        
                        <h3><?= htmlspecialchars($table) ?></h3>
                        <table>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['Field']) ?></td>
                                    <td><?= htmlspecialchars($row['Type']) ?></td>
                                    <td><?= htmlspecialchars($row['Null']) ?></td>
                                    <td><?= htmlspecialchars($row['Key']) ?></td>
                                    <td><?= $row['Default'] !== null ? htmlspecialchars($row['Default']) : 'NULL' ?></td>
                                    <td><?= htmlspecialchars($row['Extra']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="status-error">❌ Cannot display schema: Database connection failed</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html> 