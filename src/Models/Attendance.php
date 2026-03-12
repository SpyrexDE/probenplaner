<?php

namespace App\Models;

use App\Core\Model;

/**
 * Attendance Model
 *
 * Manages rehearsal attendance records (documented facts vs. promises).
 *
 * @method array|null findById(int $id)
 */
class Attendance extends Model
{
    protected $table = 'rehearsal_attendance';

    /**
     * @return array All attendance records for a rehearsal, keyed by user_id
     */
    public function getForRehearsal(int $rehearsalId): array
    {
        $sql = "SELECT ra.*, u.display_name AS recorder_name
                FROM {$this->table} ra
                LEFT JOIN users u ON ra.recorded_by = u.id
                WHERE ra.rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();

        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[(int)$row['user_id']] = $row;
        }
        $stmt->close();
        return $records;
    }

    /**
     * @return array|null Single attendance record
     */
    public function getForRehearsalAndUser(int $rehearsalId, int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE rehearsal_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $rehearsalId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Insert or update a single attendance record.
     *
     * @return bool
     */
    public function upsert(int $rehearsalId, int $userId, ?bool $present, ?string $comment, int $recordedBy): bool
    {
        $sql = "INSERT INTO {$this->table} (rehearsal_id, user_id, present, comment, recorded_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    present = VALUES(present),
                    comment = VALUES(comment),
                    recorded_by = VALUES(recorded_by),
                    updated_at = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $presentInt = $present === null ? null : ($present ? 1 : 0);
        $stmt->bind_param('iiisi', $rehearsalId, $userId, $presentInt, $comment, $recordedBy);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Bulk-confirm attendance for a rehearsal based on promise data.
     *
     * @param array $members Array of member arrays with 'id' key
     * @param array $promisesByUser Promise records keyed by user_id
     */
    public function bulkConfirm(int $rehearsalId, array $members, array $promisesByUser, int $recordedBy): int
    {
        // Only fill undocumented entries — don't overwrite existing records
        $existing = $this->getForRehearsal($rehearsalId);

        $sql = "INSERT INTO {$this->table} (rehearsal_id, user_id, present, recorded_by)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        $count = 0;
        foreach ($members as $member) {
            $userId = (int)($member['user_id'] ?? $member['id']);
            if (isset($existing[$userId])) continue;

            $promise = $promisesByUser[$userId] ?? null;
            if (!$promise) continue;

            $present = ($promise['status'] === 'yes') ? 1 : 0;
            $stmt->bind_param('iiii', $rehearsalId, $userId, $present, $recordedBy);
            $stmt->execute();
            $count++;
        }
        $stmt->close();
        return $count;
    }

    /**
     * @return bool Whether any attendance records exist for this rehearsal
     */
    public function hasRecords(int $rehearsalId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE rehearsal_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $has = $result->num_rows > 0;
        $stmt->close();
        return $has;
    }

    /**
     * @return array Rehearsal IDs that have at least one attendance record
     */
    public function getDocumentedRehearsalIds(int $orchestraId): array
    {
        $sql = "SELECT DISTINCT ra.rehearsal_id
                FROM {$this->table} ra
                JOIN rehearsals r ON ra.rehearsal_id = r.id
                WHERE r.orchestra_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['rehearsal_id'];
        }
        $stmt->close();
        return $ids;
    }

    /**
     * Delete a single attendance record.
     */
    public function deleteRecord(int $rehearsalId, int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE rehearsal_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $rehearsalId, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
