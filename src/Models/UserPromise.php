<?php
namespace App\Models;

use App\Core\Model;

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
    public function findByUserAndRehearsal($userId, $rehearsalId)
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
     * Get all promises for a specific rehearsal
     * 
     * @param int $rehearsalId Rehearsal ID
     * @return array Promises for the rehearsal
     */
    public function getByRehearsal($rehearsalId)
    {
        $sql = "SELECT up.*, u.username, u.type
                FROM {$this->table} up
                JOIN users u ON up.user_id = u.id
                WHERE up.rehearsal_id = ?
                ORDER BY u.type, u.username";
        
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
    public function getPromiseStats($rehearsalId, $orchestraId)
    {
        $stats = [
            'total' => 0,
            'attending' => 0,
            'not_attending' => 0,
            'no_response' => 0,
            'details' => []
        ];
        
        // Get rehearsal
        $rehearsalModel = new Rehearsal();
        $rehearsal = $rehearsalModel->findById($rehearsalId);
        
        if (!$rehearsal) {
            return $stats;
        }
        
        // Get relevant groups for this rehearsal
        $sql = "SELECT group_name FROM rehearsal_groups WHERE rehearsal_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[$row['group_name']] = true;
        }
        
        // Get all users from this orchestra
        $userModel = new User();
        $users = $userModel->getOrchestraMembers($orchestraId);
        
        // Check which users should attend this rehearsal
        foreach ($users as $user) {
            // Skip conductors for attendance tracking
            if ($user['role'] === 'conductor') {
                continue;
            }
            
            // Check if user is relevant for this rehearsal
            if ($this->isUserInRehearsalGroup($user['type'], $groups)) {
                $stats['total']++;
                
                // Check user's promise
                $promise = $this->findByUserAndRehearsal($user['id'], $rehearsalId);
                
                $userStat = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'type' => $user['type'],
                    'status' => 'no_response',
                    'note' => ''
                ];
                
                if ($promise) {
                    if ($promise['attending']) {
                        $userStat['status'] = 'attending';
                        $stats['attending']++;
                    } else {
                        $userStat['status'] = 'not_attending';
                        $stats['not_attending']++;
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
    
    /**
     * Check if user type is in the specified groups
     * 
     * @param string $userType User type/instrument
     * @param array $groups Groups to check
     * @return bool
     */
    private function isUserInRehearsalGroup($userType, $groups)
    {
        // Special types that apply to everyone
        if (isset($groups['Tutti']) || isset($groups['Konzert']) || isset($groups['Konzertreise']) || isset($groups['Generalprobe'])) {
            return true;
        }
        
        // Check for exact match
        if (isset($groups[$userType])) {
            return true;
        }
        
        // Check if in Streicher group
        if ($userType === 'Violine_1' || $userType === 'Violine_2' || $userType === 'Bratsche' || $userType === 'Cello' || $userType === 'Kontrabass') {
            if (isset($groups['Streicher'])) {
                return true;
            }
        }
        
        // Check if in Blechbläser group
        if ($userType === 'Trompete' || $userType === 'Posaune' || $userType === 'Tuba' || $userType === 'Horn') {
            if (isset($groups['Blechbläser']) || isset($groups['Bläser'])) {
                return true;
            }
        }
        
        // Check if in Holzbläser group
        if ($userType === 'Flöte' || $userType === 'Oboe' || $userType === 'Klarinette' || $userType === 'Fagott') {
            if (isset($groups['Holzbläser']) || isset($groups['Bläser'])) {
                return true;
            }
        }
        
        return false;
    }
} 