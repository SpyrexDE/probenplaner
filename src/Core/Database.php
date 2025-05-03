<?php
namespace App\Core;

/**
 * Database Class
 * Singleton pattern for database connection
 */
class Database
{
    private static $instance = null;
    private $connection;
    
    /**
     * Constructor is private to prevent direct instantiation
     */
    private function __construct()
    {
        try {
            // Check if mysqli extension is loaded
            if (!extension_loaded('mysqli')) {
                throw new \Exception("MySQLi extension is not loaded. Please enable it in your PHP configuration.");
            }
            
            @error_log("Attempting to connect to database: " . DB_HOST . ", User: " . DB_USER . ", DB: " . DB_NAME);
            
            // Create connection - with connection timeout handling
            $this->connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            
            // Check connection
            if (!$this->connection) {
                $error = mysqli_connect_error();
                
                // Check for specific error types
                $errorMsg = "Database connection failed: " . $error;
                
                if (strpos($error, 'Access denied') !== false) {
                    $errorMsg .= " - Please check the username and password.";
                } elseif (strpos($error, 'Unknown database') !== false) {
                    $errorMsg .= " - Database does not exist.";
                } elseif (strpos($error, 'Connection refused') !== false) {
                    $errorMsg .= " - Check that the MySQL server is running and the host is correct.";
                } elseif (strpos($error, 'Connection timed out') !== false) {
                    $errorMsg .= " - Connection timeout. Check the host address and port.";
                }
                
                @error_log($errorMsg);
                throw new \Exception($errorMsg);
            }
            
            // Set charset
            $charsets = ['utf8mb4', 'utf8', 'latin1'];
            $charset_success = false;
            foreach ($charsets as $charset) {
                try {
                    if ($this->connection->set_charset($charset)) {
                        @error_log("Successfully set charset to $charset");
                        $charset_success = true;
                        break;
                    }
                } catch (\Exception $e) {
                    @error_log("Error setting charset $charset: " . $e->getMessage());
                }
            }
            
            if (!$charset_success) {
                @error_log("Warning: Could not set any of the preferred charsets. This may cause encoding issues.");
            }
            
            // Test connection with a simple query
            $testResult = $this->connection->query("SELECT 1");
            if (!$testResult) {
                @error_log("Database connection test failed: " . $this->connection->error);
                throw new \Exception("Database connection test failed: " . $this->connection->error);
            }
            $testResult->close();
            
            @error_log("Database connection successful");
        } catch (\Exception $e) {
            @error_log("Database Error: " . $e->getMessage());
            die("Database Error: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        
        return self::$instance;
    }
    
    /**
     * Get database connection
     * 
     * @return mysqli
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * Execute a query
     * 
     * @param string $sql SQL query
     * @return mysqli_result|bool
     */
    public function query($sql)
    {
        return $this->connection->query($sql);
    }
    
    /**
     * Prepare a statement
     * 
     * @param string $sql SQL query
     * @return mysqli_stmt
     */
    public function prepare($sql)
    {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Escape a string for safe database usage
     * 
     * @param string $string The string to escape
     * @return string
     */
    public function escape($string)
    {
        return mysqli_real_escape_string($this->connection, $string);
    }
    
    /**
     * Get last insert ID
     * 
     * @return int|string
     */
    public function getLastId()
    {
        return $this->connection->insert_id;
    }
    
    /**
     * Close the database connection
     * 
     * @return bool
     */
    public function close()
    {
        return mysqli_close($this->connection);
    }
    
    /**
     * Get the last error message
     * 
     * @return string Last error message
     */
    public function getLastError()
    {
        if ($this->connection) {
            $error = $this->connection->error;
            $errno = $this->connection->errno;
            return "MySQL Error #{$errno}: {$error}";
        }
        return mysqli_connect_error();
    }
} 