<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Orchestra;
use App\Models\User;
use App\Models\UserOrchestra;

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
     * @var UserOrchestra
     */
    private $userOrchestraModel;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->orchestraModel = new Orchestra();
        $this->userModel = new User();
        $this->userOrchestraModel = new UserOrchestra();
    }
    
    /**
     * Display admin create orchestra form
     * 
     * @return void
     */
    public function create()
    {
        // Must be logged in to create orchestra
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
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
            // CSRF protection for admin verification
            try {
                $this->protectCSRF();
            } catch (\Exception $e) {
                $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
                $this->redirect('/orchestras/create');
                return;
            }
            
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
                'formData' => $formData,
                'csrf_token' => $this->getCSRFToken()
            ]);
            return;
        }
        
        // Otherwise display admin password verification form
        $this->render('orchestras/admin_verify', [
            'currentPage' => 'create_orchestra',
            'csrf_token' => $this->getCSRFToken()
        ]);
    }
    
    /**
     * Process orchestra creation
     * 
     * @return void
     */
    public function store()
    {
        // Must be logged in to create orchestra
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // CSRF protection
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/orchestras/create');
            return;
        }
        
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
        $name = Validator::sanitizeUtf8($_POST['name'] ?? '');
        $token = Validator::sanitizeUtf8($_POST['token'] ?? '');
        $leaderPassword = Validator::sanitizeUtf8($_POST['leader_password'] ?? '');
        
        // Store form data for repopulation on validation failure
        $formData = [
            'name' => $name,
            'token' => $token,
            'leader_password' => $leaderPassword,
        ];
        
        // Log input
        error_log("Orchestra creation attempt - Name: $name, Token: $token");
        
        // Validate required fields
        $requiredValidation = Validator::validateRequired([
            'name' => $name,
            'token' => $token,
            'leader_password' => $leaderPassword
        ], ['name', 'token', 'leader_password']);
        
        // Validate individual fields
        $tokenValidation = Validator::validateToken($token);
        
        // Check for duplicate token
        $tokenErrors = [];
        if (!empty($token) && $this->orchestraModel->findByToken($token)) {
            $tokenErrors[] = "Dieser Token wird bereits verwendet";
        }
        
        // Merge all validations
        $validation = Validator::mergeResults([
            $requiredValidation,
            $tokenValidation,
            ['valid' => empty($tokenErrors), 'errors' => $tokenErrors]
        ]);
        
        // If validation errors, show them
        if (!$validation['valid']) {
            // Store form data in session to repopulate the form
            $_SESSION['orchestra_form_data'] = $formData;
            
            $errorMsg = implode(", ", $validation['errors']);
            $this->addAlert('Fehler!', $errorMsg, 'error');
            $this->redirect('/orchestras/create');
            return;
        }
        
        try {
            // Create orchestra
            $orchestraData = [
                'name' => $name,
                'token' => $token,
                'leader_pw' => $leaderPassword
            ];
            
            $orchestraId = $this->orchestraModel->createOrchestra($orchestraData);
            
            if (!$orchestraId) {
                throw new \Exception("Fehler beim Erstellen des Orchesters.");
            }
            
            // Automatically join the creator as conductor
            $joinResult = $this->userOrchestraModel->joinOrchestra($_SESSION['user_id'], $orchestraId, 'none', 'conductor');
            
            if (is_array($joinResult) && isset($joinResult['error'])) {
                // Handle error from joining orchestra
                // Rollback by deleting the orchestra
                $this->orchestraModel->delete($orchestraId);
                
                // Store form data in session to repopulate the form
                $_SESSION['orchestra_form_data'] = $formData;
                
                $this->addAlert('Fehler!', $joinResult['message'], 'error');
                $this->redirect('/orchestras/create');
                return;
            }
            
            // Update orchestra with the conductor ID
            $this->orchestraModel->update($orchestraId, ['conductor_id' => $_SESSION['user_id']]);
            
            // Clear any stored form data on success
            if (isset($_SESSION['orchestra_form_data'])) {
                unset($_SESSION['orchestra_form_data']);
            }
            
            $this->setFlash('success', 'Das Orchester wurde erfolgreich erstellt. Sie sind automatisch als Dirigent beigetreten.');
            $this->redirect('/orchestras/select');
            
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
     * @param array $params Route parameters
     * @return void
     */
    public function settings($params = [])
    {
        // Validate orchestra context and require conductor role
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        
        if (!$this->requireRole('conductor', $context)) return;
        
        // Display settings form
        $this->render('orchestras/settings', [
            'currentPage' => 'orchestra_settings',
            'orchestra' => $context['orchestra']
        ]);
    }
    
    /**
     * Update orchestra settings
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function update($params = [])
    {
        // Validate orchestra context and require conductor role
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        
        if (!$this->requireRole('conductor', $context)) return;
        
        // CSRF protection
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/' . $context['orchestra_id'] . '/orchestras/settings');
            return;
        }
        
        // Check if form submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/' . $context['orchestra_id'] . '/orchestras/settings');
            return;
        }
        
        // Validate input and sanitize for UTF-8
        $name = Validator::sanitizeUtf8($_POST['name'] ?? '');
        $token = Validator::sanitizeUtf8($_POST['token'] ?? '');
        $leaderPassword = Validator::sanitizeUtf8($_POST['leader_password'] ?? '');
        
        // Validate required fields
        $requiredValidation = Validator::validateRequired([
            'name' => $name,
            'token' => $token,
            'leader_password' => $leaderPassword
        ], ['name', 'token', 'leader_password']);
        
        // Validate individual fields
        $tokenValidation = Validator::validateToken($token);
        
        // Check token uniqueness (only if changed)
        $tokenErrors = [];
        if ($token !== $context['orchestra']['token'] && $this->orchestraModel->findByToken($token)) {
            $tokenErrors[] = "Dieser Token wird bereits verwendet";
        }
        
        // Merge all validations
        $validation = Validator::mergeResults([
            $requiredValidation,
            $tokenValidation,
            ['valid' => empty($tokenErrors), 'errors' => $tokenErrors]
        ]);
        
        // If validation errors, show them
        if (!$validation['valid']) {
            $errorMsg = implode(", ", $validation['errors']);
            $this->addAlert('Fehler!', $errorMsg, 'error');
            $this->redirect('/' . $context['orchestra_id'] . '/orchestras/settings');
            return;
        }
        
        // Leaders can view all sections toggle
        $leadersCanViewAll = isset($_POST['leaders_can_view_all_sections']) ? 1 : 0;
        
        // Show rehearsal insights toggle
        $showRehearsalInsights = isset($_POST['show_rehearsal_insights']) ? 1 : 0;
        
        // Update orchestra
        $result = $this->orchestraModel->update($context['orchestra_id'], [
            'name' => $name,
            'token' => $token,
            'leader_pw' => $leaderPassword,
            'leaders_can_view_all_sections' => $leadersCanViewAll,
            'show_rehearsal_insights' => $showRehearsalInsights
        ]);
        
        if ($result) {
            // Update session orchestra name if changed
            if ($name !== $_SESSION['current_orchestra_name']) {
                $_SESSION['current_orchestra_name'] = $name;
            }
            $this->setFlash('success', 'Die Orchestereinstellungen wurden aktualisiert.');
        } else {
            $this->addAlert('Fehler!', 'Die Einstellungen konnten nicht aktualisiert werden.', 'error');
        }
        
        $this->redirect('/' . $context['orchestra_id'] . '/orchestras/settings');
    }
    
    /**
     * Delete orchestra confirmation
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function confirmDelete($params = [])
    {
        // Validate orchestra context and require conductor role
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        
        if (!$this->requireRole('conductor', $context)) return;
        
        // Display confirmation form
        $this->render('orchestras/delete', [
            'currentPage' => 'orchestra_settings',
            'orchestra' => $context['orchestra']
        ]);
    }
    
    /**
     * Process orchestra deletion
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function delete($params = [])
    {
        // Validate orchestra context and require conductor role
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        
        if (!$this->requireRole('conductor', $context)) return;
        
        // Check if form submitted with confirmation
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_delete']) || $_POST['confirm_delete'] !== 'yes') {
            $this->redirect('/' . $context['orchestra_id'] . '/orchestras/settings');
            return;
        }
        
        // Delete orchestra (cascade will delete all related data)
        $result = $this->orchestraModel->delete($context['orchestra_id']);
        
        if ($result) {
            // Clear orchestra context from session
            unset($_SESSION['current_orchestra_id']);
            unset($_SESSION['current_orchestra_name']);
            unset($_SESSION['current_type']);
            unset($_SESSION['current_role']);
            
            $this->setFlash('success', 'Das Orchester wurde erfolgreich gelöscht.');
            
            // Redirect to orchestra selection (user might have other orchestras)
            $this->redirect('/orchestras/select');
        } else {
            $this->addAlert('Fehler!', 'Das Orchester konnte nicht gelöscht werden.', 'error');
            $this->redirect('/' . $context['orchestra_id'] . '/orchestras/settings');
        }
    }
    
} 