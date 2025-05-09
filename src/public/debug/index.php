<?php
/**
 * Debug Dashboard
 * Modular debugging tools for development
 */

// Include bootstrap for database access
require_once '../../bootstrap.php';

// Security check - only allow in development environment
$devEnvironments = ['development', 'local', 'dev'];
$isDevEnvironment = isset($_ENV['APP_ENV']) && in_array(strtolower($_ENV['APP_ENV']), $devEnvironments);

// Also allow local access regardless of environment setting
$isLocalAccess = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost', '172.28.0.1', '172.17.0.1']);

if (!$isDevEnvironment && !$isLocalAccess) {
    die("<h1>Access Denied</h1><p>The debug dashboard is only available in development environments or from local access.</p>");
}

// Initialize database connection
$dbConnection = false;
$db = null;
$conn = null;

try {
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();
    $dbConnection = true;
} catch (\Exception $e) {
    $dbError = $e->getMessage();
}

// Get all modules (tabs)
$modules = [];
$modulesDir = __DIR__ . '/modules/';

if (is_dir($modulesDir)) {
    $files = scandir($modulesDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'index.php') {
            $moduleName = pathinfo($file, PATHINFO_FILENAME);
            $moduleInfo = require $modulesDir . $file;
            
            // Each module file should return an array with 'name', 'icon', and 'description'
            if (is_array($moduleInfo) && isset($moduleInfo['name'])) {
                $modules[$moduleName] = $moduleInfo;
            }
        }
    }
}

// Sort modules by priority if specified
uasort($modules, function($a, $b) {
    $aPriority = isset($a['priority']) ? $a['priority'] : 999;
    $bPriority = isset($b['priority']) ? $b['priority'] : 999;
    return $aPriority - $bPriority;
});

// Get current module
$currentModule = isset($_GET['module']) ? $_GET['module'] : key($modules);

// Handle form submissions and execution of module-specific logic
$moduleData = [];
$message = '';
$messageType = '';

if (isset($_POST['action']) && !empty($currentModule)) {
    $actionFile = __DIR__ . '/actions/' . $currentModule . '.php';
    
    if (file_exists($actionFile)) {
        // Execute the module's action and get results
        $actionResult = include $actionFile;
        
        if (is_array($actionResult)) {
            if (isset($actionResult['message'])) {
                $message = $actionResult['message'];
                $messageType = isset($actionResult['messageType']) ? $actionResult['messageType'] : 'info';
            }
            
            if (isset($actionResult['data'])) {
                $moduleData = $actionResult['data'];
            }
        }
    }
}

// Prepare module view data
if (empty($moduleData) && file_exists(__DIR__ . '/data/' . $currentModule . '.php')) {
    // Load initial data for the module if no action data exists
    $moduleData = include __DIR__ . '/data/' . $currentModule . '.php';
}

// Save a shared helper
function isColumnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Pass this helper to modules
$helpers = [
    'isColumnExists' => function($table, $column) use ($conn) {
        return isColumnExists($conn, $table, $column);
    }
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Dashboard</title>
    <link rel="stylesheet" href="assets/debug.css">
</head>
<body>
    <h1>
        Debug Dashboard
        <small>ENV: <?= htmlspecialchars($_ENV['APP_ENV'] ?? 'unknown') ?></small>
    </h1>
    
    <?php if (!empty($message)): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; gap: 20px;">
        <!-- Simple navigation -->
        <nav style="min-width: 200px;">
            <p class="status-<?= $dbConnection ? 'ok' : 'error' ?>">
                Database: <?= $dbConnection ? 'Connected' : 'Disconnected' ?>
            </p>
            
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($modules as $moduleId => $moduleInfo): ?>
                    <li style="margin: 5px 0;">
                        <a href="?module=<?= urlencode($moduleId) ?>" <?= $moduleId === $currentModule ? 'style="font-weight: bold;"' : '' ?>>
                            <?= $moduleInfo['icon'] ?? '🔧' ?> <?= htmlspecialchars($moduleInfo['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <!-- Main content -->
        <main style="flex: 1;">
            <?php
            $viewFile = __DIR__ . '/views/' . $currentModule . '.php';
            
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo '<div class="message error">Module view not found</div>';
            }
            ?>
        </main>
    </div>
    
    <footer style="margin-top: 20px; text-align: center; color: #666;">
        Debug Dashboard | <span class="timestamp"><?= date('Y-m-d H:i:s') ?></span>
    </footer>
    
    <script src="assets/debug.js"></script>
</body>
</html> 