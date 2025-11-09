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
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function profile($params = [])
    {
        // Validate orchestra context and set session variables
        $this->validateOrchestraContext($params);
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }
        
        // Add current orchestra context data to user array for display
        if (isset($_SESSION['current_type'])) {
            $user['type'] = $_SESSION['current_type'];
        }
        if (isset($_SESSION['current_role'])) {
            $user['role'] = $_SESSION['current_role'];
        }
        
        // Get small group status from user_orchestras table
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $user['is_small_group'] = $userOrchestraModel->isUserInSmallGroup((int)$user['id'], (int)$orchestraId);
        } else {
            $user['is_small_group'] = false;
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
            'csrf_token' => $this->getCSRFToken(),
            'orchestraId' => $_SESSION['current_orchestra_id'],
            'hasPassword' => !empty($user['password'])
        ]);
    }
    
    /**
     * Display conductor profile
     * 
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function conductorProfile($params = [])
    {
        // Validate orchestra context and set session variables
        $this->validateOrchestraContext($params);
        
        // Check if user is a conductor
        $this->requireRole('conductor');
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
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
            'availableThemes' => \App\Core\ThemeManager::getThemesForPreview(),
            'csrf_token' => $this->getCSRFToken(),
            'orchestraId' => $_SESSION['current_orchestra_id'],
            'hasPassword' => !empty($user['password'])
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
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/conductor/profile');
            return;
        }
        
        // Validate and sanitize input
        $newUsername = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $updateData = [];
        $usernameChanged = false;
        $redirectPath = '/' . $_SESSION['current_orchestra_id'] . '/conductor/profile';
        
        // Process username changes if provided
        $usernameResult = $this->validateAndUpdateUsername($user, $newUsername, $redirectPath);
        if ($usernameResult !== null) {
            $updateData['username'] = $usernameResult['username'];
            $usernameChanged = $usernameResult['changed'];
        }
        
        // Process password changes if provided
        $validatedPassword = $this->validateAndUpdatePassword($user, $currentPassword, $newPassword, $confirmPassword, $redirectPath);
        if ($validatedPassword !== null) {
            $updateData['password'] = $validatedPassword;
        }
        
        // If no changes were made
        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/conductor/profile');
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
                $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/conductor/profile');
            }
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error');
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/conductor/profile');
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
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/profile');
            return;
        }
        
        // Validate and sanitize input
        $newUsername = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $groupType = Validator::sanitizeUtf8($_POST['group_type'] ?? '');
        $smallGroup = isset($_POST['small_group']) ? true : false; // TODO: implement in relation if supported
        $groupLeader = isset($_POST['group_leader']) ? true : false;
        $groupLeaderPassword = Validator::sanitizeUtf8($_POST['group_leader_password'] ?? '');
        
        $updateData = [];
        $relationChangesMade = false;
        $usernameChanged = false;
        $redirectPath = '/' . $_SESSION['current_orchestra_id'] . '/profile';
        
        // Process username changes if provided
        $usernameResult = $this->validateAndUpdateUsername($user, $newUsername, $redirectPath);
        if ($usernameResult !== null) {
            $updateData['username'] = $usernameResult['username'];
            $usernameChanged = $usernameResult['changed'];
        }
        
        // Process password changes if provided
        $validatedPassword = $this->validateAndUpdatePassword($user, $currentPassword, $newPassword, $confirmPassword, $redirectPath);
        if ($validatedPassword !== null) {
            $updateData['password'] = $validatedPassword;
        }
        
        // Process group type changes in user_orchestras relation
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            if (!empty($groupType)) {
                $typeUpdated = $userOrchestraModel->updateUserType((int)$user['id'], (int)$orchestraId, $groupType);
                if ($typeUpdated) {
                    $_SESSION['current_type'] = $groupType;
                    $relationChangesMade = true;
                }
            }
            
            // Process small group status
            $smallGroupUpdated = $userOrchestraModel->updateUserSmallGroupStatus((int)$user['id'], (int)$orchestraId, $smallGroup);
            if ($smallGroupUpdated) {
                $relationChangesMade = true;
            }
        }
        
        // Process group leader status
        $isCurrentlyLeader = ($user['role'] === 'leader');
        
        if ($groupLeader && !$isCurrentlyLeader) {
            // Check leader password
            $leaderPassword = $this->getLeaderPassword();
            // Use case-insensitive comparison
            if (strtolower($groupLeaderPassword) !== strtolower($leaderPassword)) {
                $this->addAlert('Fehler!', 'Das Stimmführer-Passwort ist falsch.', 'error');
                $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/profile');
                return;
            }
            // Update role in relation
            if ($orchestraId) {
                $userOrchestraModel = $userOrchestraModel ?? new \App\Models\UserOrchestra();
                $roleUpdated = $userOrchestraModel->updateUserRole((int)$user['id'], (int)$orchestraId, 'leader');
                if ($roleUpdated) {
                    $_SESSION['current_role'] = 'leader';
                    $relationChangesMade = true;
                }
            }
        } else if (!$groupLeader && $isCurrentlyLeader) {
            if ($orchestraId) {
                $userOrchestraModel = $userOrchestraModel ?? new \App\Models\UserOrchestra();
                $roleUpdated = $userOrchestraModel->updateUserRole((int)$user['id'], (int)$orchestraId, 'member');
                if ($roleUpdated) {
                    $_SESSION['current_role'] = 'member';
                    $relationChangesMade = true;
                }
            }
        }
        
        // If no changes were made
        if (empty($updateData) && !$relationChangesMade) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/profile');
            return;
        }
        
        // Update user profile if there are user-table changes
        $result = true;
        if (!empty($updateData)) {
            $result = $this->userModel->updateProfile($user['id'], $updateData);
        }
        
        if ($result === true) {
            if ($usernameChanged) {
                // If username changed, need to log out and back in
                $this->setFlash('success', 'Profil aktualisiert. Bitte melden Sie sich erneut an.');
                $this->logout();
            } else {
                // Session updates for relation handled above when applying relation changes
                $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
                $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/profile');
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
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/profile');
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
            $this->redirect('/orchestras/select');
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
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/profile');
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
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        
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
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function getUserDetails($params = [])
    {
        // Validate orchestra context and set session variables
        $this->validateOrchestraContext($params);
        
        // Check if user is authorized (either conductor or group leader)
        $this->requireRole('leader'); // This allows both leader and conductor
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            $this->jsonError('No username provided');
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->jsonError('User not found', [], 404);
        }
        
        // Check which operation is requested
        if (isset($_GET['getLastLogin'])) {
            // Return the last login time
            $lastLogin = $user['last_login'] ?? 'N/A';
            $this->jsonSuccess(['last_login' => $lastLogin]);
        }
        
        // Default behavior - return full user details excluding password
        unset($user['password']);
        $this->jsonSuccess($user);
    }
    
    /**
     * Reset user password - API endpoint for resetting a user's password
     * 
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function resetPassword($params = [])
    {
        // Validate orchestra context and set session variables
        $this->validateOrchestraContext($params);
        
        // Check if user is authorized (either conductor or group leader)
        $this->requireRole('leader'); // This allows both leader and conductor
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            $this->jsonError('No username provided');
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->jsonError('User not found', [], 404);
        }
        
        // Generate a secure random password (min 8 chars, at least one upper and one lower)
        $newPassword = $this->generateSecurePassword(12);
        $result = $this->userModel->updateProfile($user['id'], ['password' => $newPassword]);
        
        if ($result === true) {
            $this->jsonSuccess([
                'message' => "Das Passwort des Nutzers $username wurde zurückgesetzt: $newPassword"
            ]);
        } else {
            $errorMsg = is_array($result) && isset($result['message']) 
                ? $result['message'] 
                : "Fehler beim Zurücksetzen des Passworts.";
            $this->jsonError($errorMsg, [], 500);
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
    public function deleteUser($params = [])
    {
        // Validate orchestra context and set session variables
        $this->validateOrchestraContext($params);
        
        // Check if user is authorized (either conductor or group leader)
        $this->requireRole('leader'); // This allows both leader and conductor
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            $this->jsonError('No username provided');
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->jsonError('User not found', [], 404);
        }
        
        // Delete the user account
        $result = $this->userModel->delete($user['id']);
        
        if ($result) {
            $this->jsonSuccess([
                'message' => "Der Nutzer $username wurde erfolgreich gelöscht."
            ]);
        } else {
            $this->jsonError("Fehler beim Löschen des Accounts.", [], 500);
        }
    }
    
    /**
     * Switch theme instantly via AJAX
     * 
     * @return void
     */
    public function switchTheme()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->jsonError('Nicht authentifiziert', [], 401);
        }
        
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Methode nicht erlaubt', [], 405);
        }
        
        try {
            // CSRF protection
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->jsonError('CSRF-Fehler: ' . $e->getMessage(), [], 403);
        }
        
        // Get and validate theme
        $theme = Validator::sanitizeUtf8($_POST['theme'] ?? '');
        
        if (empty($theme)) {
            $this->jsonError('Kein Theme angegeben');
        }
        
        // Validate theme using ThemeManager
        $themeValidation = \App\Core\ThemeManager::validateThemePreference($theme);
        if (!$themeValidation['valid']) {
            $this->jsonError(implode(", ", $themeValidation['errors']));
        }
        
        // Get current user
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->jsonError('Benutzer nicht gefunden', [], 404);
        }
        
        // Update theme preference
        $result = $this->userModel->updateTheme($user['id'], $theme);
        
        if ($result === true) {
            // Update session
            $_SESSION['theme'] = $theme;
            
            // Return success
            $this->jsonSuccess([
                'theme' => $theme,
                'message' => 'Theme erfolgreich gewechselt'
            ]);
        } else {
            // Handle error
            $errorMessage = 'Fehler beim Aktualisieren des Themes';
            if (is_array($result) && isset($result['message'])) {
                $errorMessage = $result['message'];
            }
            
            $this->jsonError($errorMessage, [], 500);
        }
    }
    
    /**
     * Validate and process username update
     * 
     * @param array $user Current user data
     * @param string $newUsername New username to validate
     * @param string $redirectPath Path to redirect on error
     * @return array|null Returns associative array with 'username' and 'changed' keys if valid, 
     *                    null if no change or redirects on error
     */
    private function validateAndUpdateUsername($user, $newUsername, $redirectPath)
    {
        $oldUsername = $user['username'];
        
        // Check if username actually changed
        if (empty($newUsername) || $newUsername === $oldUsername) {
            return null;
        }
        
        // Validate username using the model's validation method
        $usernameValidation = $this->userModel->validateUserInput(
            $newUsername,
            null,
            $user['id']
        );
        
        if (!$usernameValidation['valid']) {
            $this->addAlert('Fehler!', implode(", ", $usernameValidation['errors']), 'error');
            $this->redirect($redirectPath);
            return null; // This line won't be reached due to redirect, but kept for clarity
        }
        
        return [
            'username' => $newUsername,
            'changed' => true
        ];
    }
    
    /**
     * Validate and process password update
     * 
     * @param array $user Current user data
     * @param string $currentPassword Current password (for verification if user has password)
     * @param string $newPassword New password to set
     * @param string $confirmPassword Password confirmation
     * @param string $redirectPath Path to redirect on error
     * @return string|null Returns the validated new password if valid, null if no password change,
     *                     or redirects on error
     */
    private function validateAndUpdatePassword($user, $currentPassword, $newPassword, $confirmPassword, $redirectPath)
    {
        // Check if password change was requested
        if (empty($newPassword)) {
            return null;
        }
        
        $hasPassword = !empty($user['password']);
        
        // If user has an existing password, verify it
        if ($hasPassword) {
            if (empty($currentPassword)) {
                $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                $this->redirect($redirectPath);
                return null; // This line won't be reached due to redirect
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                $this->redirect($redirectPath);
                return null; // This line won't be reached due to redirect
            }
        }
        
        // Validate password using the model's validation method
        $passwordValidation = $this->userModel->validateUserInput(null, $newPassword);
        if (!$passwordValidation['valid']) {
            $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
            $this->redirect($redirectPath);
            return null; // This line won't be reached due to redirect
        }
        
        // Check passwords match
        if ($newPassword !== $confirmPassword) {
            $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
            $this->redirect($redirectPath);
            return null; // This line won't be reached due to redirect
        }
        
        return $newPassword;
    }
} 