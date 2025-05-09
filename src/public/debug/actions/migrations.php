<?php
/**
 * Migrations Module Actions
 */

// Handle run_migration action
if (isset($_POST['action']) && $_POST['action'] === 'run_migration') {
    $migrationFile = isset($_POST['migration']) ? $_POST['migration'] : null;
    
    if (!$migrationFile) {
        return [
            'message' => 'No migration specified',
            'messageType' => 'error'
        ];
    }
    
    try {
        // Check if migration exists
        $migrationPath = '/var/www/html/database/migrations/' . $migrationFile;
        if (!file_exists($migrationPath)) {
            throw new Exception("Migration file not found: $migrationFile");
        }
        
        // Check if already applied
        $stmt = $conn->prepare("SELECT 1 FROM migrations WHERE migration = ?");
        $stmt->bind_param('s', $migrationFile);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return [
                'message' => "Migration '$migrationFile' has already been applied.",
                'messageType' => 'warning'
            ];
        }
        
        // Run migration in a transaction
        $conn->begin_transaction();
        
        try {
            // Execute migration SQL
            $sql = file_get_contents($migrationPath);
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
            $stmt->bind_param('s', $migrationFile);
            $stmt->execute();
            
            $conn->commit();
            
            return [
                'message' => "Successfully applied migration: $migrationFile",
                'messageType' => 'success'
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } catch (Exception $e) {
        return [
            'message' => "Failed to apply migration: " . $e->getMessage(),
            'messageType' => 'error'
        ];
    }
}

return []; 