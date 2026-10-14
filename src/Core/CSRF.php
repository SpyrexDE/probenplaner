<?php
namespace App\Core;

/**
 * CSRF Protection Class
 * Provides Cross-Site Request Forgery protection
 */
class CSRF
{
    const TOKEN_NAME = 'csrf_token';
    const TOKEN_LENGTH = 32;

    /** Methods that never change state */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Routes that authenticate with their own token instead of the session cookie.
     * Compared with the registered route pattern, not the URL, so an organisation slug
     * like "ical" cannot switch the check off for its ensemble routes.
     * caldav/ and debug/ have their own entry points and do not reach the router.
     * /auth/keycloak/token is posted by external clients without a session token. It is
     * no wider than GET /?jmd_accesstoken=..., which logs in with a token as well.
     * /api/v1/ is the JOinJMD partner API: bearer token, no session (PartnerApiController).
     */
    private const EXEMPT_ROUTE_PREFIXES = ['/ical/', '/api/v1/'];
    private const EXEMPT_ROUTES = ['/auth/keycloak/token'];
    
    /**
     * Generate a new CSRF token for the session. The token stays the same for the whole
     * session (pages send several requests with it); only a login replaces it.
     * 
     * @return string Generated token
     */
    public static function generateToken()
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;
        return $token;
    }
    
    /**
     * Get current CSRF token
     * 
     * @return string Current token or generates new one if none exists
     */
    public static function getToken()
    {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return self::generateToken();
        }
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if token is valid
     */
    public static function validateToken($token)
    {
        if (!isset($_SESSION[self::TOKEN_NAME]) || !is_string($token)) {
            return false;
        }

        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }
    
    /**
     * Validate the token sent with the request (csrf_token field or X-CSRF-Token header)
     * 
     * @return bool True if token is valid
     */
    public static function validatePostToken()
    {
        return self::validateRequest();
    }

    /**
     * Whether the request carries the session token, either in the X-CSRF-Token header
     * (added to fetch/XHR by the layout) or in the csrf_token form field.
     */
    public static function validateRequest(): bool
    {
        foreach ([$_SERVER['HTTP_X_CSRF_TOKEN'] ?? null, $_POST[self::TOKEN_NAME] ?? null] as $token) {
            if (is_string($token) && $token !== '' && self::validateToken($token)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Generate HTML input field for CSRF token
     * 
     * @return string HTML input field
     */
    public static function getTokenField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Middleware-style CSRF protection for controllers
     * Throws exception if token is invalid
     * 
     * @throws \Exception If CSRF token is invalid
     * @return void
     */
    public static function protect()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::validatePostToken()) {
                throw new \Exception('CSRF-Prüfung fehlgeschlagen. Bitte versuchen Sie es erneut.');
            }
        }
    }

    /**
     * Central check for every routed request (called by Router::dispatch before the
     * controller runs). State-changing methods need the session token; otherwise the
     * request ends here: 403 JSON for fetch/XHR, redirect back with a flash for forms.
     *
     * @param string $route Matched route pattern, e.g. '/{org_slug}/{orchestra_id}/promises/update'
     */
    public static function enforce(string $route): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, self::SAFE_METHODS, true) || self::isExempt($route) || self::validateRequest()) {
            return;
        }

        if (self::isScriptRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            // Clients show data.error or data.message, so both carry the readable text
            $message = 'Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.';
            echo json_encode([
                'success' => false,
                'error'   => $message,
                'message' => $message,
            ]);
            exit;
        }

        $_SESSION['flash_messages'][] = [
            'type'    => 'error',
            'message' => 'Sicherheitsprüfung fehlgeschlagen. Bitte versuchen Sie es erneut.',
            'details' => null,
        ];
        header('Location: ' . self::backUrl(), true, 303);
        exit;
    }

    private static function isExempt(string $route): bool
    {
        if (in_array($route, self::EXEMPT_ROUTES, true)) {
            return true;
        }
        foreach (self::EXEMPT_ROUTE_PREFIXES as $prefix) {
            if (strncmp($route, $prefix, strlen($prefix)) === 0) {
                return true;
            }
        }

        // JOinJMD token login (HomeController::index / AuthController::login handle it first)
        if (($route === '/' || $route === '/login') && KEYCLOAK_ENABLED) {
            $jmdToken = $_GET['jmd_accesstoken'] ?? $_POST['jmd_accesstoken'] ?? null;
            return is_string($jmdToken) && (bool)$jmdToken;
        }

        return false;
    }

    /**
     * fetch()/XHR rather than a form submit
     */
    private static function isScriptRequest(): bool
    {
        $server = fn(string $key): string => is_string($_SERVER[$key] ?? null) ? $_SERVER[$key] : '';
        $fetchMode = $server('HTTP_SEC_FETCH_MODE');

        return isset($_SERVER['HTTP_X_CSRF_TOKEN'])
            || strcasecmp($server('HTTP_X_REQUESTED_WITH'), 'XMLHttpRequest') === 0
            || stripos($server('HTTP_ACCEPT'), 'application/json') !== false
            || stripos($server('CONTENT_TYPE'), 'application/json') !== false
            || ($fetchMode !== '' && $fetchMode !== 'navigate');
    }

    /**
     * Path of the Referer if it is this site, otherwise '/'
     */
    private static function backUrl(): string
    {
        $parts = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''));
        if (!$parts || empty($parts['host'])) {
            return '/';
        }

        $host = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $path = $parts['path'] ?? '/';
        if (strcasecmp($host, (string)($_SERVER['HTTP_HOST'] ?? '')) !== 0 || !preg_match('~^/(?![/\\\\])~', $path)) {
            return '/';
        }

        return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }
}
