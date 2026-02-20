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
        $this->validateOrchestraContext($params);

        // Conductors use their own profile page
        if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->redirect($this->orchestraUrl('/conductor/profile'));
            return;
        }

        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer*in nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        if (isset($_SESSION['current_type'])) {
            $user['type'] = $_SESSION['current_type'];
        }
        if (isset($_SESSION['current_permissions'])) {
            $user['permissions'] = $_SESSION['current_permissions'];
        }

        // Get small group status from user_orchestras table
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $user['is_small_group'] = $userOrchestraModel->isUserInSmallGroup((int)$user['id'], (int)$orchestraId);
        } else {
            $user['is_small_group'] = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processProfileEdit($user);
            return;
        }

        error_log("User data for profile: " . json_encode([
            'type' => $user['type'] ?? null,
            'is_small_group' => $user['is_small_group'] ?? null,
            'permissions' => $user['permissions'] ?? null
        ]));

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
        $this->validateOrchestraContext($params);

        $this->requirePermission('can_manage_ensemble');

        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer*in nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processConductorProfileEdit($user);
            return;
        }

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
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect($this->orchestraUrl('/conductor/profile'));
            return;
        }
        $oldUsername = $user['username'];
        $newUsername = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $updateData = [];
        $usernameChanged = false;

        if (!empty($newUsername) && $newUsername != $oldUsername) {
            // Validate username using the model's validation method
            $usernameValidation = $this->userModel->validateUserInput(
                $newUsername,
                null,
                $user['id']
            );

            if (!$usernameValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $usernameValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
                return;
            }

            $updateData['username'] = $newUsername;
            $usernameChanged = true;
        }

        if (!empty($newPassword)) {
            $hasPassword = !empty($user['password']);
            if ($hasPassword) {
                if (empty($currentPassword)) {
                    $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                    $this->redirect($this->orchestraUrl('/conductor/profile'));
                    return;
                }

                if (!password_verify($currentPassword, $user['password'])) {
                    $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                    $this->redirect($this->orchestraUrl('/conductor/profile'));
                    return;
                }
            }

            // Validate password using the model's validation method
            $passwordValidation = $this->userModel->validateUserInput(null, $newPassword);
            if (!$passwordValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
                return;
            }

            // Check passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
                return;
            }

            $updateData['password'] = $newPassword;
        }

        // If no changes were made
        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect($this->orchestraUrl('/conductor/profile'));
            return;
        }

        // Update user profile
        $result = $this->userModel->updateProfile($user['id'], $updateData);

        if ($result) {
            if ($usernameChanged) {
                // Re-login required after username change
                $this->setFlash('success', 'Profil aktualisiert. Bitte melden Sie sich erneut an.');
                $this->logout();
            } else {
                $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
            }
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error');
            $this->redirect($this->orchestraUrl('/conductor/profile'));
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
        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect($this->orchestraUrl('/profile'));
            return;
        }
        $oldUsername = $user['username'];
        $newUsername = Validator::sanitizeUtf8($_POST['username'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $groupType = Validator::sanitizeUtf8($_POST['group_type'] ?? '');
        $smallGroup = isset($_POST['small_group']) ? true : false;

        $updateData = [];
        $relationChangesMade = false;
        $usernameChanged = false;

        if (!empty($newUsername) && $newUsername != $oldUsername) {
            // Validate username using the model's validation method
            $usernameValidation = $this->userModel->validateUserInput(
                $newUsername,
                null,
                $user['id']
            );

            if (!$usernameValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $usernameValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/profile'));
                return;
            }

            $updateData['username'] = $newUsername;
            $usernameChanged = true;
        }

        if (!empty($newPassword)) {
            $hasPassword = !empty($user['password']);
            if ($hasPassword) {
                if (empty($currentPassword)) {
                    $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                    $this->redirect($this->orchestraUrl('/profile'));
                    return;
                }

                if (!password_verify($currentPassword, $user['password'])) {
                    $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                    $this->redirect($this->orchestraUrl('/profile'));
                    return;
                }
            }

            // Validate password using the model's validation method
            $passwordValidation = $this->userModel->validateUserInput(null, $newPassword);
            if (!$passwordValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/profile'));
                return;
            }

            // Check passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect($this->orchestraUrl('/profile'));
                return;
            }

            $updateData['password'] = $newPassword;
        }

        // Sync user_orchestras relation
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


        if (empty($updateData) && !$relationChangesMade) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect($this->orchestraUrl('/profile'));
            return;
        }

        $result = true;
        if (!empty($updateData)) {
            $result = $this->userModel->updateProfile($user['id'], $updateData);
        }

        if ($result === true) {
            if ($usernameChanged) {
                $this->setFlash('success', 'Profil aktualisiert. Bitte melden Sie sich erneut an.');
                $this->logout();
            } else {
                $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
                $this->redirect($this->orchestraUrl('/profile'));
            }
        } else {
            error_log("Profile update failed: " . json_encode($updateData));

            if (is_array($result) && isset($result['error']) && isset($result['message'])) {
                $errorDetails = isset($result['details']) ? $result['details'] : '';
                $this->addAlert('Fehler!', $result['message'], 'error', $errorDetails);
            } else {
                $db = new \App\Core\Database();
                $errorMsg = $db->getLastError();

                if (strpos($errorMsg, 'Unknown column') !== false) {
                    throw new \Exception(
                        'Missing database column. Please run migrations to update the database schema.'
                    );
                } else {
                    $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error', $errorMsg);
                }
            }
            $this->redirect($this->orchestraUrl('/profile'));
        }
    }

    /**
     * Delete user account
     * 
     * @return void
     */
    public function delete()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        $result = $this->userModel->delete($user['id']);

        if ($result) {
            $this->setFlash('success', 'Dein Account wurde erfolgreich gelöscht.');
            $this->logout();
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Löschen des Accounts.', 'error');
            $this->redirect($this->orchestraUrl('/profile'));
        }
    }

    /**
     * Leave the current orchestra
     *
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function leaveOrchestra($params = [])
    {
        $this->validateOrchestraContext($params);

        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        $orchestraId = $_SESSION['current_orchestra_id'];

        $userOrchestraModel = new \App\Models\UserOrchestra();
        $result = $userOrchestraModel->leaveOrchestra((int)$userId, (int)$orchestraId);

        if ($result) {
            $this->setFlash('success', 'Du hast das Orchester verlassen.');
            $this->redirect('/orchestras/select');
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Verlassen des Orchesters.', 'error');
            $this->redirect($this->orchestraUrl('/profile'));
        }
    }

    /**
     * Show the onboarding screen (set display_name).
     */
    public function onboarding(): void
    {
        $this->requireLogin();

        $user = $this->userModel->findById((int)$_SESSION['user_id']);
        if (!empty($user['display_name'])) {
            $this->redirect('/orchestras/select');
            return;
        }

        $this->render('user/onboarding', [
            'currentPage' => 'onboarding',
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    /**
     * Save display name from onboarding (POST).
     */
    public function saveOnboarding(): void
    {
        $this->requireLogin();
        $this->protectCSRF();

        $displayName = trim($_POST['display_name'] ?? '');
        if ($displayName === '') {
            $this->setFlash('error', 'Bitte gib deinen Namen ein.');
            $this->redirect('/onboarding');
            return;
        }

        $this->userModel->update((int)$_SESSION['user_id'], ['display_name' => $displayName]);
        $_SESSION['display_name'] = $displayName;

        // Redirect to invite flow if pending, otherwise orchestra selection
        $inviteToken = $_SESSION['invite_token'] ?? null;
        if ($inviteToken) {
            unset($_SESSION['invite_token']);
            $this->redirect('/invite/' . urlencode($inviteToken));
        } else {
            $this->redirect('/orchestras/select');
        }
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
        throw new \Exception("Orchestra groups configuration is malformed or missing 'tutti' section. Please check src/config/orchestra_groups.php.");
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
        $this->validateOrchestraContext($params);

        $this->requirePermission('can_view_own_section_stats');

        // Always return JSON for this endpoint
        header('Content-Type: application/json');

        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kein Benutzername angegeben', 'debug_message' => 'Missing username parameter']);
            return;
        }

        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'debug_message' => "User '$username' not found in database"]);
            return;
        }

        // Check which operation is requested
        if (isset($_GET['getLastLogin'])) {
            // Return the last login time
            $lastLogin = $user['last_login'] ?? '–';
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
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function resetPassword($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requirePermission('can_view_own_section_stats');

        // Always return JSON for this endpoint
        header('Content-Type: application/json');

        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kein Benutzername angegeben', 'debug_message' => 'Missing username parameter']);
            return;
        }

        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'debug_message' => "User '$username' not found in database"]);
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
            $debugMessage = is_array($result) && isset($result['debug_message']) ? $result['debug_message'] : (is_array($result) ? json_encode($result) : 'Unknown error');
            echo json_encode([
                'success' => false,
                'message' => is_array($result) && isset($result['message']) ? $result['message'] : "Fehler beim Zurücksetzen des Passworts.",
                'debug_message' => $debugMessage
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
    public function deleteUser($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requirePermission('can_view_own_section_stats');

        // Always return JSON for this endpoint
        header('Content-Type: application/json');

        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kein Benutzername angegeben', 'debug_message' => 'Missing username parameter']);
            return;
        }

        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'debug_message' => "User '$username' not found in database"]);
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
                'success' => false,
                'message' => "Fehler beim Löschen des Accounts.",
                'debug_message' => 'User deletion returned false'
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
            echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert', 'debug_message' => 'User not logged in']);
            return;
        }

        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt', 'debug_message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']]);
            return;
        }

        try {
            // CSRF protection
            $this->protectCSRF();
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sicherheitsfehler', 'debug_message' => 'CSRF error: ' . $e->getMessage()]);
            return;
        }

        // Get and validate theme
        $theme = Validator::sanitizeUtf8($_POST['theme'] ?? '');

        if (empty($theme)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kein Theme angegeben', 'debug_message' => 'Empty theme parameter']);
            return;
        }

        // Validate theme using ThemeManager
        $themeValidation = \App\Core\ThemeManager::validateThemePreference($theme);
        if (!$themeValidation['valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(", ", $themeValidation['errors']), 'debug_message' => 'Theme validation failed']);
            return;
        }

        // Get current user
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'debug_message' => "User '$username' not found"]);
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
