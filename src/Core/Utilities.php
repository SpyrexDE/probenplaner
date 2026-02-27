<?php

namespace App\Core;

/**
 * Utilities Class
 * Contains utility functions for the application
 */
class Utilities
{
    /**
     * Generate user badges for small group indicator.
     *
     * @param array $user User data array
     * @return string HTML string with badge spans
     */
    public static function generateUserBadges($user)
    {
        if (!is_array($user)) {
            return '';
        }

        $badges = [];

        if (RehearsalTypeManager::isUserInSmallGroup($user)) {
            $badges[] = '<span class="user-badge" title="' . RehearsalTypeManager::LABEL_SMALL_GROUP . '"><i class="fas fa-user-friends"></i></span>';
        }

        return implode('', $badges);
    }

    /**
     * Display a username with badges.
     *
     * @param array $user User data array
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
     * Get display text for a user's type (instrument/section).
     *
     * @param string $type User type (instrument/section)
     * @return array Array with 'type' display string
     */
    public static function getUserDisplayInfo(string $type): array
    {
        $result = ['type' => null];

        if ($type !== 'conductor' && $type !== 'none' && $type !== '') {
            $groupManager = new GroupManager();
            $result['type'] = $groupManager->getDisplayName($type);
        }

        return $result;
    }

    /**
     * Render a role tag pill.
     *
     * @param array|null $role Role data with name and tag_color
     * @return string HTML for the role tag
     */
    public static function renderRoleTag(?array $role): string
    {
        if (!$role || empty($role['name'])) {
            return '';
        }

        $label = htmlspecialchars($role['name']);
        $color = htmlspecialchars($role['tag_color'] ?? '#478cf4');

        return '<span class="role-tag" style="--role-color: ' . $color . '">' . $label . '</span>';
    }

    /**
     * Get German day abbreviation from a DateTime object.
     */
    public static function getGermanDayAbbreviation(\DateTime $date)
    {
        static $germanDays = [
            'Mon' => 'Mo', 'Tue' => 'Di', 'Wed' => 'Mi',
            'Thu' => 'Do', 'Fri' => 'Fr', 'Sat' => 'Sa', 'Sun' => 'So'
        ];
        return $germanDays[$date->format('D')] ?? $date->format('D');
    }

    /**
     * Generate FontAwesome icon HTML.
     */
    public static function icon($iconName, $classes = '')
    {
        $faClass = 'fas fa-' . $iconName;
        $allClasses = trim($faClass . ' ' . $classes);
        return '<i class="' . $allClasses . '"></i>';
    }

    /**
     * Format a date for display (dd.mm.YYYY).
     */
    public static function formatDate($date)
    {
        if (empty($date)) return '';
        try {
            return (new \DateTime($date))->format('d.m.Y');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Format a date for database storage (Y-m-d).
     */
    public static function formatDateForDb($date)
    {
        if (empty($date)) return '';
        try {
            return (new \DateTime($date))->format('Y-m-d');
        } catch (\Exception $e) {
            return $date;
        }
    }
}
