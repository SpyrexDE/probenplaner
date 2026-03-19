<?php

namespace App\Models;

use App\Core\Model;

/**
 * Organization Model
 *
 * @method array|null findById(int $id)
 * @method array findAll(string $orderBy = '')
 */
class Organization extends Model
{
    protected $table = 'organizations';

    protected function getAllowedFields(): array
    {
        return ['name', 'slug', 'created_at', 'updated_at'];
    }

    public function findBySlug(string $slug): ?array
    {
        $rows = $this->findBy('slug', $slug);
        return $rows[0] ?? null;
    }

    /**
     * @return array All organizations with ensemble/user counts
     */
    public function getAllWithStats(): array
    {
        $sql = "SELECT o.*,
                    (SELECT COUNT(*) FROM orchestras WHERE organization_id = o.id) AS ensemble_count,
                    (SELECT COUNT(DISTINCT uo.user_id)
                     FROM user_orchestras uo
                     JOIN orchestras orch ON orch.id = uo.orchestra_id
                     WHERE orch.organization_id = o.id AND uo.is_active = 1) AS user_count
                FROM organizations o
                ORDER BY o.name";
        $result = $this->db->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @return array Ensembles belonging to this organization
     */
    public function getEnsembles(int $orgId): array
    {
        $sql = "SELECT o.*,
                    (SELECT COUNT(*) FROM user_orchestras uo
                     WHERE uo.orchestra_id = o.id AND uo.is_active = 1) AS member_count
                FROM orchestras o
                WHERE o.organization_id = ?
                ORDER BY o.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orgId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * @return array|null The orga-admin user for this organization
     */
    public function getOrgAccount(int $orgId): ?array
    {
        $sql = "SELECT * FROM users WHERE organization_id = ? AND is_org_admin = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orgId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * @return array Conductors (users with can_manage_ensemble) for a given orchestra
     */
    public function getEnsembleConductors(int $orchestraId): array
    {
        $sql = "SELECT u.id, u.email, u.display_name
                FROM users u
                JOIN user_orchestras uo ON uo.user_id = u.id
                JOIN user_orchestra_roles uor ON uor.user_orchestra_id = uo.id
                JOIN roles r ON r.id = uor.role_id
                WHERE uo.orchestra_id = ? AND uo.is_active = 1
                  AND JSON_CONTAINS(r.permissions, '\"can_manage_ensemble\"')
                ORDER BY COALESCE(u.display_name, u.email)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Rename the org-admin account's email when the org slug changes.
     */
    public function renameOrgAccount(int $orgId, string $newSlug): bool
    {
        $sql = "UPDATE users SET email = ? WHERE organization_id = ? AND is_org_admin = 1";
        $stmt = $this->db->prepare($sql);
        $newEmail = $newSlug . '-admin@probenplaner.local';
        $stmt->bind_param('si', $newEmail, $orgId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
