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
    
    /**
     * Get German day abbreviation from a DateTime object
     * 
     * @param \DateTime $date Date to get day abbreviation for
     * @return string German day abbreviation (Mo, Di, etc.)
     */
    public static function getGermanDayAbbreviation(\DateTime $date)
    {
        static $germanDays = [
            'Mon' => 'Mo',
            'Tue' => 'Di',
            'Wed' => 'Mi',
            'Thu' => 'Do',
            'Fri' => 'Fr',
            'Sat' => 'Sa',
            'Sun' => 'So'
        ];
        
        $englishDay = $date->format('D');
        return $germanDays[$englishDay] ?? $englishDay;
    }
    
    /**
     * Generate FontAwesome icon HTML
     * 
     * @param string $iconName Icon name (without fa- prefix)
     * @param string $classes Additional CSS classes
     * @return string FontAwesome icon HTML
     */
    public static function icon($iconName, $classes = '')
    {
        $faClass = 'fas fa-' . $iconName;
        $allClasses = trim($faClass . ' ' . $classes);
        
        return '<i class="' . $allClasses . '"></i>';
    }
    
    /**
     * Format a date for display
     * 
     * @param string $date Date string
     * @return string Formatted date
     */
    public static function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }
        
        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format('d.m.Y');
        } catch (\Exception $e) {
            return $date; // Return original if parsing fails
        }
    }
    
    /**
     * Format a date for database storage
     * 
     * @param string $date Date string
     * @return string Formatted date for database (Y-m-d)
     */
    public static function formatDateForDb($date)
    {
        if (empty($date)) {
            return '';
        }
        
        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format('Y-m-d');
        } catch (\Exception $e) {
            return $date; // Return original if parsing fails
        }
    }
} 