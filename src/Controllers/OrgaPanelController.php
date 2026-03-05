<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Organization;
use App\Models\Orchestra;
use App\Models\User;
use App\Models\InviteLink;
use App\Models\UserOrchestra;

/**
 * Orga-Panel Controller
 *
 * For org-admin accounts (is_org_admin flag). Manages ensembles within the org's organization.
 */
class OrgaPanelController extends Controller
{
    private Organization $orgModel;
    private Orchestra $orchestraModel;
    private User $userModel;
    private InviteLink $inviteLinkModel;
    private UserOrchestra $userOrchestraModel;

    public function __construct()
    {
        parent::__construct();
        $this->orgModel = new Organization();
        $this->orchestraModel = new Orchestra();
        $this->userModel = new User();
        $this->inviteLinkModel = new InviteLink();
        $this->userOrchestraModel = new UserOrchestra();
    }

    public function dashboard(): void
    {
        if (!$this->requireOrgAdmin()) return;

        $orgId = (int)$_SESSION['organization_id'];
        $org = $this->orgModel->findById($orgId);
        if (!$org) {
            $this->setFlash('error', 'Organisation nicht gefunden.');
            $this->redirect('/login');
            return;
        }

        $ensembles = $this->orgModel->getEnsembles($orgId);

        foreach ($ensembles as &$ensemble) {
            $ensemble['conductors'] = $this->orchestraModel->getConductors((int)$ensemble['id']);
            $ensemble['member_count'] = $this->userOrchestraModel->getOrchestraUserCount((int)$ensemble['id']);
            $ensemble['member_link'] = $this->inviteLinkModel->getActiveMemberLink((int)$ensemble['id']);
            $ensemble['conductor_link'] = $this->inviteLinkModel->getActiveConductorLink((int)$ensemble['id']);
        }

        $this->render('orga/dashboard', [
            'currentPage' => 'orga_panel',
            'org' => $org,
            'ensembles' => $ensembles,
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    public function createEnsemble(): void
    {
        if (!$this->requireOrgAdmin()) return;

        $orgId = (int)$_SESSION['organization_id'];
        $org = $this->orgModel->findById($orgId);

        $this->render('orga/create-ensemble', [
            'currentPage' => 'orga_panel',
            'org' => $org,
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    public function storeEnsemble(): void
    {
        if (!$this->requireOrgAdmin()) return;
        $this->protectCSRF();

        $orgId = (int)$_SESSION['organization_id'];
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');

        if ($name === '') {
            $this->setFlash('error', 'Ensemblename erforderlich.');
            $this->redirect('/orga/ensembles/create');
            return;
        }

        // Auto-generate slug from name if not provided
        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', strtolower($name)));
            $slug = trim($slug, '-') ?: 'ensemble';
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->setFlash('error', 'Kürzel darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.');
            $this->redirect('/orga/ensembles/create');
            return;
        }

        // Ensure unique slug
        $baseSlug = $slug;
        $i = 1;
        while ($this->orchestraModel->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $i++;
        }

        try {
            $orchestraId = $this->orchestraModel->createOrchestra([
                'name' => $name,
                'slug' => $slug,
                'organization_id' => $orgId,
            ]);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Ensemble konnte nicht erstellt werden: ' . $e->getMessage());
            $this->redirect('/orga/ensembles/create');
            return;
        }

        $this->inviteLinkModel->generate((int)$orchestraId, InviteLink::TYPE_MEMBER);

        $ensemble = $this->orchestraModel->findById((int)$orchestraId);
        $this->setFlash('success', 'Ensemble erstellt! Der Einladungslink wurde automatisch generiert.');
        $this->redirect('/orga/ensembles/' . ($ensemble['slug'] ?? $orchestraId) . '/edit');
    }

    public function editEnsemble(array $params): void
    {
        if (!$this->requireOrgAdmin()) return;

        $ensemble = $this->orchestraModel->findBySlug($params['ensemble_slug'] ?? '');
        if (!$ensemble || (int)$ensemble['organization_id'] !== (int)$_SESSION['organization_id']) {
            $this->setFlash('error', 'Ensemble nicht gefunden.');
            $this->redirect('/orga/dashboard');
            return;
        }

        $org = $this->orgModel->findById((int)$_SESSION['organization_id']);
        $conductors = $this->orchestraModel->getConductors((int)$ensemble['id']);
        $memberLink = $this->inviteLinkModel->getActiveMemberLink((int)$ensemble['id']);
        $conductorLink = $this->inviteLinkModel->getActiveConductorLink((int)$ensemble['id']);
        $memberCount = $this->userOrchestraModel->getOrchestraUserCount((int)$ensemble['id']);

        $this->render('orga/edit-ensemble', [
            'currentPage' => 'orga_panel',
            'ensemble' => $ensemble,
            'org' => $org,
            'conductors' => $conductors,
            'memberLink' => $memberLink,
            'conductorLink' => $conductorLink,
            'memberCount' => $memberCount,
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    public function updateEnsemble(array $params): void
    {
        if (!$this->requireOrgAdmin()) return;
        $this->protectCSRF();

        $ensemble = $this->orchestraModel->findBySlug($params['ensemble_slug'] ?? '');
        if (!$ensemble || (int)$ensemble['organization_id'] !== (int)$_SESSION['organization_id']) {
            $this->jsonError('Ensemble nicht gefunden.', 404);
            return;
        }

        $field = $_POST['field'] ?? '';
        $value = trim($_POST['value'] ?? '');
        $allowed = ['name', 'slug'];

        if (!in_array($field, $allowed) || $value === '') {
            $this->jsonError('Ungültiges Feld oder leerer Wert.', 400);
            return;
        }

        if ($field === 'slug') {
            if (!preg_match('/^[a-z0-9-]+$/', $value)) {
                $this->jsonError('Kürzel darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.', 400);
                return;
            }
            $existing = $this->orchestraModel->findBySlug($value);
            if ($existing && (int)$existing['id'] !== (int)$ensemble['id']) {
                $this->jsonError('Dieses Kürzel ist bereits vergeben.', 400);
                return;
            }
        }

        $this->orchestraModel->updateSettings((int)$ensemble['id'], [$field => $value]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    /**
     * Generate a new invite link (member or conductor type).
     */
    public function generateLink(array $params): void
    {
        if (!$this->requireOrgAdmin()) return;
        $this->protectCSRF();

        $ensemble = $this->validateEnsembleAccess($params['ensemble_slug'] ?? '');
        if (!$ensemble) return;

        $type = $_POST['type'] ?? InviteLink::TYPE_MEMBER;
        if (!in_array($type, [InviteLink::TYPE_MEMBER, InviteLink::TYPE_CONDUCTOR])) {
            $this->jsonError('Ungültiger Link-Typ.', 400);
            return;
        }

        $link = $this->inviteLinkModel->generate((int)$ensemble['id'], $type);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'link' => $link,
            'url' => '/invite/' . $link['token'],
        ]);
    }

    /**
     * Regenerate (expire old + create new) invite link.
     */
    public function regenerateLink(array $params): void
    {
        if (!$this->requireOrgAdmin()) return;
        $this->protectCSRF();

        $ensemble = $this->validateEnsembleAccess($params['ensemble_slug'] ?? '');
        if (!$ensemble) return;

        $type = $_POST['type'] ?? InviteLink::TYPE_MEMBER;
        if (!in_array($type, [InviteLink::TYPE_MEMBER, InviteLink::TYPE_CONDUCTOR])) {
            $this->jsonError('Ungültiger Link-Typ.', 400);
            return;
        }

        $link = $this->inviteLinkModel->regenerate((int)$ensemble['id'], $type);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'link' => $link,
            'url' => '/invite/' . $link['token'],
        ]);
    }

    /**
     * Remove a conductor from the ensemble entirely.
     */
    public function removeConductor(array $params): void
    {
        if (!$this->requireOrgAdmin()) return;
        $this->protectCSRF();

        $ensemble = $this->validateEnsembleAccess($params['ensemble_slug'] ?? '');
        if (!$ensemble) return;

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->jsonError('Benutzer-ID fehlt.', 400);
            return;
        }

        $this->userOrchestraModel->removeFromOrchestra($userId, (int)$ensemble['id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function deleteEnsemble(array $params): void
    {
        if (!$this->requireOrgAdmin()) return;
        $this->protectCSRF();

        $ensemble = $this->orchestraModel->findBySlug($params['ensemble_slug'] ?? '');
        if (!$ensemble || (int)$ensemble['organization_id'] !== (int)$_SESSION['organization_id']) {
            $this->setFlash('error', 'Ensemble nicht gefunden.');
            $this->redirect('/orga/dashboard');
            return;
        }

        try {
            $this->orchestraModel->delete((int)$ensemble['id']);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Ensemble konnte nicht gelöscht werden: ' . $e->getMessage());
            $this->redirect('/orga/dashboard');
            return;
        }
        $this->setFlash('success', 'Ensemble gelöscht.');
        $this->redirect('/orga/dashboard');
    }

    private function validateEnsembleAccess(string $ensembleSlug): ?array
    {
        $ensemble = $this->orchestraModel->findBySlug($ensembleSlug);
        if (!$ensemble || (int)$ensemble['organization_id'] !== (int)$_SESSION['organization_id']) {
            $this->jsonError('Ensemble nicht gefunden.', 404);
            return null;
        }
        return $ensemble;
    }

    private function jsonError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
    }
}
