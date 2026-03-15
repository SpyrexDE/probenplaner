<?php

namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;
use App\Core\Helpers;

/**
 * Rehearsal Model
 * Handles rehearsal-related database operations
 */
class Rehearsal extends Model
{
    /**
     * @var string
     */
    protected $table = 'rehearsals';

    /**
     * @param int $orchestraId
     * @param bool $includeOld Include past rehearsals
     * @return array
     */
    public function getUpcoming(int $orchestraId, bool $includeOld = false): array
    {
        $orchestraId = (int)$orchestraId;

        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId}";
        if (!$includeOld) {
            $sql .= " AND end >= NOW()";
        }
        $sql .= " ORDER BY start ASC";

        $result = $this->db->query($sql);
        if (!$result) return [];

        $rehearsals = [];
        while ($row = $result->fetch_assoc()) {
            $rehearsals[] = $row;
        }

        return $this->enrichRows($rehearsals);
    }

    /**
     * Check if there are any past rehearsals for an orchestra
     * 
     * @param int $orchestraId Orchestra ID
     * @return bool
     */
    public function hasPastRehearsals(int $orchestraId): bool
    {
        $orchestraId = (int)$orchestraId;
        $sql = "SELECT 1 FROM {$this->table} WHERE orchestra_id = {$orchestraId} AND start < NOW() LIMIT 1";
        $result = $this->db->query($sql);
        return $result && $result->num_rows > 0;
    }

    /**
     * Get groups for a rehearsal
     * 
     * @param int $rehearsalId Rehearsal ID
     * @return array Group names
     */
    public function getGroups(int $rehearsalId): array
    {
        $sql = "SELECT name FROM rehearsal_groups WHERE rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();

        $result = $stmt->get_result();

        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row['name'];
        }

        return $groups;
    }

    /**
     * Update or create rehearsal groups
     * 
     * @param int $rehearsalId Rehearsal ID
     * @param array $groups Group names
     * @return bool Success or failure
     */
    public function updateGroups(int $rehearsalId, ?array $groups)
    {
        // Start transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // First delete existing groups
            $sql = "DELETE FROM rehearsal_groups WHERE rehearsal_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $rehearsalId);
            $result = $stmt->execute();

            if (!$result) {
                throw new \Exception($stmt->error);
            }

            // Add new groups
            foreach ($groups as $group) {
                $sql = "INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('is', $rehearsalId, $group);
                $result = $stmt->execute();

                if (!$result) {
                    throw new \Exception($stmt->error);
                }
            }

            // Commit transaction
            $this->db->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->getConnection()->rollback();
            return ErrorHandler::handleDatabaseError($e, 'Rehearsal update');
        }
    }

    /**
     * Get rehearsals visible to a specific user based on groups and roles.
     *
     * @param string $userType User instrument/section type
     * @param int $orchestraId
     * @param bool $includeOld Include past rehearsals
     * @param array $userRoleIds User's role IDs for role-scoped visibility
     * @return array
     */
    public function getForUser(string $userType, int $orchestraId, bool $includeOld = false, array $userRoleIds = []): array
    {
        $orchestraId = (int)$orchestraId;

        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} ";
        if (!$includeOld) {
            $sql .= "AND end >= NOW() ";
        }
        $sql .= "ORDER BY start ASC";

        $result = $this->db->query($sql);
        if (!$result) return [];

        // Collect all rows and batch-load groups + roles for visibility filtering
        $allRows = [];
        while ($row = $result->fetch_assoc()) {
            $allRows[] = $row;
        }
        if (empty($allRows)) return [];

        $ids = array_column($allRows, 'id');
        $allGroupsMap = $this->batchLoadGroupsAssoc($ids);
        $allRolesMap = $this->batchLoadRehearsalRoleIds($ids);

        // Filter by visibility
        $visibleRows = [];
        foreach ($allRows as $row) {
            $groups = $allGroupsMap[$row['id']] ?? [];
            $rehearsalRoleIds = $allRolesMap[$row['id']] ?? [];
            if ($this->isUserInRehearsalGroup($userType, $groups, $rehearsalRoleIds, $userRoleIds)) {
                $visibleRows[] = $row;
            }
        }

        // Convert assoc groups to list format for enrichRows reuse
        $preloadedGroups = [];
        foreach ($allGroupsMap as $id => $assoc) {
            $preloadedGroups[$id] = array_keys($assoc);
        }

        return $this->enrichRows($visibleRows, $preloadedGroups);
    }

    /**
     * Get groups for a rehearsal as associative array
     * 
     * @param int $rehearsalId Rehearsal ID
     * @return array Group names as keys with dummy values
     */
    public function getGroupsAsAssoc(int $rehearsalId): array
    {
        $sql = "SELECT name FROM rehearsal_groups WHERE rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();

        $result = $stmt->get_result();

        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[$row['name']] = 0;
        }

        return $groups;
    }

    /**
     * Get role IDs assigned to a rehearsal via rehearsal_roles table.
     *
     * @param int $rehearsalId
     * @return array Integer role IDs
     */
    public function getRehearsalRoleIds(int $rehearsalId): array
    {
        $rehearsalId = (int)$rehearsalId;
        $result = $this->db->query(
            "SELECT role_id FROM rehearsal_roles WHERE rehearsal_id = {$rehearsalId}"
        );
        $ids = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ids[] = (int)$row['role_id'];
            }
        }
        return $ids;
    }

    /**
     * Check if a user should see a rehearsal based on group membership and role scoping.
     *
     * @param string $userType User instrument/section type
     * @param array $groups Rehearsal groups (assoc array, keys are group names)
     * @param array $rehearsalRoleIds Role IDs assigned to the rehearsal
     * @param array $userRoleIds Role IDs of the user
     * @return bool
     */
    public function isUserInRehearsalGroup(string $userType, array $groups, array $rehearsalRoleIds = [], array $userRoleIds = []): bool
    {
        // Role-based visibility: if rehearsal has role restrictions, user must share at least one
        if (!empty($rehearsalRoleIds) && empty(array_intersect($rehearsalRoleIds, $userRoleIds))) {
            return false;
        }

        $groupManager = \App\Core\GroupManager::getInstance();
        $userType = $groupManager->resolveAlias($userType);

        foreach (array_keys($groups) as $group) {
            if ($groupManager->isUserInGroup($userType, $group)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new rehearsal
     * 
     * @param array $data Rehearsal data
     * @param array $groups Groups involved
     * @return int|bool Rehearsal ID or false on failure
     */
    public function create(array $data, ?array $groups)
    {
        // Start transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Set timestamp values
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Insert rehearsal
            $rehearsalId = $this->insert($data);

            if (!$rehearsalId) {
                $error = $this->db->getConnection()->error;
                $errno = $this->db->getConnection()->errno;
                error_log("Failed to insert rehearsal: Error #" . $errno . ": " . $error);

                // Get more detailed error information
                $details = "MySQL Error #" . $errno . ": " . $error;
                if ($errno == 1054) { // Unknown column
                    $details .= "\nBitte führen Sie die Migrationen aus, um die Datenbankstruktur zu aktualisieren.";
                } elseif ($errno == 1062) { // Duplicate entry
                    $details .= "\nEin Eintrag mit diesen Daten existiert bereits.";
                } elseif ($errno == 1452) { // Foreign key constraint fails
                    $details .= "\nUngültige Referenz auf einen anderen Datensatz.";
                }

                throw new \Exception($details, $errno);
            }

            // Add groups
            if ($groups && is_array($groups)) {
                error_log("Creating rehearsal groups for rehearsal ID: " . $rehearsalId);
                foreach ($groups as $group) {
                    $sql = "INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('is', $rehearsalId, $group);
                    $result = $stmt->execute();

                    if (!$result) {
                        $error = $stmt->error;
                        $errno = $stmt->errno;
                        error_log("Failed to insert rehearsal group [$group]: " . $error);

                        // Get more detailed error information
                        $details = "MySQL Error #" . $errno . ": " . $error;
                        if ($errno == 1054) { // Unknown column
                            $details .= "\nBitte führen Sie die Migrationen aus, um die Datenbankstruktur zu aktualisieren.";
                        } elseif ($errno == 1062) { // Duplicate entry
                            $details .= "\nEin Eintrag mit diesen Daten existiert bereits.";
                        } elseif ($errno == 1452) { // Foreign key constraint fails
                            $details .= "\nUngültige Referenz auf einen anderen Datensatz.";
                        }

                        throw new \Exception($details, $errno);
                    }
                }
            }

            // Commit transaction
            $this->db->getConnection()->commit();
            error_log("Successfully created rehearsal with ID: " . $rehearsalId);

            return $rehearsalId;
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->getConnection()->rollback();
            return ErrorHandler::handleDatabaseError($e, 'Rehearsal creation');
        }
    }

    /**
     * Update a rehearsal
     * 
     * @param int $id Rehearsal ID
     * @param array $data Rehearsal data
     * @param array $groups Groups involved
     * @return bool Success or failure
     */
    public function updateRehearsal(int $id, array $data, ?array $groups)
    {
        // Start transaction
        $this->db->getConnection()->begin_transaction();

        try {
            // Set timestamp value
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Update rehearsal
            $result = $this->update($id, $data);

            if (!$result) {
                $error = $this->db->getConnection()->error;
                throw new \Exception($error ? $error : "Failed to update rehearsal record");
            }

            // First delete existing groups
            $sql = "DELETE FROM rehearsal_groups WHERE rehearsal_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $result = $stmt->execute();

            if (!$result) {
                throw new \Exception($stmt->error);
            }

            // Add new groups if provided
            if ($groups && is_array($groups)) {
                foreach ($groups as $group) {
                    $sql = "INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, ?)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('is', $id, $group);
                    $result = $stmt->execute();

                    if (!$result) {
                        throw new \Exception($stmt->error);
                    }
                }
            }

            // Commit transaction
            $this->db->getConnection()->commit();

            return true;
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->getConnection()->rollback();
            return ErrorHandler::handleDatabaseError($e, 'Rehearsal update');
        }
    }

    /**
     * Delete a rehearsal and all related records
     * 
     * @param int $id Rehearsal ID
     * @return bool Success or failure
     */
    public function delete(int $id): bool
    {
        // Foreign key constraints with CASCADE will handle related records
        return parent::delete($id);
    }

    /**
     * Get a single rehearsal by ID
     * 
     * @param int $id Rehearsal ID
     * @return array|null Rehearsal data or null if not found
     */
    public function findById(int $id): ?array
    {
        $id = (int)$id;
        $rehearsal = parent::findById($id);

        if ($rehearsal) {
            $rehearsal['groups'] = $this->getGroups($id);
            $rehearsal['schedule_items'] = $this->getScheduleItems($id);
            $rehearsal['infos'] = $this->getInfos($id);
            $rehearsal['tags'] = $this->getTags($id);
            $rehearsal['date'] = date('Y-m-d', strtotime($rehearsal['start']));

            $rehearsal['start_time'] = date('H:i', strtotime($rehearsal['start']));
            $rehearsal['end_time'] = date('H:i', strtotime($rehearsal['end']));
            $rehearsal['date_formatted'] = \App\Core\Utilities::formatDate($rehearsal['date']);
            $rehearsal['start_formatted'] = $rehearsal['start_time'];
            $rehearsal['end_formatted'] = $rehearsal['end_time'];
        }

        return $rehearsal;
    }

    /** @deprecated Use getForUser() directly — kept for backwards compatibility. */
    public function getRelevantForUser($orchestraId, $userType, $includeOld = false, array $userRoleIds = [])
    {
        return $this->getForUser($userType, (int)$orchestraId, $includeOld, $userRoleIds);
    }

    /**
     * Paginated past rehearsals using SQL LIMIT/OFFSET.
     *
     * @return array{rows: array, total: int}
     */
    public function getPastPaginated(int $orchestraId, int $offset, int $limit): array
    {
        $orchestraId = (int)$orchestraId;

        $countResult = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM {$this->table} WHERE orchestra_id = {$orchestraId} AND end < NOW()"
        );
        $total = $countResult ? (int)$countResult->fetch_assoc()['cnt'] : 0;

        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} AND end < NOW() ORDER BY start DESC LIMIT {$limit} OFFSET {$offset}";
        $result = $this->db->query($sql);
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return ['rows' => $this->enrichRows($rows), 'total' => $total];
    }

    /**
     * @param int $rehearsalId
     * @return array Schedule items ordered by sort_order
     */
    public function getScheduleItems(int $rehearsalId): array
    {
        $sql = "SELECT id, time, label, sort_order FROM rehearsal_schedule_items WHERE rehearsal_id = ? ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['time_formatted'] = substr($row['time'], 0, 5);
            $items[] = $row;
        }
        return $items;
    }

    // ── Batch loading helpers ──────────────────────────────────────────

    /**
     * Enrich raw rehearsal rows with computed date fields and batch-loaded relations.
     *
     * @param array $rows Raw rehearsal rows from DB
     * @return array Enriched rows
     */
    private function enrichRows(array $rows, array $preloadedGroups = []): array
    {
        if (empty($rows)) return [];

        $ids = array_column($rows, 'id');
        $groupsMap = !empty($preloadedGroups) ? $preloadedGroups : $this->batchLoadGroups($ids);
        $scheduleMap = $this->batchLoadScheduleItems($ids);
        $infosMap = $this->batchLoadInfos($ids);
        $rolesMap = $this->batchLoadRehearsalRoles($ids);
        $tagsMap = $this->batchLoadTags($ids);

        foreach ($rows as &$row) {
            $row['date'] = date('Y-m-d', strtotime($row['start']));
            $row['start_time'] = date('H:i', strtotime($row['start']));
            $row['end_time'] = date('H:i', strtotime($row['end']));
            $row['date_formatted'] = \App\Core\Utilities::formatDate($row['date']);
            $row['start_formatted'] = $row['start_time'];
            $row['end_formatted'] = $row['end_time'];
            $row['groups'] = $groupsMap[$row['id']] ?? [];
            $row['schedule_items'] = $scheduleMap[$row['id']] ?? [];
            $row['infos'] = $infosMap[$row['id']] ?? [];
            $row['roles'] = $rolesMap[$row['id']] ?? [];
            $row['tags'] = $tagsMap[$row['id']] ?? [];
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<int, string[]> rehearsal_id => group names
     */
    private function batchLoadGroups(array $ids): array
    {
        return $this->batchQuery(
            "SELECT rehearsal_id, name FROM rehearsal_groups WHERE rehearsal_id IN (%s)",
            $ids,
            fn($row) => $row['name']
        );
    }

    /**
     * @return array<int, array<string, int>> rehearsal_id => [group_name => 0]
     */
    private function batchLoadGroupsAssoc(array $ids): array
    {
        if (empty($ids)) return [];
        $groups = $this->batchLoadGroups($ids);
        $result = [];
        foreach ($ids as $id) {
            $assoc = [];
            foreach ($groups[$id] ?? [] as $name) {
                $assoc[$name] = 0;
            }
            $result[$id] = $assoc;
        }
        return $result;
    }

    /**
     * @return array<int, int[]> rehearsal_id => role IDs
     */
    private function batchLoadRehearsalRoleIds(array $ids): array
    {
        return $this->batchQuery(
            "SELECT rehearsal_id, role_id FROM rehearsal_roles WHERE rehearsal_id IN (%s)",
            $ids,
            fn($row) => (int)$row['role_id']
        );
    }

    /**
     * @return array<int, array[]> rehearsal_id => role objects [{id, name, tag_color}]
     */
    private function batchLoadRehearsalRoles(array $ids): array
    {
        return $this->batchQuery(
            "SELECT rr.rehearsal_id, r.id, r.name, r.tag_color FROM rehearsal_roles rr JOIN roles r ON r.id = rr.role_id WHERE rr.rehearsal_id IN (%s)",
            $ids,
            function ($row) {
                return [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'tag_color' => $row['tag_color'] ?? '#478cf4',
                ];
            }
        );
    }

    /**
     * @param int[] $ids Rehearsal IDs
     * @return array<int, int[]> rehearsal_id => role IDs
     */
    public function getBatchRehearsalRoleIds(array $ids): array
    {
        return $this->batchLoadRehearsalRoleIds($ids);
    }

    /**
     * @return array<int, array[]> rehearsal_id => schedule item rows
     */
    private function batchLoadScheduleItems(array $ids): array
    {
        return $this->batchQuery(
            "SELECT rehearsal_id, id, time, label, sort_order FROM rehearsal_schedule_items WHERE rehearsal_id IN (%s) ORDER BY sort_order ASC",
            $ids,
            function ($row) {
                $row['time_formatted'] = substr($row['time'], 0, 5);
                unset($row['rehearsal_id']);
                return $row;
            }
        );
    }

    /**
     * @return array<int, array[]> rehearsal_id => info item rows
     */
    private function batchLoadInfos(array $ids): array
    {
        return $this->batchQuery(
            "SELECT rehearsal_id, id, emoji, text, sort_order FROM rehearsal_infos WHERE rehearsal_id IN (%s) ORDER BY sort_order ASC",
            $ids,
            function ($row) {
                unset($row['rehearsal_id']);
                return $row;
            }
        );
    }

    /**
     * Generic batch query grouped by rehearsal_id.
     *
     * @param string $sqlTemplate SQL with a single %s placeholder for IN(…)
     * @param int[] $ids
     * @param callable $rowMapper Transforms a fetched row into the value stored per rehearsal
     * @return array<int, array>
     */
    private function batchQuery(string $sqlTemplate, array $ids, callable $rowMapper): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = sprintf($sqlTemplate, $placeholders);
        $stmt = $this->db->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...array_values($ids));
        $stmt->execute();
        $result = $stmt->get_result();

        $map = [];
        while ($row = $result->fetch_assoc()) {
            $rId = (int)$row['rehearsal_id'];
            $map[$rId][] = $rowMapper($row);
        }
        $stmt->close();
        return $map;
    }

    /**
     * Replace all schedule items for a rehearsal (delete + reinsert).
     *
     * @param int $rehearsalId
     * @param array $items Array of ['time' => 'HH:MM', 'label' => '...']
     * @return bool
     */
    public function saveScheduleItems(int $rehearsalId, array $items): bool
    {
        $this->db->getConnection()->begin_transaction();

        try {
            $stmt = $this->db->prepare("DELETE FROM rehearsal_schedule_items WHERE rehearsal_id = ?");
            $stmt->bind_param('i', $rehearsalId);
            $stmt->execute();

            $sql = "INSERT INTO rehearsal_schedule_items (rehearsal_id, time, label, sort_order) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            foreach ($items as $i => $item) {
                $time = $item['time'] ?? '00:00';
                $label = $item['label'] ?? '';
                if (empty($label)) continue;
                // Normalise "HH:MM" to "HH:MM:00"
                if (strlen($time) === 5) $time .= ':00';
                $order = $i;
                $stmt->bind_param('issi', $rehearsalId, $time, $label, $order);
                $stmt->execute();
            }

            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            ErrorHandler::handleDatabaseError($e, 'Schedule items save');
            return false;
        }
    }
    /**
     * @param int $rehearsalId
     * @return array Info items ordered by sort_order
     */
    public function getInfos(int $rehearsalId): array
    {
        $sql = "SELECT id, emoji, text, sort_order FROM rehearsal_infos WHERE rehearsal_id = ? ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * Replace all info items for a rehearsal (delete + reinsert).
     *
     * @param int $rehearsalId
     * @param array $items Array of ['emoji' => '...', 'text' => '...']
     * @return bool
     */
    public function saveInfos(int $rehearsalId, array $items): bool
    {
        $this->db->getConnection()->begin_transaction();

        try {
            $stmt = $this->db->prepare("DELETE FROM rehearsal_infos WHERE rehearsal_id = ?");
            $stmt->bind_param('i', $rehearsalId);
            $stmt->execute();

            $sql = "INSERT INTO rehearsal_infos (rehearsal_id, emoji, text, sort_order) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            foreach ($items as $i => $item) {
                $emoji = $item['emoji'] ?? '❗';
                $text = $item['text'] ?? '';
                if (empty($text)) continue;

                $order = $i;
                $stmt->bind_param('issi', $rehearsalId, $emoji, $text, $order);
                $stmt->execute();
            }

            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            ErrorHandler::handleDatabaseError($e, 'Infos save');
            return false;
        }
    }

    // ── Tags ──────────────────────────────────────────────────────────

    /**
     * @return string[] Tag names for a rehearsal
     */
    public function getTags(int $rehearsalId): array
    {
        $stmt = $this->db->prepare("SELECT name FROM rehearsal_tags WHERE rehearsal_id = ? ORDER BY id ASC");
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row['name'];
        }
        return $tags;
    }

    /**
     * Replace all tags for a rehearsal (delete + reinsert).
     */
    public function saveTags(int $rehearsalId, int $orchestraId, array $tags): bool
    {
        $this->db->getConnection()->begin_transaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM rehearsal_tags WHERE rehearsal_id = ?");
            $stmt->bind_param('i', $rehearsalId);
            $stmt->execute();

            $sql = "INSERT INTO rehearsal_tags (rehearsal_id, orchestra_id, name) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            foreach ($tags as $name) {
                $name = trim($name);
                if ($name === '') continue;
                $stmt->bind_param('iis', $rehearsalId, $orchestraId, $name);
                $stmt->execute();
            }

            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            ErrorHandler::handleDatabaseError($e, 'Tags save');
            return false;
        }
    }

    /**
     * @return array<int, string[]> rehearsal_id => tag names
     */
    private function batchLoadTags(array $ids): array
    {
        return $this->batchQuery(
            "SELECT rehearsal_id, name FROM rehearsal_tags WHERE rehearsal_id IN (%s) ORDER BY id ASC",
            $ids,
            fn($row) => $row['name']
        );
    }

    /**
     * @return string[] All distinct tag names used in an orchestra (for autocomplete)
     */
    public function getOrchestraTags(int $orchestraId): array
    {
        $stmt = $this->db->prepare("SELECT DISTINCT name FROM rehearsal_tags WHERE orchestra_id = ? ORDER BY name ASC");
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row['name'];
        }
        return $tags;
    }
}
