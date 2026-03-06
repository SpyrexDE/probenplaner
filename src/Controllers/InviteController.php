<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InviteLink;
use App\Models\Orchestra;
use App\Models\User;
use App\Models\UserOrchestra;

/**
 * Invite Controller
 *
 * Handles invite-link landing pages, token validation, and join-with-section flow.
 */
class InviteController extends Controller
{
    private InviteLink $inviteLinkModel;
    private Orchestra $orchestraModel;
    private User $userModel;
    private UserOrchestra $userOrchestraModel;

    public function __construct()
    {
        parent::__construct();
        $this->inviteLinkModel = new InviteLink();
        $this->orchestraModel = new Orchestra();
        $this->userModel = new User();
        $this->userOrchestraModel = new UserOrchestra();
    }

    /**
     * Landing page for /invite/{token}.
     */
    public function landing(array $params): void
    {
        $token = $params['token'] ?? '';
        $link = $this->inviteLinkModel->findActiveByToken($token);

        if (!$link) {
            $this->render('invite/invalid', ['currentPage' => 'invite_invalid']);
            return;
        }

        $orchestra = $this->orchestraModel->findById((int)$link['orchestra_id']);
        if (!$orchestra) {
            $this->render('invite/invalid', ['currentPage' => 'invite_invalid']);
            return;
        }

        $orgName = null;
        if (!empty($orchestra['organization_id'])) {
            $org = (new \App\Models\Organization())->findById((int)$orchestra['organization_id']);
            $orgName = $org['name'] ?? null;
        }

        $linkType = InviteLink::getLinkType($link);

        if ($this->isLoggedIn()) {
            // Block admin/orga accounts from using invite links
            if ($this->isAdminOrOrgAccount()) {
                $this->setFlash('error', 'Admin- und Orga-Accounts können keine Einladungslinks verwenden.');
                $this->redirect('/orchestras/select');
                return;
            }

            if (!empty($link['keycloak_only']) && !$this->isKeycloakUser()) {
                $this->setFlash('error', 'Dieser Einladungslink ist nur für JMD-Accounts.');
                $this->redirect('/orchestras/select');
                return;
            }

            $existing = $this->userOrchestraModel->getUserOrchestraRelation(
                (int)$_SESSION['user_id'],
                (int)$orchestra['id']
            );

            if ($existing) {
                if ($linkType === InviteLink::TYPE_CONDUCTOR) {
                    $perms = $this->userOrchestraModel->getPermissions((int)$_SESSION['user_id'], (int)$orchestra['id']);
                    if (!empty($perms['can_manage_ensemble'])) {
                        $this->setFlash('info', 'Du bist bereits Leitung dieses Ensembles.');
                    } else {
                        $roleId = $this->inviteLinkModel->getJoinRoleId($link);
                        if ($roleId) {
                            $this->userOrchestraModel->setRole((int)$_SESSION['user_id'], (int)$orchestra['id'], $roleId);
                        }
                        $this->setFlash('success', 'Du wurdest als Leitung hinzugefügt!');
                    }
                } else {
                    $this->setFlash('info', 'Du bist bereits Mitglied dieses Ensembles.');
                }
                $this->redirect('/orchestras/select');
                return;
            }

            // Conductor links: join directly without section selection
            if ($linkType === InviteLink::TYPE_CONDUCTOR) {
                $userId = (int)$_SESSION['user_id'];
                $orchestraId = (int)$orchestra['id'];
                $roleId = $this->inviteLinkModel->getJoinRoleId($link);
                $result = $this->userModel->joinOrchestra($userId, $orchestraId, 'Leitung', $roleId);
                if (is_array($result) && isset($result['error'])) {
                    $this->setFlash('error', $result['message'] ?? 'Beitritt fehlgeschlagen.');
                    $this->redirect('/orchestras/select');
                    return;
                }
                $_SESSION['current_orchestra_id'] = $orchestraId;
                $this->setFlash('success', 'Willkommen als Leitung im Ensemble!');
                $this->redirect('/orchestras/select');
                return;
            }

            // Member links: show section picker
            $this->render('invite/section-picker', [
                'currentPage' => 'invite_section_picker',
                'orchestra' => $orchestra,
                'orgName' => $orgName,
                'token' => $token,
                'linkType' => $linkType,
                'csrf_token' => $this->getCSRFToken(),
            ]);
            return;
        }

        $_SESSION['invite_token'] = $token;

        $this->render('invite/landing', [
            'currentPage' => 'invite_landing',
            'orchestra' => $orchestra,
            'orgName' => $orgName,
            'linkType' => $linkType,
            'keycloakOnly' => !empty($link['keycloak_only']),
        ]);
    }

    /**
     * Process the join after section selection (POST).
     */
    public function join(): void
    {
        $this->requireLogin();
        $this->protectCSRF();

        if ($this->isAdminOrOrgAccount()) {
            $this->setFlash('error', 'Admin- und Orga-Accounts können keinen Ensembles beitreten.');
            $this->redirect('/orchestras/select');
            return;
        }

        $token = $_POST['token'] ?? '';
        $section = $_POST['section'] ?? '';

        $link = $this->inviteLinkModel->findActiveByToken($token);
        if (!$link) {
            $this->setFlash('error', 'Ungültiger oder abgelaufener Link.');
            $this->redirect('/orchestras/select');
            return;
        }

        if (!empty($link['keycloak_only']) && !$this->isKeycloakUser()) {
            $this->setFlash('error', 'Dieser Einladungslink ist nur für JMD-Accounts.');
            $this->redirect('/orchestras/select');
            return;
        }

        $linkType = InviteLink::getLinkType($link);

        // Conductor links don't require section selection
        if ($linkType === InviteLink::TYPE_CONDUCTOR) {
            $section = 'Leitung';
        } elseif ($section === '') {
            $this->setFlash('error', 'Bitte wähle dein Register.');
            $this->redirect('/invite/' . urlencode($token));
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $orchestraId = (int)$link['orchestra_id'];

        $roleId = $this->inviteLinkModel->getJoinRoleId($link);
        $result = $this->userModel->joinOrchestra($userId, $orchestraId, $section, $roleId);
        if (is_array($result) && isset($result['error'])) {
            $this->setFlash('error', $result['message'] ?? 'Beitritt fehlgeschlagen.');
            $this->redirect('/orchestras/select');
            return;
        }

        $this->inviteLinkModel->redeem((int)$link['id']);

        $_SESSION['current_orchestra_id'] = $orchestraId;

        $message = $linkType === InviteLink::TYPE_CONDUCTOR
            ? 'Willkommen als Leitung im Ensemble!'
            : 'Willkommen im Ensemble!';
        $this->setFlash('success', $message);
        $this->redirect('/orchestras/select');
    }

    /**
     * Redeem page — accessible from orchestra selection (3a).
     */
    public function redeemForm(): void
    {
        $this->requireLogin();

        $this->render('invite/redeem', [
            'currentPage' => 'invite_redeem',
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    /**
     * Process redeem from pasted link (POST).
     */
    public function processRedeem(): void
    {
        $this->requireLogin();
        $this->protectCSRF();

        $rawLink = trim($_POST['link'] ?? '');

        $token = $rawLink;
        if (str_contains($rawLink, '/invite/')) {
            $parts = explode('/invite/', $rawLink);
            $token = trim(end($parts));
        }

        if ($token === '') {
            $this->setFlash('error', 'Bitte gib einen Link ein.');
            $this->redirect('/orchestras/redeem');
            return;
        }

        $this->redirect('/invite/' . urlencode($token));
    }



    /**
     * Check if current user is admin or orga account.
     */
    private function isKeycloakUser(): bool
    {
        $user = $this->userModel->findById((int)$_SESSION['user_id']);
        return !empty($user['keycloak_id']);
    }

    private function isAdminOrOrgAccount(): bool
    {
        return !empty($_SESSION['is_admin']) || !empty($_SESSION['is_org_admin']);
    }

    /**
     * Regenerate the member invite link for the current orchestra (AJAX).
     */
    public function regenerate(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_members')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
            return;
        }

        $orchestraId = $context['orchestra_id'];
        $userId = (int)$_SESSION['user_id'];
        $link = $this->inviteLinkModel->regenerate($orchestraId, InviteLink::TYPE_MEMBER, $userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'token' => $link['token']]);
    }

    /**
     * Toggle keycloak-only setting on the member invite link (AJAX).
     */
    public function toggleKeycloak(array $params): void
    {
        $this->requireLogin();
        $context = $this->validateOrchestraContext($params);
        if (!$context) return;

        $this->protectCSRF();

        if (!$this->hasPermission('can_manage_members')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
            return;
        }

        $orchestraId = $context['orchestra_id'];
        $link = $this->inviteLinkModel->getActiveMemberLink($orchestraId);

        if (!$link) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Kein aktiver Link']);
            return;
        }

        $newVal = empty($link['keycloak_only']) ? 1 : 0;
        $this->inviteLinkModel->update((int)$link['id'], ['keycloak_only' => $newVal]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'keycloak_only' => (bool)$newVal]);
    }
}
