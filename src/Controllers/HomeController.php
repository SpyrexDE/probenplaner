<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Home Controller
 * Handles the main page
 */
class HomeController extends Controller
{
    /**
     * Main page
     * 
     * @return void
     */
    public function index()
    {
        // Check for JMD access token first (for mobile app integration)
        $jmdToken = $_GET['jmd_accesstoken'] ?? $_POST['jmd_accesstoken'] ?? null;
        if ($jmdToken && KEYCLOAK_ENABLED) {
            $this->processJmdTokenLogin($jmdToken);
            return;
        }

        // If logged in, redirect to orchestra selection or main app
        if ($this->isLoggedIn()) {
            // Admin → admin dashboard
            if (($_SESSION['username'] ?? '') === 'admin') {
                $this->redirect('/admin/dashboard');
                return;
            }

            // Orga-admin → orga panel
            if (!empty($_SESSION['is_org_admin'])) {
                $this->redirect('/orga');
                return;
            }

            // Onboarding only for brand-new users without any orchestra
            $userModel = new \App\Models\User();
            $user = $userModel->findById((int)$_SESSION['user_id']);
            if ($user && empty($user['display_name'])) {
                $uoModel = new \App\Models\UserOrchestra();
                $orchestras = $uoModel->getUserOrchestras((int)$_SESSION['user_id']);
                if (empty($orchestras)) {
                    $this->redirect('/onboarding');
                    return;
                }
            }

            if (isset($_SESSION['current_orchestra_slug'])) {
                if (!empty($_SESSION['current_permissions']['can_manage_rehearsals'])) {
                    $this->redirect($this->orchestraUrl('/promises/admin'));
                } else {
                    $this->redirect($this->orchestraUrl('/promises'));
                }
            } else {
                $this->redirect('/orchestras/select');
            }
            return;
        }

        // Not logged in, show login page
        $this->redirect('/login');
    }

    /**
     * Process JMD token login (for mobile app integration)
     * 
     * @param string $jmdToken JMD access token
     * @return void
     */
    private function processJmdTokenLogin(string $jmdToken)
    {
        error_log("HomeController: JMD token login attempt with token: " . substr($jmdToken, 0, 20) . "...");

        $keycloakAuth = new \App\Core\KeycloakAuth();

        // Process login with token
        $userInfo = $keycloakAuth->loginWithToken($jmdToken);
        if (!$userInfo) {
            // Set error message and redirect to login
            $this->setFlash('error', 'JMD Token ist ungültig oder abgelaufen. Bitte melden Sie sich erneut an.');
            $this->redirect('/login');
            return;
        }

        // Create or link user
        $userModel = new \App\Models\User();
        $user = $userModel->createOrLinkKeycloakUser($userInfo, true);
        if (isset($user['error'])) {
            $this->setFlash('error', 'Fehler beim Erstellen des Benutzerkontos: ' . $user['message']);
            $this->redirect('/login');
            return;
        }

        // Process successful login
        $this->processSuccessfulLogin($user);
    }

    /**
     * Handle successful login
     * 
     * @param array $user User data
     * @return void
     */
    private function processSuccessfulLogin($user)
    {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Set basic session variables (no orchestra context yet)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'] ?? '';

        // Set secure cookies
        $cookieOptions = [
            'expires' => time() + COOKIE_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => (APP_ENV !== 'development' && APP_ENV !== 'test'), // Only over HTTPS in production
            'httponly' => true, // Prevent XSS attacks
            'samesite' => 'Strict' // CSRF protection
        ];
        setcookie("username", $user['username'], $cookieOptions);

        $this->setFlash('success', 'Sie wurden erfolgreich eingeloggt.');

        // Redirect to orchestra selection
        $this->redirect('/orchestras/select');
    }
}
