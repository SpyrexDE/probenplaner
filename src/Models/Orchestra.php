<?php
namespace App\Models;

use App\Core\Model;

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
    public function findByToken($token)
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
    public function getConductor($orchestraId)
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
    public function createOrchestra($data)
    {
        // Silence any directory creation errors
        ini_set('display_errors', 0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        
        $logPrefix = "[Orchestra] [createOrchestra] ";
        $debug = true; // Enable debug mode
        
        // Create a debugging log file in a writeable location
        $debugLogFile = sys_get_temp_dir() . '/probenplaner-debug-' . date('Ymd-His') . '.log';
        $debugLog = function($message) use ($debugLogFile) {
            @file_put_contents($debugLogFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
        };
        
        $debugLog("Starting orchestra creation process");
        $debugLog("Data: " . json_encode($data));
        
        if ($debug) {
            @error_log($logPrefix . "DEBUG: Starting orchestra creation with data: " . json_encode($data));
        }
        
        // Check database connection
        if (!$this->db || !$this->db->getConnection()) {
            $debugLog("Database connection failed: " . ($this->db ? $this->db->getLastError() : "No DB instance"));
            @error_log($logPrefix . "Database connection failed: " . $this->db->getLastError());
            return false;
        }
        $debugLog("Database connection verified");
        
        // Validate required data
        if (empty($data['name']) || empty($data['token']) || empty($data['leader_pw'])) {
            $debugLog("Missing required orchestra data fields");
            @error_log($logPrefix . "Missing required orchestra data fields");
            return false;
        }
        $debugLog("Orchestra data validated");
        
        // Start transaction
        $debugLog("Attempting to start transaction");
        if (!$this->db->getConnection()->begin_transaction()) {
            $debugLog("Failed to start transaction: " . $this->db->getLastError());
            @error_log($logPrefix . "Failed to start transaction: " . $this->db->getLastError());
            return false;
        }
        $debugLog("Transaction started successfully");
        
        try {
            // Check for duplicate token
            $debugLog("Checking for duplicate token: " . $data['token']);
            $existingOrchestra = $this->findByToken($data['token']);
            if ($existingOrchestra) {
                $debugLog("Orchestra token already exists");
                @error_log($logPrefix . "Orchestra token '{$data['token']}' already exists");
                throw new \Exception("Token already exists");
            }
            $debugLog("Token is unique");
            
            // Insert orchestra
            $debugLog("Preparing to insert orchestra");
            @error_log($logPrefix . "Attempting to insert orchestra with name: {$data['name']}, token: {$data['token']}");
            
            $debugLog("Orchestra data to insert: " . json_encode($data));
            $orchestraId = $this->insert([
                'name' => $data['name'],
                'token' => $data['token'],
                'leader_pw' => $data['leader_pw']
            ]);
            
            if (!$orchestraId) {
                $error = $this->db->getLastError();
                $debugLog("Failed to create orchestra record: " . $error);
                @error_log($logPrefix . "Failed to create orchestra record: {$error}");
                throw new \Exception("Failed to create orchestra: {$error}");
            }
            $debugLog("Orchestra inserted successfully with ID: " . $orchestraId);
            
            // Commit transaction
            $debugLog("Attempting to commit transaction");
            if (!$this->db->getConnection()->commit()) {
                $error = $this->db->getLastError();
                $debugLog("Failed to commit transaction: " . $error);
                @error_log($logPrefix . "Failed to commit transaction: " . $this->db->getLastError());
                throw new \Exception("Failed to commit transaction");
            }
            $debugLog("Transaction committed successfully");
            
            @error_log($logPrefix . "Successfully created orchestra with ID: {$orchestraId}");
            $debugLog("Orchestra creation completed successfully");
            
            return $orchestraId;
        } catch (\Exception $e) {
            // Rollback on error
            $debugLog("Exception occurred: " . $e->getMessage());
            $debugLog("Attempting to rollback transaction");
            $this->db->getConnection()->rollback();
            $debugLog("Transaction rolled back");
            @error_log($logPrefix . "Orchestra creation failed: " . $e->getMessage());
            
            // Save debug log path to session for admin to see
            if (isset($_SESSION)) {
                $_SESSION['debug_log_file'] = $debugLogFile;
            }
            
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
    public function updateSettings($id, $data)
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
    public function setConductor($orchestraId, $conductorId)
    {
        return $this->update($orchestraId, ['conductor_id' => $conductorId]);
    }
    
    /**
     * Delete an orchestra and all associated data
     * 
     * @param int $id Orchestra ID
     * @return bool Success or failure
     */
    public function delete($id)
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
    public function validateLeaderPw($orchestraId, $password)
    {
        $orchestra = $this->findById($orchestraId);
        
        if (!$orchestra) {
            return false;
        }
        
        return $password === $orchestra['leader_pw'];
    }
} 