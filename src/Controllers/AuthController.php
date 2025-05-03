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
            $this->addAlert('Fehler!', 'Bitte geben Sie sowohl Benutzername als auch Passwort ein.', 'error');
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
        
        // Login failed
        $this->addAlert('Fehler!', 'Ungültiger Benutzername oder Passwort.', 'error');
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
        // Ensure user has promises field
        if (!isset($user['promises'])) {
            $user['promises'] = '';
            $this->userModel->update($user['id'], ['promises' => '']);
            error_log("LOGIN WARNING: User {$user['username']} (ID: {$user['id']}) had no promises field, initialized to empty");
        }
        
        // Get orchestra
        $orchestra = $this->orchestraModel->findById($user['orchestra_id']);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['type'] = $user['type'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['orchestra_id'] = $user['orchestra_id'];
        $_SESSION['orchestra_name'] = $orchestra['name'];
        $_SESSION['promises'] = $user['promises'];
        
        // Log session data
        error_log("LOGIN: Session initialized with user_id: {$user['id']}, promises: " . ($user['promises'] ?? 'empty'));
        
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
            $this->addAlert('Fehler!', 'Alle Felder müssen ausgefüllt werden.', 'error');
            error_log("Registration failed - Empty fields");
            $this->redirect('/register');
            return;
        }
        
        if ($password !== $passwordConfirm) {
            $this->addAlert('Fehler!', 'Die Passwörter stimmen nicht überein.', 'error');
            error_log("Registration failed - Passwords don't match");
            $this->redirect('/register');
            return;
        }
        
        if (strlen($password) < 4) {
            $this->addAlert('Fehler!', 'Das Passwort muss mindestens 4 Zeichen lang sein.', 'error');
            error_log("Registration failed - Password too short");
            $this->redirect('/register');
            return;
        }
        
        // Find the orchestra by token
        $orchestra = $this->orchestraModel->findByToken($token);
        
        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Der eingegebene Token ist ungültig.', 'error');
            error_log("Registration failed - Invalid token: $token");
            $this->redirect('/register');
            return;
        }
        
        error_log("Found orchestra: " . json_encode($orchestra));
        $orchestraId = (int)$orchestra['id'];
        
        // Register the user
        $result = $this->userModel->register($username, $password, $type, $orchestraId);
        
        if ($result) {
            $this->addAlert('Erfolg!', 'Ihr Konto wurde erfolgreich erstellt. Sie können sich jetzt anmelden.', 'success');
            error_log("Registration successful - User ID: $result");
            $this->redirect('/login?token=' . urlencode($token));
        } else {
            $this->addAlert('Fehler!', 'Der Benutzername ist bereits vergeben oder ein Systemfehler ist aufgetreten.', 'error');
            error_log("Registration failed - Username exists or system error");
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