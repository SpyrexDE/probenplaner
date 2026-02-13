<?php
namespace App\Core;

/**
 * ComponentHelper - Component Rendering
 * 
 * Provides standardized methods for rendering components with
 * proper parameter validation and consistent patterns.
 * 
 * This class provides helper methods for rendering components.
 */
class ComponentHelper {
    
    /**
     * Render the rehearsal-card component
     * 
     * @param array $rehearsal - The rehearsal data
     * @param string $context - Context: 'promises' or 'rehearsals' 
     * @param array $options - Additional options
     * @return string - Rendered HTML
     */
    public static function renderRehearsalCard($rehearsal, $context = 'rehearsals', $options = []) {
        // Validate required data
        if (!is_array($rehearsal) || empty($rehearsal['id'])) {
            throw new \InvalidArgumentException('Valid rehearsal array with id is required');
        }
        
        // Validate context
        $validContexts = ['promises', 'rehearsals'];
        if (!in_array($context, $validContexts)) {
            throw new \InvalidArgumentException('Context must be one of: ' . implode(', ', $validContexts));
        }
        
        // Set variables for existing component
        $context = $context;
        $options = $options;
        $rehearsal = $rehearsal;
        
        ob_start();
        include APP_ROOT . '/Views/components/rehearsal-card.php';
        return ob_get_clean();
    }
    
    /**
     * Render a form
     * 
     * @param array $fields - Array of field definitions
     * @param string $action - Form action URL
     * @param string $method - HTTP method (default: POST)
     * @param array $options - Additional form options
     * @return string - Rendered HTML
     */
    public static function renderFormWithExistingClasses($fields, $action, $method = 'POST', $options = []) {
        if (!is_array($fields) || empty($fields)) {
            throw new \InvalidArgumentException('Fields array is required');
        }
        
        if (empty($action)) {
            throw new \InvalidArgumentException('Form action is required');
        }
        
        $formClass = $options['class'] ?? 'form';
        $formAttributes = $options['attributes'] ?? [];
        
        $html = "<form action=\"{$action}\" method=\"{$method}\" class=\"{$formClass}\"";
        
        // Add additional form attributes
        foreach ($formAttributes as $attr => $value) {
            $html .= " {$attr}=\"" . htmlspecialchars($value) . "\"";
        }
        
        $html .= ">";
        
        foreach ($fields as $field) {
            $html .= self::renderFormField($field);
        }
        
        $html .= "</form>";
        return $html;
    }
    
    /**
     * Render a single form field
     * 
     * @param array $field - Field definition
     * @return string - Rendered HTML
     */
    public static function renderFormField($field) {
        if (!is_array($field) || empty($field['name'])) {
            throw new \InvalidArgumentException('Field must be array with name');
        }
        
        $type = $field['type'] ?? 'text';
        $name = $field['name'];
        $label = $field['label'] ?? ucfirst($name);
        $value = $field['value'] ?? '';
        $required = $field['required'] ?? false;
        $placeholder = $field['placeholder'] ?? '';
        $class = $field['class'] ?? '';
        $attributes = $field['attributes'] ?? [];
        
        $html = "<div class=\"form-group-modern\">";
        
        // Label
        $html .= "<label for=\"{$name}\" class=\"form-label-modern\">";
        $html .= htmlspecialchars($label);
        if ($required) {
            $html .= " <span class=\"text-red-500\">*</span>";
        }
        $html .= "</label>";
        
        // Input field
        $inputClass = "form-input-modern {$class}";
        $html .= "<input type=\"{$type}\" id=\"{$name}\" name=\"{$name}\" ";
        $html .= "value=\"" . htmlspecialchars($value) . "\" ";
        $html .= "class=\"{$inputClass}\" ";
        
        if ($placeholder) {
            $html .= "placeholder=\"" . htmlspecialchars($placeholder) . "\" ";
        }
        
        if ($required) {
            $html .= "required ";
        }
        
        // Add additional attributes
        foreach ($attributes as $attr => $attrValue) {
            $html .= "{$attr}=\"" . htmlspecialchars($attrValue) . "\" ";
        }
        
        $html .= "/>";
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Render buttons
     * 
     * @param string $text - Button text
     * @param string $variant - Button variant (primary, secondary, success, danger)
     * @param array $options - Additional options
     * @return string - Rendered HTML
     */
    public static function renderButton($text, $variant = 'primary', $options = []) {
        if (empty($text)) {
            throw new \InvalidArgumentException('Button text is required');
        }
        
        $validVariants = ['primary', 'secondary', 'success', 'danger', 'outline'];
        if (!in_array($variant, $validVariants)) {
            throw new \InvalidArgumentException('Variant must be one of: ' . implode(', ', $validVariants));
        }
        
        $type = $options['type'] ?? 'button';
        $size = $options['size'] ?? '';
        $icon = $options['icon'] ?? false;
        $id = $options['id'] ?? '';
        $class = $options['class'] ?? '';
        $attributes = $options['attributes'] ?? [];
        
        $buttonClass = "btn-base btn-{$variant}";
        
        if ($size && in_array($size, ['sm', 'lg'])) {
            $buttonClass .= " btn-{$size}";
        }
        
        if ($icon) {
            $buttonClass .= " btn-icon";
        }
        
        if ($class) {
            $buttonClass .= " {$class}";
        }
        
        $html = "<button type=\"{$type}\" class=\"{$buttonClass}\"";
        
        if ($id) {
            $html .= " id=\"{$id}\"";
        }
        
        // Add additional attributes
        foreach ($attributes as $attr => $attrValue) {
            $html .= " {$attr}=\"" . htmlspecialchars($attrValue) . "\"";
        }
        
        $html .= ">";
        $html .= htmlspecialchars($text);
        $html .= "</button>";
        
        return $html;
    }
    
    /**
     * Render the empty state component
     * 
     * @param string $title - Empty state title
     * @param string $message - Empty state message
     * @param array $options - Additional options
     * @return string - Rendered HTML
     */
    public static function renderEmptyState($title, $message, $options = []) {
        if (empty($title) || empty($message)) {
            throw new \InvalidArgumentException('Title and message are required');
        }
        
        // Set variables for existing component
        $emptyTitle = $title;
        $emptyMessage = $message;
        $actionHref = $options['actionHref'] ?? '';
        $actionLabel = $options['actionLabel'] ?? '';
        
        ob_start();
        include APP_ROOT . '/Views/components/empty-state.php';
        return ob_get_clean();
    }
    
    /**
     * Render the compact color picker component
     * 
     * @param string $selectedColor - Currently selected color
     * @param array $options - Additional options
     * @return string - Rendered HTML
     */
    public static function renderCompactColorPicker($selectedColor = '#ffffff', $options = []) {
        // Set variables for existing component
        $selectedColor = $selectedColor ?? '#ffffff';
        $name = $options['name'] ?? 'color';
        
        ob_start();
        include APP_ROOT . '/Views/components/compact-color-picker.php';
        return ob_get_clean();
    }
    
    /**
     * Render notification
     * 
     * @param string $message - Notification message
     * @param string $type - Notification type (success, error, info)
     * @return string - JavaScript code for notification
     */
    public static function renderNotification($message, $type = 'info') {
        if (empty($message)) {
            throw new \InvalidArgumentException('Message is required');
        }
        
        $validTypes = ['success', 'error', 'info'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException('Type must be one of: ' . implode(', ', $validTypes));
        }
        
        $jsMessage = json_encode($message);
        
        return "<script>
            if (typeof window.notify{$type} === 'function') {
                window.notify" . ucfirst($type) . "({$jsMessage});
            } else {
                console.warn('Notification system not available');
            }
        </script>";
    }
}
