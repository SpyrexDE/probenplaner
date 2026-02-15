<?php

/**
 * JSO-Planer
 * Main entry point
 */

// Handle static files for PHP development server
if (php_sapi_name() === 'cli-server') {
    $requestUri = $_SERVER['REQUEST_URI'];
    $filePath = parse_url($requestUri, PHP_URL_PATH);

    // Check if it's a request for a static file
    if (preg_match('/\.(css|js|html|htm|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i', $filePath)) {
        $staticFile = __DIR__ . $filePath;
        if (file_exists($staticFile)) {
            // Determine MIME type
            $extension = pathinfo($staticFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'html' => 'text/html',
                'htm' => 'text/html',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'ico' => 'image/x-icon',
                'svg' => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];

            $mimeType = $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            readfile($staticFile);
            return;
        }
    }
}

try {
    // Bootstrap the application
    require_once __DIR__ . '/../bootstrap.php';
} catch (\Exception $e) {
    // Handle critical bootstrap errors
    error_log("Critical bootstrap error: " . $e->getMessage());

    // Show user-friendly error page
    http_response_code(500);
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Service Temporarily Unavailable</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                margin-top: 50px;
            }

            .error {
                color: #d32f2f;
            }
        </style>
    </head>

    <body>
        <h1>Service Temporarily Unavailable</h1>
        <p class="error">The application is currently experiencing technical difficulties.</p>
        <p>Please try again later or contact your system administrator.</p>
        <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
            <hr>
            <h3>Debug Information (Development Mode)</h3>
            <p><?= htmlspecialchars($e->getMessage()) ?></p>
        <?php endif; ?>
    </body>

    </html>
<?php
    exit;
}

// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Simple router
$router = new \App\Core\Router();

// Define routes

// Authentication routes (no orchestra context)
$router->addRoute('/', 'HomeController', 'index');
$router->addRoute('/login', 'AuthController', 'login');
$router->addRoute('/register', 'AuthController', 'showRegisterForm', 'GET');
$router->addRoute('/register', 'AuthController', 'register', 'POST');
$router->addRoute('/logout', 'AuthController', 'logout');

// Keycloak authentication routes
$router->addRoute('/auth/keycloak/login', 'AuthController', 'keycloakLogin');
$router->addRoute('/auth/keycloak/callback', 'AuthController', 'keycloakCallback');
$router->addRoute('/auth/keycloak/token', 'AuthController', 'keycloakTokenLogin', 'POST');

// Orchestra selection routes (no orchestra context)
$router->addRoute('/orchestras/select', 'OrchestraSelectionController', 'select');
$router->addRoute('/orchestras/set-current', 'OrchestraSelectionController', 'setCurrentOrchestra', 'POST');
$router->addRoute('/orchestras/join', 'OrchestraSelectionController', 'showJoinForm', 'GET');
$router->addRoute('/orchestras/join', 'OrchestraSelectionController', 'join', 'POST');
$router->addRoute('/orchestras/select-section', 'OrchestraSelectionController', 'showSectionSelection', 'GET');
$router->addRoute('/orchestras/complete-join', 'OrchestraSelectionController', 'completeJoin', 'POST');
$router->addRoute('/orchestras/switch/{orchestra_id}', 'OrchestraSelectionController', 'switchOrchestra');

// Orchestra-specific routes (with orchestra_id context)
// Promises routes
$router->addRoute('/{orchestra_id}/promises', 'PromiseController', 'index');
$router->addRoute('/{orchestra_id}/promises/leader', 'PromiseController', 'leader');
$router->addRoute('/{orchestra_id}/promises/admin', 'PromiseController', 'admin');
$router->addRoute('/{orchestra_id}/promises/update', 'PromiseController', 'update');
$router->addRoute('/{orchestra_id}/promises/note', 'PromiseController', 'note');

// Rehearsal routes
$router->addRoute('/{orchestra_id}/rehearsals', 'RehearsalController', 'index');
$router->addRoute('/{orchestra_id}/rehearsals/create', 'RehearsalController', 'create');
$router->addRoute('/{orchestra_id}/rehearsals/edit/{id}', 'RehearsalController', 'edit');
$router->addRoute('/{orchestra_id}/rehearsals/delete/{id}', 'RehearsalController', 'delete');

// Probenplan route
$router->addRoute('/{orchestra_id}/probenplan', 'ProbenplanController', 'index');

// User profile routes (orchestra-specific context)
$router->addRoute('/{orchestra_id}/profile', 'UserController', 'profile');
$router->addRoute('/{orchestra_id}/profile/check-leader-password', 'UserController', 'checkLeaderPassword');
$router->addRoute('/{orchestra_id}/profile/switch-theme', 'UserController', 'switchTheme', 'POST');
$router->addRoute('/{orchestra_id}/profile/delete', 'UserController', 'delete');
$router->addRoute('/{orchestra_id}/conductor/profile', 'UserController', 'conductorProfile');

// Orchestra settings routes (conductor only)
$router->addRoute('/{orchestra_id}/orchestras/settings', 'OrchestraController', 'settings');
$router->addRoute('/{orchestra_id}/orchestras/update', 'OrchestraController', 'update');
$router->addRoute('/{orchestra_id}/orchestras/delete-confirm', 'OrchestraController', 'confirmDelete');
$router->addRoute('/{orchestra_id}/orchestras/delete', 'OrchestraController', 'delete');

// User management API routes (orchestra-specific)
$router->addRoute('/{orchestra_id}/user/getUserDetails', 'UserController', 'getUserDetails');
$router->addRoute('/{orchestra_id}/user/resetPassword', 'UserController', 'resetPassword');
$router->addRoute('/{orchestra_id}/user/deleteUser', 'UserController', 'deleteUser');

// API routes (orchestra-specific)
$router->addRoute('/{orchestra_id}/api/test', 'ApiController', 'test');
$router->addRoute('/{orchestra_id}/api/minimal-stats', 'ApiController', 'minimalStats');
$router->addRoute('/{orchestra_id}/api/user-stats', 'ApiController', 'getUserStats');
$router->addRoute('/{orchestra_id}/api/settings/{entity}/{entity_id}', 'SettingsApiController', 'update', 'POST');

// Global orchestra creation routes (admin only)
$router->addRoute('/orchestras/create', 'OrchestraController', 'create');
$router->addRoute('/orchestras/store', 'OrchestraController', 'store');

// Process the request
try {
    $router->dispatch($uri);
} catch (\Exception $e) {
    // Handle routing/controller errors
    error_log("Application error: " . $e->getMessage());

    // Show user-friendly error page
    http_response_code(500);
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Application Error</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                margin-top: 50px;
            }

            .error {
                color: #d32f2f;
            }
        </style>
    </head>

    <body>
        <h1>Application Error</h1>
        <p class="error">An error occurred while processing your request.</p>
        <p><a href="/">Return to Home</a></p>
        <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
            <hr>
            <h3>Debug Information (Development Mode)</h3>
            <p><?= htmlspecialchars($e->getMessage()) ?></p>
            <pre><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
        <?php endif; ?>
    </body>

    </html>
<?php
}
