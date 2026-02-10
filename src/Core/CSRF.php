<?php
namespace App\Core;

/**
 * CSRF Protection Class
 * Provides Cross-Site Request Forgery protection
 */
class CSRF
{
    const TOKEN_NAME = 'csrf_token';
    const TOKEN_LENGTH = 32;
    
    /**
     * Generate a CSRF token
     * 
     * @return string Generated token
     */
    public static function generateToken()
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;
        return $token;
    }
    
    /**
     * Get current CSRF token
     * 
     * @return string Current token or generates new one if none exists
     */
    public static function getToken()
    {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return self::generateToken();
        }
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if token is valid
     */
    public static function validateToken($token)
    {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::TOKEN_NAME], $token);
    }
    
    /**
     * Validate CSRF token from POST data
     * 
     * @return bool True if token is valid
     */
    public static function validatePostToken()
    {
        $token = $_POST[self::TOKEN_NAME] ?? '';
        return self::validateToken($token);
    }
    
    /**
     * Generate HTML input field for CSRF token
     * 
     * @return string HTML input field
     */
    public static function getTokenField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Clear current CSRF token (call after successful form submission)
     * 
     * @return void
     */
    public static function clearToken()
    {
        unset($_SESSION[self::TOKEN_NAME]);
    }
    
    /**
     * Middleware-style CSRF protection for controllers
     * Throws exception if token is invalid
     * 
     * @throws \Exception If CSRF token is invalid
     * @return void
     */
    public static function protect()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::validatePostToken()) {
                throw new \Exception('CSRF-Prüfung fehlgeschlagen. Bitte versuchen Sie es erneut.');
            }
        }
    }
}
