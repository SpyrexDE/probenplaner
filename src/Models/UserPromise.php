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
     * Get all promises for a specific rehearsal
     * 
     * @param int $rehearsalId Rehearsal ID
     * @return array Promises for the rehearsal
     */
    public function getByRehearsal(int $rehearsalId): array
    {
        $sql = "SELECT up.*, u.username, uo.type
                FROM {$this->table} up
                JOIN users u ON up.user_id = u.id
                JOIN user_orchestras uo ON u.id = uo.user_id
                JOIN rehearsals r ON up.rehearsal_id = r.id
                WHERE up.rehearsal_id = ? AND uo.orchestra_id = r.orchestra_id AND uo.is_active = 1
                ORDER BY uo.type, u.username";
        
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
        
        foreach ($users as $user) {
            // Skip conductors for attendance tracking
            if ($user['role'] === 'conductor') {
                continue;
            }
            
            // Determine relevance based on group membership
            $isSmallGroup = isset($user['is_small_group']) && $user['is_small_group'];
            $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal);
            if ($rehearsalModel->isUserInRehearsalGroup($user['type'], $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                $stats['total']++;
                
                $promise = $this->findByUserAndRehearsal($user['id'], $rehearsalId);
                
                $userStat = [
                    'id' => $user['id'],
                    'username' => $user['username'],
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