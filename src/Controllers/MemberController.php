<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FieldRegistry;
use App\Models\UserOrchestra;
use App\Models\User;
use App\Models\InviteLink;
use App\Models\Role;

/**
 * Member Controller
 *
 * Handles the members listing, member editing, and role management.
 */
class MemberController extends Controller
{
    private UserOrchestra $userOrchestraModel;
    private User $userModel;
    private InviteLink $inviteLinkModel;
    private Role $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->userOrchestraModel = new UserOrchestra();
        $this->userModel = new User();
        $this->inviteLinkModel = new InviteLink();
        $this->roleModel = new Role();
    }

    /**
     * Members list page.
     */
    public function index(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        $orchestraId = $context['orchestra_id'];

        $canView = $this->hasPermission('can_view_members');
        $canManage = $this->hasPermission('can_manage_members');

        if (!$canView && !$canManage) {
            $this->setFlash('error', 'Keine Berechtigung.');
            $this->redirect($this->orchestraUrl('/promises'));
            return;
        }

        $members = $this->userOrchestraModel->getOrchestraUsers($orchestraId);
        $sections = FieldRegistry::getSections();

        $grouped = [];
        foreach ($members as $member) {
            $type = $member['type'] ?? 'Sonstige';
            $grouped[$type][] = $member;
        }

        $inviteLink = null;
        if ($canManage) {
            $inviteLink = $this->inviteLinkModel->getActiveMemberLink($orchestraId);
        }

        $roles = $this->roleModel->getByOrchestra($orchestraId);

        $this->render('members/index', [
            'currentPage' => 'members',
            'orchestraId' => $orchestraId,
            'grouped' => $grouped,
            'sections' => $sections,
            'roles' => $roles,
            'canManage' => $canManage,
            'canManagePermissions' => $this->hasPermission('can_manage_permissions'),
            'inviteLink' => $inviteLink,
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    /**
     * Get member details for edit modal (AJAX).
     */
    public function getDetails(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        $orchestraId = $context['orchestra_id'];
        $memberId = (int)$params['member_id'];

        if (!$this->hasPermission('can_manage_members')) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            return;
        }

        $relation = $this->userOrchestraModel->getUserOrchestraRelation($memberId, $orchestraId, true);
        $user = $this->userModel->findById($memberId);
        if (!$relation || !$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Mitglied nicht gefunden']);
            return;
        }

        $sections = FieldRegistry::getSections();
        $groupManager = new \App\Core\GroupManager();
        $displayNames = [];
        foreach ($sections as $key => $items) {
            if ($key !== '') {
                $displayNames[$key] = $groupManager->getDisplayName($key);
            }
            foreach ($items as $item) {
                $displayNames[$item] = $groupManager->getDisplayName($item);
            }
        }

        $roles = $this->roleModel->getByOrchestra($orchestraId);
        $userRoles = $this->userOrchestraModel->getUserRoles($memberId, $orchestraId);
        $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);

        header('Content-Type: application/json');
        echo json_encode([
            'user_id' => $user['id'],
            'display_name' => $user['display_name'] ?? $user['email'] ?? '',
            'email' => $user['email'],
            'type' => $relation['type'] ?? '',
            'role_ids' => $userRoleIds,
            'available_roles' => array_map(fn($r) => [
                'id' => $r['id'],
                'name' => $r['name'],
                'tag_color' => $r['tag_color'],
                'is_system' => $r['is_system'],
                'is_default' => $r['is_default'] ?? 0,
                'is_self_assignable' => $r['is_self_assignable'] ?? 0,
            ], $roles),
            'available_sections' => $sections,
            'display_names' => $displayNames,
            'current_user_can_manage_permissions' => $this->hasPermission('can_manage_permissions'),
        ]);
    }

    /**
     * Update member (section, roles) via AJAX.
     */
    public function updateMember(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        $orchestraId = $context['orchestra_id'];
        $memberId = (int)$params['member_id'];

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_members')) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            return;
        }

        $data = [];

        if (isset($_POST['type'])) {
            $data['type'] = $_POST['type'];
        }

        // Multi-role assignment (requires can_manage_permissions)
        if (isset($_POST['role_ids']) && $this->hasPermission('can_manage_permissions')) {
            $roleIds = $_POST['role_ids'];
            if (is_string($roleIds)) {
                $roleIds = json_decode($roleIds, true) ?: [];
            }
            $roleIds = array_map('intval', array_filter($roleIds));
            $this->userOrchestraModel->setRoles($memberId, $orchestraId, $roleIds);
        }

        if (!empty($data)) {
            $relation = $this->userOrchestraModel->getUserOrchestraRelation($memberId, $orchestraId);
            if ($relation) {
                $this->userOrchestraModel->update((int)$relation['id'], $data);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    /**
     * Remove member from orchestra.
     */
    public function removeMember(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;
        $orchestraId = $context['orchestra_id'];
        $memberId = (int)$params['member_id'];

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_members')) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            return;
        }

        if ($memberId === (int)$_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Du kannst dich nicht selbst entfernen.']);
            return;
        }

        $this->userOrchestraModel->removeFromOrchestra($memberId, $orchestraId);

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }

        $this->setFlash('success', 'Mitglied entfernt.');
        $this->redirect($this->orchestraUrl('/members'));
    }

    // ── Role CRUD endpoints ─────────────────────────────────────────

    /**
     * Get all roles for the current orchestra (AJAX).
     */
    public function getRoles(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;

        $roles = $this->roleModel->getByOrchestra($context['orchestra_id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'roles' => $roles]);
    }

    /**
     * Create a new custom role (AJAX).
     */
    public function createRole(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_permissions')) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $tagColor = trim($_POST['tag_color'] ?? '#478cf4');
        $permissions = $_POST['permissions'] ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }
        $isSelfAssignable = !empty($_POST['is_self_assignable']);

        if ($name === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Name darf nicht leer sein.']);
            return;
        }

        try {
            $id = $this->roleModel->createRole($context['orchestra_id'], $name, $tagColor, $permissions, $isSelfAssignable);
            $role = $this->roleModel->findByIdDecoded($id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'role' => $role]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Rolle konnte nicht erstellt werden: ' . $e->getMessage()]);
        }
    }

    /**
     * Update an existing role (AJAX). Only Leitung (is_system=1) is immutable.
     */
    public function updateRole(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_permissions')) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            return;
        }

        $roleId = (int)($params['role_id'] ?? 0);
        $role = $this->roleModel->findById($roleId);

        if (!$role || (int)$role['orchestra_id'] !== $context['orchestra_id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Rolle nicht gefunden']);
            return;
        }

        if (!empty($role['is_system'])) {
            http_response_code(403);
            echo json_encode(['error' => 'System-Rollen können nicht bearbeitet werden.']);
            return;
        }

        $data = [];
        if (isset($_POST['name'])) $data['name'] = trim($_POST['name']);
        if (isset($_POST['tag_color'])) $data['tag_color'] = trim($_POST['tag_color']);
        if (isset($_POST['permissions'])) {
            $perms = $_POST['permissions'];
            if (is_string($perms)) $perms = json_decode($perms, true) ?: [];
            $data['permissions'] = $perms;
        }
        if (isset($_POST['is_self_assignable'])) {
            $data['is_self_assignable'] = !empty($_POST['is_self_assignable']);
        }

        if (isset($_POST['is_default'])) {
            $toggled = $this->roleModel->toggleDefault($context['orchestra_id'], $roleId, !empty($_POST['is_default']));
            if (!$toggled) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Es muss mindestens eine Standardrolle existieren.']);
                return;
            }
        }

        $success = $this->roleModel->updateRole($roleId, $data);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    /**
     * Delete a custom role (AJAX).
     */
    public function deleteRole(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_permissions')) {
            http_response_code(403);
            echo json_encode(['error' => 'Keine Berechtigung']);
            return;
        }

        $roleId = (int)($params['role_id'] ?? 0);
        $role = $this->roleModel->findById($roleId);

        if (!$role || (int)$role['orchestra_id'] !== $context['orchestra_id']) {
            http_response_code(404);
            echo json_encode(['error' => 'Rolle nicht gefunden']);
            return;
        }

        if (!empty($role['is_system'])) {
            http_response_code(403);
            echo json_encode(['error' => 'System-Rollen können nicht gelöscht werden.']);
            return;
        }

        $success = $this->roleModel->deleteRole($roleId);

        if (!$success) {
            http_response_code(400);
            echo json_encode(['error' => 'Rolle hat noch zugewiesene Mitglieder oder ist die letzte Rolle.']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
