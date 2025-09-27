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
        // If already logged in, redirect to orchestra selection or main app
        if ($this->isLoggedIn()) {
            // Check if user has selected an orchestra
            if (isset($_SESSION['current_orchestra_id'])) {
                // Redirect based on role in current orchestra
                if ($_SESSION['current_role'] === 'conductor') {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises/admin');
                } else {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises');
                }
            } else {
                // No orchestra selected, go to orchestra selection
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
        
        // Authenticate user (orchestra-independent)
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
        
        // Set basic session variables (no orchestra context yet)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
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
        
        // Redirect to orchestra selection
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
            // Check if user has selected an orchestra
            if (isset($_SESSION['current_orchestra_id'])) {
                // Redirect based on role in current orchestra
                if ($_SESSION['current_role'] === 'conductor') {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises/admin');
                } else {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises');
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
        $username = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // For debugging - log registration attempt
        error_log("Registration attempt - Username: $username");
        
        // Validate required fields
        $requiredValidation = Validator::validateRequired([
            'username' => $username,
            'password' => $password,
            'password_confirm' => $passwordConfirm
        ], ['username', 'password', 'password_confirm']);
        
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
        
        $validation = Validator::mergeResults([$usernameValidation, $passwordValidation]);
        
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
        
        // Register the user
        $result = $this->userModel->register($username, $password);
        
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
        setcookie("password", "", $cookieOptions);
        
        $this->redirect('/login');
    }
    
} 