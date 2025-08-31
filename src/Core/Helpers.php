<?php
/**
 * Global Helper Functions
 * These functions are available throughout the application
 */

use App\Core\Utilities;

if (!function_exists('icon')) {
    /**
     * Generate FontAwesome icon HTML
     * 
     * @param string $iconName Icon name (without fa- prefix)
     * @param string $classes Additional CSS classes
     * @return string FontAwesome icon HTML
     */
    function icon($iconName, $classes = '') {
        return Utilities::icon($iconName, $classes);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities
     * 
     * @param string $value Value to escape
     * @return string Escaped value
     */
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}