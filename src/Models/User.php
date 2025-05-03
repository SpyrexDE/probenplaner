<?php
namespace App\Models;

use App\Core\Model;
use App\Models\Orchestra;
use App\Models\UserPromise;

/**
 * User Model
 * Handles user-related database operations
 */
class User extends Model
{
    /**
     * @var string
     */
    protected $table = 'users';
    
    /**
     * Find user by username within an orchestra
     * 
     * @param string $username
     * @param int $orchestraId
     * @return array|null
     */
    public function findByUsername($username, $orchestraId = null)
    {
        $username = $this->db->escape($username);
        
        $sql = "SELECT * FROM {$this->table} WHERE username = '{$username}'";
        
        if ($orchestraId !== null) {
            $orchestraId = (int)$orchestraId;
            $sql .= " AND orchestra_id = {$orchestraId}";
        }
        
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Find users by type (instrument/section) within an orchestra
     * 
     * @param string $type
     * @param int $orchestraId
     * @return array
     */
    public function findByType($type, $orchestraId)
    {
        $type = $this->db->escape($type);
        $orchestraId = (int)$orchestraId;
        
        $sql = "SELECT * FROM {$this->table} WHERE type = '{$type}' AND orchestra_id = {$orchestraId}";
        $result = $this->db->query($sql);
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * Get all members of an orchestra
     * 
     * @param int $orchestraId
     * @return array
     */
    public function getOrchestraMembers($orchestraId)
    {
        $orchestraId = (int)$orchestraId;
        
        $sql = "SELECT * FROM {$this->table} WHERE orchestra_id = {$orchestraId} ORDER BY type, username";
        $result = $this->db->query($sql);
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * Authenticate user
     * 
     * @param string $username
     * @param string $password
     * @param int|null $orchestraId
     * @return array|null
     */
    public function authenticate($username, $password, $orchestraId = null)
    {
        $user = $this->findByUsername($username, $orchestraId);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    /**
     * Register a new user
     * 
     * @param string $username
     * @param string $password
     * @param string $type
     * @param int $orchestraId
     * @param string $role
     * @return int|bool
     */
    public function register($username, $password, $type, $orchestraId, $role = 'member')
    {
        // Check if username exists in the same orchestra
        if ($this->findByUsername($username, $orchestraId)) {
            error_log("Registration failed: Username already exists in this orchestra");
            return false;
        }
        
        // Validate orchestraId exists
        $orchestraModel = new Orchestra();
        if (!$orchestraModel->findById($orchestraId)) {
            error_log("Registration failed: Orchestra ID $orchestraId does not exist");
            return false;
        }
        
        // Explicitly convert types to ensure proper database insertion
        $orchestraId = (int)$orchestraId; // Make sure it's an integer
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $userData = [
            'username' => $username,
            'password' => $hashedPassword,
            'type' => $type,
            'orchestra_id' => $orchestraId,
            'role' => $role,
            'promises' => '' // Initialize with empty promises
        ];
        
        error_log("Registering user: " . json_encode($userData));
        
        // Insert and return the result
        return $this->insert($userData);
    }
    
    /**
     * Update user profile
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProfile($id, $data)
    {
        // If updating password, hash it
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Update user promise
     * 
     * @param int $userId
     * @param int $rehearsalId
     * @param bool $attending
     * @param string $note
     * @return bool
     */
    public function updatePromise($userId, $rehearsalId, $attending, $note = '')
    {
        $promiseModel = new UserPromise();
        
        // Check if promise exists
        $existingPromise = $promiseModel->findByUserAndRehearsal($userId, $rehearsalId);
        
        $result = false;
        if ($existingPromise) {
            // Update existing promise
            $result = $promiseModel->update($existingPromise['id'], [
                'attending' => $attending ? 1 : 0,
                'note' => $note
            ]);
        } else {
            // Insert new promise
            $result = $promiseModel->insert([
                'user_id' => $userId,
                'rehearsal_id' => $rehearsalId,
                'attending' => $attending ? 1 : 0,
                'note' => $note
            ]);
        }
        
        // If successful, refresh the promises string in the user table
        if ($result) {
            $this->refreshPromises($userId);
        }
        
        return $result;
    }
    
    /**
     * Refresh the promises string in the user table
     * 
     * @param int $userId
     * @return bool
     */
    public function refreshPromises($userId)
    {
        // Get all promises for the user
        $promises = $this->getPromises($userId);
        $promisesStr = '';
        
        foreach ($promises as $promise) {
            $prefix = $promise['attending'] ? '' : '!';
            $rehearsalId = $promise['rehearsal_id'];
            
            // Add note if exists
            $noteStr = '';
            if (!empty($promise['note'])) {
                $noteStr = '(' . $promise['note'] . ')';
            }
            
            // Add to promises string
            if (!empty($promisesStr)) {
                $promisesStr .= '|';
            }
            
            $promisesStr .= $prefix . $rehearsalId . $noteStr;
        }
        
        // Update the user record
        $result = $this->update($userId, ['promises' => $promisesStr]);
        
        // Small delay to ensure database transaction completes
        usleep(100000); // 100ms delay
        
        // Log for debugging
        error_log("Refreshed promises for user $userId: $promisesStr (result: " . ($result ? 'success' : 'failure') . ")");
        
        // Also update session if this is the current user
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
            $_SESSION['promises'] = $promisesStr;
            error_log("Updated session promises for current user: $promisesStr");
        }
        
        return $result;
    }
    
    /**
     * Ensure user has an initialized promises field
     *
     * @param array $user User data
     * @return array Updated user data
     */
    public function ensurePromisesField($user)
    {
        if (!isset($user['promises'])) {
            $user['promises'] = '';
            // Also try to update the database record
            if (isset($user['id'])) {
                $this->update($user['id'], ['promises' => '']);
            }
        }
        return $user;
    }
    
    /**
     * Get user promises
     * 
     * @param int $userId
     * @return array
     */
    public function getPromises($userId)
    {
        $sql = "SELECT up.*, r.date, r.time, r.location, r.description
                FROM user_promises up
                JOIN rehearsals r ON up.rehearsal_id = r.id
                WHERE up.user_id = ?
                ORDER BY r.date, r.time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        $promises = [];
        while ($row = $result->fetch_assoc()) {
            $promises[] = $row;
        }
        
        return $promises;
    }
    
    /**
     * Set user as leader
     * 
     * @param int $userId
     * @return bool
     */
    public function setAsLeader($userId)
    {
        return $this->update($userId, ['role' => 'leader']);
    }
    
    /**
     * Check if user is in specific role
     * 
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function isInRole($userId, $role)
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return false;
        }
        
        return $user['role'] === $role;
    }
    
    /**
     * Delete user account
     * 
     * @param int $userId
     * @return bool
     */
    public function delete($userId)
    {
        return parent::delete($userId);
    }
} 