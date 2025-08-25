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
        // If already logged in, redirect
        if ($this->isLoggedIn()) {
            // Redirect based on role
            if ($_SESSION['role'] === 'conductor') {
                $this->redirect('/promises/admin');
            } else {
                $this->redirect('/promises');
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
        $username = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $validation = Validator::validateRequired(['username' => $username, 'password' => $password], ['username', 'password']);
        
        if (!$validation['valid']) {
            $this->addAlert(
                'Fehler!', 
                implode(' ', $validation['errors']), 
                'error'
            );
            $this->redirect('/login');
            return;
        }
        
        // First try to authenticate with any orchestra
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            // Login successful
            $this->processSuccessfulLogin($user);
            return;
        }
        
        // Check if user exists to provide more specific error message
        $userExists = $this->userModel->findByUsername($username);
        if ($userExists) {
            $this->addAlert(
                'Fehler!', 
                'Das eingegebene Passwort ist falsch.', 
                'error',
                'Bitte überprüfen Sie Ihr Passwort. Falls Sie Ihr Passwort vergessen haben, kontaktieren Sie bitte Ihren Dirigenten.'
            );
            // Log failed login without exposing username for privacy
            error_log("Login failed - Wrong password attempt");
        } else {
            $this->addAlert(
                'Fehler!', 
                'Der Benutzername wurde nicht gefunden.', 
                'error',
                'Bitte überprüfen Sie Ihren Benutzernamen oder registrieren Sie sich, falls Sie noch kein Konto haben.'
            );
            error_log("Login failed - Username not found: $username");
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
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        // Get orchestra
        $orchestra = $this->orchestraModel->findById($user['orchestra_id']);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['orchestra_id'] = $user['orchestra_id'];
        $_SESSION['orchestra_name'] = $orchestra['name'];
        $_SESSION['is_small_group'] = isset($user['is_small_group']) && $user['is_small_group'] ? true : false;
        
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
        // Do not store password in cookie for security reasons
        
        $this->setFlash('success', 'Sie wurden erfolgreich eingeloggt.');
        
        // Redirect based on role
        if ($user['role'] === 'conductor') {
            $this->redirect('/promises/admin');
        } else {
            $this->redirect('/promises');
        }
    }
    
    /**
     * Display registration form
     * 
     * @return void
     */
    public function showRegisterForm()
    {
        // If already logged in, redirect
        if ($this->isLoggedIn()) {
            // Redirect based on role
            if ($_SESSION['role'] === 'conductor') {
                $this->redirect('/promises/admin');
            } else {
                $this->redirect('/promises');
            }
            return;
        }
        
        // Display registration form
        $this->render('auth/register', [
            'currentPage' => 'register',
            'typeStructure' => $this->getTypeStructure(),
            'csrf_token' => $this->getCSRFToken()
        ]);
    }
    
    /**
     * Process registration
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
        $username = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $type = Validator::sanitizeUtf8($_POST['type'] ?? '');
        $token = Validator::sanitizeUtf8($_POST['token'] ?? '');
        
        // For debugging - log registration attempt
        error_log("Registration attempt - Type: $type, Token: $token");
        
        // Validate required fields
        $requiredValidation = Validator::validateRequired([
            'username' => $username,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
            'type' => $type,
            'token' => $token
        ], ['username', 'password', 'password_confirm', 'type', 'token']);
        
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
        
        // Validate individual field formats
        $usernameValidation = Validator::validateUsername($username);
        $passwordValidation = Validator::validatePassword($password, $passwordConfirm);
        $tokenValidation = Validator::validateToken($token);
        
        $validation = Validator::mergeResults([$usernameValidation, $passwordValidation, $tokenValidation]);
        
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
        
        // Find the orchestra by token
        $orchestra = $this->orchestraModel->findByToken($token);
        
        if (!$orchestra) {
            $this->addAlert(
                'Fehler!', 
                'Der eingegebene Orchester-Token ist ungültig.', 
                'error',
                'Der Token wurde nicht gefunden. Bitte überprüfen Sie den Token oder kontaktieren Sie Ihren Dirigenten für den korrekten Token.'
            );
            error_log("Registration failed - Invalid token: $token");
            $this->redirect('/register');
            return;
        }
        
        error_log("Found orchestra: " . json_encode($orchestra));
        $orchestraId = (int)$orchestra['id'];
        
        // Register the user
        $result = $this->userModel->register($username, $password, $type, $orchestraId);
        
        if (is_array($result) && isset($result['error'])) {
            $this->addAlert(
                'Fehler!', 
                $result['message'], 
                'error',
                $result['details']
            );
            error_log("Registration failed: " . $result['message'] . " - " . $result['details']);
            $this->redirect('/register');
            return;
        }
        
        if ($result) {
            $this->setFlash('success', 'Ihr Konto wurde erfolgreich erstellt. Sie können sich jetzt anmelden.');
            error_log("Registration successful - User ID: $result");
            $this->redirect('/login?token=' . urlencode($token));
        } else {
            $this->addAlert(
                'Fehler!', 
                'Bei der Registrierung ist ein Fehler aufgetreten.', 
                'error',
                'Es gab ein unerwartetes technisches Problem bei der Registrierung. Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.'
            );
            error_log("Registration failed - Unexpected error");
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
        setcookie("password", "", $cookieOptions);
        
        $this->redirect('/login');
    }
    
    /**
     * Get instrument/section type structure
     * 
     * @return array
     */
    private function getTypeStructure()
    {
        return [
            'Streicher' => [
                'Violine_1',
                'Violine_2',
                'Bratsche',
                'Cello',
                'Kontrabass'
            ],
            'Holzbläser' => [
                'Flöte',
                'Oboe',
                'Klarinette',
                'Fagott'
            ],
            'Blechbläser' => [
                'Horn',
                'Trompete',
                'Posaune',
                'Tuba'
            ],
            'Andere' => [
                'Schlagwerk',
                'Pauke',
                'Harfe',
                'Klavier'
            ]
        ];
    }
} 