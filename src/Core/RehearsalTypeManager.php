<?php
namespace App\Core;

/**
 * Rehearsal Type Manager
 * 
 * System for handling rehearsal types and small group logic.
 */
class RehearsalTypeManager
{
    // Rehearsal Type Constants (values kept for DB/display compatibility)
    public const TYPE_REHEARSAL = 'Probe';
    public const TYPE_SECTIONAL = 'Registerprobe';
    public const TYPE_CONCERT = 'Konzert';
    public const TYPE_DRESS_REHEARSAL = 'Generalprobe';
    public const TYPE_CONCERT_TOUR = 'Konzertreise';
    
    // Small Group Constants
    public const SMALL_GROUP_ENABLED = 1;
    public const SMALL_GROUP_DISABLED = 0;
    
    // Display Labels (values for UI)
    public const LABEL_SMALL_GROUP = 'Kleingruppe';
    public const LABEL_SMALL_GROUP_REHEARSAL = 'Kleingruppenprobe';
    
    /**
     * Check if a rehearsal is a small group rehearsal
     */
    public static function isSmallGroupRehearsal(array $rehearsal): bool
    {
        return isset($rehearsal['is_small_group']) && 
               (int)$rehearsal['is_small_group'] === self::SMALL_GROUP_ENABLED;
    }
    
    /**
     * Check if a user is in a small group
     * 
     * @param array $user User data array
     * @param int|null $orchestraId Orchestra ID (optional, uses session if not provided)
     * @return bool
     */
    public static function isUserInSmallGroup(array $user, ?int $orchestraId = null): bool
    {
        // Resolve orchestra context if not provided
        if ($orchestraId === null) {
            $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        }

        // If we have an orchestra context, try relation-based lookup first
        if ($orchestraId) {
            // Prefer explicit user id, otherwise try session
            $userId = isset($user['id']) ? (int)$user['id'] : (int)($_SESSION['user_id'] ?? 0);

            if ($userId > 0 && isset($user['id'])) {
                // Only do database lookup if we have a valid user ID from the user array
                $userOrchestraModel = new \App\Models\UserOrchestra();
                return $userOrchestraModel->isUserInSmallGroup($userId, (int)$orchestraId);
            }

            // No reliable user id available, fall back to provided flag if present
            if (isset($user['is_small_group'])) {
                return (int)$user['is_small_group'] === self::SMALL_GROUP_ENABLED;
            }

            // Conservative default
            return false;
        }

        // No orchestra context: fall back to provided flag for backward compatibility
        return isset($user['is_small_group']) &&
               (int)$user['is_small_group'] === self::SMALL_GROUP_ENABLED;
    }
    
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
     * Check if user should see this rehearsal based on small group logic
     */
    public static function canUserSeeRehearsal(array $user, array $rehearsal): bool
    {
        $userIsSmallGroup = self::isUserInSmallGroup($user);
        $rehearsalIsSmallGroup = self::isSmallGroupRehearsal($rehearsal);
        
        // If it's a small group rehearsal, only small group users should see it
        if ($rehearsalIsSmallGroup && !$userIsSmallGroup) {
            return false;
        }
        
        return true;
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
