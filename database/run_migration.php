<?php
// Include configuration file to get database credentials
require_once __DIR__ . '/../src/config/config.php';

// Connect to the database
try {
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully\n";
    
    // Read and execute the SQL file
    $migrationFile = __DIR__ . '/migrations/20240530_add_is_small_group_to_rehearsals.sql';
    $sql = file_get_contents($migrationFile);
    
    // Split the SQL into separate statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "Migration completed successfully\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} 