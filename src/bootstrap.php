<?php
/**
 * Bootstrap file
 * Initializes the application environment
 */

// Define the application root directory
define('APP_ROOT', __DIR__);

// Load configuration
require_once APP_ROOT . '/config/config.php';

// Register autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    $baseDir = APP_ROOT;
    
    // If the class doesn't use the namespace prefix, move to the next registered autoloader
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Replace namespace separators with directory separators
    // and append .php
    $file = $baseDir . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Load the file if it exists
    if (file_exists($file)) {
        require $file;
    }
});

// Start session
session_start();

// Set error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configure error logging
ini_set('log_errors', 1);

// Always use system temp directory for logs to avoid permission issues
$logPath = sys_get_temp_dir() . '/jso-app-php-errors.log';

// Set the error log path
ini_set('error_log', $logPath);

// Check required PHP extensions
$requiredExtensions = ['mysqli', 'mbstring', 'json', 'session'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    $errorMsg = "Missing required PHP extensions: " . implode(', ', $missingExtensions);
    error_log($errorMsg);
    if (PHP_SAPI !== 'cli') {
        die($errorMsg);
    }
}

// Log application bootstrap without using error_log to avoid recursion
if (function_exists('error_log')) {
    @error_log("Application bootstrap started: " . date('Y-m-d H:i:s'));
}

// Initialize the database connection
\App\Core\Database::getInstance(); 