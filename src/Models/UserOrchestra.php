<?php
namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;

/**
 * UserOrchestra Model
 * Handles user-orchestra relationship operations (junction table)
 */
class UserOrchestra extends Model
{
    /**
     * @var string
     */
    protected $table = 'user_orchestras';
    
    /**
     * Get all orchestras for a user
     * 
     * @param int $userId
     * @param bool $activeOnly Only return active relationships
     * @return array
     */
    public function getUserOrchestras(int $userId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";
        
        $sql = "SELECT uo.*, o.name as orchestra_name, o.token as orchestra_token
                FROM {$this->table} uo
                JOIN orchestras o ON uo.orchestra_id = o.id
                WHERE uo.user_id = ? {$activeClause}
                ORDER BY uo.joined_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orchestras = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orchestras[] = $row;
            }
        }
        
        $stmt->close();
        return $orchestras;
    }
    
    /**
     * Get all users for an orchestra
     * 
     * @param int $orchestraId
     * @param bool $activeOnly Only return active relationships
     * @return array
     */
    public function getOrchestraUsers(int $orchestraId, bool $activeOnly = true): array
    {
        $activeClause = $activeOnly ? "AND uo.is_active = 1" : "";
        
        $sql = "SELECT uo.*, u.username, u.created_at as user_created_at
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                WHERE uo.orchestra_id = ? {$activeClause}
                ORDER BY uo.type, u.username";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        $stmt->close();
        return $users;
    }
    
    /**
     * Get specific user-orchestra relationship
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param bool $activeOnly Only return if active
     * @return array|null
     */
    public function getUserOrchestraRelation(int $userId, int $orchestraId, bool $activeOnly = true): ?array
    {
        $activeClause = $activeOnly ? "AND is_active = 1" : "";
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? AND orchestra_id = ? {$activeClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $relation = null;
        if ($result && $result instanceof \mysqli_result) {
            $relation = $result->fetch_assoc();
        }
        
        $stmt->close();
        return $relation;
    }
    
    /**
     * Join user to orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param string $type Instrument/section
     * @param string $role User role (member, leader, conductor)
     * @return int|array Relationship ID on success, error array on failure
     */
    public function joinOrchestra(int $userId, int $orchestraId, string $type, string $role = 'member')
    {
        try {
            // Check if relationship already exists (including inactive ones)
            $existing = $this->getUserOrchestraRelation($userId, $orchestraId, false);
            
            if ($existing) {
                if ($existing['is_active']) {
                    return ['error' => true, 'message' => 'Sie sind bereits Mitglied dieses Orchesters.'];
                } else {
                    // Reactivate existing relationship
                    $result = $this->update($existing['id'], [
                        'type' => $type,
                        'role' => $role,
                        'is_active' => true,
                        'joined_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    return $result ? $existing['id'] : ['error' => true, 'message' => 'Fehler beim Reaktivieren der Mitgliedschaft.'];
                }
            }
            
            // Create new relationship
            $data = [
                'user_id' => $userId,
                'orchestra_id' => $orchestraId,
                'type' => $type,
                'role' => $role,
                'is_active' => true,
                'joined_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->insert($data);
            
            if ($result === false) {
                $error = $this->db->getLastError();
                error_log("Failed to join orchestra - Database error: " . $error);
                return ErrorHandler::handleDatabaseError(new \Exception($error), 'Orchestra join');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return ErrorHandler::handleDatabaseError($e, 'Orchestra join');
        }
    }
    
    /**
     * Leave orchestra (soft delete)
     * 
     * @param int $userId
     * @param int $orchestraId
     * @return bool Success or failure
     */
    public function leaveOrchestra(int $userId, int $orchestraId): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        
        if (!$relation) {
            return false; // User is not a member of this orchestra
        }
        
        return $this->update($relation['id'], [
            'is_active' => false,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update user role in orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param string $role New role
     * @return bool Success or failure
     */
    public function updateUserRole(int $userId, int $orchestraId, string $role): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        
        if (!$relation) {
            return false;
        }
        
        return $this->update($relation['id'], [
            'role' => $role,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update user type (instrument/section) in orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param string $type New type
     * @return bool Success or failure
     */
    public function updateUserType(int $userId, int $orchestraId, string $type): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        
        if (!$relation) {
            return false;
        }
        
        return $this->update($relation['id'], [
            'type' => $type,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Update user small group status in orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param bool $isSmallGroup Whether user is in small group
     * @return bool Success or failure
     */
    public function updateUserSmallGroupStatus(int $userId, int $orchestraId, bool $isSmallGroup): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        
        if (!$relation) {
            return false;
        }
        
        return $this->update($relation['id'], [
            'is_small_group' => $isSmallGroup ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Check if user is in small group for orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @return bool
     */
    public function isUserInSmallGroup(int $userId, int $orchestraId): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        
        return $relation && (int)$relation['is_small_group'] === 1;
    }
    
    /**
     * Check if user has specific role in orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param string $role Role to check
     * @return bool
     */
    public function hasRole(int $userId, int $orchestraId, string $role): bool
    {
        $relation = $this->getUserOrchestraRelation($userId, $orchestraId, true);
        
        return $relation && $relation['role'] === $role;
    }
    
    /**
     * Get users by type within an orchestra
     * 
     * @param string $type
     * @param int $orchestraId
     * @return array
     */
    public function getUsersByType(string $type, int $orchestraId): array
    {
        $sql = "SELECT uo.*, u.username, u.created_at as user_created_at
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                WHERE uo.type = ? AND uo.orchestra_id = ? AND uo.is_active = 1
                ORDER BY u.username";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $type, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        $stmt->close();
        return $users;
    }
    
    /**
     * Get users by role within an orchestra
     * 
     * @param string $role
     * @param int $orchestraId
     * @return array
     */
    public function getUsersByRole(string $role, int $orchestraId): array
    {
        $sql = "SELECT uo.*, u.username, u.created_at as user_created_at
                FROM {$this->table} uo
                JOIN users u ON uo.user_id = u.id
                WHERE uo.role = ? AND uo.orchestra_id = ? AND uo.is_active = 1
                ORDER BY u.username";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $role, $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        $stmt->close();
        return $users;
    }
}
