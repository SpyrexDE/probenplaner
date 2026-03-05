<?php

namespace App\Core;

/**
 * Utilities Class
 * Contains utility functions for the application
 */
class Utilities
{
    /**
     * Generate all inline labels for a user (role tags).
     *
     * @param array $user User data with roles array or role_tag_label/role_tag_color keys
     * @return string HTML: role-tag pills for non-default roles
     */
    public static function generateUserLabels(array $user): string
    {
        $html = '';

        // Multi-role mode: user has a 'roles' array
        if (!empty($user['roles']) && is_array($user['roles'])) {
            foreach ($user['roles'] as $role) {
                if (!empty($role['is_default'])) continue;
                $html .= self::renderRoleTag([
                    'name'      => $role['name'] ?? '',
                    'tag_color' => $role['tag_color'] ?? '#478cf4',
                ]);
            }
            return $html;
        }

        // Legacy single-role fallback
        $isDefault = !empty($user['role_is_default']);
        $roleLabel = $user['role_tag_label'] ?? $user['role_name'] ?? '';
        if ($roleLabel && !$isDefault) {
            $html .= self::renderRoleTag([
                'name'      => $roleLabel,
                'tag_color' => $user['role_tag_color'] ?? '#478cf4',
            ]);
        }

        return $html;
    }

    /**
     * Like generateUserLabels but shows only the first tag + a gray "+N" overflow badge.
     *
     * @param array $roles Array of role arrays with name, tag_color, is_default keys
     * @return string Condensed role tag HTML
     */
    public static function generateUserLabelsCondensed(array $roles): string
    {
        $nonDefault = array_values(array_filter($roles, fn($r) => empty($r['is_default'])));
        if (empty($nonDefault)) return '';

        $html = self::renderRoleTag($nonDefault[0]);
        $extra = count($nonDefault) - 1;
        if ($extra > 0) {
            $html .= '<span class="role-tag role-tag-overflow">+' . $extra . '</span>';
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
     * @param array|null $role Role data with name, tag_color, and optionally is_default
     * @return string HTML for the role tag
     */
    public static function renderRoleTag(?array $role): string
    {
        if (!$role || empty($role['name'])) {
            return '';
        }

        $label = htmlspecialchars($role['name']);
        $color = htmlspecialchars($role['tag_color'] ?? '#478cf4');
        $star = !empty($role['is_default']) ? '<i class="fas fa-star" style="font-size:10px;"></i>' : '';

        return '<span class="role-tag" style="--role-color: ' . $color . '">' . $star . $label . '</span>';
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
