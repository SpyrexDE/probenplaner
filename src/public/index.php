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
$router->addRoute('/orchestras/switch/{orchestra_id}', 'OrchestraSelectionController', 'switchOrchestra');
$router->addRoute('/orchestras/redeem', 'InviteController', 'redeemForm', 'GET');
$router->addRoute('/orchestras/redeem', 'InviteController', 'processRedeem', 'POST');

// Super-Admin routes
$router->addRoute('/admin', 'AdminController', 'dashboard');
$router->addRoute('/admin/dashboard', 'AdminController', 'dashboard');
$router->addRoute('/admin/orgs/create', 'AdminController', 'createOrg', 'GET');
$router->addRoute('/admin/orgs/store', 'AdminController', 'storeOrg', 'POST');
$router->addRoute('/admin/orgs/{org_slug}/edit', 'AdminController', 'editOrg');
$router->addRoute('/admin/orgs/{org_slug}/update', 'AdminController', 'updateOrg', 'POST');
$router->addRoute('/admin/orgs/{org_slug}/delete', 'AdminController', 'deleteOrg', 'POST');
$router->addRoute('/admin/orgs/{org_slug}/regenerate-password', 'AdminController', 'regeneratePassword', 'POST');
$router->addRoute('/admin/logout', 'AdminController', 'logout');

// Orga-Panel routes
$router->addRoute('/orga', 'OrgaPanelController', 'dashboard');
$router->addRoute('/orga/dashboard', 'OrgaPanelController', 'dashboard');
$router->addRoute('/orga/ensembles/create', 'OrgaPanelController', 'createEnsemble', 'GET');
$router->addRoute('/orga/ensembles/store', 'OrgaPanelController', 'storeEnsemble', 'POST');
$router->addRoute('/orga/ensembles/{ensemble_slug}/edit', 'OrgaPanelController', 'editEnsemble');
$router->addRoute('/orga/ensembles/{ensemble_slug}/update', 'OrgaPanelController', 'updateEnsemble', 'POST');
$router->addRoute('/orga/ensembles/{ensemble_slug}/delete', 'OrgaPanelController', 'deleteEnsemble', 'POST');
$router->addRoute('/orga/ensembles/{ensemble_slug}/generate-link', 'OrgaPanelController', 'generateLink', 'POST');
$router->addRoute('/orga/ensembles/{ensemble_slug}/regenerate-link', 'OrgaPanelController', 'regenerateLink', 'POST');
$router->addRoute('/orga/ensembles/{ensemble_slug}/remove-conductor', 'OrgaPanelController', 'removeConductor', 'POST');

// Invite routes
$router->addRoute('/invite/{token}', 'InviteController', 'landing');
$router->addRoute('/invite/join', 'InviteController', 'join', 'POST');

// Onboarding route
$router->addRoute('/onboarding', 'UserController', 'onboarding', 'GET');
$router->addRoute('/onboarding/save', 'UserController', 'saveOnboarding', 'POST');

// Orchestra-specific routes (with org_slug/orchestra_id context)
// Promises routes
$router->addRoute('/{org_slug}/{orchestra_id}/promises', 'PromiseController', 'index');
$router->addRoute('/{org_slug}/{orchestra_id}/promises/leader', 'PromiseController', 'leader');
$router->addRoute('/{org_slug}/{orchestra_id}/promises/admin', 'PromiseController', 'admin');
$router->addRoute('/{org_slug}/{orchestra_id}/promises/update', 'PromiseController', 'update');
$router->addRoute('/{org_slug}/{orchestra_id}/promises/note', 'PromiseController', 'note');

// Attendance routes
$router->addRoute('/{org_slug}/{orchestra_id}/attendance', 'AttendanceController', 'index');
$router->addRoute('/{org_slug}/{orchestra_id}/attendance/update', 'AttendanceController', 'update', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/attendance/bulk-confirm', 'AttendanceController', 'bulkConfirm', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/attendance/load-rehearsal', 'AttendanceController', 'loadRehearsal');

// Rehearsal routes
$router->addRoute('/{org_slug}/{orchestra_id}/rehearsals', 'RehearsalController', 'index');
$router->addRoute('/{org_slug}/{orchestra_id}/rehearsals/create', 'RehearsalController', 'create');
$router->addRoute('/{org_slug}/{orchestra_id}/rehearsals/edit/{id}', 'RehearsalController', 'edit');
$router->addRoute('/{org_slug}/{orchestra_id}/rehearsals/delete/{id}', 'RehearsalController', 'delete');

// Probenplan route
$router->addRoute('/{org_slug}/{orchestra_id}/probenplan', 'ProbenplanController', 'index');

// User profile routes (orchestra-specific context)
$router->addRoute('/{org_slug}/{orchestra_id}/profile', 'UserController', 'profile');
$router->addRoute('/{org_slug}/{orchestra_id}/profile/switch-theme', 'UserController', 'switchTheme', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/profile/delete', 'UserController', 'delete');
$router->addRoute('/{org_slug}/{orchestra_id}/profile/leave', 'UserController', 'leaveOrchestra');
$router->addRoute('/{org_slug}/{orchestra_id}/conductor/profile', 'UserController', 'conductorProfile');
$router->addRoute('/{org_slug}/{orchestra_id}/conductor/profile/delete', 'UserController', 'delete');
$router->addRoute('/{org_slug}/{orchestra_id}/conductor/profile/leave', 'UserController', 'leaveOrchestra');

// Member routes
$router->addRoute('/{org_slug}/{orchestra_id}/members', 'MemberController', 'index');
$router->addRoute('/{org_slug}/{orchestra_id}/members/{member_id}/details', 'MemberController', 'getDetails');
$router->addRoute('/{org_slug}/{orchestra_id}/members/{member_id}/update', 'MemberController', 'updateMember', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/members/{member_id}/remove', 'MemberController', 'removeMember', 'POST');

// Role management routes
$router->addRoute('/{org_slug}/{orchestra_id}/roles', 'MemberController', 'getRoles');
$router->addRoute('/{org_slug}/{orchestra_id}/roles/create', 'MemberController', 'createRole', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/roles/{role_id}/update', 'MemberController', 'updateRole', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/roles/{role_id}/delete', 'MemberController', 'deleteRole', 'POST');

// Invite management routes (within orchestra context)
$router->addRoute('/{org_slug}/{orchestra_id}/invite/regenerate', 'InviteController', 'regenerate', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/invite/toggle-keycloak', 'InviteController', 'toggleKeycloak', 'POST');

// Orchestra settings routes (conductor only)
$router->addRoute('/{org_slug}/{orchestra_id}/orchestras/settings', 'OrchestraController', 'settings');
$router->addRoute('/{org_slug}/{orchestra_id}/orchestras/section-config', 'OrchestraController', 'sectionConfig');
$router->addRoute('/{org_slug}/{orchestra_id}/orchestras/update', 'OrchestraController', 'update');
$router->addRoute('/{org_slug}/{orchestra_id}/orchestras/delete-confirm', 'OrchestraController', 'confirmDelete');
$router->addRoute('/{org_slug}/{orchestra_id}/orchestras/delete', 'OrchestraController', 'delete');

// User management API routes (orchestra-specific)
$router->addRoute('/{org_slug}/{orchestra_id}/user/getUserDetails', 'UserController', 'getUserDetails');
$router->addRoute('/{org_slug}/{orchestra_id}/user/resetPassword', 'UserController', 'resetPassword');
$router->addRoute('/{org_slug}/{orchestra_id}/user/deleteUser', 'UserController', 'deleteUser');

// API routes (orchestra-specific)
$router->addRoute('/{org_slug}/{orchestra_id}/api/test', 'ApiController', 'test');
$router->addRoute('/{org_slug}/{orchestra_id}/api/minimal-stats', 'ApiController', 'minimalStats');
$router->addRoute('/{org_slug}/{orchestra_id}/api/user-stats', 'ApiController', 'getUserStats');
$router->addRoute('/{org_slug}/{orchestra_id}/api/settings/{entity}/{entity_id}', 'SettingsApiController', 'update', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/api/section-members', 'SettingsApiController', 'sectionMembers', 'POST');
$router->addRoute('/{org_slug}/{orchestra_id}/api/reassign-members', 'SettingsApiController', 'reassignMembers', 'POST');



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
