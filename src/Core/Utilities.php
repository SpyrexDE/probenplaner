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
     * Generate all inline labels for a user (badges + role tag).
     *
     * @param array $user User data with is_small_group, role_tag_label, role_tag_color keys
     * @return string Combined HTML: user-badge icons followed by role-tag pill
     */
    public static function generateUserLabels(array $user): string
    {
        $html = self::generateUserBadges($user);

        // Only show non-default roles to avoid visual noise
        $isDefault = !empty($user['role_is_default']);
        $roleLabel = $user['role_tag_label'] ?? $user['role_name'] ?? '';
        if ($roleLabel && !$isDefault) {
            $html .= self::renderRoleTag([
                'name' => $roleLabel,
                'tag_color' => $user['role_tag_color'] ?? '#478cf4',
            ]);
        }

        return $html;
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
            'Mon' => 'Mo',
            'Tue' => 'Di',
            'Wed' => 'Mi',
            'Thu' => 'Do',
            'Fri' => 'Fr',
            'Sat' => 'Sa',
            'Sun' => 'So'
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
