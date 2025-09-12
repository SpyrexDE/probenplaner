<?php
namespace App\Core;

/**
 * Theme Manager
 * Handles theme operations and configurations
 */
class ThemeManager
{
    /**
     * Available themes configuration
     * @var array
     */
    private static $themes = [
        'default' => [
            'name' => 'Standard',
            'description' => 'Das Standard-Farbschema mit blauen Akzenten',
            'css_file' => 'theme-default.css',
            'preview_colors' => [
                'primary' => '#478cf4',
                'secondary' => '#f4476b',
                'success' => '#10b981'
            ]
        ],
        'jeunesse' => [
            'name' => 'Jeunesse',
            'description' => 'Professionelles Blau-Rot Schema',
            'css_file' => 'theme-jeunesse.css',
            'preview_colors' => [
                'primary' => '#0073b4',
                'secondary' => '#e30513',
                'success' => '#92c02b'
            ]
        ]
    ];
    
    /**
     * Default theme
     * @var string
     */
    private static $defaultTheme = 'default';
    
    /**
     * Get all available themes
     * 
     * @return array
     */
    public static function getAvailableThemes(): array
    {
        return self::$themes;
    }
    
    /**
     * Get theme configuration by key
     * 
     * @param string $theme Theme key
     * @return array|null Theme configuration or null if not found
     */
    public static function getTheme(string $theme): ?array
    {
        return self::$themes[$theme] ?? null;
    }
    
    /**
     * Check if a theme exists
     * 
     * @param string $theme Theme key
     * @return bool
     */
    public static function themeExists(string $theme): bool
    {
        return isset(self::$themes[$theme]);
    }
    
    /**
     * Get theme CSS file path
     * 
     * @param string $theme Theme key
     * @return string CSS file path or default theme path if not found
     */
    public static function getThemeCssFile(string $theme): string
    {
        $themeConfig = self::getTheme($theme);
        
        if ($themeConfig && isset($themeConfig['css_file'])) {
            return '/assets/css/themes/' . $themeConfig['css_file'];
        }
        
        // Fallback to default theme
        $defaultConfig = self::getTheme(self::$defaultTheme);
        return '/assets/css/themes/' . $defaultConfig['css_file'];
    }
    
    /**
     * Get user's theme preference
     * 
     * @param array|null $user User data
     * @return string Theme key
     */
    public static function getUserTheme(?array $user = null): string
    {
        // Try to get from user data
        if ($user && isset($user['theme']) && self::themeExists($user['theme'])) {
            return $user['theme'];
        }
        
        // Try to get from session
        if (isset($_SESSION['theme']) && self::themeExists($_SESSION['theme'])) {
            return $_SESSION['theme'];
        }
        
        // Return default theme
        return self::$defaultTheme;
    }
    
    /**
     * Set user's theme preference in session
     * 
     * @param string $theme Theme key
     * @return bool Success status
     */
    public static function setUserSessionTheme(string $theme): bool
    {
        if (self::themeExists($theme)) {
            $_SESSION['theme'] = $theme;
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current active theme
     * 
     * @return string Current theme key
     */
    public static function getCurrentTheme(): string
    {
        // Try to get user from session
        $user = null;
        if (isset($_SESSION['user_id'])) {
            // We'll let the controller or template renderer pass the user data
            // For now, just check session
            if (isset($_SESSION['theme']) && self::themeExists($_SESSION['theme'])) {
                return $_SESSION['theme'];
            }
        }
        
        return self::$defaultTheme;
    }
    
    /**
     * Generate theme CSS link tag
     * 
     * @param string $theme Theme key
     * @return string HTML link tag
     */
    public static function generateThemeCssLink(string $theme): string
    {
        $cssFile = self::getThemeCssFile($theme);
        $themeConfig = self::getTheme($theme);
        $themeName = $themeConfig['name'] ?? $theme;
        
        return '<link rel="stylesheet" href="' . htmlspecialchars($cssFile) . '" data-theme="' . htmlspecialchars($theme) . '" title="' . htmlspecialchars($themeName) . '">';
    }
    
    /**
     * Get default theme
     * 
     * @return string Default theme key
     */
    public static function getDefaultTheme(): string
    {
        return self::$defaultTheme;
    }
    
    /**
     * Validate theme preference
     * 
     * @param string $theme Theme key to validate
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public static function validateThemePreference(string $theme): array
    {
        $errors = [];
        
        if (empty($theme)) {
            $errors[] = 'Theme darf nicht leer sein';
        } elseif (!self::themeExists($theme)) {
            $errors[] = 'Das gewählte Theme existiert nicht';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get theme preview data for UI
     * 
     * @return array Themes with preview information
     */
    public static function getThemesForPreview(): array
    {
        $preview = [];
        
        foreach (self::$themes as $key => $theme) {
            $preview[$key] = [
                'key' => $key,
                'name' => $theme['name'],
                'description' => $theme['description'],
                'preview_colors' => $theme['preview_colors'] ?? []
            ];
        }
        
        return $preview;
    }
}
