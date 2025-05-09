<?php
/**
 * Configuration file
 * Contains application settings and constants
 */

// Define environment
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'probenplaner');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'kDo1#a43');
define('DB_NAME', getenv('DB_NAME') ?: 'probenplaner');

// Application settings
define('APP_NAME', 'Probenplaner');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Europe/Berlin');

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (APP_ENV !== 'development') {
    ini_set('session.cookie_secure', 1);
}

// Application-specific constants
define('ADMIN_PW', 'Y4y6N2h'); // Password for creating new orchestras 
