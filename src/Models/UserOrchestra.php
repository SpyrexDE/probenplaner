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

    /**
     * @return array All orchestras for a user with orchestra name
     */
    public function getUserOrchestras(int $userId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";

        $sql = "SELECT uo.*, o.name as orchestra_name, o.slug as orchestra_slug,
                r.name as role_name, r.name as role_tag_label, r.tag_color as role_tag_color
                FROM {$this->table} uo
                JOIN orchestras o ON uo.orchestra_id = o.id
                LEFT JOIN roles r ON uo.role_id = r.id
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
     * @return array All active users for an orchestra with role info
     */
    public function getOrchestraUsers(int $orchestraId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";

        $sql = "SELECT uo.*, u.username, u.display_name, u.created_at as user_created_at,
                r.id as role_id, r.name as role_name, r.name as role_tag_label,
                r.tag_color as role_tag_color, r.permissions as role_permissions
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                LEFT JOIN roles r ON uo.role_id = r.id
                WHERE uo.orchestra_id = ? {$activeClause}
                ORDER BY r.sort_order, uo.type, COALESCE(u.display_name, u.username)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $perms = json_decode($row['role_permissions'] ?? '[]', true) ?: [];
            $row['permissions'] = $perms;
            $row['can_attend_rehearsals'] = in_array('can_attend_rehearsals', $perms);
            unset($row['role_permissions']);
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
     * @param int|null $roleId Role to assign (null uses orchestra default)
     * @return int|array Relationship ID on success, error array on failure
     */
    public function joinOrchestra(int $userId, int $orchestraId, string $type, ?int $roleId = null)
    {
        try {
            if ($roleId === null) {
                $roleModel = new Role();
                $defaultRole = $roleModel->getDefaultRole($orchestraId);
                $roleId = $defaultRole ? (int)$defaultRole['id'] : null;
            }

            $existing = $this->getUserOrchestraRelation($userId, $orchestraId, false);

            if ($existing) {
                if ($existing['is_active']) {
                    return ['error' => true, 'message' => 'Sie sind bereits Mitglied dieses Orchesters.'];
                }

                // Reactivate
                $result = $this->update($existing['id'], [
                    'type' => $type,
                    'is_active' => 1,
                    'role_id' => $roleId,
                    'joined_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($result) {
                    return $existing['id'];
                }
                return ['error' => true, 'message' => 'Fehler beim Reaktivieren der Mitgliedschaft.'];
            }

            $relationId = $this->insert([
                'user_id' => $userId,
                'orchestra_id' => $orchestraId,
                'type' => $type,
                'is_active' => 1,
                'role_id' => $roleId,
                'joined_at' => date('Y-m-d H:i:s'),
            ]);

            if ($relationId === false) {
                $error = $this->db->getLastError();
                error_log("Failed to join orchestra - Database error: " . $error);
                return ErrorHandler::handleDatabaseError(new \Exception($error), 'Orchestra join');
            }

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

    // ── Role helpers ────────────────────────────────────────────────

    /**
     * Assign a role to a user in an orchestra.
     */
    public function setRole(int $userId, int $orchestraId, int $roleId): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        return $this->update($relation['id'], [
            'role_id' => $roleId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array|null Full role row (decoded permissions), or null
     */
    public function getRole(int $userId, int $orchestraId): ?array
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation || empty($relation['role_id'])) return null;

        $roleModel = new Role();
        return $roleModel->findByIdDecoded((int)$relation['role_id']);
    }

    /**
     * Check if user has a specific permission via their role.
     */
    public function hasPermission(int $userId, int $orchestraId, string $permission): bool
    {
        $role = $this->getRole($userId, $orchestraId);
        if (!$role) return false;
        return in_array($permission, $role['permissions'], true);
    }

    /**
     * @return array Associative map of permission name => bool
     */
    public function getPermissions(int $userId, int $orchestraId): array
    {
        $role = $this->getRole($userId, $orchestraId);
        $granted = $role ? $role['permissions'] : [];
        $map = [];
        foreach (Role::getAvailablePermissions() as $perm) {
            $map[$perm] = in_array($perm, $granted, true);
        }
        return $map;
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
                r.id as role_id, r.name as role_name, r.name as role_tag_label,
                r.tag_color as role_tag_color, r.permissions as role_permissions
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                LEFT JOIN roles r ON uo.role_id = r.id
                WHERE uo.type = ? AND uo.orchestra_id = ? AND uo.is_active = 1
                ORDER BY COALESCE(u.display_name, u.username)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $type, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $perms = json_decode($row['role_permissions'] ?? '[]', true) ?: [];
            $row['permissions'] = $perms;
            $row['can_attend_rehearsals'] = in_array('can_attend_rehearsals', $perms);
            unset($row['role_permissions']);
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
}
