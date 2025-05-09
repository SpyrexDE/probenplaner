<?php 
/**
 * Database Module View
 */

// Prepare data if not already passed
if (empty($moduleData)) {
    $moduleData = [
        'connection' => $dbConnection,
        'error' => $dbError ?? '',
        'info' => [],
        'tables' => []
    ];
    
    if ($dbConnection) {
        // Database info
        $moduleData['info'] = [
            'Server version' => $conn->server_info,
            'Host info' => $conn->host_info,
            'Character set' => $conn->character_set_name(),
            'Client info' => $conn->client_info,
            'Protocol version' => $conn->protocol_version,
        ];
        
        // Table statuses
        $tables = ['orchestras', 'users', 'rehearsals', 'rehearsal_groups', 'user_promises'];
        
        foreach ($tables as $table) {
            $exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
            $rowCount = $exists ? $conn->query("SELECT COUNT(*) AS count FROM $table")->fetch_assoc()['count'] : 0;
            
            $moduleData['tables'][$table] = [
                'exists' => $exists,
                'rowCount' => $rowCount
            ];
            
            // Check if required columns exist if table exists
            if ($exists) {
                $moduleData['tables'][$table]['columns'] = [];
                
                // Check specific columns based on table
                switch ($table) {
                    case 'users':
                        $requiredColumns = ['id', 'username', 'password', 'type', 'orchestra_id', 'role', 'is_small_group'];
                        break;
                    case 'orchestras':
                        $requiredColumns = ['id', 'name', 'token', 'leader_pw', 'conductor_id'];
                        break;
                    case 'rehearsals':
                        $requiredColumns = ['id', 'date', 'time', 'location', 'groups_data', 'orchestra_id'];
                        break;
                    default:
                        $requiredColumns = [];
                }
                
                foreach ($requiredColumns as $column) {
                    $moduleData['tables'][$table]['columns'][$column] = isColumnExists($conn, $table, $column);
                }
            }
        }
    }
}
?>

<h2>💾 Database Information</h2>
<p>View the current database connection status and schema information</p>

<!-- Connection Status -->
<div class="module-card">
    <h3>Connection Status</h3>
    <?php if ($moduleData['connection']): ?>
        <p class="status-ok">✅ Database connection is working properly!</p>
        
        <h4>Database Information</h4>
        <table>
            <?php foreach ($moduleData['info'] as $key => $value): ?>
                <tr>
                    <th width="30%"><?= htmlspecialchars($key) ?></th>
                    <td><?= htmlspecialchars($value) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="status-error">❌ Database connection failed: <?= htmlspecialchars($moduleData['error']) ?></p>
        <p class="message warning">Check configuration in <code>config.php</code> and make sure the MySQL server is running.</p>
    <?php endif; ?>
</div>

<!-- Table Status -->
<?php if ($moduleData['connection']): ?>
<div class="module-card">
    <h3>Table Status</h3>
    <table>
        <tr>
            <th>Table Name</th>
            <th>Status</th>
            <th>Row Count</th>
            <th>Required Columns</th>
        </tr>
        <?php foreach ($moduleData['tables'] as $table => $info): ?>
            <tr>
                <td><code><?= htmlspecialchars($table) ?></code></td>
                <td class="<?= $info['exists'] ? 'status-ok' : 'status-error' ?>">
                    <?= $info['exists'] ? '✅ Exists' : '❌ Missing' ?>
                </td>
                <td><?= $info['exists'] ? $info['rowCount'] : 'N/A' ?></td>
                <td>
                    <?php if ($info['exists'] && !empty($info['columns'])): ?>
                        <?php foreach ($info['columns'] as $column => $exists): ?>
                            <code style="margin-right: 5px;" class="<?= $exists ? 'status-ok' : 'status-error' ?>">
                                <?= htmlspecialchars($column) ?> <?= $exists ? '✓' : '✗' ?>
                            </code>
                        <?php endforeach; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <?php 
    // Check for missing columns across all tables
    $missingColumns = [];
    foreach ($moduleData['tables'] as $table => $info) {
        if (!empty($info['columns'])) {
            foreach ($info['columns'] as $column => $exists) {
                if (!$exists) {
                    $missingColumns[] = "$table.$column";
                }
            }
        }
    }
    
    // Check for missing tables
    $missingTables = array_filter($moduleData['tables'], function($info) {
        return !$info['exists'];
    });
    
    if (!empty($missingTables) || !empty($missingColumns)):
    ?>
        <div class="message warning">
            <h4>Schema Issues Detected</h4>
            
            <?php if (!empty($missingTables)): ?>
                <p><strong>Missing tables (<?= count($missingTables) ?>):</strong> 
                    <?= implode(', ', array_keys($missingTables)) ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($missingColumns)): ?>
                <p><strong>Missing columns (<?= count($missingColumns) ?>):</strong> 
                    <?= implode(', ', $missingColumns) ?>
                </p>
            <?php endif; ?>
            
            <p>➡️ Go to the <a href="?module=migrations">Migrations</a> module to fix these issues.</p>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?> 