<?php
// Load configuration first to get APP_ENV
require_once __DIR__ . '/config/config.php';

// Configure error logging based on environment
if (APP_ENV === 'development') {
    ini_set('log_errors', 1);
    ini_set('error_log', '/var/www/html/debug.log');
    error_log('Starting application in development mode...');
}

// Initialize the application
require_once __DIR__ . '/bootstrap.php'; 