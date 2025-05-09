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
     * @return array|bool Array with error info or true on success
     */
    public function register($username, $password, $type, $orchestraId, $role = 'member')
    {
        // Check if username exists in the same orchestra
        if ($this->findByUsername($username, $orchestraId)) {
            error_log("Registration failed: Username already exists in this orchestra");
            return ['error' => true, 'message' => 'Der Benutzername ist bereits vergeben.', 'details' => 'Ein Benutzer mit diesem Namen existiert bereits in diesem Orchester.'];
        }
        
        // Validate orchestraId exists
        $orchestraModel = new Orchestra();
        if (!$orchestraModel->findById($orchestraId)) {
            error_log("Registration failed: Orchestra ID $orchestraId does not exist");
            return ['error' => true, 'message' => 'Das Orchester wurde nicht gefunden.', 'details' => 'Das angegebene Orchester existiert nicht mehr. Bitte kontaktieren Sie Ihren Dirigenten.'];
        }
        
        // Explicitly convert types to ensure proper database insertion
        $orchestraId = (int)$orchestraId;
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $userData = [
            'username' => $username,
            'password' => $hashedPassword,
            'type' => $type,
            'orchestra_id' => $orchestraId,
            'role' => $role
        ];
        
        error_log("Registering user: " . json_encode($userData));
        
        // Insert and return the result
        $result = $this->insert($userData);
        
        if ($result === false) {
            $error = $this->db->getLastError();
            error_log("Registration failed - Database error: " . $error);
            
            // Check for specific error types
            if (strpos($error, '1062') !== false) { // Duplicate entry
                return ['error' => true, 'message' => 'Der Benutzername ist bereits vergeben.', 'details' => 'Ein Benutzer mit diesem Namen existiert bereits.'];
            } elseif (strpos($error, '1452') !== false) { // Foreign key constraint
                return ['error' => true, 'message' => 'Das Orchester wurde nicht gefunden.', 'details' => 'Das angegebene Orchester existiert nicht mehr. Bitte kontaktieren Sie Ihren Dirigenten.'];
            } else {
                return ['error' => true, 'message' => 'Bei der Registrierung ist ein Fehler aufgetreten.', 'details' => 'Technischer Fehler: ' . $error];
            }
        }
        
        return $result;
    }
    
    /**
     * Update user profile
     * 
     * @param int $id
     * @param array $data
     * @return bool|array True on success, error details array on failure
     */
    public function updateProfile($id, $data)
    {
        try {
            // If updating password, hash it
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Debug log
            error_log("Updating user profile. ID: $id, Data: " . json_encode($data));
            
            $result = $this->update($id, $data);
            
            if ($result === false) {
                $error = $this->db->getLastError();
                error_log("User profile update failed - Database error: " . $error);
                
                // Check for specific error types
                if (strpos($error, '1062') !== false) { // Duplicate entry
                    return ['error' => true, 'message' => 'Der Benutzername ist bereits vergeben.', 'details' => 'Ein Benutzer mit diesem Namen existiert bereits.'];
                } elseif (strpos($error, '1054') !== false) { // Unknown column
                    return ['error' => true, 'message' => 'Datenbank-Schema-Fehler', 'details' => 'Eine Spalte in der Datenbank fehlt. Bitte führen Sie alle Migrationen aus.'];
                } else {
                    return ['error' => true, 'message' => 'Bei der Aktualisierung ist ein Fehler aufgetreten.', 'details' => 'Technischer Fehler: ' . $error];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Exception in updateProfile: " . $e->getMessage());
            return ['error' => true, 'message' => 'Bei der Aktualisierung ist ein Fehler aufgetreten.', 'details' => 'Exception: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update user promise
     * 
     * @param int $userId
     * @param int $rehearsalId
     * @param bool $attending
     * @param string $note
     * @return array|bool Array with error info or true on success
     */
    public function updatePromise($userId, $rehearsalId, $attending, $note = '')
    {
        try {
            $promiseModel = new UserPromise();
            
            // Check if promise exists
            $existingPromise = $promiseModel->findByUserAndRehearsal($userId, $rehearsalId);
            
            // Check if rehearsal exists
            $rehearsalModel = new \App\Models\Rehearsal();
            $rehearsal = $rehearsalModel->findById($rehearsalId);
            if (!$rehearsal) {
                error_log("Failed to update promise: Rehearsal not found (ID: $rehearsalId)");
                return ['error' => true, 'message' => 'Die Probe wurde nicht gefunden.', 'details' => 'Die angegebene Probe existiert nicht mehr.'];
            }
            
            if ($existingPromise) {
                // Update existing promise
                $result = $promiseModel->update($existingPromise['id'], [
                    'attending' => $attending ? 1 : 0,
                    'note' => $note,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Insert new promise
                $result = $promiseModel->insert([
                    'user_id' => $userId,
                    'rehearsal_id' => $rehearsalId,
                    'attending' => $attending ? 1 : 0,
                    'note' => $note,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            if ($result === false) {
                $error = $this->db->getLastError();
                error_log("Failed to update promise - Database error: " . $error);
                
                // Check for specific error types
                if (strpos($error, '1062') !== false) { // Duplicate entry
                    return ['error' => true, 'message' => 'Doppelter Eintrag.', 'details' => 'Es existiert bereits eine Zusage für diese Probe.'];
                } elseif (strpos($error, '1452') !== false) { // Foreign key constraint
                    return ['error' => true, 'message' => 'Ungültige Referenz.', 'details' => 'Die Probe oder der Benutzer existiert nicht mehr.'];
                } else {
                    return ['error' => true, 'message' => 'Bei der Aktualisierung ist ein Fehler aufgetreten.', 'details' => 'Technischer Fehler: ' . $error];
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Exception in updatePromise: " . $e->getMessage());
            return ['error' => true, 'message' => 'Bei der Aktualisierung ist ein Fehler aufgetreten.', 'details' => 'Technischer Fehler: ' . $e->getMessage()];
        }
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