<?php

namespace App\Core;

use App\Models\UserOrchestra;

/**
 * Entry from the JOinJMD app tile: ?jmd_accesstoken=…&orchestra={JMD orchestra ID}
 *
 * The JMD orchestra ID must equal the ensemble slug (case-insensitive). It only selects
 * among the user's own active memberships, so it can open an orchestra but never grants
 * access to one.
 */
class JmdLaunch
{
    /** JMD orchestra IDs: letters, digits, '_' and '-' */
    private const REF_PATTERN = '/^[A-Za-z0-9_-]{1,100}$/';

    /**
     * The orchestra requested by the app, or null if absent/malformed.
     */
    public static function requestedOrchestra(): ?string
    {
        $ref = $_POST['orchestra'] ?? $_GET['orchestra'] ?? null;
        if (!is_string($ref)) {
            return null;
        }
        $ref = trim($ref);
        return preg_match(self::REF_PATTERN, $ref) ? $ref : null;
    }

    /**
     * Landing page inside the requested orchestra, or null unless exactly one of the
     * user's active memberships has that slug (compared case-insensitively).
     */
    public static function landingUrl(int $userId, string $ref): ?string
    {
        $userOrchestraModel = new UserOrchestra();
        $matches = [];

        foreach ($userOrchestraModel->getUserOrchestras($userId) as $membership) {
            if (strcasecmp((string)($membership['orchestra_slug'] ?? ''), $ref) === 0) {
                $matches[] = $membership;
            }
        }

        if (count($matches) !== 1 || empty($matches[0]['org_slug'])) {
            return null;
        }

        $membership = $matches[0];
        $permissions = $userOrchestraModel->getPermissions($userId, (int)$membership['orchestra_id']);
        $page = !empty($permissions['can_manage_ensemble']) ? '/promises/admin' : '/promises';

        return '/' . rawurlencode($membership['org_slug']) . '/' . rawurlencode($membership['orchestra_slug']) . $page;
    }
}
