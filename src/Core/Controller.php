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
        return isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent';
    }
    
    /**
     * Check if user is leader
     * 
     * @return bool
     */
    protected function isLeader(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'leader';
    }
    
    /**
     * Add alert message to session
     * 
     * @param string $title Alert title
     * @param string|array $message Alert message(s)
     * @param string $type Alert type (success, error, info)
     * @param string|null $details Optional detailed information
     * @return void
     */
    protected function addAlert(string $title, $message, string $type, ?string $details = null): void
    {
        if (!isset($_SESSION['alerts']) || !is_array($_SESSION['alerts'])) {
            $_SESSION['alerts'] = [];
        }
        
        // Handle array messages
        if (is_array($message)) {
            $message = implode(', ', $message);
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
} 