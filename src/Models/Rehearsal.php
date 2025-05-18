<?php
namespace App\Models;

use App\Core\Model;
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
    public function getUpcoming($orchestraId, $includeOld = false)
    {
        $orchestraId = (int)$orchestraId;
        
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId}";
        
        if (!$includeOld) {
            $sql .= " AND date >= CURDATE()";
        }
        
        $sql .= " ORDER BY date, start_time";
        
        $result = $this->db->query($sql);
        
        // If no rehearsals, return empty array
        if (!$result) {
            return [];
        }
        
        $rehearsals = [];
        while ($row = $result->fetch_assoc()) {
            // Format date to dd.mm.yyyy
            $row['date'] = Helpers::formatDate($row['date']);
            
            // Add related groups
            $row['groups'] = $this->getGroups($row['id']);
            $rehearsals[] = $row;
        }
        
        return $rehearsals;
    }
    
    /**
     * Get groups for a rehearsal
     * 
     * @param int $rehearsalId Rehearsal ID
     * @return array Group names
     */
    public function getGroups($rehearsalId)
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
    public function updateGroups($rehearsalId, $groups)
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
            return ['error' => true, 'message' => $e->getMessage()];
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
    public function getForUser($userType, $orchestraId, $includeOld = false, $isSmallGroup = false)
    {
        $orchestraId = (int)$orchestraId;
        $today = date('Y-m-d');
        
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} ";
        
        if (!$includeOld) {
            $sql .= "AND date >= '{$today}' ";
        }
        
        $sql .= "ORDER BY date, start_time";
        
        $result = $this->db->query($sql);
        
        $rehearsals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $groups = $this->getGroupsAsAssoc($row['id']);
                $rehearsalIsSmallGroup = isset($row['is_small_group']) && $row['is_small_group'] == 1;
                
                if ($this->isUserInRehearsalGroup($userType, $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    $row['date_formatted'] = Helpers::formatDate($row['date']);
                    $row['groups'] = $this->getGroups($row['id']);
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
    public function getGroupsAsAssoc($rehearsalId)
    {
        $sql = "SELECT name FROM rehearsal_groups WHERE rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[$row['name']] = 0; // Using 0 as dummy value as in previous groups_data
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
    public function isUserInRehearsalGroup($userType, $isSmallGroup, $groups, $rehearsalIsSmallGroup = false)
    {
        // Special types that apply to everyone
        if (isset($groups['Tutti']) || isset($groups['Konzert']) || isset($groups['Konzertreise']) || isset($groups['Generalprobe'])) {
            return true;
        }
        
        // If it's a small group rehearsal, only users with is_small_group should attend
        if ($rehearsalIsSmallGroup && !$isSmallGroup) {
            return false;
        }
        
        // Check if user type matches a group
        foreach (array_keys($groups) as $group) {
            if ($group === $userType) {
                return true;
            }
            
            // Check parent groups
            // Strings like "Streicher", "Bläser", "Blechbläser", "Holzbläser" contain multiple instrument groups
            switch ($group) {
                case 'Streicher':
                    if (in_array($userType, ['Violine_1', 'Violine_2', 'Bratsche', 'Cello', 'Kontrabass'])) {
                        return true;
                    }
                    break;
                case 'Bläser':
                    if (in_array($userType, ['Flöte', 'Oboe', 'Klarinette', 'Fagott', 'Trompete', 'Posaune', 'Tuba', 'Horn'])) {
                        return true;
                    }
                    break;
                case 'Blechbläser':
                    if (in_array($userType, ['Trompete', 'Posaune', 'Tuba', 'Horn'])) {
                        return true;
                    }
                    break;
                case 'Holzbläser':
                    if (in_array($userType, ['Flöte', 'Oboe', 'Klarinette', 'Fagott'])) {
                        return true;
                    }
                    break;
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
    public function create($data, $groups)
    {
        // Start transaction
        $this->db->getConnection()->begin_transaction();
        
        try {
            // Set explicit timestamp values in MySQL format - this is confirmed to work
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Debug output before insert
            error_log("Raw rehearsal data before insert: " . json_encode($data));
            
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
                
                throw new \Exception($error ? $error : "Failed to create rehearsal record", $errno);
            }
            
            // Add groups
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
                    
                    throw new \Exception($error, $errno);
                }
            }
            
            // Commit transaction
            $this->db->getConnection()->commit();
            error_log("Successfully created rehearsal with ID: " . $rehearsalId);
            
            return $rehearsalId;
        } catch (\Exception $e) {
            // Rollback on error
            $this->db->getConnection()->rollback();
            error_log("Exception during rehearsal creation: " . $e->getMessage());
            
            // Get detailed error information
            $details = $e->getMessage();
            if ($e->getCode() == 1054) { // Unknown column
                $details .= "\nBitte führen Sie die Migrationen aus, um die Datenbankstruktur zu aktualisieren.";
            } elseif ($e->getCode() == 1062) { // Duplicate entry
                $details .= "\nEin Eintrag mit diesen Daten existiert bereits.";
            } elseif ($e->getCode() == 1452) { // Foreign key constraint fails
                $details .= "\nUngültige Referenz auf einen anderen Datensatz.";
            }
            
            return [
                'error' => true, 
                'message' => $e->getMessage(),
                'details' => $details
            ];
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
    public function updateRehearsal($id, $data, $groups)
    {
        // Start transaction
        $this->db->getConnection()->begin_transaction();
        
        try {
            // Set explicit timestamp value in MySQL format - this is confirmed to work
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
            
            // Add new groups
            foreach ($groups as $group) {
                $sql = "INSERT INTO rehearsal_groups (rehearsal_id, name) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('is', $id, $group);
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
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete a rehearsal and all related records
     * 
     * @param int $id Rehearsal ID
     * @return bool Success or failure
     */
    public function delete($id)
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
    public function findById($id)
    {
        $id = (int)$id;
        $rehearsal = parent::findById($id);
        
        if ($rehearsal) {
            // Make sure date is in Y-m-d format for forms
            // Don't convert to dd.mm.yyyy here since the controller needs Y-m-d for the date input
            $rehearsal['groups'] = $this->getGroups($id);
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
     * @return array
     */
    public function getRelevantForUser($orchestraId, $userType, $userGroups = [], $includeOld = false)
    {
        $orchestraId = (int)$orchestraId;
        $today = date('Y-m-d');
        
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} ";
        
        if (!$includeOld) {
            $sql .= "AND date >= '{$today}' ";
        }
        
        $sql .= "ORDER BY date, start_time";
        
        $result = $this->db->query($sql);
        
        $rehearsals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $groups = $this->getGroupsAsAssoc($row['id']);
                $isSmallGroup = isset($userGroups['is_small_group']) && $userGroups['is_small_group'];
                $rehearsalIsSmallGroup = isset($row['is_small_group']) && $row['is_small_group'] == 1;
                
                if ($this->isUserInRehearsalGroup($userType, $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    // Format the date in a user-friendly format
                    $row['date_formatted'] = Helpers::formatDate($row['date']);
                    $row['groups'] = $this->getGroups($row['id']);
                    $rehearsals[] = $row;
                }
            }
        }
        
        return $rehearsals;
    }
} 