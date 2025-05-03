<?php
namespace App\Core;

/**
 * Helper functions for the application
 */
class Helpers
{
    /**
     * Format a date from Y-m-d to dd.mm.yyyy
     * 
     * @param string $date Date in Y-m-d format
     * @return string Date in dd.mm.yyyy format
     */
    public static function formatDate($date)
    {
        if (!empty($date) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return $matches[3] . '.' . $matches[2] . '.' . $matches[1]; // dd.mm.yyyy format
        }
        return $date;
    }
    
    /**
     * Convert date from dd.mm.yyyy to Y-m-d
     * 
     * @param string $date Date in dd.mm.yyyy format
     * @return string Date in Y-m-d format
     */
    public static function formatDateForDb($date)
    {
        if (!empty($date) && preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1]; // Y-m-d format
        }
        return $date;
    }
} 