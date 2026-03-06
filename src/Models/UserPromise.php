<?php

namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;

/**
 * UserPromise Model
 * Handles user promise database operations
 */
class UserPromise extends Model
{
    /**
     * @var string
     */
    protected $table = 'user_promises';

    /**
     * Find a promise by user ID and rehearsal ID
     * 
     * @param int $userId User ID
     * @param int $rehearsalId Rehearsal ID
     * @return array|null Promise data or null if not found
     */
    public function findByUserAndRehearsal(int $userId, int $rehearsalId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $rehearsalId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * @param int[] $rehearsalIds
     * @return array<int, array{attending: bool, note: string}> Keyed by rehearsal_id
     */
    public function findPromisesForRehearsalsAndUser(array $rehearsalIds, int $userId): array
    {
        if (empty($rehearsalIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rehearsalIds), '?'));
        $types = str_repeat('i', count($rehearsalIds)) . 'i';

        $sql = "SELECT rehearsal_id, status, note
                FROM {$this->table}
                WHERE rehearsal_id IN ({$placeholders}) AND user_id = ?";

        $stmt = $this->db->prepare($sql);
        $params = [...$rehearsalIds, $userId];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $promises = [];
        while ($row = $result->fetch_assoc()) {
            $promises[(int)$row['rehearsal_id']] = [
                'attending' => $row['status'] === 'yes',
                'note' => $row['note'] ?? '',
            ];
        }
        $stmt->close();
        return $promises;
    }

    /**
     * Load all promises for an orchestra in one query.
     *
     * @return array<int, array<int, array{status: string, note: string}>> [user_id][rehearsal_id]
     */
    public function getAllForOrchestra(int $orchestraId): array
    {
        $sql = "SELECT up.user_id, up.rehearsal_id, up.status, up.note
                FROM {$this->table} up
                JOIN rehearsals r ON up.rehearsal_id = r.id
                WHERE r.orchestra_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $uid = (int)$row['user_id'];
            $rid = (int)$row['rehearsal_id'];
            $map[$uid][$rid] = [
                'status' => $row['status'],
                'note' => $row['note'] ?? '',
            ];
        }
        $stmt->close();
        return $map;
    }

    /**
     * Get all promises for a specific rehearsal
     * 
     * @param int $rehearsalId Rehearsal ID
     * @return array Promises for the rehearsal
     */
    public function getByRehearsal(int $rehearsalId): array
    {
        $sql = "SELECT up.*, u.email, u.display_name, uo.type
                FROM {$this->table} up
                JOIN users u ON up.user_id = u.id
                JOIN user_orchestras uo ON u.id = uo.user_id
                JOIN rehearsals r ON up.rehearsal_id = r.id
                WHERE up.rehearsal_id = ? AND uo.orchestra_id = r.orchestra_id AND uo.is_active = 1
                ORDER BY uo.type, COALESCE(u.display_name, u.email)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();

        $result = $stmt->get_result();

        $promises = [];
        while ($row = $result->fetch_assoc()) {
            $promises[] = $row;
        }

        return $promises;
    }

    /**
     * Get promise statistics for a rehearsal
     * 
     * @param int $rehearsalId Rehearsal ID
     * @param int $orchestraId Orchestra ID
     * @return array Statistics
     */
    public function getPromiseStats(int $rehearsalId, int $orchestraId): array
    {
        $stats = [
            'total' => 0,
            'attending' => 0,
            'not_attending' => 0,
            'no_response' => 0,
            'details' => []
        ];

        $rehearsalModel = new Rehearsal();
        $rehearsal = $rehearsalModel->findById($rehearsalId);

        if (!$rehearsal) {
            return $stats;
        }

        $sql = "SELECT name FROM rehearsal_groups WHERE rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();

        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[$row['name']] = true;
        }

        $userOrchestraModel = new UserOrchestra();
        $users = $userOrchestraModel->getOrchestraUsers($orchestraId);

        // Batch-load once outside the loop
        $rehearsalRoleIds = $rehearsalModel->getRehearsalRoleIds($rehearsalId);

        $promiseStmt = $this->db->prepare("SELECT user_id, status, note FROM {$this->table} WHERE rehearsal_id = ?");
        $promiseStmt->bind_param('i', $rehearsalId);
        $promiseStmt->execute();
        $promiseResult = $promiseStmt->get_result();
        $allPromises = [];
        while ($pRow = $promiseResult->fetch_assoc()) {
            $allPromises[(int)$pRow['user_id']] = $pRow;
        }
        $promiseStmt->close();

        foreach ($users as $user) {
            if (empty($user['can_attend_rehearsals'])) {
                continue;
            }

            $memberRoleIds = isset($user['role_ids']) ? $user['role_ids'] : [];
            if ($rehearsalModel->isUserInRehearsalGroup($user['type'], $groups, $rehearsalRoleIds, $memberRoleIds)) {
                $stats['total']++;

                $promise = $allPromises[(int)$user['id']] ?? null;

                $userStat = [
                    'id' => $user['id'],
                    'display_name' => $user['display_name'] ?? $user['email'] ?? '',
                    'type' => $user['type'],
                    'status' => 'no_response',
                    'note' => ''
                ];

                if ($promise && isset($promise['status'])) {
                    if ($promise['status'] === 'yes') {
                        $userStat['status'] = 'attending';
                        $stats['attending']++;
                    } elseif ($promise['status'] === 'no') {
                        $userStat['status'] = 'not_attending';
                        $stats['not_attending']++;
                    } else {
                        $userStat['status'] = 'no_response';
                        $stats['no_response']++;
                    }

                    $userStat['note'] = $promise['note'] ?? '';
                } else {
                    $stats['no_response']++;
                }

                $stats['details'][] = $userStat;
            }
        }

        return $stats;
    }
}
