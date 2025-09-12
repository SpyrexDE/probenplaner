<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\ErrorHandler;
use App\Models\User;

/**
 * User Controller
 * Handles user profile management
 */
class UserController extends Controller
{
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
        $this->userModel = new User();
    }
    
    /**
     * Display user profile
     * 
     * @return void
     */
    public function profile()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/');
            return;
        }
        
        // Ensure we have the latest data from the session as well
        if (isset($_SESSION['type'])) {
            $user['type'] = $_SESSION['type'];
        }
        if (isset($_SESSION['is_small_group'])) {
            $user['is_small_group'] = $_SESSION['is_small_group'];
        }
        if (isset($_SESSION['role'])) {
            $user['role'] = $_SESSION['role'];
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processProfileEdit($user);
            return;
        }
        
        // Add debugging info for troubleshooting
        error_log("User data for profile: " . json_encode([
            'type' => $user['type'] ?? null,
            'is_small_group' => $user['is_small_group'] ?? null,
            'role' => $user['role'] ?? null
        ]));
        
        // Render profile view
        $this->render('user/profile', [
            'currentPage' => 'profile',
            'user' => $user,
            'typeStructure' => $this->getTypeStructure(),
            'availableThemes' => \App\Core\ThemeManager::getThemesForPreview(),
            'csrf_token' => $this->getCSRFToken()
        ]);
    }
    
    /**
     * Display conductor profile
     * 
     * @return void
     */
    public function conductorProfile()
    {
        // Check if user is logged in and is a conductor
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Force conductor role check
        if ($_SESSION['role'] !== 'conductor') {
            $this->addAlert('Fehler!', 'Sie haben keine Berechtigung für diese Seite.', 'error');
            $this->redirect('/profile');
            return;
        }
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/');
            return;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processConductorProfileEdit($user);
            return;
        }
        
        // Render conductor profile view
        $this->render('user/conductor_profile', [
            'currentPage' => 'conductor_profile',
            'user' => $user,
            'csrf_token' => $this->getCSRFToken()
        ]);
    }
    
    /**
     * Process conductor profile edit form
     * 
     * @param array $user Current user data
     * @return void
     */
    private function processConductorProfileEdit($user)
    {
        // CSRF protection
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/conductor/profile');
            return;
        }
        // Validate and sanitize input
        $oldUsername = $user['username'];
        $newUsername = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $updateData = [];
        $usernameChanged = false;
        
        // Process username changes if provided
        if (!empty($newUsername) && $newUsername != $oldUsername) {
            // Validate username using the model's validation method
            $usernameValidation = $this->userModel->validateUserInput(
                $newUsername, 
                null, 
                $_SESSION['orchestra_id'], 
                $user['id']
            );
            
            if (!$usernameValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $usernameValidation['errors']), 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            $updateData['username'] = $newUsername;
            $usernameChanged = true;
        }
        
        // Process password changes if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Validate password using the model's validation method
            $passwordValidation = $this->userModel->validateUserInput('', $newPassword);
            if (!$passwordValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Check passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            $updateData['password'] = $newPassword;
        }
        
        // If no changes were made
        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect('/conductor/profile');
            return;
        }
        
        // Update user profile
        $result = $this->userModel->updateProfile($user['id'], $updateData);
        
        if ($result) {
            if ($usernameChanged) {
                // If username changed, need to log out and back in
                $this->setFlash('success', 'Profil aktualisiert. Bitte melden Sie sich erneut an.');
                $this->logout();
            } else {
                $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
                $this->redirect('/conductor/profile');
            }
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error');
            $this->redirect('/conductor/profile');
        }
    }
    
    /**
     * Process profile edit form
     * 
     * @param array $user Current user data
     * @return void
     */
    private function processProfileEdit($user)
    {
        // CSRF protection
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/profile');
            return;
        }
        // Validate and sanitize input
        $oldUsername = $user['username'];
        $newUsername = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $groupType = Validator::sanitizeUtf8($_POST['group_type'] ?? '');
        $smallGroup = isset($_POST['small_group']) ? true : false;
        $groupLeader = isset($_POST['group_leader']) ? true : false;
        $groupLeaderPassword = Validator::sanitizeUtf8($_POST['group_leader_password'] ?? '');
        
        $updateData = [];
        $usernameChanged = false;
        
        // Process username changes if provided
        if (!empty($newUsername) && $newUsername != $oldUsername) {
            // Validate username using the model's validation method
            $usernameValidation = $this->userModel->validateUserInput(
                $newUsername, 
                null, 
                $_SESSION['orchestra_id'], 
                $user['id']
            );
            
            if (!$usernameValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $usernameValidation['errors']), 'error');
                $this->redirect('/profile');
                return;
            }
            
            $updateData['username'] = $newUsername;
            $usernameChanged = true;
        }
        
        // Process password changes if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Validate password using the model's validation method
            $passwordValidation = $this->userModel->validateUserInput('', $newPassword);
            if (!$passwordValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Check passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            $updateData['password'] = $newPassword;
        }
        
        // Process group type changes if provided
        if (!empty($groupType)) {
            $updateData['type'] = $groupType;
            $updateData['is_small_group'] = $smallGroup ? 1 : 0;
        } else if (isset($_POST['small_group'])) {
            // Only small group status changed
            $updateData['is_small_group'] = $smallGroup ? 1 : 0;
        }
        
        // Process group leader status
        $isCurrentlyLeader = ($user['role'] === 'leader');
        
        if ($groupLeader && !$isCurrentlyLeader) {
            // Check leader password
            $leaderPassword = $this->getLeaderPassword();
            // Use case-insensitive comparison
            if (strtolower($groupLeaderPassword) !== strtolower($leaderPassword)) {
                $this->addAlert('Fehler!', 'Das Stimmführer-Passwort ist falsch.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Set user role to leader
            $updateData['role'] = 'leader';
        } else if (!$groupLeader && $isCurrentlyLeader) {
            // Remove leader role
            $updateData['role'] = 'member';
        }
        
        // If no changes were made
        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect('/profile');
            return;
        }
        
        // Update user profile
        $result = $this->userModel->updateProfile($user['id'], $updateData);
        
        if ($result === true) {
            if ($usernameChanged) {
                // If username changed, need to log out and back in
                $this->setFlash('success', 'Profil aktualisiert. Bitte melden Sie sich erneut an.');
                $this->logout();
            } else {
                // Update session variables to reflect changes
                if (isset($updateData['type'])) {
                    $_SESSION['type'] = $updateData['type'];
                }
                if (isset($updateData['is_small_group'])) {
                    $_SESSION['is_small_group'] = $updateData['is_small_group'] ? true : false;
                }
                if (isset($updateData['role'])) {
                    $_SESSION['role'] = $updateData['role'];
                }
                
                $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
                $this->redirect('/profile');
            }
        } else {
            // Log the error for debugging
            error_log("Profile update failed: " . json_encode($updateData));
            
            // Check if it's an array with error details
            if (is_array($result) && isset($result['error']) && isset($result['message'])) {
                $errorDetails = isset($result['details']) ? $result['details'] : '';
                $this->addAlert('Fehler!', $result['message'], 'error', $errorDetails);
            } else {
                // Try to get a better error message from the database
                $db = new \App\Core\Database();
                $errorMsg = $db->getLastError();
                
                if (strpos($errorMsg, 'Unknown column') !== false) {
                    throw new \Exception(
                        'Es fehlt eine Spalte in der Datenbank. Bitte führen Sie die Migrationen aus, um die Datenbankstruktur zu aktualisieren.'
                    );
                } else {
                    $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error', $errorMsg);
                }
            }
            $this->redirect('/profile');
        }
    }
    
    /**
     * Check if leader password is valid
     * 
     * @return void
     */
    public function checkLeaderPassword()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            echo json_encode(['valid' => false]);
            return;
        }
        
        // Get submitted password
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        // Get leader password from configuration
        $leaderPassword = $this->getLeaderPassword();
        
        // Check if the password matches (case-insensitive)
        $isValid = (strtolower($password) === strtolower($leaderPassword));
        
        // Return result
        echo json_encode(['valid' => $isValid]);
    }
    
    /**
     * Delete user account
     * 
     * @return void
     */
    public function delete()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/');
            return;
        }
        
        // Delete user account
        $result = $this->userModel->delete($user['id']);
        
        if ($result) {
            // Log out the user
            $this->setFlash('success', 'Dein Account wurde erfolgreich gelöscht.');
            $this->logout();
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Löschen des Accounts.', 'error');
            $this->redirect('/profile');
        }
    }
    
    /**
     * Get leader password from the current orchestra
     * 
     * @return string
     */
    private function getLeaderPassword()
    {
        // Get the current orchestra from session
        $orchestraId = $_SESSION['orchestra_id'] ?? null;
        
        if ($orchestraId) {
            $orchestraModel = new \App\Models\Orchestra();
            $orchestra = $orchestraModel->findById($orchestraId);
            
            if ($orchestra && isset($orchestra['leader_pw'])) {
                return $orchestra['leader_pw'];
            }
        }
        
        // Fallback default password
        return 'stimmfuehrer';
    }
    
    /**
     * Log out the user
     * 
     * @return void
     */
    private function logout()
    {
        // Clear session data
        $_SESSION = [];
        
        // Destroy the session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Redirect to login page
        $this->redirect('/login');
    }
    
    /**
     * Get instrument/section type structure dynamically
     * 
     * @return array
     */
    private function getTypeStructure()
    {
        $groupManager = new \App\Core\GroupManager();
        
        // Convert the hierarchical config to a format compatible with the existing code
        $config = $groupManager->getConfig();
        
        // Extract the main structure under 'tutti'
        if (isset($config['tutti']['children'])) {
            $structure = [];
            
            foreach ($config['tutti']['children'] as $sectionKey => $section) {
                if ($section['type'] === 'section' && isset($section['children'])) {
                    $sectionChildren = [];
                    
                    foreach ($section['children'] as $childKey => $child) {
                        if ($child['type'] === 'instrument') {
                            $sectionChildren[] = $child['id'];
                        } elseif ($child['type'] === 'section' && isset($child['children'])) {
                            // Handle nested sections from dynamic configuration
                            $subSection = [];
                            foreach ($child['children'] as $instrumentKey => $instrument) {
                                if ($instrument['type'] === 'instrument') {
                                    $subSection[] = $instrument['id'];
                                }
                            }
                            $sectionChildren[$child['id']] = $subSection;
                        }
                    }
                    
                    $structure[$section['id']] = $sectionChildren;
                } elseif ($section['type'] === 'section' && !isset($section['children'])) {
                    // Simple sections like Schlagwerk, Andere
                    $structure[] = $section['id'];
                }
            }
            
            return ["Tutti" => $structure];
        }
        
        // Configuration is malformed - this should not happen with proper setup
        throw new \Exception("Orchestra groups configuration is malformed or missing 'tutti' section. Please check src/config/orchestra_groups.php");
    }
    
    /**
     * Get user account details - API endpoint to fetch user information
     * for the AJAX requests in the promises admin view
     * 
     * @return void
     */
    public function getUserDetails()
    {
        // Check if user is authorized (either conductor or group leader)
        if (!$this->isLoggedIn() || 
            ($_SESSION['role'] !== 'conductor' && $_SESSION['role'] !== 'leader')) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission']);
            return;
        }
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No username provided']);
            return;
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Check which operation is requested
        if (isset($_GET['getLastLogin'])) {
            // Return the last login time
            $lastLogin = $user['last_login'] ?? 'N/A';
            echo json_encode(['last_login' => $lastLogin]);
            return;
        }
        
        // Default behavior - return full user details excluding password
        unset($user['password']);
        echo json_encode($user);
    }
    
    /**
     * Reset user password - API endpoint for resetting a user's password
     * 
     * @return void
     */
    public function resetPassword()
    {
        // Check if user is authorized (either conductor or group leader)
        if (!$this->isLoggedIn() || 
            ($_SESSION['role'] !== 'conductor' && $_SESSION['role'] !== 'leader')) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission']);
            return;
        }
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No username provided']);
            return;
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Generate a secure random password (min 8 chars, at least one upper and one lower)
        $newPassword = $this->generateSecurePassword(12);
        $result = $this->userModel->updateProfile($user['id'], ['password' => $newPassword]);
        
        if ($result === true) {
            echo json_encode([
                'success' => true,
                'message' => "Das Passwort des Nutzers $username wurde zurückgesetzt: $newPassword"
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => is_array($result) && isset($result['message']) ? $result['message'] : "Fehler beim Zurücksetzen des Passworts."
            ]);
        }
    }

    /**
     * Generate a secure password containing at least one uppercase, one lowercase, and one digit
     *
     * @param int $length
     * @return string
     */
    private function generateSecurePassword($length = 12)
    {
        $length = max(8, (int)$length);
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $all = $lower . $upper . $digits;
        
        // Ensure required character classes
        $passwordChars = [];
        $passwordChars[] = $lower[random_int(0, strlen($lower) - 1)];
        $passwordChars[] = $upper[random_int(0, strlen($upper) - 1)];
        $passwordChars[] = $digits[random_int(0, strlen($digits) - 1)];
        
        // Fill remaining length
        for ($i = count($passwordChars); $i < $length; $i++) {
            $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
        }
        
        // Shuffle to avoid predictable pattern
        for ($i = 0; $i < $length; $i++) {
            $j = random_int(0, $length - 1);
            $tmp = $passwordChars[$i];
            $passwordChars[$i] = $passwordChars[$j];
            $passwordChars[$j] = $tmp;
        }
        
        return implode('', $passwordChars);
    }
    
    /**
     * Delete user account - API endpoint for deleting a user account
     * 
     * @return void
     */
    public function deleteUser()
    {
        // Check if user is authorized (either conductor or group leader)
        if (!$this->isLoggedIn() || 
            ($_SESSION['role'] !== 'conductor' && $_SESSION['role'] !== 'leader')) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission']);
            return;
        }
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No username provided']);
            return;
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Delete the user account
        $result = $this->userModel->delete($user['id']);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => "Der Nutzer $username wurde erfolgreich gelöscht."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => "Fehler beim Löschen des Accounts."
            ]);
        }
    }
    
    /**
     * Switch theme instantly via AJAX
     * 
     * @return void
     */
    public function switchTheme()
    {
        // Set content type to JSON
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
            return;
        }
        
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt']);
            return;
        }
        
        try {
            // CSRF protection
            $this->protectCSRF();
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF-Fehler: ' . $e->getMessage()]);
            return;
        }
        
        // Get and validate theme
        $theme = Validator::sanitizeUtf8($_POST['theme'] ?? '');
        
        if (empty($theme)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kein Theme angegeben']);
            return;
        }
        
        // Validate theme using ThemeManager
        $themeValidation = \App\Core\ThemeManager::validateThemePreference($theme);
        if (!$themeValidation['valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(", ", $themeValidation['errors'])]);
            return;
        }
        
        // Get current user
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            return;
        }
        
        // Update theme preference
        $result = $this->userModel->updateTheme($user['id'], $theme);
        
        if ($result === true) {
            // Update session
            $_SESSION['theme'] = $theme;
            
            // Return success
            echo json_encode([
                'success' => true,
                'theme' => $theme,
                'message' => 'Theme erfolgreich gewechselt'
            ]);
        } else {
            // Handle error
            $errorMessage = 'Fehler beim Aktualisieren des Themes';
            if (is_array($result) && isset($result['message'])) {
                $errorMessage = $result['message'];
            }
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $errorMessage]);
        }
    }
} 