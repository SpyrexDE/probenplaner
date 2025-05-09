<?php
/**
 * Database Migration Runner
 * Automatically runs all pending migrations in order
 */

require_once __DIR__ . '/../bootstrap.php';

try {
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Starting migration process...\n";
    
    // Get list of migration files
    $migrationDir = __DIR__ . '/migrations';
    $files = array_diff(scandir($migrationDir), ['.', '..', 'README.md']);
    $sqlFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });
    sort($sqlFiles); // Ensure migrations run in order
    
    // Create migrations table if it doesn't exist
    echo "Checking migrations table...\n";
    $conn->query("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Get list of applied migrations
    $result = $conn->query("SELECT migration FROM migrations ORDER BY applied_at");
    $appliedMigrations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $appliedFiles = array_column($appliedMigrations, 'migration');
    
    // Run pending migrations
    foreach ($sqlFiles as $file) {
        if (!in_array($file, $appliedFiles)) {
            echo "Running migration: $file\n";
            
            $sql = file_get_contents($migrationDir . '/' . $file);
            
            try {
                $conn->begin_transaction();
                
                // Execute migration
                if (!$conn->multi_query($sql)) {
                    throw new Exception($conn->error);
                }
                
                // Wait for all results
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                
                // Record migration
                $stmt = $conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $stmt->bind_param('s', $file);
                $stmt->execute();
                
                $conn->commit();
                echo "Successfully applied migration: $file\n";
            } catch (Exception $e) {
                $conn->rollback();
                throw new Exception("Failed to apply migration $file: " . $e->getMessage());
            }
        } else {
            echo "Skipping already applied migration: $file\n";
        }
    }
    
    echo "Migration process completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 