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
    protected function render($view, $data = [])
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
    protected function renderPartial($view, $data = [])
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
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    protected function isLoggedIn()
    {
        return isset($_SESSION['username']);
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool
     */
    protected function isAdmin()
    {
        return isset($_SESSION['type']) && $_SESSION['type'] === 'Dirigent';
    }
    
    /**
     * Check if user is leader
     * 
     * @return bool
     */
    protected function isLeader()
    {
        return isset($_SESSION['username']) && strpos($_SESSION['username'], '♚') !== false;
    }
    
    /**
     * Add alert message to session
     * 
     * @param string $title Alert title
     * @param string $message Alert message
     * @param string $type Alert type (success, error, info)
     * @param string|null $details Optional detailed information
     * @return void
     */
    protected function addAlert($title, $message, $type, $details = null)
    {
        if (!isset($_SESSION['alerts']) || !is_array($_SESSION['alerts'])) {
            $_SESSION['alerts'] = [];
        }
        
        $_SESSION['alerts'][] = [$title, $message, $type, $details];
    }
    
    /**
     * Set flash message
     * 
     * @param string $type Message type (success, error, info, warning)
     * @param string $message Message text
     * @return void
     */
    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
} 