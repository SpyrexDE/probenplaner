<?php
namespace App\Core;

/**
 * Application Constants Class
 * Centralizes all application constants for better maintainability
 */
class Constants
{
    // HTTP Status Codes
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    
    // User Roles
    const ROLE_MEMBER = 'member';
    const ROLE_LEADER = 'leader';
    const ROLE_CONDUCTOR = 'conductor';
    
    // Instrument/Section Types
    const SECTION_STRINGS = 'Streicher';
    const SECTION_WINDS = 'Bläser';
    const SECTION_BRASS = 'Blech';
    const SECTION_PERCUSSION = 'Schlagwerk';
    const SECTION_OTHER = 'Andere';
    
    // Rehearsal Types
    const REHEARSAL_TUTTI = 'tutti';
    const REHEARSAL_SECTIONAL = 'sectional';
    const REHEARSAL_SMALL_GROUP = 'small_group';
    
    // Color Codes for Rehearsals
    const COLOR_WHITE = '#ffffff';
    const COLOR_BLUE = '#3b82f6';
    const COLOR_GREEN = '#10b981';
    const COLOR_YELLOW = '#f59e0b';
    const COLOR_RED = '#ef4444';
    const COLOR_PURPLE = '#8b5cf6';
    const COLOR_ORANGE = '#f97316';
    const COLOR_PINK = '#ec4899';
    const COLOR_TEAL = '#14b8a6';
    const COLOR_INDIGO = '#6366f1';
    const COLOR_GRAY = '#6b7280';
    const COLOR_SLATE = '#475569';
    
    // Date/Time Formats
    const DATE_FORMAT = 'Y-m-d';
    const TIME_FORMAT = 'H:i';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DISPLAY_DATE_FORMAT = 'd.m.Y';
    const DISPLAY_TIME_FORMAT = 'H:i';
    
    // Database Table Names (if centralized)
    const TABLE_USERS = 'users';
    const TABLE_ORCHESTRAS = 'orchestras';
    const TABLE_REHEARSALS = 'rehearsals';
    const TABLE_USER_PROMISES = 'user_promises';
    
    // Cache Keys (if caching is implemented)
    const CACHE_USER_PREFIX = 'user_';
    const CACHE_ORCHESTRA_PREFIX = 'orchestra_';
    const CACHE_REHEARSAL_PREFIX = 'rehearsal_';
    
    /**
     * Get available rehearsal colors
     * 
     * @return array Array of color options
     */
    public static function getRehearsalColors(): array
    {
        return [
            self::COLOR_WHITE => 'Weiß',
            self::COLOR_BLUE => 'Blau',
            self::COLOR_GREEN => 'Grün',
            self::COLOR_YELLOW => 'Gelb',
            self::COLOR_RED => 'Rot',
            self::COLOR_PURPLE => 'Lila',
            self::COLOR_ORANGE => 'Orange',
            self::COLOR_PINK => 'Pink',
            self::COLOR_TEAL => 'Türkis',
            self::COLOR_INDIGO => 'Indigo',
            self::COLOR_GRAY => 'Grau',
            self::COLOR_SLATE => 'Schiefer'
        ];
    }
    
    /**
     * Get available user roles
     * 
     * @return array Array of role options
     */
    public static function getUserRoles(): array
    {
        return [
            self::ROLE_MEMBER => 'Mitglied',
            self::ROLE_LEADER => 'Stimmführer',
            self::ROLE_CONDUCTOR => 'Dirigent'
        ];
    }
}
