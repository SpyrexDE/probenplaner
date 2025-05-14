#!/usr/bin/env php
<?php
/**
 * Database Migration CLI Tool
 * Usage:
 *   php cli-migrate.php [command] [options]
 * 
 * Commands:
 *   status        - Show status of all migrations
 *   up [name]     - Run all pending migrations or a specific one
 *   create [name] - Create a new migration file
 */

// Try Docker path first, then local path
$bootstrapPaths = [
    __DIR__ . '/../src/bootstrap.php',
    '/var/www/html/bootstrap.php'
];

$bootstrapLoaded = false;
foreach ($bootstrapPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $bootstrapLoaded = true;
        break;
    }
}

if (!$bootstrapLoaded) {
    die("Error: Could not find bootstrap.php in any of the expected locations.\n");
}

// Parse command line arguments
$command = $argv[1] ?? 'status';
$argument = $argv[2] ?? null;

try {
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();
    
    // Ensure migrations table exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Get list of migration files
    $migrationDir = __DIR__ . '/migrations';
    $files = array_diff(scandir($migrationDir), ['.', '..', 'README.md']);
    $sqlFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });
    sort($sqlFiles);

    // Get list of applied migrations
    $result = $conn->query("SELECT migration, applied_at FROM migrations ORDER BY applied_at");
    $appliedMigrations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $appliedFiles = array_column($appliedMigrations, 'migration');

    switch ($command) {
        case 'status':
            echo "\nMigration Status:\n";
            echo str_repeat('-', 80) . "\n";
            echo sprintf("%-40s %-20s %s\n", 'Migration', 'Status', 'Applied At');
            echo str_repeat('-', 80) . "\n";
            
            foreach ($sqlFiles as $file) {
                $status = in_array($file, $appliedFiles) ? 'Applied' : 'Pending';
                $appliedAt = '';
                foreach ($appliedMigrations as $migration) {
                    if ($migration['migration'] === $file) {
                        $appliedAt = $migration['applied_at'];
                        break;
                    }
                }
                echo sprintf("%-40s %-20s %s\n", $file, $status, $appliedAt);
            }
            echo str_repeat('-', 80) . "\n";
            break;

        case 'up':
            if ($argument) {
                // Run specific migration
                if (!in_array($argument, $sqlFiles)) {
                    throw new Exception("Migration file not found: $argument");
                }
                $sqlFiles = [$argument];
            }

            foreach ($sqlFiles as $file) {
                if (!in_array($file, $appliedFiles)) {
                    echo "Running migration: $file\n";
                    
                    $sql = file_get_contents($migrationDir . '/' . $file);
                    
                    try {
                        $conn->begin_transaction();
                        
                        if (!$conn->multi_query($sql)) {
                            throw new Exception($conn->error);
                        }
                        
                        do {
                            if ($result = $conn->store_result()) {
                                $result->free();
                            }
                        } while ($conn->more_results() && $conn->next_result());
                        
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
            break;

        case 'create':
            if (!$argument) {
                throw new Exception("Migration name is required for create command");
            }
            
            $timestamp = date('Ymd_His');
            $filename = "{$timestamp}_{$argument}.sql";
            $filepath = "$migrationDir/$filename";
            
            $template = "-- [Description]";
            
            if (file_put_contents($filepath, $template)) {
                echo "Created new migration file: $filename\n";
            } else {
                throw new Exception("Failed to create migration file");
            }
            break;

        default:
            echo "Unknown command: $command\n";
            echo "Usage: php cli-migrate.php [status|up|create] [name]\n";
            exit(1);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 