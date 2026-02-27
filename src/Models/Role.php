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

    /** Fixed permission sets for system roles */
    private const SYSTEM_PERMISSIONS = [
        'Leitung' => [
            'can_attend_rehearsals',
            'can_view_own_section_stats',
            'can_view_all_section_stats',
            'can_view_members',
            'can_manage_rehearsals',
            'can_manage_members',
            'can_manage_permissions',
            'can_manage_ensemble',
        ],
        'Mitglied' => [
            'can_attend_rehearsals',
        ],
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
        $sql = "SELECT r.*, (SELECT COUNT(*) FROM user_orchestras uo WHERE uo.role_id = r.id) AS user_count
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
     * @return array|null The default role for new members in an orchestra
     */
    public function getDefaultRole(int $orchestraId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = ? AND is_default = 1 LIMIT 1";
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
     * @return int New role ID
     */
    public function createRole(int $orchestraId, string $name, string $tagColor, array $permissions): int
    {
        $filtered = array_values(array_intersect($permissions, self::ALL_PERMISSIONS));
        return $this->insert([
            'orchestra_id' => $orchestraId,
            'name'         => $name,
            'tag_color'    => $tagColor,
            'permissions'  => json_encode($filtered),
            'is_system'    => 0,
            'is_default'   => 0,
            'sort_order'   => 50,
        ]);
    }

    /**
     * @return bool False if role is a system role
     */
    public function updateRole(int $id, array $data): bool
    {
        $role = $this->findById($id);
        if (!$role || !empty($role['is_system'])) return false;

        if (isset($data['permissions'])) {
            $data['permissions'] = json_encode(
                array_values(array_intersect($data['permissions'], self::ALL_PERMISSIONS))
            );
        }

        return $this->update($id, $data);
    }

    /**
     * @return bool False if role is a system role or still has users assigned
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->findById($id);
        if (!$role || !empty($role['is_system'])) return false;

        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM user_orchestras WHERE role_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        if ($count > 0) return false;

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
     * Create the two immutable system roles for a newly created orchestra.
     */
    public function createDefaultRoles(int $orchestraId): void
    {
        foreach (self::SYSTEM_PERMISSIONS as $name => $perms) {
            $isDefault = ($name === 'Mitglied') ? 1 : 0;
            $color = ($name === 'Leitung') ? '#478cf4' : '#10b981';
            $sort = ($name === 'Leitung') ? 0 : 100;

            $this->insert([
                'orchestra_id' => $orchestraId,
                'name'         => $name,
                'tag_color'    => $color,
                'permissions'  => json_encode($perms),
                'is_system'    => 1,
                'is_default'   => $isDefault,
                'sort_order'   => $sort,
            ]);
        }
    }
}
