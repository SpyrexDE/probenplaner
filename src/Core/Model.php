<?php
namespace App\Core;

/**
 * Base Model Class
 * All models will extend this class
 */
abstract class Model
{
    /**
     * @var Database
     */
    protected $db;
    
    /**
     * @var string
     */
    protected $table;
    
    /**
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find all records
     * 
     * @param string $orderBy Order by clause
     * @return array
     */
    public function findAll($orderBy = '')
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $result = $this->db->query($sql);
        
        $rows = [];
        if ($result && $result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    /**
     * Find record by ID
     * 
     * @param int $id ID to find
     * @return array|null
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
            
            // Special case for users table - ensure promises field exists
            if ($this->table === 'users' && !isset($record['promises'])) {
                $record['promises'] = '';
                // Update the record in the database
                $this->update($id, ['promises' => '']);
            }
            
            return $record;
        }
        
        return null;
    }
    
    /**
     * Find records by field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array
     */
    public function findBy($field, $value)
    {
        $field = $this->db->escape($field);
        $value = $this->db->escape($value);
        
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = '{$value}'";
        $result = $this->db->query($sql);
        
        $rows = [];
        if ($result && $result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        
        return $rows;
    }
    
    /**
     * Insert a record
     * 
     * @param array $data Data to insert
     * @return int|bool Inserted ID or false on failure
     */
    public function insert($data)
    {
        // If empty data, return false
        if (empty($data)) {
            @error_log("Cannot insert: empty data array");
            return false;
        }
        
        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $values = array_values($data);
            
            // Build the SQL statement
            $fields_str = '`' . implode('`, `', $fields) . '`';
            $placeholders_str = implode(', ', $placeholders);
            
            $sql = "INSERT INTO {$this->table} ({$fields_str}) VALUES ({$placeholders_str})";
            
            // Debug output
            @error_log("DEBUG SQL: " . $sql);
            @error_log("DEBUG params: " . json_encode($values));
            
            // If no values to bind, perform a simple insert
            if (empty($values)) {
                $result = $this->db->query($sql);
                if ($result) {
                    return $this->db->getLastId();
                }
                @error_log("Query execution failed: " . $this->db->getLastError());
                return false;
            }
            
            // Prepare and execute with parameters
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                @error_log("Failed to prepare statement: " . $this->db->getConnection()->error);
                return false;
            }
            
            // Determine types for bind_param
            $types = '';
            foreach ($values as $value) {
                if (is_int($value) || (is_string($value) && ctype_digit($value) && strlen($value) < 10)) {
                    $types .= 'i';
                } elseif (is_float($value) || (is_string($value) && is_numeric($value) && strpos($value, '.') !== false)) {
                    $types .= 'd';
                } elseif (is_string($value)) {
                    $types .= 's';
                } else {
                    $types .= 's'; // Default to string
                }
            }
            
            // Debug output for types
            @error_log("DEBUG: Value count: " . count($values) . ", Types string: " . $types . ", Length: " . strlen($types));
            
            // Verify that types string length matches value count
            if (strlen($types) !== count($values)) {
                @error_log("ERROR: Type string length (" . strlen($types) . ") does not match parameter count (" . count($values) . ")");
                $types = str_repeat('s', count($values)); // Fallback to all strings
                @error_log("Falling back to all strings: " . $types);
            }
            
            // Bind parameters - safer approach avoiding references
            if (count($values) > 0) {
                // Create a new array for params starting with types string
                $params = [];
                $params[] = $types;  // Add types string as first parameter
                
                // Create references to all values
                foreach ($values as $key => $value) {
                    // Create a new variable reference for each value
                    $params[] = &$values[$key];
                }
                
                // Call bind_param with the array of references
                try {
                    call_user_func_array([$stmt, 'bind_param'], $params);
                } catch (\Exception $e) {
                    @error_log("bind_param error: " . $e->getMessage());
                    return false;
                }
            }
            
            // Execute and check result
            $result = $stmt->execute();
            
            if ($result) {
                $insertId = $this->db->getLastId();
                $stmt->close();
                return $insertId;
            } else {
                $error = $stmt->error;
                $errno = $stmt->errno;
                @error_log("Failed to execute statement: MySQL Error #{$errno}: {$error}");
                
                // Check for specific error cases
                if ($errno == 1062) { // Duplicate entry
                    @error_log("Duplicate entry error detected");
                } elseif ($errno == 1054) { // Unknown column
                    @error_log("Unknown column error detected");
                } elseif ($errno == 1452) { // Foreign key constraint fails
                    @error_log("Foreign key constraint error detected");
                }
                
                $stmt->close();
                return false;
            }
        } catch (\Exception $e) {
            @error_log("Exception in insert method: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a record
     * 
     * @param int $id ID of record to update
     * @param array $data Data to update
     * @return bool Success or failure
     */
    public function update($id, $data)
    {
        if (empty($data)) {
            error_log("Cannot update: empty data array");
            return false;
        }
        
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            
            // Build SET part of query
            $setParts = [];
            foreach ($fields as $field) {
                $setParts[] = "`{$field}` = ?";
            }
            
            $setStr = implode(', ', $setParts);
            
            // Create SQL with placeholders
            $sql = "UPDATE {$this->table} SET {$setStr} WHERE {$this->primaryKey} = ?";
            
            // Add ID to values array
            $values[] = $id;
            
            // Prepare statement
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Failed to prepare update statement: " . $this->db->getConnection()->error);
                return false;
            }
            
            // Determine types for bind_param
            $types = '';
            foreach ($values as $value) {
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } elseif (is_string($value)) {
                    $types .= 's';
                } else {
                    $types .= 's'; // Default to string
                }
            }
            
            // Bind parameters - safer approach avoiding references
            if (count($values) > 0) {
                // Create a new array for params starting with types string
                $params = [];
                $params[] = $types;  // Add types string as first parameter
                
                // Create references to all values
                foreach ($values as $key => $value) {
                    // Create a new variable reference for each value
                    $params[] = &$values[$key];
                }
                
                // Call bind_param with the array of references
                try {
                    call_user_func_array([$stmt, 'bind_param'], $params);
                } catch (\Exception $e) {
                    @error_log("bind_param error: " . $e->getMessage());
                    return false;
                }
            }
            
            // Execute and check result
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (\Exception $e) {
            error_log("Exception in update method: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a record
     * 
     * @param int $id ID of record to delete
     * @return bool Success or failure
     */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Failed to prepare delete statement: " . $this->db->getConnection()->error);
                return false;
            }
            
            $stmt->bind_param('i', $id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (\Exception $e) {
            error_log("Exception in delete method: " . $e->getMessage());
            return false;
        }
    }
} 