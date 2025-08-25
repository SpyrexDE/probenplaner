<?php
/**
 * JSO-Planer
 * Main entry point
 */

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
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
            .error { color: #d32f2f; }
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
$router->addRoute('/', 'HomeController', 'index');
$router->addRoute('/login', 'AuthController', 'login');
$router->addRoute('/register', 'AuthController', 'showRegisterForm', 'GET');
$router->addRoute('/register', 'AuthController', 'register', 'POST');
$router->addRoute('/logout', 'AuthController', 'logout');

// Promises routes
$router->addRoute('/promises', 'PromiseController', 'index');
$router->addRoute('/promises/leader', 'PromiseController', 'leader');
$router->addRoute('/promises/admin', 'PromiseController', 'admin');
$router->addRoute('/promises/update', 'PromiseController', 'update');
$router->addRoute('/promises/note', 'PromiseController', 'note');

// Rehearsal routes
$router->addRoute('/rehearsals', 'RehearsalController', 'index');
$router->addRoute('/rehearsals/create', 'RehearsalController', 'create');
$router->addRoute('/rehearsals/edit/{id}', 'RehearsalController', 'edit');
$router->addRoute('/rehearsals/delete/{id}', 'RehearsalController', 'delete');

// Probenplan route
$router->addRoute('/probenplan', 'ProbenplanController', 'index');

// User profile routes
$router->addRoute('/profile', 'UserController', 'profile');
$router->addRoute('/profile/check-leader-password', 'UserController', 'checkLeaderPassword');
$router->addRoute('/profile/delete', 'UserController', 'delete');
$router->addRoute('/conductor/profile', 'UserController', 'conductorProfile');

// Routes for the user management API (replacing accModifier.php)
$router->addRoute('/user/getUserDetails', 'UserController', 'getUserDetails');
$router->addRoute('/user/resetPassword', 'UserController', 'resetPassword');
$router->addRoute('/user/deleteUser', 'UserController', 'deleteUser');

// Orchestra management routes
$router->addRoute('/orchestras/create', 'OrchestraController', 'create');
$router->addRoute('/orchestras/store', 'OrchestraController', 'store');
$router->addRoute('/orchestras/settings', 'OrchestraController', 'settings');
$router->addRoute('/orchestras/update', 'OrchestraController', 'update');
$router->addRoute('/orchestras/delete-confirm', 'OrchestraController', 'confirmDelete');
$router->addRoute('/orchestras/delete', 'OrchestraController', 'delete');

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
            body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
            .error { color: #d32f2f; }
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