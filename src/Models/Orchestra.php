<?php
namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;

/**
 * Orchestra Model
 * Handles orchestra-related database operations
 */
class Orchestra extends Model
{
    /**
     * @var string
     */
    protected $table = 'orchestras';
    
    /**
     * Find orchestra by token
     * 
     * @param string $token
     * @return array|null
     */
    public function findByToken(string $token): ?array
    {
        $token = $this->db->escape($token);
        
        $sql = "SELECT * FROM {$this->table} WHERE token = '{$token}'";
        $result = $this->db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get conductor for an orchestra
     * 
     * @param int $orchestraId
     * @return array|null
     */
    public function getConductor(int $orchestraId): ?array
    {
        $orchestra = $this->findById($orchestraId);
        
        if (!$orchestra || !$orchestra['conductor_id']) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->findById($orchestra['conductor_id']);
    }
    
    /**
     * Create a new orchestra
     * 
     * @param array $data Orchestra data
     * @return int|bool Orchestra ID or false on failure
     */
    public function createOrchestra(array $data)
    {
        // Silence any directory creation errors
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        
        $logPrefix = "[Orchestra] [createOrchestra] ";
        
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log($logPrefix . "DEBUG: Starting orchestra creation with data: " . json_encode($data));
        }
        
        try {
            // Check database connection
            if (!$this->db || !$this->db->getConnection()) {
                throw new \Exception('Datenbankverbindung fehlgeschlagen: ' . $this->db->getLastError());
            }
            
            // Validate required data
            if (empty($data['name']) || empty($data['token']) || empty($data['leader_pw'])) {
                throw new \Exception('Erforderliche Orchesterdaten fehlen');
            }
            
            // Start transaction
            if (!$this->db->getConnection()->begin_transaction()) {
                throw new \Exception('Transaktion konnte nicht gestartet werden: ' . $this->db->getLastError());
            }
            
            // Check for duplicate token
            $existingOrchestra = $this->findByToken($data['token']);
            if ($existingOrchestra) {
                // We're in a transaction, but haven't written anything yet, so straightforward throw
                throw new \Exception("Token already exists");
            }
            
            // Insert orchestra
            error_log($logPrefix . "Attempting to insert orchestra with name: {$data['name']}, token: {$data['token']}");
            
            $orchestraId = $this->insert([
                'name' => $data['name'],
                'token' => $data['token'],
                'leader_pw' => $data['leader_pw']
            ]);
            
            if (!$orchestraId) {
                $error = $this->db->getLastError();
                throw new \Exception("Failed to create orchestra: {$error}");
            }
            
            // Commit transaction
            if (!$this->db->getConnection()->commit()) {
                throw new \Exception("Failed to commit transaction: " . $this->db->getLastError());
            }
            
            error_log($logPrefix . "Successfully created orchestra with ID: {$orchestraId}");
            
            return $orchestraId;
            
        } catch (\Exception $e) {
            // Rollback only if transaction was active (checking logic is complex, but safe to try rollback if connection exists)
            if ($this->db && $this->db->getConnection()) {
                $this->db->getConnection()->rollback();
            }
            
            error_log($logPrefix . "Orchestra creation failed: " . $e->getMessage());
            
            // Return false so controller can handle it (controller throws generic error)
            return false;
        }
    }
    
    /**
     * Update orchestra settings
     * 
     * @param int $id Orchestra ID
     * @param array $data Update data
     * @return bool Success or failure
     */
    public function updateSettings(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
    
    /**
     * Set conductor for an orchestra
     * 
     * @param int $orchestraId Orchestra ID
     * @param int $conductorId User ID of the conductor
     * @return bool Success or failure
     */
    public function setConductor(int $orchestraId, int $conductorId): bool
    {
        return $this->update($orchestraId, ['conductor_id' => $conductorId]);
    }
    
    /**
     * Delete an orchestra and all associated data
     * 
     * @param int $id Orchestra ID
     * @return bool Success or failure
     */
    public function delete(int $id): bool
    {
        // Note: With proper foreign key constraints and CASCADE option,
        // this will automatically delete all related records
        return parent::delete($id);
    }
    
    /**
     * Validate leader password
     * 
     * @param int $orchestraId Orchestra ID
     * @param string $password Password to check
     * @return bool Valid or not
     */
    public function validateLeaderPw(int $orchestraId, string $password): bool
    {
        $orchestra = $this->findById($orchestraId);
        
        if (!$orchestra) {
            return false;
        }
        
        return $password === $orchestra['leader_pw'];
    }
} 