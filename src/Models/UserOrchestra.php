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
     * @return array All active users for an orchestra with role info
     */
    public function getOrchestraUsers(int $orchestraId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";

        $sql = "SELECT uo.*, u.email, u.display_name, uo.display_name as orchestra_display_name, u.created_at as user_created_at
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                WHERE uo.orchestra_id = ? {$activeClause}
                ORDER BY uo.type, COALESCE(uo.display_name, u.display_name, u.email)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $this->applyRolesToUsers($users);
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
     * @param int|null $roleId Role to assign (null uses orchestra defaults)
     * @return int|array Relationship ID on success, error array on failure
     */
    public function joinOrchestra(int $userId, int $orchestraId, string $type, ?int $roleId = null)
    {
        try {
            $roleModel = new Role();
            $roleIds = [];
            if ($roleId !== null) {
                $roleIds = [$roleId];
            } else {
                $defaults = $roleModel->getDefaultRoles($orchestraId);
                $roleIds = array_map(fn($r) => (int)$r['id'], $defaults);
            }

            $existing = $this->getUserOrchestraRelation($userId, $orchestraId, false);

            if ($existing) {
                if ($existing['is_active']) {
                    return ['error' => true, 'message' => 'Sie sind bereits Mitglied dieses Orchesters.'];
                }

                $result = $this->update($existing['id'], [
                    'type' => $type,
                    'is_active' => 1,
                    'joined_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                if ($result) {
                    if (!empty($roleIds)) {
                        $this->setRolesForRelation($existing['id'], $roleIds);
                    }
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

            if (!empty($roleIds)) {
                $this->setRolesForRelation($relationId, $roleIds);
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

    // ── Role helpers (multi-role via junction table) ─────────────

    /**
     * Get all roles for a user_orchestras row.
     *
     * @return array Array of role rows with decoded permissions
     */
    private function getRolesForRelation(int $userOrchestraId): array
    {
        $sql = "SELECT r.* FROM roles r
                JOIN user_orchestra_roles uor ON uor.role_id = r.id
                WHERE uor.user_orchestra_id = ?
                ORDER BY r.sort_order, r.name";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException("Failed to query user_orchestra_roles — run migration 20260305_110500_role_system_refactor.sql: " . $this->db->getLastError());
        }
        $stmt->bind_param('i', $userOrchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        $stmt->close();
        return $roles;
    }

    /**
     * Replace all roles for a user_orchestras row.
     */
    private function setRolesForRelation(int $userOrchestraId, array $roleIds): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_orchestra_roles WHERE user_orchestra_id = ?");
        $stmt->bind_param('i', $userOrchestraId);
        $stmt->execute();
        $stmt->close();

        if (empty($roleIds)) return;

        $stmt = $this->db->prepare("INSERT INTO user_orchestra_roles (user_orchestra_id, role_id) VALUES (?, ?)");
        foreach ($roleIds as $roleId) {
            $roleId = (int)$roleId;
            $stmt->bind_param('ii', $userOrchestraId, $roleId);
            $stmt->execute();
        }
        $stmt->close();
    }

    /**
     * Set roles for a user in an orchestra.
     * System roles (Leitung) are preserved and cannot be added/removed here.
     */
    public function setRoles(int $userId, int $orchestraId, array $roleIds): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        $roleModel = new \App\Models\Role();

        // Preserve existing system roles and strip incoming system role IDs
        $existing = $this->getRolesForRelation($relation['id']);
        $systemRoleIds = [];
        foreach ($existing as $r) {
            if (!empty($r['is_system'])) {
                $systemRoleIds[] = (int)$r['id'];
            }
        }
        $roleIds = array_filter($roleIds, function ($id) use ($roleModel) {
            $role = $roleModel->findById((int)$id);
            return $role && empty($role['is_system']);
        });

        $finalRoleIds = array_unique(array_merge($systemRoleIds, array_map('intval', $roleIds)));
        $this->setRolesForRelation($relation['id'], $finalRoleIds);
        return true;
    }

    /**
     * @return array All role rows for a user in an orchestra
     */
    public function getUserRoles(int $userId, int $orchestraId): array
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return [];

        return $this->getRolesForRelation((int)$relation['id']);
    }

    /**
     * @return array|null Primary role (highest priority / first non-default), or null
     */
    public function getRole(int $userId, int $orchestraId): ?array
    {
        $roles = $this->getUserRoles($userId, $orchestraId);
        if (empty($roles)) return null;

        foreach ($roles as $role) {
            if (empty($role['is_default'])) {
                $decoded = $role;
                $decoded['permissions'] = json_decode($role['permissions'], true) ?: [];
                return $decoded;
            }
        }

        $first = $roles[0];
        $first['permissions'] = json_decode($first['permissions'], true) ?: [];
        return $first;
    }

    /**
     * Check if user has a specific permission via any of their roles.
     */
    public function hasPermission(int $userId, int $orchestraId, string $permission): bool
    {
        $roles = $this->getUserRoles($userId, $orchestraId);
        foreach ($roles as $role) {
            $perms = json_decode($role['permissions'] ?? '[]', true) ?: [];
            if (in_array($permission, $perms, true)) return true;
        }
        return false;
    }

    /**
     * @return array Associative map of permission name => bool (merged from all roles)
     */
    public function getPermissions(int $userId, int $orchestraId): array
    {
        $roles = $this->getUserRoles($userId, $orchestraId);
        $granted = [];
        foreach ($roles as $role) {
            $perms = json_decode($role['permissions'] ?? '[]', true) ?: [];
            $granted = array_merge($granted, $perms);
        }
        $granted = array_unique($granted);

        $map = [];
        foreach (Role::getAvailablePermissions() as $perm) {
            $map[$perm] = in_array($perm, $granted, true);
        }
        return $map;
    }

    /**
     * Check if user has any of the given role IDs.
     */
    public function hasAnyRole(int $userId, int $orchestraId, array $roleIds): bool
    {
        $roles = $this->getUserRoles($userId, $orchestraId);
        $userRoleIds = array_column($roles, 'id');
        return !empty(array_intersect($userRoleIds, $roleIds));
    }

    // ── Type helpers ────────────────────────────────────────────

    public function updateUserType(int $userId, int $orchestraId, string $type): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        if (!$relation) return false;

        return $this->update($relation['id'], [
            'type' => $type,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array Users of a specific instrument/section within an orchestra
     */
    public function getUsersByType(string $type, int $orchestraId): array
    {
        $sql = "SELECT uo.*, u.email, u.display_name, uo.display_name as orchestra_display_name, u.created_at as user_created_at
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                WHERE uo.type = ? AND uo.orchestra_id = ? AND uo.is_active = 1
                ORDER BY COALESCE(uo.display_name, u.display_name, u.email)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $type, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        return $this->applyRolesToUsers($users);
    }

    /**
     * Batch-load roles for multiple user_orchestras rows and enrich each user row.
     *
     * @param array $users Raw user rows (must have 'id' = user_orchestras.id)
     * @return array Users with roles, permissions, and primary role fields
     */
    private function applyRolesToUsers(array $users): array
    {
        if (empty($users)) return [];

        // Batch-load all roles in one query
        $uoIds = array_column($users, 'id');
        $placeholders = implode(',', array_fill(0, count($uoIds), '?'));
        $types = str_repeat('i', count($uoIds));

        $sql = "SELECT uor.user_orchestra_id, r.*
                FROM roles r
                JOIN user_orchestra_roles uor ON uor.role_id = r.id
                WHERE uor.user_orchestra_id IN ({$placeholders})
                ORDER BY r.sort_order, r.name";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException("Failed to query user_orchestra_roles — run migration 20260305_110500_role_system_refactor.sql: " . $this->db->getLastError());
        }
        $stmt->bind_param($types, ...array_values($uoIds));
        $stmt->execute();
        $result = $stmt->get_result();

        $roleMap = [];
        while ($row = $result->fetch_assoc()) {
            $uoId = (int)$row['user_orchestra_id'];
            unset($row['user_orchestra_id']);
            $roleMap[$uoId][] = $row;
        }
        $stmt->close();

        foreach ($users as &$user) {
            $roles = $roleMap[(int)$user['id']] ?? [];
            $user['roles'] = $roles;
            $user['role_ids'] = array_map(fn($r) => (int)$r['id'], $roles);

            $allPerms = [];
            foreach ($roles as $role) {
                $rolePerms = json_decode($role['permissions'] ?? '[]', true) ?: [];
                $allPerms = array_merge($allPerms, $rolePerms);
            }
            $allPerms = array_unique($allPerms);
            $user['permissions'] = $allPerms;
            $user['can_attend_rehearsals'] = in_array('can_attend_rehearsals', $allPerms);

            $primaryRole = null;
            foreach ($roles as $r) {
                if (empty($r['is_default'])) {
                    $primaryRole = $r;
                    break;
                }
            }
            $primaryRole = $primaryRole ?? ($roles[0] ?? null);
            $user['role_id'] = $primaryRole['id'] ?? null;
            $user['role_name'] = $primaryRole['name'] ?? '';
            $user['role_tag_label'] = $primaryRole['name'] ?? '';
            $user['role_tag_color'] = $primaryRole['tag_color'] ?? '';
            $user['role_is_default'] = !empty($primaryRole['is_default']);
        }
        unset($user);

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
