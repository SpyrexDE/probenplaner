<?php

namespace App\Core;

/**
 * Rehearsal Type Manager
 *
 * Handles rehearsal types and role-based rehearsal visibility.
 */
class RehearsalTypeManager
{
    // Rehearsal Type Constants
    public const TYPE_REHEARSAL = 'Probe';
    public const TYPE_SECTIONAL = 'Registerprobe';
    public const TYPE_CONCERT = 'Konzert';
    public const TYPE_DRESS_REHEARSAL = 'Generalprobe';
    public const TYPE_CONCERT_TOUR = 'Konzertreise';

    /**
     * Get rehearsal type from database field
     */
    public static function getRehearsalType(array $rehearsal): string
    {
        return $rehearsal['type'] ?? '';
    }

    /**
     * Check if rehearsal type should be displayed (not default "Probe" or empty)
     */
    public static function shouldDisplayType(string $type): bool
    {
        return !empty($type) && $type !== self::TYPE_REHEARSAL;
    }

    /**
     * Check if user should see this rehearsal based on role restrictions.
     *
     * A rehearsal with no assigned roles is visible to everyone.
     * A rehearsal with roles is only visible to users sharing at least one of those roles.
     */
    public static function canUserSeeRehearsal(array $user, array $rehearsal): bool
    {
        // No role restriction → visible to all
        if (empty($rehearsal['role_ids'])) return true;

        // Get user's role IDs
        $userRoleIds = [];
        if (!empty($user['roles']) && is_array($user['roles'])) {
            $userRoleIds = array_column($user['roles'], 'id');
        } elseif (!empty($user['role_id'])) {
            $userRoleIds = [(int)$user['role_id']];
        }

        return !empty(array_intersect($userRoleIds, $rehearsal['role_ids']));
    }

    /**
     * Get all valid rehearsal types
     */
    public static function getAllTypes(): array
    {
        return [
            self::TYPE_REHEARSAL,
            self::TYPE_SECTIONAL,
            self::TYPE_CONCERT,
            self::TYPE_DRESS_REHEARSAL,
            self::TYPE_CONCERT_TOUR,
        ];
    }

    /**
     * Validate rehearsal type
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAllTypes());
    }
}
