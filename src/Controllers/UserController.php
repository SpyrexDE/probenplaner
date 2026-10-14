<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CSRF;
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

        if ($this->isConductor()) {
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

        $this->requireConductorRole();

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

        // updateProfile() returns true on success and an error array on validation/DB failure
        if ($result === true) {
            if (isset($updateData['email'])) {
                $_SESSION['email'] = $updateData['email'];
            }
            if (isset($updateData['display_name'])) {
                $_SESSION['display_name'] = $updateData['display_name'];
            }
            $this->setFlash('success', 'Profil erfolgreich aktualisiert.');
            $this->redirect($this->orchestraUrl('/conductor/profile'));
        } else {
            if (is_array($result) && !empty($result['message'])) {
                // Same message format as processProfileEdit()
                $msg = $result['message'];
                if (!empty($result['details']) && is_string($result['details'])) {
                    $msg .= ' ' . $result['details'];
                }
                $this->addAlert('Fehler!', $msg, 'error');
            } else {
                $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error');
            }
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

        // Destructive: only via the confirmation dialog in the profile (POST + CSRF),
        // never through a plain link or an <img> tag on a foreign page.
        if (!$this->requireFormPostWithCsrf('Bitte lösche deinen Account über den Button in deinem Profil.')) {
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
        $ctx = $this->validateOrchestraContext($params);

        if (!$this->isLoggedIn() || !$ctx) {
            $this->redirect('/login');
            return;
        }

        // Only via the confirmation dialog in the profile (POST + CSRF)
        if (!$this->requireFormPostWithCsrf('Bitte verlasse das Ensemble über den Button in deinem Profil.')) {
            return;
        }

        $userId = $_SESSION['user_id'];
        $orchestraId = $ctx['orchestra_id'];

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
     * Member confirms the section guessed from the JOinJMD instrument (orchestras/confirm-section,
     * shown by Controller::render instead of the requested page), then goes on to that page.
     */
    public function confirmSection($params = [])
    {
        $context = $this->validateOrchestraContext($params);
        if (!$context) {
            return;
        }

        // Back to the page that was requested: a path inside this ensemble only
        $return = $_POST['return'] ?? '';
        $prefix = $this->orchestraUrl('/');
        if (!is_string($return) || strpos($return, $prefix) !== 0) {
            $return = $this->orchestraUrl('/promises');
        }

        // Valid sections: the leaves offered by instrument-select
        $leaves = array_merge([], ...array_values(\App\Core\GroupManager::getInstance()->getFlattenedSections()));
        $type = $_POST['section'] ?? '';
        if (!is_string($type) || !in_array($type, $leaves, true)) {
            $this->setFlash('error', 'Bitte wähle deine Stimme.');
            $this->redirect($return);
            return;
        }

        $userOrchestraModel = new \App\Models\UserOrchestra();
        if (!$userOrchestraModel->updateUserType((int)$_SESSION['user_id'], $context['orchestra_id'], $type)) {
            $this->setFlash('error', 'Die Stimme konnte nicht gespeichert werden.');
            $this->redirect($return);
            return;
        }
        $_SESSION['current_type'] = $type;

        $this->redirect($return);
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


    // ── Member administration (getUserDetails / resetPassword / deleteUser) ──
    //
    // These endpoints act on OTHER accounts. The target must be an active member of the
    // orchestra in the URL and inside the caller's member scope there. Never org-admin
    // accounts, never the caller, and Leitung accounts only for Leitung. Everything out of
    // scope is a 404 so the endpoints cannot be used to probe for user IDs.
    //
    // getUserDetails (read-only) uses the section scope of the promise dashboard / attendance:
    //   - can_manage_members or can_view_all_section_stats → every member
    //   - can_view_parent_section_stats → members of the leader's parent section
    //   - can_view_own_section_stats    → members of the leader's own instrument group
    //
    // resetPassword / deleteUser hand over or destroy a GLOBAL account, so the section scope
    // is not enough (every member can change their own instrument). They need
    // can_manage_members, and canAdministerAccountIn() must hold in this orchestra and — for
    // the password — in every other ensemble the account is active in.

    /** Permissions every ordinary member has; they are no rights over other people. */
    private const BASIC_MEMBER_PERMISSIONS = ['can_attend_rehearsals', 'can_view_schedule'];

    /**
     * Send a JSON error in this file's response format.
     */
    private function jsonError(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    }

    /**
     * State-changing JSON endpoints: POST only, valid CSRF token in the body.
     * Same check as protectCSRF(), but always answers in JSON.
     */
    private function requireJsonPostWithCsrf(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->jsonError(405, 'Methode nicht erlaubt');
            return false;
        }
        try {
            CSRF::protect();
        } catch (\Throwable $e) {
            // \Throwable: a non-string csrf_token (csrf_token[]=…) makes hash_equals() throw a TypeError
            $this->jsonError(403, 'Sicherheitsprüfung fehlgeschlagen. Bitte lade die Seite neu und versuche es erneut.');
            return false;
        }
        return true;
    }

    /**
     * Destructive profile actions (delete account / leave ensemble): POST only with a valid
     * CSRF token, otherwise back to the profile with a flash message.
     */
    private function requireFormPostWithCsrf(string $notPostMessage): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->setFlash('error', $notPostMessage);
            $this->redirect($this->profileUrl());
            return false;
        }
        try {
            CSRF::protect();
        } catch (\Throwable $e) {
            $this->setFlash('error', 'CSRF-Prüfung fehlgeschlagen. Bitte versuchen Sie es erneut.');
            $this->redirect($this->profileUrl());
            return false;
        }
        return true;
    }

    /**
     * Whether a role list contains the immutable Leitung system role.
     */
    private static function hasLeitungRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!empty($role['is_system']) && ($role['name'] ?? '') === 'Leitung') {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether the caller may take over (reset the password of) or delete the target's account
     * as far as one ensemble is concerned: the caller is Leitung there, or manages members
     * there and the target is not Leitung there and has no right there the caller lacks.
     * Prevents gaining more rights by logging in as the target afterwards.
     */
    private function canAdministerAccountIn(int $callerId, int $targetId, int $orchestraId): bool
    {
        $userOrchestraModel = new \App\Models\UserOrchestra();

        // [] when the caller is no active member of that ensemble
        $callerRoles = $userOrchestraModel->getUserRoles($callerId, $orchestraId);
        if (empty($callerRoles)) {
            return false;
        }
        if (self::hasLeitungRole($callerRoles)) {
            return true;
        }
        if (self::hasLeitungRole($userOrchestraModel->getUserRoles($targetId, $orchestraId))) {
            return false;
        }

        $callerPermissions = $userOrchestraModel->getPermissions($callerId, $orchestraId);
        if (empty($callerPermissions['can_manage_members'])) {
            return false;
        }
        foreach ($userOrchestraModel->getPermissions($targetId, $orchestraId) as $permission => $granted) {
            if ($granted && empty($callerPermissions[$permission])
                && !in_array($permission, self::BASIC_MEMBER_PERMISSIONS, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * The caller's member scope in the current orchestra, or null without any permission.
     *
     * @return string|null 'all', 'parent_section', 'own_section' or null
     */
    private function memberManagementScope(): ?string
    {
        if ($this->hasPermission('can_manage_members') || $this->hasPermission('can_view_all_section_stats')) {
            return 'all';
        }
        if ($this->hasPermission('can_view_parent_section_stats')) {
            return 'parent_section';
        }
        if ($this->hasPermission('can_view_own_section_stats')) {
            return 'own_section';
        }
        return null;
    }

    /**
     * Load the target user of a member-admin endpoint and check it against the caller's scope.
     * Emits the JSON error itself and returns null when the caller may not act on the target.
     *
     * @param array $ctx Return value of validateOrchestraContext()
     * @param mixed $rawUserId user_id from the request
     * @param string $scope Result of memberManagementScope()
     * @return array|null ['user' => users row, 'relation' => user_orchestras row]
     */
    private function resolveManagedMember(array $ctx, $rawUserId, string $scope): ?array
    {
        $targetId = is_numeric($rawUserId) ? (int)$rawUserId : 0;
        if ($targetId <= 0) {
            $this->jsonError(400, 'Keine Benutzer-ID angegeben');
            return null;
        }

        if ($targetId === (int)$_SESSION['user_id']) {
            $this->jsonError(400, 'Diese Aktion ist für deinen eigenen Account nicht möglich. Nutze dafür dein Profil.');
            return null;
        }

        $notFound = 'Mitglied nicht gefunden';
        $orchestraId = (int)$ctx['orchestra_id'];

        $userOrchestraModel = new \App\Models\UserOrchestra();
        $relation = $userOrchestraModel->getUserOrchestraRelation($targetId, $orchestraId, true);
        $user = $relation ? $this->userModel->findById($targetId) : null;

        if (!$relation || !$user || !empty($user['is_org_admin'])) {
            $this->jsonError(404, $notFound);
            return null;
        }

        // Leitung accounts can only be handled by Leitung (no takeover of the conductor by a
        // member manager or section leader)
        if (!$this->isConductor() && self::hasLeitungRole($userOrchestraModel->getUserRoles($targetId, $orchestraId))) {
            $this->jsonError(404, $notFound);
            return null;
        }

        if ($scope !== 'all') {
            $groupManager = \App\Core\GroupManager::getInstance();
            // Leader's instrument from the membership row validateOrchestraContext() just loaded
            $leaderType = $groupManager->resolveAlias((string)($ctx['user_type'] ?? ''));
            $memberType = $groupManager->resolveAlias((string)($relation['type'] ?? ''));

            $inScope = false;
            if ($leaderType !== '') {
                if ($scope === 'parent_section') {
                    $leaderSection = $groupManager->getSectionForInstrument($leaderType);
                    $inScope = $leaderSection
                        ? $groupManager->getSectionForInstrument($memberType) === $leaderSection
                        : $memberType === $leaderType;
                } else {
                    $inScope = $memberType === $leaderType;
                }
            }

            if (!$inScope) {
                $this->jsonError(404, $notFound);
                return null;
            }
        }

        return ['user' => $user, 'relation' => $relation];
    }

    /**
     * Build the URL of the own profile page (fallback: orchestra selection).
     */
    private function profileUrl(): string
    {
        if (empty($_SESSION['current_orchestra_slug'])) {
            return '/orchestras/select';
        }
        return $this->orchestraUrl('/profile');
    }

    /**
     * Get user account details of a member in the caller's scope (JSON).
     *
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function getUserDetails($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        // Always return JSON for this endpoint
        header('Content-Type: application/json');

        $scope = $this->memberManagementScope();
        if ($scope === null) {
            $this->jsonError(403, 'Keine Berechtigung');
            return;
        }

        $target = $this->resolveManagedMember($ctx, $_GET['user_id'] ?? null, $scope);
        if (!$target) return;
        $user = $target['user'];

        if (isset($_GET['getLastLogin'])) {
            $lastLogin = $user['last_login'] ?? '–';
            echo json_encode(['last_login' => $lastLogin]);
            return;
        }

        // Never hand out secrets (password hash, calendar tokens, Keycloak subject)
        echo json_encode([
            'id' => $user['id'],
            'email' => $user['email'] ?? null,
            'display_name' => $user['display_name'] ?? null,
            'auth_provider' => $user['auth_provider'] ?? 'local',
            'theme' => $user['theme'] ?? null,
            'created_at' => $user['created_at'] ?? null,
            'updated_at' => $user['updated_at'] ?? null,
            'type' => $target['relation']['type'] ?? '',
        ]);
    }

    /**
     * Reset the password of a local-auth member (JSON, POST + CSRF).
     *
     * The new password is returned in plain text, i.e. the caller can log in as the target.
     * So it needs can_manage_members (the only UI entry, the member edit modal, needs it too —
     * a section scope would be escapable because members can change their own instrument), no
     * target with rights the caller lacks, and the same authority in every other ensemble
     * the account is active in.
     *
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function resetPassword($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        // Always return JSON for this endpoint
        header('Content-Type: application/json');

        if (!$this->requireJsonPostWithCsrf()) return;

        if (!$this->hasPermission('can_manage_members')) {
            $this->jsonError(403, 'Keine Berechtigung: Passwörter können nur Personen mit dem Recht „Mitglieder verwalten“ zurücksetzen.');
            return;
        }

        $target = $this->resolveManagedMember($ctx, $_POST['user_id'] ?? $_GET['user_id'] ?? null, 'all');
        if (!$target) return;
        $user = $target['user'];
        $callerId = (int)$_SESSION['user_id'];
        $orchestraId = (int)$ctx['orchestra_id'];
        $displayName = $user['display_name'] ?? $user['email'];

        if (!$this->canAdministerAccountIn($callerId, (int)$user['id'], $orchestraId)) {
            $this->jsonError(403, "$displayName hat Rechte, die du selbst nicht hast. Deshalb kannst du das Passwort nicht zurücksetzen.");
            return;
        }

        // JMD/Keycloak accounts: the password lives in Keycloak, a local one must not be set
        if (!empty($user['keycloak_id']) || ($user['auth_provider'] ?? 'local') === 'keycloak') {
            $this->jsonError(422, 'Passwort wird über den JMD-Account verwaltet');
            return;
        }

        // The password is global: a reset from this ensemble must not hand over the account's
        // access to other ensembles the caller has no (or less) authority in.
        $userOrchestraModel = new \App\Models\UserOrchestra();
        foreach ($userOrchestraModel->getUserOrchestras((int)$user['id'], true) as $membership) {
            $otherOrchestraId = (int)$membership['orchestra_id'];
            if ($otherOrchestraId !== $orchestraId
                && !$this->canAdministerAccountIn($callerId, (int)$user['id'], $otherOrchestraId)) {
                $this->jsonError(
                    409,
                    "Der Account von $displayName wird auch in anderen Ensembles verwendet, in denen du nicht dieselben Rechte hast. "
                    . 'Das Passwort kann deshalb nur jemand zurücksetzen, der in all diesen Ensembles Mitglieder verwalten darf.'
                );
                return;
            }
        }

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
     * Delete the global account of a member (JSON, POST + CSRF).
     *
     * Only for member managers, and only when the account is used in no other ensemble:
     * deleting the account cascades to memberships, promises and attendance everywhere.
     *
     * @return void
     */
    public function deleteUser($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        // Always return JSON for this endpoint
        header('Content-Type: application/json');

        if (!$this->requireJsonPostWithCsrf()) return;

        // Deleting an account is more than removing a member (MemberController::removeMember),
        // so it needs at least the same permission.
        if (!$this->hasPermission('can_manage_members')) {
            $this->jsonError(403, 'Keine Berechtigung');
            return;
        }

        $target = $this->resolveManagedMember($ctx, $_POST['user_id'] ?? $_GET['user_id'] ?? null, 'all');
        if (!$target) return;
        $user = $target['user'];

        $displayName = $user['display_name'] ?? $user['email'];

        if (!$this->canAdministerAccountIn((int)$_SESSION['user_id'], (int)$user['id'], (int)$ctx['orchestra_id'])) {
            $this->jsonError(403, "$displayName hat Rechte, die du selbst nicht hast. Deshalb kannst du den Account nicht löschen.");
            return;
        }

        // Account is (or was) also used in other ensembles → do not touch their data
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $otherMemberships = array_filter(
            $userOrchestraModel->getUserOrchestras((int)$user['id'], false),
            fn($m) => (int)$m['orchestra_id'] !== (int)$ctx['orchestra_id']
        );
        if (!empty($otherMemberships)) {
            $this->jsonError(
                409,
                "Der Account von $displayName wird auch in anderen Ensembles verwendet und kann deshalb nicht gelöscht werden. "
                . 'Entferne die Person stattdessen aus diesem Ensemble (Mitglieder → Mitglied anklicken → „Entfernen“).'
            );
            return;
        }

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
