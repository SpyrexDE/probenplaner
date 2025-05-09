<?php
namespace App\Controllers;

use App\Core\Controller;
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
            'currentPage' => 'login'
        ]);
    }
    
    /**
     * Process login form submission
     * 
     * @return void
     */
    private function processLogin()
    {
        // Validate input
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        if (empty($username) || empty($password)) {
            $missingFields = array_filter([
                empty($username) ? 'Benutzername' : null,
                empty($password) ? 'Passwort' : null
            ]);
            $this->addAlert(
                'Fehler!', 
                'Bitte füllen Sie alle erforderlichen Felder aus.', 
                'error',
                'Fehlende Felder: ' . implode(', ', $missingFields)
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
            error_log("Login failed - Wrong password for user: $username");
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
        
        // Set cookies for 7 days
        setcookie("username", $user['username'], time() + 604800);
        // Do not store password in cookie for security reasons
        
        $this->addAlert('Willkommen!', 'Sie wurden erfolgreich eingeloggt.', 'success');
        
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
            'typeStructure' => $this->getTypeStructure()
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
        
        // Get POST data
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $type = trim($_POST['type'] ?? '');
        $token = trim($_POST['token'] ?? '');
        
        // For debugging - log registration attempt
        error_log("Registration attempt - Username: $username, Type: $type, Token: $token");
        
        // Validate inputs
        if (empty($username) || empty($password) || empty($passwordConfirm) || empty($type) || empty($token)) {
            $missingFields = array_filter([
                empty($username) ? 'Benutzername' : null,
                empty($password) ? 'Passwort' : null,
                empty($passwordConfirm) ? 'Passwort bestätigen' : null,
                empty($type) ? 'Instrument/Rolle' : null,
                empty($token) ? 'Orchester-Token' : null
            ]);
            $this->addAlert(
                'Fehler!', 
                'Bitte füllen Sie alle erforderlichen Felder aus.', 
                'error',
                'Fehlende Felder: ' . implode(', ', $missingFields)
            );
            error_log("Registration failed - Empty fields: " . implode(', ', $missingFields));
            $this->redirect('/register');
            return;
        }
        
        if ($password !== $passwordConfirm) {
            $this->addAlert(
                'Fehler!', 
                'Die Passwörter stimmen nicht überein.', 
                'error',
                'Die eingegebenen Passwörter sind unterschiedlich. Bitte stellen Sie sicher, dass Sie das gleiche Passwort zweimal eingeben.'
            );
            error_log("Registration failed - Passwords don't match");
            $this->redirect('/register');
            return;
        }
        
        // Password requirements
        $passwordErrors = [];
        if (strlen($password) < 8) {
            $passwordErrors[] = 'mindestens 8 Zeichen';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $passwordErrors[] = 'mindestens ein Großbuchstabe';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $passwordErrors[] = 'mindestens ein Kleinbuchstabe';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $passwordErrors[] = 'mindestens eine Zahl';
        }
        
        if (!empty($passwordErrors)) {
            $this->addAlert(
                'Fehler!', 
                'Das Passwort muss mindestens 8 Zeichen lang sein und mindestens einen Großbuchstaben, einen Kleinbuchstaben und eine Zahl enthalten.', 
                'error',
                'Das Passwort muss folgende Anforderungen erfüllen: ' . implode(', ', $passwordErrors)
            );
            error_log("Registration failed - Password requirements not met: " . implode(', ', $passwordErrors));
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
            $this->addAlert('Erfolg!', 'Ihr Konto wurde erfolgreich erstellt. Sie können sich jetzt anmelden.', 'success');
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
        
        // Clear cookies
        setcookie("username", "", time() - 3600);
        setcookie("password", "", time() - 3600);
        
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