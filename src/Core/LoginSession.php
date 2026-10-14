<?php

namespace App\Core;

use App\Models\User;

/**
 * Starts the session of a user who has just authenticated (password, Keycloak, JOinJMD token).
 */
class LoginSession
{
    /**
     * @param array $user User row (id, email, display_name)
     * @param bool $keepSession The session is already logged in as this user (JOinJMD relaunch):
     *                          keep session id and CSRF token, other open tabs still use them
     */
    public static function start(array $user, bool $keepSession = false): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['display_name'] = $user['display_name'] ?? '';

        // Force theme re-fetch from DB on next page load
        unset($_SESSION['theme']);

        $embeddedApp = $_SESSION['embedded_app'] ?? null;

        if (!$keepSession) {
            session_regenerate_id(true);
            CSRF::generateToken();
        }

        if (!isset($_SESSION['user_id'])) {
            error_log("LoginSession: Session data lost after regenerate_id. Re-setting...");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['display_name'] = $user['display_name'] ?? '';
            if ($embeddedApp !== null) {
                $_SESSION['embedded_app'] = $embeddedApp;
            }
        }

        // Flag to prompt username change if display_name looks like an email
        if (str_contains($user['display_name'] ?? '', '@')) {
            $_SESSION['prompt_username_change'] = true;
        }

        (new User())->recordLogin((int)$user['id']);
    }
}
