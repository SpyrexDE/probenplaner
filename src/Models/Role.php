<?php

namespace App\Models;

use App\Core\Model;

/**
 * Role Model
 *
 * @method array|null findById(int $id)
 */
class Role extends Model
{
    protected $table = 'roles';

    /** Fixed permission set for the immutable conductor role */
    private const CONDUCTOR_PERMISSIONS = [
        'can_attend_rehearsals',
        'can_view_own_section_stats',
        'can_view_all_section_stats',
        'can_view_members',
        'can_manage_rehearsals',
        'can_manage_members',
        'can_manage_permissions',
        'can_manage_ensemble',
    ];

    /** All valid permission names in the system */
    private const ALL_PERMISSIONS = [
        'can_attend_rehearsals',
        'can_view_own_section_stats',
        'can_view_all_section_stats',
        'can_view_members',
        'can_manage_rehearsals',
        'can_manage_members',
        'can_manage_permissions',
        'can_manage_ensemble',
    ];

    /** Human-readable labels for permissions */
    public const PERMISSION_LABELS = [
        'can_attend_rehearsals'       => 'Proben besuchen',
        'can_view_own_section_stats'  => 'Eigene Register-Statistik',
        'can_view_all_section_stats'  => 'Alle Register-Statistiken',
        'can_view_members'            => 'Mitglieder ansehen',
        'can_manage_rehearsals'       => 'Termine verwalten',
        'can_manage_members'          => 'Mitglieder verwalten',
        'can_manage_permissions'      => 'Rollen verwalten',
        'can_manage_ensemble'         => 'Ensemble verwalten',
    ];

    /**
     * Hierarchy tree for the role-editor UI.
     *
     * Each entry is a top-level node; nested 'children' arrays define
     * dependent permissions. Checking a child auto-checks its parent,
     * unchecking a parent auto-unchecks its children.
     */
    public const PERMISSION_HIERARCHY = [
        ['id' => 'can_attend_rehearsals'],
        [
            'id'       => 'can_view_own_section_stats',
            'children' => [
                ['id' => 'can_view_all_section_stats'],
            ],
        ],
        [
            'id'       => 'can_view_members',
            'children' => [
                ['id' => 'can_manage_members'],
                ['id' => 'can_manage_permissions'],
            ],
        ],
        ['id' => 'can_manage_rehearsals'],
        ['id' => 'can_manage_ensemble'],
    ];

    /**
     * @return string[] All valid permission names
     */
    public static function getAvailablePermissions(): array
    {
        return self::ALL_PERMISSIONS;
    }

    /**
     * @return array All roles for an orchestra, ordered by sort_order
     */
    public function getByOrchestra(int $orchestraId): array
    {
        $sql = "SELECT r.*,
                (SELECT COUNT(DISTINCT uor.user_orchestra_id)
                 FROM user_orchestra_roles uor
                 JOIN user_orchestras uo ON uor.user_orchestra_id = uo.id
                 WHERE uor.role_id = r.id AND uo.is_active = 1) AS user_count
                FROM {$this->table} r
                WHERE r.orchestra_id = ?
                ORDER BY r.sort_order, r.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
            $roles[] = $row;
        }
        $stmt->close();
        return $roles;
    }

    /**
     * @return array|null Role with decoded permissions, or null
     */
    public function findByIdDecoded(int $id): ?array
    {
        $row = $this->findById($id);
        if ($row) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
        }
        return $row;
    }

    /**
     * @return array All default roles for new members in an orchestra
     */
    public function getDefaultRoles(int $orchestraId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = ? AND is_default = 1 ORDER BY sort_order";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
            $roles[] = $row;
        }
        $stmt->close();
        return $roles;
    }

    /**
     * @return array|null The "Leitung" system role for an orchestra
     */
    public function getConductorRole(int $orchestraId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = ? AND is_system = 1 AND name = 'Leitung' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        if ($row) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
        }
        return $row;
    }

    /**
     * @return array Self-assignable roles for a given orchestra
     */
    public function getSelfAssignableRoles(int $orchestraId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = ? AND is_self_assignable = 1 ORDER BY sort_order, name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
            $roles[] = $row;
        }
        $stmt->close();
        return $roles;
    }

    /**
     * @return int New role ID
     */
    public function createRole(int $orchestraId, string $name, string $tagColor, array $permissions, bool $isSelfAssignable = false): int
    {
        $filtered = array_values(array_intersect($permissions, self::ALL_PERMISSIONS));
        return $this->insert([
            'orchestra_id'     => $orchestraId,
            'name'             => $name,
            'tag_color'        => $tagColor,
            'permissions'      => json_encode($filtered),
            'is_system'        => 0,
            'is_default'       => 0,
            'is_self_assignable' => $isSelfAssignable ? 1 : 0,
            'sort_order'       => 50,
        ]);
    }

    /**
     * @return bool False if role is the immutable Leitung system role
     */
    public function updateRole(int $id, array $data): bool
    {
        $role = $this->findById($id);
        if (!$role) return false;

        // Only Leitung (is_system=1) is truly immutable
        if (!empty($role['is_system'])) return false;

        if (isset($data['permissions'])) {
            $data['permissions'] = json_encode(
                array_values(array_intersect($data['permissions'], self::ALL_PERMISSIONS))
            );
        }
        if (isset($data['is_self_assignable'])) {
            $data['is_self_assignable'] = $data['is_self_assignable'] ? 1 : 0;
        }

        return $this->update($id, $data);
    }

    /**
     * Toggle default flag for a role. Enforces at least one default per orchestra.
     */
    public function toggleDefault(int $orchestraId, int $roleId, bool $isDefault): bool
    {
        if (!$isDefault) {
            $defaults = $this->getDefaultRoles($orchestraId);
            $defaultIds = array_column($defaults, 'id');
            if (count($defaultIds) <= 1 && in_array($roleId, array_map('intval', $defaultIds))) {
                return false;
            }
        }
        return $this->update($roleId, ['is_default' => $isDefault ? 1 : 0]);
    }

    /**
     * @return bool False if role is a system role, the last default role, or the last role
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->findById($id);
        if (!$role || !empty($role['is_system'])) return false;

        $orchestraId = (int)$role['orchestra_id'];

        // Cannot delete last default role
        if (!empty($role['is_default'])) {
            $defaults = $this->getDefaultRoles($orchestraId);
            if (count($defaults) <= 1) return false;
        }

        // Prevent deleting the last role in an orchestra
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE orchestra_id = ?");
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $roleCount = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        if ($roleCount <= 1) return false;

        // Find members who will lose this role and might end up with none
        $defaults = $this->getDefaultRoles($orchestraId);
        $defaultIds = array_map(fn($d) => (int)$d['id'], $defaults);
        $firstDefaultId = $defaultIds[0] ?? null;

        if ($firstDefaultId) {
            $stmt = $this->db->prepare(
                "SELECT uor.user_orchestra_id FROM user_orchestra_roles uor
                 WHERE uor.role_id = ?
                 AND (SELECT COUNT(*) FROM user_orchestra_roles uor2
                      WHERE uor2.user_orchestra_id = uor.user_orchestra_id AND uor2.role_id != ?) = 0"
            );
            $stmt->bind_param('ii', $id, $id);
            $stmt->execute();
            $orphans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Assign first default role to members who would be left with no role
            foreach ($orphans as $row) {
                $uoId = (int)$row['user_orchestra_id'];
                $ins = $this->db->prepare("INSERT IGNORE INTO user_orchestra_roles (user_orchestra_id, role_id) VALUES (?, ?)");
                $ins->bind_param('ii', $uoId, $firstDefaultId);
                $ins->execute();
                $ins->close();
            }
        }

        // Remove all assignments of this role
        $stmt = $this->db->prepare("DELETE FROM user_orchestra_roles WHERE role_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        // Remove from rehearsal_roles
        $stmt = $this->db->prepare("DELETE FROM rehearsal_roles WHERE role_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        return $this->delete($id);
    }

    /**
     * @return bool Whether the given role has the specified permission
     */
    public function hasPermission(int $roleId, string $permission): bool
    {
        $role = $this->findByIdDecoded($roleId);
        if (!$role) return false;
        return in_array($permission, $role['permissions'], true);
    }

    /**
     * @return array Associative map permission_name => bool
     */
    public function getPermissionMap(int $roleId): array
    {
        $role = $this->findByIdDecoded($roleId);
        $granted = $role ? $role['permissions'] : [];
        $map = [];
        foreach (self::ALL_PERMISSIONS as $perm) {
            $map[$perm] = in_array($perm, $granted, true);
        }
        return $map;
    }

    /**
     * Get roles assigned to a specific rehearsal.
     *
     * @return array Role rows with decoded permissions
     */
    public function getRehearsalRoles(int $rehearsalId): array
    {
        $sql = "SELECT r.* FROM {$this->table} r
                JOIN rehearsal_roles rr ON rr.role_id = r.id
                WHERE rr.rehearsal_id = ?
                ORDER BY r.sort_order, r.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();

        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $row['permissions'] = json_decode($row['permissions'], true) ?: [];
            $roles[] = $row;
        }
        $stmt->close();
        return $roles;
    }

    /**
     * Set roles for a rehearsal (replaces existing).
     */
    public function setRehearsalRoles(int $rehearsalId, array $roleIds): void
    {
        $stmt = $this->db->prepare("DELETE FROM rehearsal_roles WHERE rehearsal_id = ?");
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $stmt->close();

        if (empty($roleIds)) return;

        $stmt = $this->db->prepare("INSERT INTO rehearsal_roles (rehearsal_id, role_id) VALUES (?, ?)");
        foreach ($roleIds as $roleId) {
            $roleId = (int)$roleId;
            $stmt->bind_param('ii', $rehearsalId, $roleId);
            $stmt->execute();
        }
        $stmt->close();
    }

    /**
     * Create the two default roles for a newly created orchestra.
     * Leitung is the immutable system role; Mitglied is the default (editable).
     */
    public function createDefaultRoles(int $orchestraId): void
    {
        // Leitung — immutable system role
        $this->insert([
            'orchestra_id' => $orchestraId,
            'name'         => 'Leitung',
            'tag_color'    => '#478cf4',
            'permissions'  => json_encode(self::CONDUCTOR_PERMISSIONS),
            'is_system'    => 1,
            'is_default'   => 0,
            'is_self_assignable' => 0,
            'sort_order'   => 0,
        ]);

        // Mitglied — editable default role
        $this->insert([
            'orchestra_id' => $orchestraId,
            'name'         => 'Mitglied',
            'tag_color'    => '#10b981',
            'permissions'  => json_encode(['can_attend_rehearsals']),
            'is_system'    => 0,
            'is_default'   => 1,
            'is_self_assignable' => 0,
            'sort_order'   => 100,
        ]);
    }
}
