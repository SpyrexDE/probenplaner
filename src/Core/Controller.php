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
    private function isJsonRequest(): bool
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
     * Check if user is admin
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return isset($_SESSION['current_role']) && $_SESSION['current_role'] === 'conductor';
    }
    
    /**
     * Check if user is leader
     * 
     * @return bool
     */
    protected function isLeader(): bool
    {
        return isset($_SESSION['current_role']) && $_SESSION['current_role'] === 'leader';
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
     * Protect against CSRF attacks
     * Call this in controllers that handle POST requests
     * 
     * @throws \Exception If CSRF token is invalid
     * @return void
     */
    protected function protectCSRF(): void
    {
        CSRF::protect();
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
     * Validate orchestra context for orchestra-specific routes
     * 
     * @param array $params Route parameters (should contain orchestra_id)
     * @return array|null Orchestra context data or null if invalid
     */
    protected function validateOrchestraContext(array $params): ?array
    {
        // Must be logged in
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
        
        // Extract orchestra ID from route parameters
        $orchestraId = (int)($params['orchestra_id'] ?? 0);
        
        if (!$orchestraId) {
            $this->addAlert('Fehler!', 'Ungültige Orchester-ID.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Ungültige Orchester-ID.']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return null;
        }
        
        // Check if user has access to this orchestra
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $relation = $userOrchestraModel->getUserOrchestraRelation($_SESSION['user_id'], $orchestraId, true);
        
        if (!$relation) {
            $this->addAlert('Fehler!', 'Sie haben keinen Zugriff auf dieses Orchester.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Kein Zugriff auf dieses Orchester']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return null;
        }
        
        // Get orchestra details
        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($orchestraId);
        
        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Orchester nicht gefunden.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Orchester nicht gefunden']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return null;
        }
        
        // Set current orchestra context if not already set or different
        if (!isset($_SESSION['current_orchestra_id']) || $_SESSION['current_orchestra_id'] != $orchestraId) {
            $_SESSION['current_orchestra_id'] = $orchestraId;
            $_SESSION['current_orchestra_name'] = $orchestra['name'];
            $_SESSION['current_type'] = $relation['type'];
            $_SESSION['current_role'] = $relation['role'];
        }
        
        return [
            'orchestra_id' => $orchestraId,
            'orchestra' => $orchestra,
            'relation' => $relation,
            'user_role' => $relation['role'],
            'user_type' => $relation['type']
        ];
    }
    
    /**
     * Require specific role in current orchestra
     * 
     * @param string $requiredRole Required role (member, leader, conductor)
     * @param array|null $context Orchestra context from validateOrchestraContext()
     * @return bool
     */
    protected function requireRole(string $requiredRole, ?array $context = null): bool
    {
        if (!$context && isset($_SESSION['current_role'])) {
            $userRole = $_SESSION['current_role'];
        } elseif ($context) {
            $userRole = $context['user_role'];
        } else {
            $this->addAlert('Fehler!', 'Keine Berechtigung.', 'error');
            if ($this->isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
                exit;
            }
            $this->redirect('/orchestras/select');
            return false;
        }
        
        // Role hierarchy: conductor > leader > member
        $roleHierarchy = ['member' => 1, 'leader' => 2, 'conductor' => 3];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        if ($userLevel < $requiredLevel) {
            $this->addAlert('Fehler!', 'Sie haben nicht die erforderliche Berechtigung für diese Aktion.', 'error');
            
            if ($this->isJsonRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Nicht ausreichend berechtigt']);
                exit;
            }
            // Redirect based on current role
            if (isset($_SESSION['current_orchestra_id'])) {
                if ($userRole === 'conductor') {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises/admin');
                } else {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises');
                }
            } else {
                $this->redirect('/orchestras/select');
            }
            return false;
        }
        
        return true;
    }
} 