<?php
/**
 * Bootstrap file
 * Initializes the application environment
 */

// Define the application root directory
define('APP_ROOT', __DIR__);

// Load configuration
require_once APP_ROOT . '/config/config.php';

// Load global helper functions
require_once APP_ROOT . '/Core/Helpers.php';

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
if (APP_ENV === 'development' || APP_ENV === 'test') {
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
        throw new \Exception($errorMsg);
    }
}

// Log application bootstrap without using error_log to avoid recursion
if (function_exists('error_log')) {
    @error_log("Application bootstrap started: " . date('Y-m-d H:i:s'));
}

// Initialize the database connection
\App\Core\Database::getInstance();

// Set global exception handler
set_exception_handler(function ($e) {
    // Log exception first
    $context = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    \App\Core\ErrorHandler::log($e->getMessage(), $context);
    
    // Check if it's an AJAX/JSON request
    $isJson = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        || isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        || isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    );
    
    $message = "Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
    $details = "Fehler: " . $e->getMessage() . "\n" .
               "Datei: " . $e->getFile() . " (" . $e->getLine() . ")\n\n" .
               "Stack Trace:\n" . $e->getTraceAsString();
    
    if ($isJson) {
        // Return JSON response
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => $message,
            'details' => $details
        ]);
    } else {
        // Render error page or redirect
        if (!headers_sent() && isset($_SESSION)) {
            // If session is active, try to use flash message and redirect
            // Helper function to set flash without using Controller class directly
            if (!isset($_SESSION['alerts'])) {
                $_SESSION['alerts'] = [];
            }
            $_SESSION['alerts'][] = ['Fehler!', $message, 'error', $details];
            
            // Redirect to previous page or home
            $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
            // Avoid redirect loops if we are already on an error path (though uncommon here)
            header("Location: " . $referrer);
        } else {
            // Fallback: Simple error output
            if (!headers_sent()) {
                http_response_code(500);
            }
            // Basic HTML error page
            echo '<!DOCTYPE html><html><head><title>Systemfehler</title><style>body{font-family:sans-serif;padding:20px;text-align:center;color:#333;}.error-box{background:#fee;border:1px solid #eba;padding:15px;border-radius:5px;max-width:600px;margin:20px auto;}.details{display:none;text-align:left;background:#fff;padding:10px;border:1px solid #ddd;overflow:auto;margin-top:10px;font-size:12px;}</style></head><body>';
            echo '<h1>Uups! Etwas ist schiefgelaufen.</h1>';
            echo '<div class="error-box">';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            echo '<button onclick="var d=document.getElementById(\'d\');d.style.display=d.style.display===\'block\'?\'none\':\'block\';this.innerText=d.style.display===\'block\'?\'Details ausblenden\':\'Details anzeigen\'">Details anzeigen</button>';
            echo '<div id="d" class="details"><pre>' . htmlspecialchars($details) . '</pre></div>';
            echo '</div>';
            echo '<p><a href="/">Zurück zur Startseite</a></p>';
            echo '</body></html>';
        }
    }
    exit;
}); 