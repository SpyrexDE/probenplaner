<?php

namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;

/**
 * Orchestra Model
 * Handles ensemble-related database operations
 */
class Orchestra extends Model
{
    protected $table = 'orchestras';

    public function findBySlug(string $slug): ?array
    {
        $rows = $this->findBy('slug', $slug);
        return $rows[0] ?? null;
    }

    /**
     * Find orchestra by slug, including its organization's slug.
     */
    public function findBySlugWithOrg(string $slug): ?array
    {
        $sql = "SELECT o.*, org.slug AS org_slug
                FROM {$this->table} o
                LEFT JOIN organizations org ON org.id = o.organization_id
                WHERE o.slug = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * @return int|false Orchestra ID or false on failure
     */
    public function createOrchestra(array $data)
    {
        try {
            if (!$this->db || !$this->db->getConnection()) {
                throw new \Exception('Datenbankverbindung fehlgeschlagen');
            }

            if (empty($data['name'])) {
                throw new \Exception('Ensemblename fehlt');
            }

            if (!$this->db->getConnection()->begin_transaction()) {
                throw new \Exception('Transaktion konnte nicht gestartet werden');
            }

            $insertData = ['name' => $data['name']];
            if (!empty($data['slug'])) {
                $insertData['slug'] = $data['slug'];
            }
            if (!empty($data['organization_id'])) {
                $insertData['organization_id'] = $data['organization_id'];
            }
            $orchestraId = $this->insert($insertData);

            if (!$orchestraId) {
                throw new \Exception('Ensemble konnte nicht erstellt werden: ' . $this->db->getLastError());
            }

            if (!$this->db->getConnection()->commit()) {
                throw new \Exception('Transaktion konnte nicht abgeschlossen werden');
            }

            return $orchestraId;
        } catch (\Exception $e) {
            if ($this->db && $this->db->getConnection()) {
                $this->db->getConnection()->rollback();
            }
            error_log("[Orchestra] createOrchestra failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateSettings(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * @return array Users with can_manage_ensemble permission
     */
    public function getConductors(int $orchestraId): array
    {
        $sql = "SELECT u.id, u.username, u.display_name
                FROM users u
                JOIN user_orchestras uo ON uo.user_id = u.id
                JOIN user_ensemble_permissions uep ON uep.user_orchestra_id = uo.id
                JOIN permissions p ON uep.permission_id = p.id
                WHERE uo.orchestra_id = ? AND uo.is_active = 1 AND p.name = 'can_manage_ensemble'
                ORDER BY COALESCE(u.display_name, u.username)";
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

    public function delete(int $id): bool
    {
        return parent::delete($id);
    }
}
