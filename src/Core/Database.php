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
    private $lastStmtErrno = 0;
    private $lastStmtError = '';

    /**
     * Constructor is private to prevent direct instantiation
     */
    private function __construct()
    {
        try {
            if (!extension_loaded('mysqli')) {
                throw new \Exception("MySQLi extension is not loaded. Please enable it in your PHP configuration.");
            }

            $this->connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

            if (!$this->connection) {
                $error = mysqli_connect_error();
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

                throw new \Exception($errorMsg);
            }

            $charsets = DB_CHARSET_FALLBACK;
            foreach ($charsets as $charset) {
                if ($this->connection->set_charset($charset)) {
                    break;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
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
        // Prefer last statement error if available
        if ($this->lastStmtErrno !== 0 || $this->lastStmtError !== '') {
            return "MySQL Error #{$this->lastStmtErrno}: {$this->lastStmtError}";
        }
        if ($this->connection) {
            $error = $this->connection->error;
            $errno = $this->connection->errno;
            return "MySQL Error #{$errno}: {$error}";
        }
        return mysqli_connect_error();
    }

    /**
     * Set last statement error (errno and message) to surface precise failures
     *
     * @param int $errno
     * @param string $error
     * @return void
     */
    public function setLastError($errno, $error)
    {
        $this->lastStmtErrno = (int)$errno;
        $this->lastStmtError = (string)$error;
    }
}
