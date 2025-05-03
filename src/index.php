<?php
// Configure error logging to a file
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/debug.log');
error_log('Starting application...');

// Initialize the application
require_once __DIR__ . '/bootstrap.php'; 