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
     * Get upcoming rehearsals for an orchestra
     * 
     * @param int $orchestraId Orchestra ID
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

        // If no rehearsals, return empty array
        if (!$result) {
            return [];
        }

        $rehearsals = [];
        while ($row = $result->fetch_assoc()) {
            $row['date'] = date('Y-m-d', strtotime($row['start']));
            $row['start_time'] = date('H:i', strtotime($row['start']));
            $row['end_time'] = date('H:i', strtotime($row['end']));
            $row['date_formatted'] = \App\Core\Utilities::formatDate($row['date']);
            $row['start_formatted'] = $row['start_time'];
            $row['end_formatted'] = $row['end_time'];
            $row['groups'] = $this->getGroups($row['id']);
            $row['schedule_items'] = $this->getScheduleItems($row['id']);
            $row['infos'] = $this->getInfos($row['id']);
            $rehearsals[] = $row;
        }

        return $rehearsals;
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
     * Get rehearsals for a specific user
     * 
     * @param string $userType User type/instrument
     * @param int $orchestraId Orchestra ID
     * @param bool $includeOld Whether to include past rehearsals
     * @param bool $isSmallGroup Whether user is in small group
     * @return array
     */
    public function getForUser(string $userType, int $orchestraId, bool $includeOld = false, bool $isSmallGroup = false): array
    {
        $orchestraId = (int)$orchestraId;

        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} ";

        if (!$includeOld) {
            $sql .= "AND end >= NOW() ";
        }

        $sql .= "ORDER BY start ASC";

        $result = $this->db->query($sql);

        $rehearsals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $groups = $this->getGroupsAsAssoc($row['id']);
                $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($row);

                if ($this->isUserInRehearsalGroup($userType, $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    $row['date'] = date('Y-m-d', strtotime($row['start']));
                    $row['start_time'] = date('H:i', strtotime($row['start']));
                    $row['end_time'] = date('H:i', strtotime($row['end']));
                    $row['date_formatted'] = \App\Core\Utilities::formatDate($row['date']);
                    $row['start_formatted'] = $row['start_time'];
                    $row['end_formatted'] = $row['end_time'];
                    $row['groups'] = $this->getGroups($row['id']);
                    $row['schedule_items'] = $this->getScheduleItems($row['id']);
                    $row['infos'] = $this->getInfos($row['id']);
                    $rehearsals[] = $row;
                }
            }
        }

        return $rehearsals;
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
     * Check if user type is in the specified groups
     * 
     * @param string $userType User type/instrument
     * @param bool $isSmallGroup Whether the user is in small group
     * @param array $groups Groups to check
     * @param bool $rehearsalIsSmallGroup Whether the rehearsal is a small group rehearsal
     * @return bool
     */
    public function isUserInRehearsalGroup(string $userType, bool $isSmallGroup, $groups, bool $rehearsalIsSmallGroup = false): bool
    {
        // Initialize user/rehearsal arrays for visibility check
        $user = ['is_small_group' => $isSmallGroup ? \App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED : \App\Core\RehearsalTypeManager::SMALL_GROUP_DISABLED];
        $rehearsal = ['is_small_group' => $rehearsalIsSmallGroup ? \App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED : \App\Core\RehearsalTypeManager::SMALL_GROUP_DISABLED];

        if (!\App\Core\RehearsalTypeManager::canUserSeeRehearsal($user, $rehearsal)) {
            return false;
        }

        // Use GroupManager for dynamic group checking
        $groupManager = new \App\Core\GroupManager();

        // Resolve any aliases first
        $userType = $groupManager->resolveAlias($userType);

        // Check each group
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
            $rehearsal['date'] = date('Y-m-d', strtotime($rehearsal['start']));

            $rehearsal['start_time'] = date('H:i', strtotime($rehearsal['start']));
            $rehearsal['end_time'] = date('H:i', strtotime($rehearsal['end']));
            $rehearsal['date_formatted'] = \App\Core\Utilities::formatDate($rehearsal['date']);
            $rehearsal['start_formatted'] = $rehearsal['start_time'];
            $rehearsal['end_formatted'] = $rehearsal['end_time'];
        }

        return $rehearsal;
    }

    /**
     * Get rehearsals relevant for a specific user
     * 
     * @param int $orchestraId Orchestra ID
     * @param string $userType User type/instrument
     * @param array $userGroups User's groups
     * @param bool $includeOld Whether to include past rehearsals
     * @param bool $isSmallGroup Whether the user is in a small group
     * @return array
     */
    public function getRelevantForUser($orchestraId, $userType, $includeOld = false, $isSmallGroup = false)
    {
        $orchestraId = (int)$orchestraId;

        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} ";

        if (!$includeOld) {
            $sql .= "AND end >= NOW() ";
        }

        $sql .= "ORDER BY start ASC";

        $result = $this->db->query($sql);

        $rehearsals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $groups = $this->getGroupsAsAssoc($row['id']);
                $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($row);

                if ($this->isUserInRehearsalGroup($userType, $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    $row['date'] = date('Y-m-d', strtotime($row['start']));
                    $row['start_time'] = date('H:i', strtotime($row['start']));
                    $row['end_time'] = date('H:i', strtotime($row['end']));
                    $row['date_formatted'] = \App\Core\Utilities::formatDate($row['date']);
                    $row['start_formatted'] = $row['start_time'];
                    $row['end_formatted'] = $row['end_time'];
                    $row['groups'] = $this->getGroups($row['id']);
                    $row['schedule_items'] = $this->getScheduleItems($row['id']);
                    $row['infos'] = $this->getInfos($row['id']);
                    $rehearsals[] = $row;
                }
            }
        }

        return $rehearsals;
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
}
