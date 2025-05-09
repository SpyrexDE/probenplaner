<?php
namespace App\Core;

/**
 * Utilities Class
 * Contains utility functions for the application
 */
class Utilities
{
    /**
     * Format a username for display with appropriate icons
     * (adds crown symbol for group leaders and star for small group members)
     * 
     * @param string $username User's name
     * @param string $role User's role (leader, member, conductor)
     * @param bool $isSmallGroup Whether user is in a small group
     * @return string Formatted username with icons
     */
    public static function formatUsername($username, $role = 'member', $isSmallGroup = false) 
    {
        $formattedName = htmlspecialchars($username);
        
        // Add crown for group leaders
        if ($role === 'leader') {
            $formattedName .= ' ♚';
        }
        
        // Add star for small group members
        if ($isSmallGroup) {
            $formattedName .= ' *';
        }
        
        return $formattedName;
    }
    
    /**
     * Display a properly formatted username with role and small group indicators
     * (can be used in views)
     * 
     * @param array $user User data array containing username, role, and is_small_group
     * @return string Formatted username with role and small group indicators
     */
    public static function displayUserName($user)
    {
        if (!is_array($user)) {
            return '';
        }
        
        $username = $user['username'] ?? '';
        $role = $user['role'] ?? 'member';
        $isSmallGroup = isset($user['is_small_group']) && $user['is_small_group'] ? true : false;
        
        return self::formatUsername($username, $role, $isSmallGroup);
    }
} 