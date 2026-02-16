<?php
require_once __DIR__ . '/src/bootstrap.php';

try {
    $db = \App\Core\Database::getInstance();
    $conn = $db->getConnection();

    echo "Connected to database successfully\n";

    $migrationFile = __DIR__ . '/database/migrations/20260216_125700_add_rehearsal_infos.sql';
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }

    $sql = file_get_contents($migrationFile);

    echo "Running migration...\n";

    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $conn->query($statement);
            echo "Executed statement.\n";
        }
    }

    echo "Migration completed successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
