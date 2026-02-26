<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FieldRegistry;
use App\Models\UserOrchestra;
use App\Models\User;
use App\Models\InviteLink;

/**
 * Member Controller
 *
 * Handles the members listing and member editing (section, permissions).
 */
class MemberController extends Controller
{
    private UserOrchestra $userOrchestraModel;
    private User $userModel;
    private InviteLink $inviteLinkModel;

    public function __construct()
    {
        parent::__construct();
        $this->userOrchestraModel = new UserOrchestra();
        $this->userModel = new User();
        $this->inviteLinkModel = new InviteLink();
    }

    /**
     * Members list page (6a).
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

        // Group by section
        $grouped = [];
        foreach ($members as $member) {
            $type = $member['type'] ?? 'Sonstige';
            $grouped[$type][] = $member;
        }

        // Get invite link info
        $inviteLink = null;
        if ($canManage) {
            $inviteLink = $this->inviteLinkModel->getActiveMemberLink($orchestraId);
        }

        $this->render('members/index', [
            'currentPage' => 'members',
            'orchestraId' => $orchestraId,
            'grouped' => $grouped,
            'sections' => $sections,
            'canManage' => $canManage,
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

        $permissions = $this->userOrchestraModel->getPermissions($memberId, $orchestraId);

        $sections = FieldRegistry::getSections();
        $groupManager = new \App\Core\GroupManager();
        $displayNames = [];
        foreach ($sections as $key => $items) {
            $displayNames[$key] = $groupManager->getDisplayName($key);
            foreach ($items as $item) {
                $displayNames[$item] = $groupManager->getDisplayName($item);
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'user_id' => $user['id'],
            'display_name' => $user['display_name'] ?? $user['username'],
            'username' => $user['username'],
            'type' => $relation['type'] ?? '',
            'is_small_group' => !empty($relation['is_small_group']),
            'permissions' => $permissions,
            'available_sections' => $sections,
            'display_names' => $displayNames,
            'current_user_can_manage_permissions' => $this->hasPermission('can_manage_permissions'),
        ]);
    }

    /**
     * Update member (section, permissions, small_group) via AJAX.
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
        if (isset($_POST['is_small_group'])) {
            $data['is_small_group'] = (int)$_POST['is_small_group'];
        }

        // Permission updates (only if user has can_manage_permissions)
        if (isset($_POST['permissions']) && $this->hasPermission('can_manage_permissions')) {
            $perms = is_array($_POST['permissions']) ? $_POST['permissions'] : json_decode($_POST['permissions'], true);
            if (is_array($perms)) {
                $this->userOrchestraModel->setPermissions($memberId, $orchestraId, $perms);
            }
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

        // Don't allow removing yourself
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
}
