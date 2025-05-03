<?php
namespace App\Core;

/**
 * Template Renderer Class
 * Handles template rendering with layout support
 */
class TemplateRenderer
{
    /**
     * @var string Layout file to use
     */
    private $layout = 'layouts/default';
    
    /**
     * @var array Data for the template
     */
    private $data = [];
    
    /**
     * @var string Output content
     */
    private $content = '';
    
    /**
     * Render a template
     * 
     * @param string $template Template file path
     * @param array $data Template data
     * @return string Rendered content
     */
    public function render($template, $data = [])
    {
        // Store the data
        $this->data = $data;
        
        // Capture the template output
        ob_start();
        $this->renderTemplate($template, $data);
        $this->content = ob_get_clean();
        
        // If a layout should be used
        if ($this->layout) {
            // Render the layout
            ob_start();
            $this->renderTemplate($this->layout, array_merge($data, ['content' => $this->content]));
            $this->content = ob_get_clean();
        }
        
        return $this->content;
    }
    
    /**
     * Set the layout to use
     * 
     * @param string $layout Layout template file path
     * @param array $params Layout parameters
     * @return mixed Output of the layout or false
     */
    public function layout($layout, $params = [])
    {
        $this->layout = $layout;
        return true;
    }
    
    /**
     * Render a partial template
     * 
     * @param string $template Template file path
     * @param array $data Template data
     * @return void
     */
    private function renderTemplate($template, $data = [])
    {
        // Create a new template instance as $this for the view
        $template_obj = clone $this;
        
        // Extract data to make it available in the view
        extract($data);
        
        // Include the template
        include APP_ROOT . '/Views/' . $template . '.php';
    }
} 