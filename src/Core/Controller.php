<?php

namespace App\Core;

/**
 * Base Controller Class
 * All controllers will extend this class
 */
class Controller
{
    /**
     * @var Database
     */
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Render a view
     * 
     * @param string $view The view file to render
     * @param array $data Data to pass to the view
     * @return void
     */
    protected function render(string $view, array $data = []): void
    {
        // Create a template renderer
        $template = new TemplateRenderer();

        // Render the view
        $content = $template->render($view, $data);

        // Output the content
        echo $content;
    }

    /**
     * Render a view without header and footer
     * 
     * @param string $view The view file to render
     * @param array $data Data to pass to the view
     * @return void
     */
    protected function renderPartial(string $view, array $data = []): void
    {
        // Extract data to make it available in the view
        extract($data);

        // Include the view
        include APP_ROOT . '/Views/' . $view . '.php';
    }

    /**
     * Detect whether the current request expects a JSON response
     *
     * @return bool
     */
    protected function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return (stripos($accept, 'application/json') !== false)
            || (strcasecmp($xhr, 'XMLHttpRequest') === 0);
    }

    /**
     * Redirect to a URL
     * 
     * @param string $url The URL to redirect to
     * @return void
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['username']);
    }

    /**
     * Check if user has a specific permission in the current orchestra.
     */
    protected function hasPermission(string $permission): bool
    {
        if (!isset($_SESSION['current_permissions'])) return false;
        return !empty($_SESSION['current_permissions'][$permission]);
    }

    /**
     * Add alert message to session
     * 
     * @param string $title Alert title
     * @param string|array $message Alert message(s)
     * @param string $type Alert type (success, error, info)
     * @param string|array|null $details Optional detailed information
     * @return void
     */
    protected function addAlert(string $title, $message, string $type, $details = null): void
    {
        if (!isset($_SESSION['alerts']) || !is_array($_SESSION['alerts'])) {
            $_SESSION['alerts'] = [];
        }

        // Handle array messages
        if (is_array($message)) {
            $message = implode(', ', $message);
        }

        // Handle array details
        if (is_array($details)) {
            $details = json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $_SESSION['alerts'][] = [$title, $message, $type, $details];
    }

    /**
     * Handle error response consistently using ErrorHandler
     * 
     * @param array $errorResponse Error response from ErrorHandler
     * @param string $redirectUrl URL to redirect to after showing error
     * @return void
     */
    protected function handleErrorResponse(array $errorResponse, ?string $redirectUrl = null): void
    {
        if (!$errorResponse['success']) {
            $this->addAlert(
                'Fehler!',
                $errorResponse['messages'],
                $errorResponse['type']
            );
        } else {
            $this->addAlert(
                'Erfolg!',
                $errorResponse['messages'],
                $errorResponse['type']
            );
        }

        if ($redirectUrl) {
            $this->redirect($redirectUrl);
        }
    }

    /**
     * Set flash message using SweetAlert toast
     * 
     * @param string $type Message type (success, error, info, warning)
     * @param string $message Message text
     * @param string|null $details Optional detailed information
     * @return void
     */
    protected function setFlash($type, $message, $details = null)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        // Convert type to match SweetAlert types
        $swalType = $type === 'warning' ? 'warning' : ($type === 'error' ? 'error' : ($type === 'success' ? 'success' : 'info'));

        $_SESSION['flash_messages'][] = [
            'type' => $swalType,
            'message' => $message,
            'details' => $details
        ];
    }

    /**
     * Protect against CSRF attacks.
     * Returns JSON error for AJAX or redirects with flash for form submissions.
     */
    protected function protectCSRF(): void
    {
        try {
            CSRF::protect();
        } catch (\Exception $e) {
            if ($this->isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $this->setFlash('error', $e->getMessage());
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            $this->redirect($referer);
        }
    }

    /**
     * Get CSRF token for forms
     * 
     * @return string CSRF token
     */
    protected function getCSRFToken(): string
    {
        return CSRF::getToken();
    }

    /**
     * Get CSRF token HTML field
     * 
     * @return string HTML input field
     */
    protected function getCSRFField(): string
    {
        return CSRF::getTokenField();
    }

    /**
     * Validate orchestra context for orchestra-specific routes.
     * Resolves the URL slug to a numeric orchestra ID.
     * 
     * @param array $params Route parameters (orchestra_id contains the slug)
     * @return array|null Orchestra context data or null if invalid
     */
    protected function validateOrchestraContext(array $params): ?array
    {
        if (!$this->isLoggedIn()) {
            if ($this->isJsonRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt']);
                exit;
            }
            $this->redirect('/login');
            return null;
        }

        $slug = $params['orchestra_id'] ?? '';
        $orgSlug = $params['org_slug'] ?? '';

        if (empty($slug)) {
            $this->addAlert('Fehler!', 'Ungültiger Ensemble-Slug.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Ungültiger Ensemble-Slug.']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return null;
        }

        // Resolve slug to orchestra with org info
        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findBySlugWithOrg($slug);

        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Ensemble nicht gefunden.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Ensemble nicht gefunden']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return null;
        }

        $orchestraId = (int)$orchestra['id'];

        // Check user access
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $relation = $userOrchestraModel->getUserOrchestraRelation($_SESSION['user_id'], $orchestraId, true);

        if (!$relation) {
            $this->addAlert('Fehler!', 'Sie haben keinen Zugriff auf dieses Ensemble.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Kein Zugriff auf dieses Ensemble']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return null;
        }

        // Set current orchestra context
        if (!isset($_SESSION['current_orchestra_id']) || $_SESSION['current_orchestra_id'] != $orchestraId) {
            $_SESSION['current_orchestra_id'] = $orchestraId;
            $_SESSION['current_orchestra_slug'] = $orchestra['slug'];
            $_SESSION['current_org_slug'] = $orchestra['org_slug'] ?? '';
            $_SESSION['current_orchestra_name'] = $orchestra['name'];
            $_SESSION['current_type'] = $relation['type'];
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $_SESSION['current_permissions'] = $userOrchestraModel->getPermissions($_SESSION['user_id'], $orchestraId);
        }
        // Keep slugs in sync
        $_SESSION['current_orchestra_slug'] = $orchestra['slug'];
        $_SESSION['current_org_slug'] = $orchestra['org_slug'] ?? '';

        return [
            'orchestra_id' => $orchestraId,
            'orchestra' => $orchestra,
            'relation' => $relation,
            'user_type' => $relation['type'],
        ];
    }

    /**
     * Build an orchestra-scoped URL using the current session slug.
     *
     * @param string $path Path relative to the orchestra root (e.g. '/promises')
     */
    protected function orchestraUrl(string $path = ''): string
    {
        $orgSlug = $_SESSION['current_org_slug'] ?? '';
        $slug = $_SESSION['current_orchestra_slug'] ?? '';
        return '/' . $orgSlug . '/' . $slug . $path;
    }
    /**
     * Require a specific permission in the current orchestra context.
     */
    protected function requirePermission(string $permission): bool
    {
        if (!$this->hasPermission($permission)) {
            if ($this->isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
                exit;
            }
            $this->addAlert('Fehler!', 'Sie haben nicht die erforderliche Berechtigung für diese Aktion.', 'error');
            $slug = $_SESSION['current_orchestra_slug'] ?? null;
            $this->redirect($slug ? $this->orchestraUrl('/probenplan') : '/orchestras/select');
            return false;
        }
        return true;
    }

    /**
     * Require authenticated user session.
     */
    protected function requireLogin(): bool
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return false;
        }
        return true;
    }

    /**
     * Require super-admin session (username "admin").
     */
    protected function requireSuperAdmin(): bool
    {
        if (empty($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
            $this->redirect('/login');
            return false;
        }
        return true;
    }

    /**
     * Require org-admin session.
     */
    protected function requireOrgAdmin(): bool
    {
        if (empty($_SESSION['is_org_admin'])) {
            $this->redirect('/login');
            return false;
        }
        return true;
    }
}
