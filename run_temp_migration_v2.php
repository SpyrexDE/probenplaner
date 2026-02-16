<?php
// Force development environment to see errors
putenv('APP_ENV=development');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting migration script...\n";

try {
    require_once __DIR__ . '/src/bootstrap.php';
} catch (Throwable $e) {
    echo "Bootstrap failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    echo "Getting DB instance...\n";
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();

    echo "Connected to database successfully\n";

    $migrationFile = __DIR__ . '/database/migrations/20260216_125700_add_rehearsal_infos.sql';
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }

    $sql = file_get_contents($migrationFile);

    echo "Running migration content:\n$sql\n";

    // Simple split by semicolon for this specific file
    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $conn->query($statement);
                echo "Executed statement successfully.\n";
            } catch (Exception $e) {
                // Ignore "table exists" errors for idempotency if needed, but better to fail if not IF NOT EXISTS
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "Table already exists, skipping.\n";
                } else {
                    throw $e;
                }
            }
        }
    }

    echo "Migration completed successfully\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
