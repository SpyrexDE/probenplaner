<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Orchestra;
use App\Models\User;

/**
 * Orchestra Controller
 * Handles orchestra administration
 */
class OrchestraController extends Controller
{
    /**
     * @var Orchestra
     */
    private $orchestraModel;
    
    /**
     * @var User
     */
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->orchestraModel = new Orchestra();
        $this->userModel = new User();
    }
    
    /**
     * Display admin create orchestra form
     * 
     * @return void
     */
    public function create()
    {
        // Check if we have form data from a failed validation - this means admin was already verified
        $formData = [];
        $adminVerified = false;
        
        if (isset($_SESSION['orchestra_form_data'])) {
            $formData = $_SESSION['orchestra_form_data'];
            // Admin was already verified if we have form data
            $adminVerified = true;
            // Don't unset the form data here - we'll need it if redirected again
        }
        
        // Check if ADMIN password provided
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['admin_password']) && $_POST['admin_password'] === ADMIN_PW) {
                $adminVerified = true;
            } else {
                $this->addAlert('Fehler!', 'Falsches Admin-Passwort.', 'error');
            }
        }
        
        // If admin is verified, show the creation form
        if ($adminVerified) {
            $this->render('orchestras/create', [
                'currentPage' => 'create_orchestra',
                'admin_verified' => true,
                'formData' => $formData
            ]);
            return;
        }
        
        // Otherwise display admin password verification form
        $this->render('orchestras/admin_verify', [
            'currentPage' => 'create_orchestra'
        ]);
    }
    
    /**
     * Process orchestra creation
     * 
     * @return void
     */
    public function store()
    {
        // Activate custom error handler
        set_error_handler(function($severity, $message, $file, $line) {
            // Ignore directory permission errors
            if (strpos($message, 'mkdir') !== false && strpos($message, 'Permission denied') !== false) {
                return true; // Suppress this error
            }
            
            error_log("Orchestra creation error: $message in $file on line $line");
            return false;
        });
        
        // Check if form submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/orchestras/create');
            return;
        }
        
        // Validate input and sanitize for UTF-8
        $name = isset($_POST['name']) ? $this->sanitizeUtf8(trim($_POST['name'])) : '';
        $token = isset($_POST['token']) ? $this->sanitizeUtf8(trim($_POST['token'])) : '';
        $leaderPw = isset($_POST['leader_pw']) ? $this->sanitizeUtf8(trim($_POST['leader_pw'])) : '';
        $conductorUsername = isset($_POST['conductor_username']) ? $this->sanitizeUtf8(trim($_POST['conductor_username'])) : '';
        $conductorPassword = isset($_POST['conductor_password']) ? $this->sanitizeUtf8(trim($_POST['conductor_password'])) : '';
        
        // Store form data for repopulation on validation failure
        $formData = [
            'name' => $name,
            'token' => $token,
            'leader_pw' => $leaderPw,
            'conductor_username' => $conductorUsername,
            // Don't store password for security reasons
        ];
        
        // Log input
        error_log("Orchestra creation attempt - Name: $name, Token: $token, ConductorUser: $conductorUsername");
        
        // Add detailed validation
        $errors = [];
        
        // Validate orchestra data
        if (empty($name)) {
            $errors[] = "Orchestername fehlt";
        }
        
        if (empty($token)) {
            $errors[] = "Token fehlt";
        } elseif (strlen($token) < 2) {
            $errors[] = "Token muss mindestens 2 Zeichen lang sein";
        } elseif ($this->orchestraModel->findByToken($token)) {
            $errors[] = "Dieser Token wird bereits verwendet";
        }
        
        if (empty($leaderPw)) {
            $errors[] = "Stimmführer-Passwort fehlt";
        }
        
        // Validate conductor data using the User model's validation method
        $userValidation = $this->userModel->validateUserInput($conductorUsername, $conductorPassword);
        if (!$userValidation['valid']) {
            // Add conductor-specific prefix to error messages
            foreach ($userValidation['errors'] as $error) {
                $errors[] = "Dirigent: " . $error;
            }
        }
        
        // If validation errors, show them
        if (!empty($errors)) {
            // Store form data in session to repopulate the form
            $_SESSION['orchestra_form_data'] = $formData;
            
            $errorMsg = implode(", ", $errors);
            $this->addAlert('Fehler!', $errorMsg, 'error');
            $this->redirect('/orchestras/create');
            return;
        }
        
        try {
            // Create orchestra
            $orchestraData = [
                'name' => $name,
                'token' => $token,
                'leader_pw' => $leaderPw
            ];
            
            $orchestraId = $this->orchestraModel->createOrchestra($orchestraData);
            
            if (!$orchestraId) {
                throw new \Exception("Fehler beim Erstellen des Orchesters.");
            }
            
            // Create conductor
            $userResult = $this->userModel->register(
                $conductorUsername, 
                $conductorPassword,
                'Dirigent', 
                $orchestraId,
                'conductor'
            );
            
            if (is_array($userResult) && isset($userResult['error'])) {
                // Handle error from user creation
                // Rollback by deleting the orchestra
                $this->orchestraModel->delete($orchestraId);
                
                // Store form data in session to repopulate the form
                $_SESSION['orchestra_form_data'] = $formData;
                
                $this->addAlert('Fehler!', $userResult['message'], 'error', $userResult['details'] ?? '');
                $this->redirect('/orchestras/create');
                return;
            }
            
            $conductorId = $userResult;
            
            // Update orchestra with the conductor ID
            $this->orchestraModel->update($orchestraId, ['conductor_id' => $conductorId]);
            
            // Clear any stored form data on success
            if (isset($_SESSION['orchestra_form_data'])) {
                unset($_SESSION['orchestra_form_data']);
            }
            
            $this->addAlert('Erfolg!', 'Das Orchester wurde erfolgreich erstellt.', 'success');
            $this->redirect('/login');
            
        } catch (\Exception $e) {
            error_log("Exception during orchestra creation: " . $e->getMessage());
            
            // Store form data in session to repopulate the form
            $_SESSION['orchestra_form_data'] = $formData;
            
            $this->addAlert('Fehler!', 'Fehler bei der Erstellung: ' . $e->getMessage(), 'error');
            $this->redirect('/orchestras/create');
        } finally {
            // Restore original error handler
            restore_error_handler();
        }
    }
    
    /**
     * Display orchestra settings form (for conductor)
     * 
     * @return void
     */
    public function settings()
    {
        // Check if logged in as conductor
        if (!$this->isLoggedIn() || $_SESSION['role'] !== 'conductor') {
            $this->addAlert('Fehler!', 'Sie haben keine Berechtigung für diese Seite.', 'error');
            $this->redirect('/login');
            return;
        }
        
        // Get orchestra data
        $orchestra = $this->orchestraModel->findById($_SESSION['orchestra_id']);
        
        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Orchester nicht gefunden.', 'error');
            $this->redirect('/promises');
            return;
        }
        
        // Display settings form
        $this->render('orchestras/settings', [
            'currentPage' => 'orchestra_settings',
            'orchestra' => $orchestra
        ]);
    }
    
    /**
     * Update orchestra settings
     * 
     * @return void
     */
    public function update()
    {
        // Check if logged in as conductor
        if (!$this->isLoggedIn() || $_SESSION['role'] !== 'conductor') {
            $this->addAlert('Fehler!', 'Sie haben keine Berechtigung für diese Seite.', 'error');
            $this->redirect('/login');
            return;
        }
        
        // Check if form submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/orchestras/settings');
            return;
        }
        
        // Validate input
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $leaderPw = isset($_POST['leader_pw']) ? trim($_POST['leader_pw']) : '';
        
        if (empty($name) || empty($token) || empty($leaderPw)) {
            $this->addAlert('Fehler!', 'Alle Felder müssen ausgefüllt werden.', 'error');
            $this->redirect('/orchestras/settings');
            return;
        }
        
        // Get current orchestra
        $orchestra = $this->orchestraModel->findById($_SESSION['orchestra_id']);
        
        // Check token uniqueness (only if changed)
        if ($token !== $orchestra['token'] && $this->orchestraModel->findByToken($token)) {
            $this->addAlert('Fehler!', 'Dieser Token wird bereits verwendet.', 'error');
            $this->redirect('/orchestras/settings');
            return;
        }
        
        // Update orchestra
        $result = $this->orchestraModel->update($_SESSION['orchestra_id'], [
            'name' => $name,
            'token' => $token,
            'leader_pw' => $leaderPw
        ]);
        
        if ($result) {
            $this->addAlert('Erfolg!', 'Die Orchestereinstellungen wurden aktualisiert.', 'success');
        } else {
            $this->addAlert('Fehler!', 'Die Einstellungen konnten nicht aktualisiert werden.', 'error');
        }
        
        $this->redirect('/orchestras/settings');
    }
    
    /**
     * Delete orchestra confirmation
     * 
     * @return void
     */
    public function confirmDelete()
    {
        // Check if logged in as conductor
        if (!$this->isLoggedIn() || $_SESSION['role'] !== 'conductor') {
            $this->addAlert('Fehler!', 'Sie haben keine Berechtigung für diese Seite.', 'error');
            $this->redirect('/login');
            return;
        }
        
        // Get orchestra data
        $orchestra = $this->orchestraModel->findById($_SESSION['orchestra_id']);
        
        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Orchester nicht gefunden.', 'error');
            $this->redirect('/promises');
            return;
        }
        
        // Display confirmation form
        $this->render('orchestras/delete', [
            'currentPage' => 'orchestra_settings',
            'orchestra' => $orchestra
        ]);
    }
    
    /**
     * Process orchestra deletion
     * 
     * @return void
     */
    public function delete()
    {
        // Check if logged in as conductor
        if (!$this->isLoggedIn() || $_SESSION['role'] !== 'conductor') {
            $this->addAlert('Fehler!', 'Sie haben keine Berechtigung für diese Seite.', 'error');
            $this->redirect('/login');
            return;
        }
        
        // Check if form submitted with confirmation
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
            $this->redirect('/orchestras/settings');
            return;
        }
        
        // Delete orchestra (cascade will delete all related data)
        $result = $this->orchestraModel->delete($_SESSION['orchestra_id']);
        
        if ($result) {
            // Logout user
            $this->addAlert('Erfolg!', 'Das Orchester wurde erfolgreich gelöscht.', 'success');
            $this->redirect('/logout');
        } else {
            $this->addAlert('Fehler!', 'Das Orchester konnte nicht gelöscht werden.', 'error');
            $this->redirect('/orchestras/settings');
        }
    }
    
    /**
     * Sanitize UTF-8 string to prevent encoding issues
     * 
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    private function sanitizeUtf8($string)
    {
        // Remove any invalid UTF-8 characters
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        // Remove any potentially problematic characters
        $string = preg_replace('/[^\p{L}\p{N}\s\-_\.]/u', '', $string);
        
        return $string;
    }
} 