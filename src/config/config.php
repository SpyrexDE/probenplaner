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
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_NAME', getenv('DB_NAME') ?: 'probenplaner');

// Application settings
define('APP_NAME', 'Probenplaner');
define('APP_VERSION', '1.0.0');
define('DEFAULT_TIMEZONE', 'Europe/Berlin');

// Set default timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Session settings for security
ini_set('session.cookie_httponly', 1);           // Prevent XSS attacks
ini_set('session.use_only_cookies', 1);          // Only use cookies, not URL parameters
ini_set('session.cookie_samesite', 'Lax');       // Lax allows OAuth/SSO redirects while still protecting against CSRF
ini_set('session.use_strict_mode', 1);           // Prevent uninitialized session IDs
// Removed deprecated session.sid_length and session.sid_bits_per_character settings

if (APP_ENV !== 'development' && APP_ENV !== 'test') {
    ini_set('session.cookie_secure', 1);         // Only over HTTPS in production
}

// Application-specific constants
define('ADMIN_PW', getenv('ADMIN_PW')); // Password for creating new orchestras
define('DEFAULT_LEADER_PASSWORD', getenv('DEFAULT_LEADER_PASSWORD') ?: 'stimmfuehrer'); // Fallback when no orchestra context

// Security Constants
define('PASSWORD_MIN_LENGTH', 8);
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 20);
define('TOKEN_MIN_LENGTH', 2);
define('COOKIE_LIFETIME', 604800); // 7 days in seconds

// Password Requirements
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

// Session Constants
define('SESSION_LIFETIME', 86400); // 24 hours
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes

// Database Constants
define('DB_CHARSET_FALLBACK', ['utf8mb4', 'utf8', 'latin1']);

// File Upload Constants (if needed in future)
define('MAX_UPLOAD_SIZE', 10485760); // 10MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);

// Debug Settings
define('DEBUG_LOG_QUERIES', false); // Set to true only in development
define('DEBUG_LOG_PASSWORDS', false); // Should always be false

// Application Limits
define('MAX_REHEARSALS_PER_PAGE', 50);
define('MAX_USERS_PER_ORCHESTRA', 200);
define('MAX_ORCHESTRA_NAME_LENGTH', 100);

// Keycloak Configuration
define('KEYCLOAK_ENABLED', getenv('KEYCLOAK_ENABLED') ?: true);
define('KEYCLOAK_BASE_URL', getenv('KEYCLOAK_BASE_URL') ?: 'https://auth.digil.me');
define('KEYCLOAK_REALM', getenv('KEYCLOAK_REALM') ?: 'jmd');
define('KEYCLOAK_CLIENT_ID', getenv('KEYCLOAK_CLIENT_ID') ?: 'starthere-appclient-prod');
define('KEYCLOAK_CLIENT_SECRET', getenv('KEYCLOAK_CLIENT_SECRET'));
define('KEYCLOAK_REDIRECT_URI', getenv('KEYCLOAK_REDIRECT_URI') ?: 'http://localhost:8080');
