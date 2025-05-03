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
        // Check if ADMIN password provided
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['admin_password']) && $_POST['admin_password'] === ADMIN_PW) {
                $this->render('orchestras/create', [
                    'currentPage' => 'create_orchestra',
                    'admin_verified' => true
                ]);
                return;
            } else {
                $this->addAlert('Fehler!', 'Falsches Admin-Passwort.', 'error');
            }
        }
        
        // Display admin password verification form
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
        
        // Log input
        error_log("Orchestra creation attempt - Name: $name, Token: $token, ConductorUser: $conductorUsername");
        
        // Add detailed validation
        $errors = [];
        
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
        
        if (empty($conductorUsername)) {
            $errors[] = "Dirigenten-Benutzername fehlt";
        } elseif (strlen($conductorUsername) < 2) {
            $errors[] = "Dirigenten-Benutzername muss mindestens 2 Zeichen lang sein";
        }
        
        if (empty($conductorPassword)) {
            $errors[] = "Dirigenten-Passwort fehlt";
        } elseif (strlen($conductorPassword) < 4) {
            $errors[] = "Dirigenten-Passwort muss mindestens 4 Zeichen lang sein";
        }
        
        // If validation errors, show them
        if (!empty($errors)) {
            $errorMsg = implode(", ", $errors);
            $this->addAlert('Fehler!', $errorMsg, 'error');
            $this->redirect('/orchestras/create');
            return;
        }
        
        try {
            // Create orchestra and conductor
            $result = $this->orchestraModel->createOrchestra(
                [
                    'name' => $name,
                    'token' => $token,
                    'leader_pw' => $leaderPw
                ],
                [
                    'username' => $conductorUsername,
                    'password' => $conductorPassword
                ]
            );
            
            // If regular creation fails, try a fallback method with direct queries
            if (!$result) {
                error_log("Regular orchestra creation failed, attempting fallback method");
                $result = $this->attemptFallbackCreation($name, $token, $leaderPw, $conductorUsername, $conductorPassword);
            }
            
            if ($result) {
                $this->addAlert('Erfolg!', 'Das Orchester wurde erfolgreich erstellt.', 'success');
                $this->redirect('/login');
            } else {
                // Get a more detailed error message by checking for common issues
                if ($this->orchestraModel->findByToken($token)) {
                    $this->addAlert('Fehler!', 'Dieser Token wird bereits verwendet.', 'error');
                } else {
                    $errorInfo = error_get_last();
                    $errorMsg = $errorInfo ? $errorInfo['message'] : 'Unbekannter Fehler';
                    
                    // Skip directory permission errors in the error message
                    if (strpos($errorMsg, 'mkdir') !== false && strpos($errorMsg, 'Permission denied') !== false) {
                        $errorMsg = 'Interner Serverfehler';
                    }
                    
                    $this->addAlert('Fehler!', 'Das Orchester konnte nicht erstellt werden: ' . $errorMsg, 'error');
                    error_log("Orchestra creation failed with error: $errorMsg");
                    
                    // If we're in development environment and have a debug log, show its path
                    if (defined('APP_ENV') && APP_ENV === 'development' && isset($_SESSION['debug_log_file'])) {
                        $this->addAlert('Debug Info', 'Debug log: ' . $_SESSION['debug_log_file'], 'info');
                    }
                }
                $this->redirect('/orchestras/create');
            }
        } catch (\Exception $e) {
            error_log("Exception during orchestra creation: " . $e->getMessage());
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
    
    /**
     * Attempt fallback creation method using direct SQL
     * This is used when the model-based approach fails
     * 
     * @param string $name Orchestra name
     * @param string $token Orchestra token
     * @param string $leaderPw Leader password
     * @param string $username Conductor username
     * @param string $password Conductor password
     * @return int|bool Orchestra ID or false on failure
     */
    private function attemptFallbackCreation($name, $token, $leaderPw, $username, $password)
    {
        try {
            // Get database connection
            $db = \App\Core\Database::getInstance();
            $conn = $db->getConnection();
            
            // Check if there might be a permission issue with foreign keys
            $permissionTest = $conn->query("SHOW GRANTS FOR CURRENT_USER");
            $limitedPermissions = false;
            
            if ($permissionTest) {
                $permissionString = '';
                while ($row = $permissionTest->fetch_row()) {
                    $permissionString .= $row[0] . ' ';
                }
                
                // Check if permissions might be limited
                if (strpos($permissionString, 'ALL PRIVILEGES') === false) {
                    $limitedPermissions = true;
                    error_log("Limited database permissions detected, using ultra-safe mode");
                }
            }
            
            // Start transaction with ultra-safe mode for limited permissions
            if (!$limitedPermissions) {
                $conn->begin_transaction();
            }
            
            // Step 1: Check if token exists first to avoid duplication
            $checkToken = $conn->prepare("SELECT id FROM orchestras WHERE token = ?");
            $checkToken->bind_param("s", $token);
            $checkToken->execute();
            $tokenResult = $checkToken->get_result();
            
            if ($tokenResult->num_rows > 0) {
                error_log("Token $token already exists");
                return false;
            }
            
            // Step 2: Insert orchestra using direct query
            $insertOrchestra = $conn->prepare("INSERT INTO orchestras (name, token, leader_pw, conductor_username) VALUES (?, ?, ?, ?)");
            $insertOrchestra->bind_param("ssss", $name, $token, $leaderPw, $username);
            
            if (!$insertOrchestra->execute()) {
                throw new \Exception("Failed to insert orchestra: " . $conn->error);
            }
            
            $orchestraId = $conn->insert_id;
            
            // Step 3: Insert conductor user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $insertUser = $conn->prepare("INSERT INTO users (username, password, type, orchestra_id, role) VALUES (?, ?, 'Dirigent', ?, 'conductor')");
            $insertUser->bind_param("ssi", $username, $hashedPassword, $orchestraId);
            
            if (!$insertUser->execute()) {
                // If we're in a transaction, roll it back
                if (!$limitedPermissions) {
                    $conn->rollback();
                } else {
                    // In ultra-safe mode, we need to manually delete the orchestra
                    $cleanup = $conn->prepare("DELETE FROM orchestras WHERE id = ?");
                    $cleanup->bind_param("i", $orchestraId);
                    $cleanup->execute();
                }
                
                throw new \Exception("Failed to insert conductor: " . $conn->error);
            }
            
            // Commit transaction if we started one
            if (!$limitedPermissions) {
                $conn->commit();
            }
            
            error_log("Fallback orchestra creation successful with ID: $orchestraId" . ($limitedPermissions ? " in ultra-safe mode" : ""));
            return $orchestraId;
        } catch (\Exception $e) {
            // Log the error
            error_log("Fallback orchestra creation failed: " . $e->getMessage());
            
            // Rollback if possible
            if (isset($conn) && !$limitedPermissions && $conn->ping()) {
                $conn->rollback();
            }
            
            return false;
        }
    }
} 