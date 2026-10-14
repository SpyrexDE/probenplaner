<?php

namespace App\Core;

use App\Models\User;

/**
 * Login with the Keycloak access token the JOinJMD app appends to every URL it opens in the
 * Probenplaner tile (?jmd_accesstoken=...): the tile entry (/ and /login, see JmdLaunch) and
 * links from push notifications to any other page.
 */
class JmdTokenLogin
{
    public const PARAM = 'jmd_accesstoken';

    /** Removed from deep links; a refresh token is never accepted */
    private const STRIPPED_PARAMS = [self::PARAM, 'jmd_refreshtoken', 'jmd_refresh_token', 'refresh_token'];

    /**
     * The token of this request, or null (also when Keycloak login is off on this instance).
     */
    public static function requestToken(): ?string
    {
        if (!KEYCLOAK_ENABLED) {
            return null;
        }
        $token = $_GET[self::PARAM] ?? $_POST[self::PARAM] ?? null;
        return (is_string($token) && $token !== '') ? $token : null;
    }

    /**
     * Validate the token and find or create its user. The session is not started yet
     * (LoginSession::start), but a different user's session data is already dropped.
     *
     * @return array{user: array, same_user: bool}|string Result, or the error message
     */
    public static function authenticate(string $token)
    {
        // Request comes from the JOinJMD app WebView: remember it for the whole session
        // (survives session_regenerate_id) so app-only UI like the PWA install card is hidden.
        $_SESSION['embedded_app'] = 'joinjmd';

        error_log("JMD token login attempt");

        $userInfo = (new KeycloakAuth())->loginWithToken($token);
        if (!$userInfo) {
            return 'JMD Token ist ungültig oder abgelaufen. Bitte melden Sie sich erneut an.';
        }

        $user = (new User())->createOrLinkKeycloakUser($userInfo, true);
        if (!$user || isset($user['error'])) {
            return 'Fehler beim Erstellen des Benutzerkontos: ' . ($user['message'] ?? 'Unbekannter Fehler');
        }

        // Same user relaunching the tile: keep session id and CSRF token, other open tabs still use them
        $sameUser = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$user['id'];

        // Another person in the same WebView (shared cookie jar): drop the previous user's context
        if (isset($_SESSION['user_id']) && !$sameUser) {
            $_SESSION = ['embedded_app' => 'joinjmd'];
        }

        return ['user' => $user, 'same_user' => $sameUser];
    }

    /**
     * Deep link from JOinJMD (e.g. a push notification) to any page but / and /login:
     * log in with the token, then reload the same page without it.
     * Called before routing; returns without output if the request has no token.
     */
    public static function handleDeepLink(string $path): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }
        $token = self::requestToken();
        if ($token === null) {
            return;
        }
        $route = $path === '/' ? $path : rtrim($path, '/');
        if ($route === '/' || $route === '/login' || str_starts_with($route, '/api/v1/')) {
            return;
        }

        $result = self::authenticate($token);
        if (is_array($result)) {
            LoginSession::start($result['user'], $result['same_user']);
        } elseif (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_messages'][] = ['type' => 'error', 'message' => $result, 'details' => null];
            header('Location: /login', true, 303);
            exit;
        }
        // Token invalid but still logged in (e.g. the app relaunched with an expired token): just open the page

        header('Location: ' . self::cleanUrl($path), true, 303);
        exit;
    }

    /**
     * The request path with its query minus the token parameters. Only a local path.
     */
    private static function cleanUrl(string $path): string
    {
        if ($path === '' || $path[0] !== '/' || (isset($path[1]) && ($path[1] === '/' || $path[1] === '\\'))) {
            return '/';
        }

        $kept = [];
        foreach (explode('&', (string)($_SERVER['QUERY_STRING'] ?? '')) as $pair) {
            if ($pair === '') {
                continue;
            }
            // Parameter name as PHP reads it ("jmd.accesstoken" and "jmd_accesstoken[]" fill $_GET['jmd_accesstoken'] too)
            $name = explode('[', urldecode(explode('=', $pair, 2)[0]), 2)[0];
            if (in_array(strtr(ltrim($name, ' '), '. ', '__'), self::STRIPPED_PARAMS, true)) {
                continue;
            }
            $kept[] = $pair;
        }

        return $path . ($kept ? '?' . implode('&', $kept) : '');
    }
}
