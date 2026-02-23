<?php

namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;

/**
 * UserOrchestra Model
 * Handles user-orchestra relationship operations (junction table)
 */
class UserOrchestra extends Model
{
    protected $table = 'user_orchestras';

    /** Named permission presets (map preset name -> list of permission names) */
    public const PRESETS = [
        'member' => [
            'can_attend_rehearsals',
        ],
        'section_leader' => [
            'can_attend_rehearsals',
            'can_view_own_section_stats',
            'can_view_all_section_stats',
            'can_manage_rehearsals',
            'can_view_members',
        ],
        'conductor' => [
            'can_view_own_section_stats',
            'can_view_all_section_stats',
            'can_view_members',
            'can_manage_rehearsals',
            'can_manage_members',
            'can_manage_permissions',
            'can_manage_ensemble',
        ],
    ];

    /**
     * @return array All orchestras for a user with orchestra name
     */
    public function getUserOrchestras(int $userId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";

        $sql = "SELECT uo.*, o.name as orchestra_name, o.slug as orchestra_slug
                FROM {$this->table} uo
                JOIN orchestras o ON uo.orchestra_id = o.id
                WHERE uo.user_id = ? {$activeClause}
                ORDER BY uo.joined_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $orchestras = [];
        while ($row = $result->fetch_assoc()) {
            $orchestras[] = $row;
        }
        $stmt->close();
        return $orchestras;
    }

    /**
     * @return array All active users for an orchestra
     */
    public function getOrchestraUsers(int $orchestraId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";

        $sql = "SELECT uo.*, u.username, u.display_name, u.created_at as user_created_at,
                GROUP_CONCAT(p.name) as permission_names
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                LEFT JOIN user_ensemble_permissions uep ON uep.user_orchestra_id = uo.id
                LEFT JOIN permissions p ON uep.permission_id = p.id
                WHERE uo.orchestra_id = ? {$activeClause}
                GROUP BY uo.id
                ORDER BY uo.type, COALESCE(u.display_name, u.username)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $permNames = $row['permission_names'] ? explode(',', $row['permission_names']) : [];
            $row['permissions'] = $permNames;
            $row['can_attend_rehearsals'] = in_array('can_attend_rehearsals', $permNames);
            unset($row['permission_names']);
            $users[] = $row;
        }
        $stmt->close();
        return $users;
    }

    /**
     * @return array|null The relationship record, or null if not found
     */
    public function getUserOrchestraRelation(int $userId, int $orchestraId, bool $activeOnly = true): ?array
    {
        $activeClause = $activeOnly ? "AND is_active = 1" : "";

        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = ? AND orchestra_id = ? {$activeClause}";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $relation = ($result instanceof \mysqli_result) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $relation;
    }

    /**
     * @param array $permissions Permission column names to enable
     * @return int|array Relationship ID on success, error array on failure
     */
    public function joinOrchestra(int $userId, int $orchestraId, string $type, array $permissions = [])
    {
        try {
            $existing = $this->getUserOrchestraRelation($userId, $orchestraId, false);

            if ($existing) {
                if ($existing['is_active']) {
                    return ['error' => true, 'message' => 'Sie sind bereits Mitglied dieses Orchesters.'];
                }

                // Reactivate
                $result = $this->update($existing['id'], [
                    'type' => $type,
                    'is_active' => 1,
                    'joined_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($result) {
                    $this->syncPermissions($existing['id'], $permissions);
                    return $existing['id'];
                }
                return ['error' => true, 'message' => 'Fehler beim Reaktivieren der Mitgliedschaft.'];
            }

            $relationId = $this->insert([
                'user_id' => $userId,
                'orchestra_id' => $orchestraId,
                'type' => $type,
                'is_active' => 1,
                'joined_at' => date('Y-m-d H:i:s'),
            ]);

            if ($relationId === false) {
                $error = $this->db->getLastError();
                error_log("Failed to join orchestra - Database error: " . $error);
                return ErrorHandler::handleDatabaseError(new \Exception($error), 'Orchestra join');
            }

            $this->syncPermissions($relationId, $permissions);
            return $relationId;
        } catch (\Exception $e) {
            return ErrorHandler::handleDatabaseError($e, 'Orchestra join');
        }
    }

    /**
     * Soft-delete the membership.
     */
    public function leaveOrchestra(int $userId, int $orchestraId): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        return $this->update($relation['id'], [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ── Permission helpers ───────────────────────────────────────

    /**
     * Check if user has a specific permission in an orchestra.
     */
    public function hasPermission(int $userId, int $orchestraId, string $permission): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        $sql = "SELECT 1 FROM user_ensemble_permissions uep
                JOIN permissions p ON uep.permission_id = p.id
                WHERE uep.user_orchestra_id = ? AND p.name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $relation['id'], $permission);
        $stmt->execute();
        $result = $stmt->get_result();
        $found = $result->num_rows > 0;
        $stmt->close();
        return $found;
    }

    /**
     * @return array Associative map of permission name => bool
     */
    public function getPermissions(int $userId, int $orchestraId): array
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);

        // Start with all permissions as false
        $allPerms = $this->getAllPermissionNames('ensemble');
        $perms = array_fill_keys($allPerms, false);

        if (!$relation) return $perms;

        $sql = "SELECT p.name FROM user_ensemble_permissions uep
                JOIN permissions p ON uep.permission_id = p.id
                WHERE uep.user_orchestra_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $relation['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $perms[$row['name']] = true;
        }
        $stmt->close();
        return $perms;
    }

    /**
     * Replace all permissions for a membership with the given set.
     *
     * @param array $permissions Permission names to enable (all others revoked)
     */
    public function setPermissions(int $userId, int $orchestraId, array $permissions): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        return $this->syncPermissions($relation['id'], $permissions);
    }

    /**
     * Apply a named permission preset.
     */
    public function applyPreset(int $userId, int $orchestraId, string $presetName): bool
    {
        $permissions = self::PRESETS[$presetName] ?? [];
        return $this->setPermissions($userId, $orchestraId, $permissions);
    }

    /**
     * Derive the highest matching preset name from current permissions.
     */
    public static function derivePreset(array $permissionMap): string
    {
        foreach (['conductor', 'section_leader'] as $preset) {
            $presetPerms = self::PRESETS[$preset];
            $match = true;
            foreach ($permissionMap as $name => $granted) {
                $expected = in_array($name, $presetPerms);
                if ($expected !== (bool)$granted) {
                    $match = false;
                    break;
                }
            }
            if ($match) return $preset;
        }
        return 'custom';
    }

    // ── Type / small-group helpers ───────────────────────────────

    public function updateUserType(int $userId, int $orchestraId, string $type): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        return $this->update($relation['id'], [
            'type' => $type,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateUserSmallGroupStatus(int $userId, int $orchestraId, bool $isSmallGroup): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        return $this->update($relation['id'], [
            'is_small_group' => $isSmallGroup ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function isUserInSmallGroup(int $userId, int $orchestraId): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        return $relation && (int)($relation['is_small_group'] ?? 0) === 1;
    }

    /**
     * @return array Users of a specific instrument/section within an orchestra
     */
    public function getUsersByType(string $type, int $orchestraId): array
    {
        $sql = "SELECT uo.*, u.username, u.display_name, u.created_at as user_created_at,
                GROUP_CONCAT(p.name) as permission_names
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                LEFT JOIN user_ensemble_permissions uep ON uep.user_orchestra_id = uo.id
                LEFT JOIN permissions p ON uep.permission_id = p.id
                WHERE uo.type = ? AND uo.orchestra_id = ? AND uo.is_active = 1
                GROUP BY uo.id
                ORDER BY COALESCE(u.display_name, u.username)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $type, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $permNames = $row['permission_names'] ? explode(',', $row['permission_names']) : [];
            $row['permissions'] = $permNames;
            $row['can_attend_rehearsals'] = in_array('can_attend_rehearsals', $permNames);
            unset($row['permission_names']);
            $users[] = $row;
        }
        $stmt->close();
        return $users;
    }

    /**
     * Remove a user from an orchestra (hard delete).
     */
    public function removeFromOrchestra(int $userId, int $orchestraId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND orchestra_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $orchestraId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Count members in an orchestra.
     */
    public function getOrchestraUserCount(int $orchestraId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE orchestra_id = ?");
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($result['cnt'] ?? 0);
    }

    // ── Internal ─────────────────────────────────────────────────

    /**
     * @return string[] All permission names for a given scope
     */
    private function getAllPermissionNames(string $scope = 'ensemble'): array
    {
        $sql = "SELECT name FROM permissions WHERE scope = ? ORDER BY id";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $scope);
        $stmt->execute();
        $result = $stmt->get_result();
        $names = [];
        while ($row = $result->fetch_assoc()) {
            $names[] = $row['name'];
        }
        $stmt->close();
        return $names;
    }

    /**
     * Sync permissions for a user_orchestras membership row (delete + insert).
     */
    private function syncPermissions(int $userOrchestraId, array $permissionNames): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_ensemble_permissions WHERE user_orchestra_id = ?");
        $stmt->bind_param('i', $userOrchestraId);
        $stmt->execute();
        $stmt->close();

        if (empty($permissionNames)) return true;

        $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
        $sql = "INSERT INTO user_ensemble_permissions (user_orchestra_id, permission_id)
                SELECT ?, id FROM permissions WHERE name IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $types = 'i' . str_repeat('s', count($permissionNames));
        $params = array_merge([$userOrchestraId], $permissionNames);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Remove a single permission from a user's orchestra membership.
     */
    public function removePermission(int $userId, int $orchestraId, string $permissionName): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        $sql = "DELETE uep FROM user_ensemble_permissions uep
                JOIN permissions p ON p.id = uep.permission_id
                WHERE uep.user_orchestra_id = ? AND p.name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $relation['id'], $permissionName);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
