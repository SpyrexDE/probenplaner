<?php
namespace App\Core;

/**
 * Utilities Class
 * Contains utility functions for the application
 */
class Utilities
{
    /**
     * Generate user badges for role and small group indicators
     * 
     * @param array $user User data array containing role and is_small_group
     * @return string HTML string with modern badge-style badges
     */
    public static function generateUserBadges($user)
    {
        if (!is_array($user)) {
            return '';
        }
        
        $badges = [];
        
        // Add crown badge for section leaders (Stimmführer)
        if (isset($user['role']) && $user['role'] === 'leader') {
            $badges[] = '<span class="user-badge" title="Stimmführer"><i class="fas fa-crown"></i></span>';
        }
        
        // Add small group badge for small group members using modern system
        if (\App\Core\RehearsalTypeManager::isUserInSmallGroup($user)) {
            $badges[] = '<span class="user-badge" title="' . \App\Core\RehearsalTypeManager::LABEL_KLEINGRUPPE . '"><i class="fas fa-user-friends"></i></span>';
        }
        
        return implode('', $badges);
    }
    
    /**
     * Display a username with role and small group badges
     * 
     * @param array $user User data array containing username, role, and is_small_group
     * @return string Formatted username with badges
     */
    public static function displayUserNameWithBadges($user)
    {
        if (!is_array($user)) {
            return '';
        }
        
        $username = htmlspecialchars($user['username'] ?? '');
        $badges = self::generateUserBadges($user);
        
        return $username . $badges;
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