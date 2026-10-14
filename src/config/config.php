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


if (APP_ENV !== 'development' && APP_ENV !== 'test') {
    ini_set('session.cookie_secure', 1);         // Only over HTTPS in production
}

// Application-specific constants
define('ADMIN_PW', getenv('ADMIN_PW'));

// Security Constants
define('PASSWORD_MIN_LENGTH', 8);
define('DISPLAY_NAME_MIN_LENGTH', 2);
define('DISPLAY_NAME_MAX_LENGTH', 100);
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
// KEYCLOAK_ENABLED: unset or empty => enabled (the prod compose file passes
// KEYCLOAK_ENABLED=${KEYCLOAK_ENABLED}, i.e. an empty string when .env has no value).
// "false"/"0"/"no"/"off" => disabled (instances with local login only).
// Unrecognised values keep the previous default (enabled).
$keycloakEnabledEnv = getenv('KEYCLOAK_ENABLED');
define(
    'KEYCLOAK_ENABLED',
    ($keycloakEnabledEnv === false || trim($keycloakEnabledEnv) === '')
        ? true
        : (filter_var(trim($keycloakEnabledEnv), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true)
);
unset($keycloakEnabledEnv);
define('KEYCLOAK_BASE_URL', getenv('KEYCLOAK_BASE_URL') ?: 'https://auth.digil.me');
define('KEYCLOAK_REALM', getenv('KEYCLOAK_REALM') ?: 'jmd');
define('KEYCLOAK_CLIENT_ID', getenv('KEYCLOAK_CLIENT_ID') ?: 'starthere-appclient-prod');
define('KEYCLOAK_CLIENT_SECRET', getenv('KEYCLOAK_CLIENT_SECRET'));
define('KEYCLOAK_REDIRECT_URI', getenv('KEYCLOAK_REDIRECT_URI') ?: 'http://localhost:8080');
// KEYCLOAK_LINK_REQUIRE_VERIFIED: a Keycloak/JMD login that matches an existing Probenplaner
// account only by e-mail (no matching keycloak_id) links that account only if Keycloak reports
// email_verified === true. Otherwise anyone who can register in the realm with someone else's
// address would take over that person's account.
// Unset or empty => true (check on). "false"/"0"/"no"/"off" => link by e-mail without the check
// (only for realms that verify every e-mail but do not send the email_verified claim).
// Unrecognised values keep the check on.
$keycloakLinkVerifiedEnv = getenv('KEYCLOAK_LINK_REQUIRE_VERIFIED');
define(
    'KEYCLOAK_LINK_REQUIRE_VERIFIED',
    ($keycloakLinkVerifiedEnv === false || trim($keycloakLinkVerifiedEnv) === '')
        ? true
        : (filter_var(trim($keycloakLinkVerifiedEnv), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true)
);
unset($keycloakLinkVerifiedEnv);

// JOinJMD partner API (/api/v1, see docs/partner-api.md)
// PARTNER_API_TOKEN: shared secret the JOinJMD server sends as "Authorization: Bearer <token>".
// Unset or empty => the API is off and every /api/v1 route answers 404.
// PARTNER_API_ORG: slug of the organization new orchestras from the API are created in.
define('PARTNER_API_TOKEN', (string)(getenv('PARTNER_API_TOKEN') ?: ''));
define('PARTNER_API_ORG', (string)(getenv('PARTNER_API_ORG') ?: ''));

// JOinJMD push notifications: endpoint and secret of the JOinJMD push API.
// Unset or empty URL => no push, the reminder action is hidden.
define('JMD_PUSH_URL', (string)(getenv('JMD_PUSH_URL') ?: ''));
define('JMD_PUSH_TOKEN', (string)(getenv('JMD_PUSH_TOKEN') ?: ''));
