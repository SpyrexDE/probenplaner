<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Organization;
use App\Models\User;

/**
 * Super-Admin Panel Controller
 *
 * Access restricted to super-admin via is_super_admin flag.
 */
class AdminController extends Controller
{
    /** Allowed organization slug characters (underscore needed for JOinJMD IDs). */
    private const SLUG_PATTERN = '/^[a-z0-9_-]+$/';
    /**
     * organizations.slug is VARCHAR(100), but the slug also builds the org account's login
     * e-mail "{slug}-admin@probenplaner.local" (User::createOrgAccount,
     * Organization::renameOrgAccount). Its local part may have at most 64 characters
     * (FILTER_VALIDATE_EMAIL), so the slug is limited to 64 - strlen('-admin') = 58.
     */
    private const SLUG_MAX_LENGTH = 58;
    private const SLUG_ERROR = 'Slug darf nur Kleinbuchstaben, Zahlen, Binde- und Unterstriche enthalten.';
    /** First path segments the router uses itself (/api/v1/...), so no organization may take them */
    private const RESERVED_SLUGS = ['api'];

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
                // On a failed insert 'user' is null: leave org_account empty (the card hides it)
                if (!empty($result['user'])) {
                    $orgAccount = $result['user'];
                    $orgAccount['generated_password'] = $result['password'];
                } else {
                    error_log('AdminController::dashboard: org account for organization ' . (int)$org['id'] . ' could not be created');
                }
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

        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            $this->setFlash('error', self::SLUG_ERROR);
            $this->redirect('/admin/orgs/create');
            return;
        }

        if (strlen($slug) > self::SLUG_MAX_LENGTH) {
            $this->setFlash('error', 'Slug darf höchstens ' . self::SLUG_MAX_LENGTH . ' Zeichen lang sein.');
            $this->redirect('/admin/orgs/create');
            return;
        }

        if (in_array($slug, self::RESERVED_SLUGS, true) || $this->orgModel->findBySlug($slug)) {
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
            $this->setFlash('error', 'Organisation konnte nicht erstellt werden: ' . $e->getMessage());
            $this->redirect('/admin/orgs/create');
            return;
        }

        $result = $this->userModel->createOrgAccount((int)$orgId, $slug);

        if (empty($result['user']) || empty($result['password'])) {
            // The org exists, but its account insert failed (e.g. e-mail already taken).
            // The dashboard creates missing org accounts when it is opened.
            error_log('AdminController::storeOrg: org account for organization ' . (int)$orgId . " ('{$slug}') could not be created");
            $this->setFlash('error', 'Die Organisation wurde erstellt, aber der Orga-Account konnte nicht angelegt werden.');
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->render('admin/create-org', [
            'currentPage' => 'admin_panel',
            'csrf_token' => $this->getCSRFToken(),
            'created' => [
                'org_name' => $name,
                'email' => $result['user']['email'],
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
            if (!preg_match(self::SLUG_PATTERN, $value)) {
                $this->jsonResponse(['error' => self::SLUG_ERROR], 400);
                return;
            }
            if (strlen($value) > self::SLUG_MAX_LENGTH) {
                $this->jsonResponse(['error' => 'Slug darf höchstens ' . self::SLUG_MAX_LENGTH . ' Zeichen lang sein.'], 400);
                return;
            }
            $existing = $this->orgModel->findBySlug($value);
            if (in_array($value, self::RESERVED_SLUGS, true) || ($existing && (int)$existing['id'] !== $orgId)) {
                $this->jsonResponse(['error' => 'Dieser Slug ist bereits vergeben.'], 400);
                return;
            }
        }

        if (!$this->orgModel->update($orgId, [$field => $value])) {
            $this->jsonResponse(['error' => 'Speichern fehlgeschlagen.'], 500);
            return;
        }

        $response = ['success' => true];

        // Cascade slug rename to org account username
        if ($field === 'slug') {
            if ($value !== $org['slug']) {
                $this->orgModel->renameOrgAccount($orgId, $value);
            }
            // The client needs the new slug to build follow-up URLs (/admin/orgs/{slug}/...)
            $response['slug'] = $value;
        }

        $this->jsonResponse($response);
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
            $this->setFlash('error', 'Organisation konnte nicht gelöscht werden: ' . $e->getMessage());
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
            'email' => $orgAccount['email'],
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
