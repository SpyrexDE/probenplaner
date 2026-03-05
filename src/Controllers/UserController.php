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

        if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->redirect($this->orchestraUrl('/conductor/profile'));
            return;
        }

        $user = $this->userModel->findById((int)$_SESSION['user_id']);

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

        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        $allRoles = [];
        $selfAssignableIds = [];
        $userRoleIds = [];
        if ($orchestraId) {
            $roleModel = new \App\Models\Role();
            $allNonSystem = $roleModel->getByOrchestra((int)$orchestraId);
            $allRoles = array_filter($allNonSystem, fn($r) => empty($r['is_system']));
            $selfAssignableIds = array_map(
                fn($r) => (int)$r['id'],
                array_filter($allRoles, fn($r) => !empty($r['is_self_assignable']))
            );
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$user['id'], (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);

            // Default roles are implicitly assigned to all members
            $defaultRoleIds = array_map(
                fn($r) => (int)$r['id'],
                array_filter($allRoles, fn($r) => !empty($r['is_default']))
            );
            $userRoleIds = array_values(array_unique(array_merge($userRoleIds, $defaultRoleIds)));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processProfileEdit($user);
            return;
        }

        $this->render('user/profile', [
            'currentPage' => 'profile',
            'user' => $user,
            'typeStructure' => $this->getTypeStructure(),
            'availableThemes' => \App\Core\ThemeManager::getThemesForPreview(),
            'csrf_token' => $this->getCSRFToken(),
            'orchestraId' => $_SESSION['current_orchestra_id'],
            'hasPassword' => !empty($user['password']),
            'allRoles' => array_values($allRoles),
            'selfAssignableIds' => $selfAssignableIds,
            'userRoleIds' => $userRoleIds,
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

        $user = $this->userModel->findById((int)$_SESSION['user_id']);

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
        $newEmail = Validator::sanitizeUtf8($_POST['email'] ?? '');
        $newDisplayName = Validator::sanitizeUtf8($_POST['display_name'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $updateData = [];

        if (!empty($newEmail) && $newEmail !== ($user['email'] ?? '')) {
            $emailValidation = $this->userModel->validateUserInput($newEmail, null, $user['id']);
            if (!$emailValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $emailValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
                return;
            }
            $updateData['email'] = $newEmail;
        }

        if (!empty($newDisplayName) && $newDisplayName !== ($user['display_name'] ?? '')) {
            $updateData['display_name'] = $newDisplayName;
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

            $passwordValidation = $this->userModel->validateUserInput(null, $newPassword);
            if (!$passwordValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect($this->orchestraUrl('/conductor/profile'));
                return;
            }

            $updateData['password'] = $newPassword;
        }

        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect($this->orchestraUrl('/conductor/profile'));
            return;
        }

        $result = $this->userModel->updateProfile($user['id'], $updateData);

        if ($result) {
            if (isset($updateData['email'])) {
                $_SESSION['email'] = $updateData['email'];
            }
            if (isset($updateData['display_name'])) {
                $_SESSION['display_name'] = $updateData['display_name'];
            }
            $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
            $this->redirect($this->orchestraUrl('/conductor/profile'));
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
        $newEmail = Validator::sanitizeUtf8($_POST['email'] ?? '');
        $newDisplayName = Validator::sanitizeUtf8($_POST['display_name'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $groupType = Validator::sanitizeUtf8($_POST['group_type'] ?? '');

        $updateData = [];
        $relationChangesMade = false;

        if (!empty($newEmail) && $newEmail !== ($user['email'] ?? '')) {
            $emailValidation = $this->userModel->validateUserInput($newEmail, null, $user['id']);
            if (!$emailValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $emailValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/profile'));
                return;
            }
            $updateData['email'] = $newEmail;
        }

        if (!empty($newDisplayName) && $newDisplayName !== ($user['display_name'] ?? '')) {
            $updateData['display_name'] = $newDisplayName;
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

            $passwordValidation = $this->userModel->validateUserInput(null, $newPassword);
            if (!$passwordValidation['valid']) {
                $this->addAlert('Fehler!', implode(", ", $passwordValidation['errors']), 'error');
                $this->redirect($this->orchestraUrl('/profile'));
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect($this->orchestraUrl('/profile'));
                return;
            }

            $updateData['password'] = $newPassword;
        }

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

            // Handle self-assignable roles
            if (!empty($_POST['role_ids_submitted'])) {
                $submittedRoleIds = $_POST['role_ids'] ?? [];
                if (is_string($submittedRoleIds)) {
                    $submittedRoleIds = json_decode($submittedRoleIds, true) ?: [];
                }
                $submittedRoleIds = array_map('intval', array_filter($submittedRoleIds));

                // Only allow toggling self-assignable roles
                $roleModel = new \App\Models\Role();
                $selfAssignable = $roleModel->getSelfAssignableRoles((int)$orchestraId);
                $selfAssignableIds = array_map(fn($r) => (int)$r['id'], $selfAssignable);

                $currentRoles = $userOrchestraModel->getUserRoles((int)$user['id'], (int)$orchestraId);
                $currentRoleIds = array_map(fn($r) => (int)$r['id'], $currentRoles);

                // Keep non-self-assignable roles, replace self-assignable with submitted
                $preservedRoleIds = array_values(array_diff($currentRoleIds, $selfAssignableIds));
                $newSelfAssigned = array_values(array_intersect($submittedRoleIds, $selfAssignableIds));
                $finalRoleIds = array_values(array_unique(array_merge($preservedRoleIds, $newSelfAssigned)));

                $userOrchestraModel->setRoles((int)$user['id'], (int)$orchestraId, $finalRoleIds);
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
            if (isset($updateData['email'])) {
                $_SESSION['email'] = $updateData['email'];
            }
            if (isset($updateData['display_name'])) {
                $_SESSION['display_name'] = $updateData['display_name'];
            }
            $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
            $this->redirect($this->orchestraUrl('/profile'));
        } else {
            error_log("Profile update failed: " . json_encode($updateData));

            if (is_array($result) && isset($result['error']) && isset($result['message'])) {
                $msg = $result['message'];
                if (!empty($result['details']) && is_string($result['details'])) {
                    $msg .= ' ' . $result['details'];
                }
                $this->addAlert('Fehler!', $msg, 'error');
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

        $user = $this->userModel->findById((int)$_SESSION['user_id']);

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

        if (!isset($_GET['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Keine Benutzer-ID angegeben']);
            return;
        }

        $user = $this->userModel->findById((int)$_GET['user_id']);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            return;
        }

        if (isset($_GET['getLastLogin'])) {
            $lastLogin = $user['last_login'] ?? '–';
            echo json_encode(['last_login' => $lastLogin]);
            return;
        }

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

        if (!isset($_GET['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Keine Benutzer-ID angegeben']);
            return;
        }

        $user = $this->userModel->findById((int)$_GET['user_id']);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            return;
        }

        $displayName = $user['display_name'] ?? $user['email'];
        $newPassword = $this->generateSecurePassword(12);
        $result = $this->userModel->updateProfile($user['id'], ['password' => $newPassword]);

        if ($result === true) {
            echo json_encode([
                'success' => true,
                'password' => $newPassword,
                'message' => "Das Passwort von $displayName wurde zurückgesetzt.",
            ]);
        } else {
            http_response_code(500);
            $debugMessage = is_array($result) && isset($result['debug_message']) ? $result['debug_message'] : (is_array($result) ? json_encode($result) : 'Unknown error');
            echo json_encode([
                'success' => false,
                'message' => is_array($result) && isset($result['message']) ? $result['message'] : "Fehler beim Zurücksetzen des Passworts.",
                'debug_message' => $debugMessage,
            ]);
        }
    }

    /**
     * Generate a readable temporary password with at least one uppercase, one lowercase, and one digit.
     * Excludes ambiguous characters (l, I, O, 0, 1) for easy communication.
     *
     * @param int $length
     * @return string
     */
    private function generateSecurePassword($length = 10)
    {
        $length = max(8, (int)$length);
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $digits = '23456789';
        $all = $lower . $upper . $digits;

        $passwordChars = [];
        $passwordChars[] = $lower[random_int(0, strlen($lower) - 1)];
        $passwordChars[] = $upper[random_int(0, strlen($upper) - 1)];
        $passwordChars[] = $digits[random_int(0, strlen($digits) - 1)];

        for ($i = count($passwordChars); $i < $length; $i++) {
            $passwordChars[] = $all[random_int(0, strlen($all) - 1)];
        }

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

        if (!isset($_GET['user_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Keine Benutzer-ID angegeben']);
            return;
        }

        $user = $this->userModel->findById((int)$_GET['user_id']);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            return;
        }

        $displayName = $user['display_name'] ?? $user['email'];
        $result = $this->userModel->delete($user['id']);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => "$displayName wurde erfolgreich gelöscht.",
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "Fehler beim Löschen des Accounts.",
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
        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->findById($userId);

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
