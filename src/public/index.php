<?php
/**
 * JSO-Planer
 * Main entry point
 */

// Bootstrap the application
require_once __DIR__ . '/../bootstrap.php';

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
$router->dispatch($uri); 