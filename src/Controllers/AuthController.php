<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\CSRF;
use App\Models\User;
use App\Models\Orchestra;

/**
 * Authentication Controller
 * Handles user authentication and registration
 */
class AuthController extends Controller
{
    /**
     * @var User
     */
    private $userModel;

    /**
     * @var Orchestra
     */
    private $orchestraModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->orchestraModel = new Orchestra();
    }

    /**
     * Display login form
     * 
     * @return void
     */
    public function login()
    {
        // Check for JMD access token first (for mobile app integration)
        $jmdToken = $_GET['jmd_accesstoken'] ?? $_POST['jmd_accesstoken'] ?? null;
        if ($jmdToken && KEYCLOAK_ENABLED) {
            $this->processJmdTokenLogin($jmdToken);
            return;
        }

        // If already logged in, redirect to orchestra selection or main app
        if ($this->isLoggedIn()) {
            // Admin → admin dashboard
            if (!empty($_SESSION['is_super_admin'])) {
                $this->redirect('/admin/dashboard');
                return;
            }

            // Orga-admin → orga panel
            if (!empty($_SESSION['is_org_admin'])) {
                $this->redirect('/orga');
                return;
            }

            if (isset($_SESSION['current_orchestra_slug'])) {
                if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
                    $this->redirect($this->orchestraUrl('/promises/admin'));
                } else {
                    $this->redirect($this->orchestraUrl('/promises'));
                }
            } else {
                $this->redirect('/orchestras/select');
            }
            return;
        }

        // If form submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
            return;
        }

        // Display login form
        $this->render('auth/login', [
            'currentPage' => 'login',
            'csrf_token' => $this->getCSRFToken()
        ]);
    }

    /**
     * Process login form submission
     * 
     * @return void
     */
    private function processLogin()
    {
        // CSRF protection
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/login');
            return;
        }
        // Validate input
        $email = Validator::sanitizeUtf8($_POST['username'] ?? $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $validation = Validator::validateRequired(['email' => $email, 'password' => $password], ['email', 'password']);

        if (!$validation['valid']) {
            $this->addAlert(
                'Fehler!',
                implode(' ', $validation['errors']),
                'error'
            );
            $this->redirect('/login');
            return;
        }

        // Super-admin: authenticate against ADMIN_PW
        if ($email === 'admin') {
            $adminPw = defined('ADMIN_PW') ? ADMIN_PW : ($_ENV['ADMIN_PW'] ?? '');
            if ($adminPw !== '' && hash_equals($adminPw, $password)) {
                $_SESSION['user_id'] = 0;
                $_SESSION['is_super_admin'] = true;
                session_regenerate_id(true);
                $this->setFlash('success', 'Admin-Login erfolgreich.');
                $this->redirect('/admin/dashboard');
                return;
            }
            $this->addAlert('Fehler!', 'Das eingegebene Passwort ist falsch.', 'error');
            $this->redirect('/login');
            return;
        }

        $user = $this->userModel->authenticate($email, $password);

        if ($user) {
            $this->processSuccessfulLogin($user);
            return;
        }

        $userExists = $this->userModel->findByEmail($email);
        if ($userExists) {
            $this->addAlert(
                'Fehler!',
                'Das eingegebene Passwort ist falsch.',
                'error',
                'Bitte überprüfen Sie Ihr Passwort. Falls Sie Ihr Passwort vergessen haben, kontaktieren Sie bitte Ihren Dirigenten.'
            );
            error_log("Login failed - Wrong password attempt");
        } else {
            $this->addAlert(
                'Fehler!',
                'Diese E-Mail-Adresse wurde nicht gefunden.',
                'error',
                'Bitte überprüfen Sie Ihre E-Mail-Adresse oder registrieren Sie sich, falls Sie noch kein Konto haben.'
            );
            error_log("Login failed - Email not found: $email");
        }
        $this->redirect('/login');
    }

    /**
     * Handle successful login
     * 
     * @param array $user User data
     * @return void
     */
    private function processSuccessfulLogin($user)
    {
        if (!$user || !isset($user['id']) || !isset($user['email'])) {
            error_log("processSuccessfulLogin: Invalid user data: " . json_encode($user));
            $this->addAlert('Fehler!', 'Benutzerdaten konnten nicht geladen werden.', 'error');
            $this->redirect('/login');
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['display_name'] = $user['display_name'] ?? '';

        session_regenerate_id(true);

        if (!isset($_SESSION['user_id'])) {
            error_log("processSuccessfulLogin: Session data lost after regenerate_id. Re-setting...");
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['display_name'] = $user['display_name'] ?? '';
        }

        $this->setFlash('success', 'Sie wurden erfolgreich eingeloggt.');

        // Org-admin → orga panel
        if (!empty($user['is_org_admin'])) {
            $_SESSION['is_org_admin'] = true;
            $_SESSION['organization_id'] = $user['organization_id'];
            $this->redirect('/orga');
            return;
        }

        // Regular user → orchestra selection
        $this->redirect('/orchestras/select');
    }

    /**
     * Display registration form
     * 
     * @return void
     */
    public function showRegisterForm()
    {
        // If already logged in, redirect to orchestra selection or main app
        if ($this->isLoggedIn()) {
            if (isset($_SESSION['current_orchestra_slug'])) {
                if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
                    $this->redirect($this->orchestraUrl('/promises/admin'));
                } else {
                    $this->redirect($this->orchestraUrl('/promises'));
                }
            } else {
                // No orchestra selected, go to orchestra selection
                $this->redirect('/orchestras/select');
            }
            return;
        }

        // Display registration form
        $this->render('auth/register', [
            'currentPage' => 'register',
            'csrf_token' => $this->getCSRFToken()
        ]);
    }

    /**
     * Process registration (orchestra-independent)
     * 
     * @return void
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }

        // CSRF protection
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/register');
            return;
        }

        // Get and sanitize POST data
        $email = Validator::sanitizeUtf8($_POST['email'] ?? '');
        $displayName = Validator::sanitizeUtf8($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        error_log("Registration attempt - Email: $email");

        $requiredValidation = Validator::validateRequired([
            'email' => $email,
            'display_name' => $displayName,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
        ], ['email', 'display_name', 'password', 'password_confirm']);

        if (!$requiredValidation['valid']) {
            $this->addAlert(
                'Fehler!',
                implode(' ', $requiredValidation['errors']),
                'error'
            );
            error_log("Registration failed - Missing required fields");
            $this->redirect('/register');
            return;
        }

        $emailValidation = Validator::validateEmail($email);
        $displayNameValidation = Validator::validateDisplayName($displayName);
        $passwordValidation = Validator::validatePassword($password, $passwordConfirm);

        $validation = Validator::mergeResults([$emailValidation, $displayNameValidation, $passwordValidation]);

        if (!$validation['valid']) {
            $this->addAlert(
                'Fehler!',
                implode(', ', $validation['errors']),
                'error'
            );
            error_log("Registration failed - Validation errors: " . implode(', ', $validation['errors']));
            $this->redirect('/register');
            return;
        }

        $result = $this->userModel->register($email, $displayName, $password);

        if (is_array($result)) {
            // Handle different array response formats
            if (isset($result['messages'])) {
                // ErrorHandler format with 'messages' array
                $message = is_array($result['messages']) ? implode(', ', $result['messages']) : $result['messages'];
                $details = $result['data'] ?? '';
            } else {
                // Direct format with 'message' string
                $message = $result['message'] ?? 'Bei der Registrierung ist ein Fehler aufgetreten.';
                $details = $result['details'] ?? '';
            }

            $this->addAlert(
                'Fehler!',
                $message,
                'error',
                is_array($details) ? json_encode($details) : $details
            );
            $this->redirect('/register');
            return;
        }

        if (is_int($result) && $result > 0) {
            $this->setFlash('success', 'Ihr Konto wurde erfolgreich erstellt. Sie können sich jetzt anmelden.');
            $this->redirect('/login');
        } else {
            // Handle unexpected non-array, non-integer results
            $this->addAlert(
                'Fehler!',
                'Bei der Registrierung ist ein Fehler aufgetreten.',
                'error',
                'Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.'
            );
            $this->redirect('/register');
        }
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public function logout()
    {
        // Destroy session
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        // Clear cookies securely
        $cookieOptions = [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => (APP_ENV !== 'development' && APP_ENV !== 'test'),
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        setcookie("username", "", $cookieOptions);

        $this->redirect('/login');
    }

    /**
     * Initiate Keycloak login
     * 
     * @return void
     */
    public function keycloakLogin()
    {
        if (!KEYCLOAK_ENABLED) {
            $this->addAlert('Fehler!', 'Keycloak-Anmeldung ist nicht verfügbar.', 'error');
            $this->redirect('/login');
            return;
        }

        $keycloakAuth = new \App\Core\KeycloakAuth();
        $authUrl = $keycloakAuth->getAuthUrl();

        // Debug logging
        error_log("Keycloak auth URL: " . $authUrl);
        error_log("Keycloak config - Client ID: " . KEYCLOAK_CLIENT_ID . ", Redirect URI: " . KEYCLOAK_REDIRECT_URI);

        $this->redirect($authUrl);
    }

    /**
     * Handle Keycloak callback
     * 
     * @return void
     */
    public function keycloakCallback()
    {
        if (!KEYCLOAK_ENABLED) {
            $this->addAlert('Fehler!', 'Keycloak-Anmeldung ist nicht verfügbar.', 'error');
            $this->redirect('/login');
            return;
        }

        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        // Debug logging
        error_log("Keycloak callback received. Code: " . ($code ? 'present' : 'missing') . ", State: " . ($state ? 'present' : 'missing'));
        error_log("GET parameters: " . json_encode($_GET));
        error_log("Session keycloak_state: " . (isset($_SESSION['keycloak_state']) ? $_SESSION['keycloak_state'] : 'NOT SET'));
        error_log("Session ID: " . session_id());

        // Validate state parameter
        if (!$state || !isset($_SESSION['keycloak_state']) || $state !== $_SESSION['keycloak_state']) {
            error_log("Keycloak state validation failed. URL state: $state, Session state: " . ($_SESSION['keycloak_state'] ?? 'NOT SET'));
            $this->addAlert('Sicherheitsfehler!', 'Ungültiger State-Parameter. Bitte versuchen Sie es erneut.', 'error');
            $this->redirect('/login');
            return;
        }

        if (!$code) {
            $this->addAlert('Fehler!', 'Autorisierungscode fehlt.', 'error');
            $this->redirect('/login');
            return;
        }

        $keycloakAuth = new \App\Core\KeycloakAuth();

        // Exchange code for token
        $tokenData = $keycloakAuth->exchangeCodeForToken($code);
        if (!$tokenData || !isset($tokenData['access_token'])) {
            error_log("Keycloak token exchange failed. Code: $code, Token data: " . json_encode($tokenData));
            $this->addAlert('Fehler!', 'Token-Austausch fehlgeschlagen.', 'error');
            $this->redirect('/login');
            return;
        }

        // Get user info
        $userInfo = $keycloakAuth->getUserInfo($tokenData['access_token']);
        if (!$userInfo) {
            $this->addAlert('Fehler!', 'Benutzerinformationen konnten nicht abgerufen werden.', 'error');
            $this->redirect('/login');
            return;
        }

        // Create or link user
        $user = $this->userModel->createOrLinkKeycloakUser($userInfo);
        if (isset($user['error'])) {
            error_log("keycloakCallback: createOrLinkKeycloakUser failed: " . json_encode($user));
            $this->addAlert('Fehler!', $user['message'] ?? 'Benutzerkonto konnte nicht erstellt werden.', 'error');
            $this->redirect('/login');
            return;
        }

        // Additional validation: ensure user data is complete
        if (!$user || !isset($user['id']) || !isset($user['email'])) {
            error_log("keycloakCallback: Invalid user data after createOrLinkKeycloakUser: " . json_encode($user));
            $this->addAlert('Fehler!', 'Benutzerdaten konnten nicht geladen werden.', 'error');
            $this->redirect('/login');
            return;
        }

        // Process successful login
        $this->processSuccessfulLogin($user);
    }

    /**
     * Handle Keycloak login with access token (for mobile app integration)
     * 
     * @return void
     */
    public function keycloakTokenLogin()
    {
        if (!KEYCLOAK_ENABLED) {
            $this->addAlert('Fehler!', 'Keycloak-Anmeldung ist nicht verfügbar.', 'error');
            $this->redirect('/login');
            return;
        }

        $accessToken = $_POST['access_token'] ?? null;

        if (!$accessToken) {
            $this->addAlert('Fehler!', 'Access Token fehlt.', 'error');
            $this->redirect('/login');
            return;
        }

        $keycloakAuth = new \App\Core\KeycloakAuth();

        // Process login with token
        $userInfo = $keycloakAuth->loginWithToken($accessToken);
        if (!$userInfo) {
            $this->addAlert('Fehler!', 'Token-Validierung fehlgeschlagen.', 'error');
            $this->redirect('/login');
            return;
        }

        // Create or link user
        $user = $this->userModel->createOrLinkKeycloakUser($userInfo);
        if (isset($user['error'])) {
            $this->addAlert('Fehler!', $user['message'], 'error');
            $this->redirect('/login');
            return;
        }

        // Process successful login
        $this->processSuccessfulLogin($user);
    }

    /**
     * Process JMD token login (for mobile app integration)
     * 
     * @param string $jmdToken JMD access token
     * @return void
     */
    private function processJmdTokenLogin(string $jmdToken)
    {
        error_log("JMD token login attempt with token: " . substr($jmdToken, 0, 20) . "...");

        $keycloakAuth = new \App\Core\KeycloakAuth();

        // Process login with token
        $userInfo = $keycloakAuth->loginWithToken($jmdToken);
        if (!$userInfo) {
            $this->setFlash('error', 'JMD Token ist ungültig oder abgelaufen. Bitte melden Sie sich erneut an.');
            $this->redirect('/login');
            return;
        }

        // Create or link user
        $user = $this->userModel->createOrLinkKeycloakUser($userInfo, true);
        if (isset($user['error'])) {
            $this->setFlash('error', 'Fehler beim Erstellen des Benutzerkontos: ' . $user['message']);
            $this->redirect('/login');
            return;
        }

        // Process successful login
        $this->processSuccessfulLogin($user);
    }
}
