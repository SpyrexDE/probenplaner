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
        
        $sql .= " ORDER BY date, time";
        
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
        $sql = "SELECT group_name FROM rehearsal_groups WHERE rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row['group_name'];
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
                $sql = "INSERT INTO rehearsal_groups (rehearsal_id, group_name) VALUES (?, ?)";
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
        
        $sql .= "ORDER BY date, time";
        
        $result = $this->db->query($sql);
        
        $rehearsals = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $groups = json_decode($row['groups_data'] ?? '{}', true);
                if ($this->isUserInRehearsalGroup($userType, $isSmallGroup, $groups)) {
                    $row['date_formatted'] = Helpers::formatDate($row['date']);
                    $row['groups'] = $this->getGroups($row['id']);
                    $rehearsals[] = $row;
                }
            }
        }
        
        return $rehearsals;
    }
    
    /**
     * Check if user type is in the specified groups
     * 
     * @param string $userType User type/instrument
     * @param bool $isSmallGroup Whether the user is in small group
     * @param array $groups Groups to check
     * @return bool
     */
    public function isUserInRehearsalGroup($userType, $isSmallGroup, $groups)
    {
        // Special types that apply to everyone
        if (isset($groups['Tutti']) || isset($groups['Konzert']) || isset($groups['Konzertreise']) || isset($groups['Generalprobe'])) {
            return true;
        }
        
        // Check for exact match - checking if the group has a * suffix for small groups
        $smallGroupGroups = array_filter(array_keys($groups), function($group) {
            return strpos($group, '*') !== false;
        });
        
        $regularGroups = array_filter(array_keys($groups), function($group) {
            return strpos($group, '*') === false && 
                   $group !== 'Tutti' && 
                   $group !== 'Konzert' && 
                   $group !== 'Konzertreise' && 
                   $group !== 'Generalprobe';
        });
        
        // If it's a small group rehearsal, only users with is_small_group should attend
        if (!empty($smallGroupGroups) && !$isSmallGroup) {
            return false;
        }
        
        // Check if user type matches a group
        foreach ($regularGroups as $group) {
            if ($group === $userType) {
                return true;
            }
        }
        
        // Check for small group match
        foreach ($smallGroupGroups as $group) {
            $baseGroup = str_replace('*', '', $group);
            if ($baseGroup === $userType && $isSmallGroup) {
                return true;
            }
        }
        
        // Check if in Streicher group
        if ($userType === 'Violine_1' || $userType === 'Violine_2' || $userType === 'Bratsche' || $userType === 'Cello' || $userType === 'Kontrabass') {
            if (isset($groups['Streicher'])) {
                return true;
            }
            
            if (isset($groups['Streicher*']) && $isSmallGroup) {
                return true;
            }
        }
        
        // Check if in Blechbläser group
        if ($userType === 'Trompete' || $userType === 'Posaune' || $userType === 'Tuba' || $userType === 'Horn') {
            if (isset($groups['Blechbläser']) || isset($groups['Bläser'])) {
                return true;
            }
            
            if ((isset($groups['Blechbläser*']) || isset($groups['Bläser*'])) && $isSmallGroup) {
                return true;
            }
        }
        
        // Check if in Holzbläser group
        if ($userType === 'Flöte' || $userType === 'Oboe' || $userType === 'Klarinette' || $userType === 'Fagott') {
            if (isset($groups['Holzbläser']) || isset($groups['Bläser'])) {
                return true;
            }
            
            if ((isset($groups['Holzbläser*']) || isset($groups['Bläser*'])) && $isSmallGroup) {
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
    public function create($data, $groups)
    {
        // Start transaction
        $this->db->getConnection()->begin_transaction();
        
        try {
            // Validate JSON in groups_data
            if (isset($data['groups_data'])) {
                $decoded = json_decode($data['groups_data']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Invalid JSON in groups_data: " . json_last_error_msg());
                    throw new \Exception("Invalid JSON data: " . json_last_error_msg());
                }
            }
            
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
                throw new \Exception($error ? $error : "Failed to create rehearsal record");
            }
            
            // Add groups
            error_log("Creating rehearsal groups for rehearsal ID: " . $rehearsalId);
            foreach ($groups as $group) {
                $sql = "INSERT INTO rehearsal_groups (rehearsal_id, group_name) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('is', $rehearsalId, $group);
                $result = $stmt->execute();
                
                if (!$result) {
                    $error = $stmt->error;
                    error_log("Failed to insert rehearsal group [$group]: " . $error);
                    throw new \Exception($error);
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
            return ['error' => true, 'message' => $e->getMessage()];
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
                $sql = "INSERT INTO rehearsal_groups (rehearsal_id, group_name) VALUES (?, ?)";
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
} 