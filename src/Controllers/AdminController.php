<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Organization;
use App\Models\User;

/**
 * Super-Admin Panel Controller
 *
 * Access restricted to user with username "admin" via normal login.
 */
class AdminController extends Controller
{
    private Organization $orgModel;
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->orgModel = new Organization();
        $this->userModel = new User();
    }

    public function dashboard(): void
    {
        if (!$this->requireSuperAdmin()) return;

        $organizations = $this->orgModel->getAllWithStats();

        foreach ($organizations as &$org) {
            $orgAccount = $this->orgModel->getOrgAccount((int)$org['id']);
            if (!$orgAccount) {
                $result = $this->userModel->createOrgAccount((int)$org['id'], $org['slug']);
                $orgAccount = $result['user'];
                $orgAccount['generated_password'] = $result['password'];
            }
            $org['org_account'] = $orgAccount;
        }

        $this->render('admin/dashboard', [
            'currentPage' => 'admin_panel',
            'organizations' => $organizations,
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    public function createOrg(): void
    {
        if (!$this->requireSuperAdmin()) return;

        $this->render('admin/create-org', [
            'currentPage' => 'admin_panel',
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    public function storeOrg(): void
    {
        if (!$this->requireSuperAdmin()) return;
        $this->protectCSRF();

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');

        if ($name === '' || $slug === '') {
            $this->setFlash('error', 'Name und Slug sind erforderlich.');
            $this->redirect('/admin/orgs/create');
            return;
        }

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $this->setFlash('error', 'Slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.');
            $this->redirect('/admin/orgs/create');
            return;
        }

        if ($this->orgModel->findBySlug($slug)) {
            $this->setFlash('error', 'Dieser Slug ist bereits vergeben.');
            $this->redirect('/admin/orgs/create');
            return;
        }

        try {
            $orgId = $this->orgModel->insert(['name' => $name, 'slug' => $slug]);
            if (!$orgId) {
                throw new \Exception('insert() returned false');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', 'Organisation konnte nicht erstellt werden.', $e->getMessage());
            $this->redirect('/admin/orgs/create');
            return;
        }

        $result = $this->userModel->createOrgAccount((int)$orgId, $slug);

        $this->render('admin/create-org', [
            'currentPage' => 'admin_panel',
            'csrf_token' => $this->getCSRFToken(),
            'created' => [
                'org_name' => $name,
                'username' => $result['user']['username'],
                'password' => $result['password'],
            ],
        ]);
    }

    public function editOrg(array $params): void
    {
        if (!$this->requireSuperAdmin()) return;

        $org = $this->orgModel->findBySlug($params['org_slug'] ?? '');
        if (!$org) {
            $this->setFlash('error', 'Organisation nicht gefunden.');
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->render('admin/edit-org', [
            'currentPage' => 'admin_panel',
            'org' => $org,
            'csrf_token' => $this->getCSRFToken(),
        ]);
    }

    public function updateOrg(array $params): void
    {
        if (!$this->requireSuperAdmin()) return;
        $this->protectCSRF();

        $org = $this->orgModel->findBySlug($params['org_slug'] ?? '');
        if (!$org) {
            $this->jsonResponse(['error' => 'Organisation nicht gefunden.'], 404);
            return;
        }
        $orgId = (int)$org['id'];

        $field = $_POST['field'] ?? '';
        $value = trim($_POST['value'] ?? '');
        $allowed = ['name', 'slug'];

        if (!in_array($field, $allowed) || $value === '') {
            $this->jsonResponse(['error' => 'Ungültiges Feld oder leerer Wert.'], 400);
            return;
        }

        if ($field === 'slug') {
            if (!preg_match('/^[a-z0-9-]+$/', $value)) {
                $this->jsonResponse(['error' => 'Slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.'], 400);
                return;
            }
            $existing = $this->orgModel->findBySlug($value);
            if ($existing && (int)$existing['id'] !== $orgId) {
                $this->jsonResponse(['error' => 'Dieser Slug ist bereits vergeben.'], 400);
                return;
            }
        }

        $this->orgModel->update($orgId, [$field => $value]);

        // Cascade slug rename to org account username
        if ($field === 'slug' && $value !== $org['slug']) {
            $this->orgModel->renameOrgAccount($orgId, $value);
        }

        $this->jsonResponse(['success' => true]);
    }

    public function deleteOrg(array $params): void
    {
        if (!$this->requireSuperAdmin()) return;
        $this->protectCSRF();

        $org = $this->orgModel->findBySlug($params['org_slug'] ?? '');
        if (!$org) {
            $this->setFlash('error', 'Organisation nicht gefunden.');
            $this->redirect('/admin/dashboard');
            return;
        }
        $orgId = (int)$org['id'];

        // Delete org account
        $orgAccount = $this->orgModel->getOrgAccount($orgId);
        if ($orgAccount) {
            $this->userModel->delete((int)$orgAccount['id']);
        }

        try {
            $this->orgModel->delete($orgId);
        } catch (\Exception $e) {
            $this->setFlash('error', 'Organisation konnte nicht gelöscht werden.', $e->getMessage());
            $this->redirect('/admin/dashboard');
            return;
        }
        $this->setFlash('success', 'Organisation gelöscht.');
        $this->redirect('/admin/dashboard');
    }

    public function showPassword(array $params): void
    {
        if (!$this->requireSuperAdmin()) return;

        $orgAccount = $this->orgModel->getOrgAccount((int)($this->orgModel->findBySlug($params['org_slug'] ?? '')['id'] ?? 0));
        if (!$orgAccount) {
            $this->jsonResponse(['error' => 'Kein Orga-Account gefunden.'], 404);
            return;
        }

        // Passwords are hashed — we can only show the username
        // To reveal a password, regenerate it
        $this->jsonResponse([
            'username' => $orgAccount['username'],
            'message' => 'Passwort ist gehasht. Nutze "PW neu generieren" für ein neues Passwort.',
        ]);
    }

    public function regeneratePassword(array $params): void
    {
        if (!$this->requireSuperAdmin()) return;
        $this->protectCSRF();

        $org = $this->orgModel->findBySlug($params['org_slug'] ?? '');
        if (!$org) {
            $this->setFlash('error', 'Organisation nicht gefunden.');
            $this->redirect('/admin/dashboard');
            return;
        }

        $orgAccount = $this->orgModel->getOrgAccount((int)$org['id']);
        if (!$orgAccount) {
            $this->setFlash('error', 'Kein Orga-Account gefunden.');
            $this->redirect('/admin/dashboard');
            return;
        }

        $newPassword = bin2hex(random_bytes(8));
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userModel->update((int)$orgAccount['id'], ['password' => $hash]);

        if ($this->isJsonRequest()) {
            $this->jsonResponse(['password' => $newPassword]);
            return;
        }

        $this->setFlash('success', 'Neues Passwort: ' . $newPassword);
        $this->redirect('/admin/orgs/' . ($org['slug'] ?? $params['org_slug']) . '/edit');
    }

    public function logout(): void
    {
        $this->redirect('/logout');
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
