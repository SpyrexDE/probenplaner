<?php 
/**
 * Schema Module View
 */

if (empty($moduleData)) {
    $moduleData = [
        'tables' => []
    ];
    
    if (isset($dbConnection) && $dbConnection && isset($conn) && $conn) {
        $tablesResult = $conn->query("SHOW TABLES");
        if ($tablesResult) {
            while ($row = $tablesResult->fetch_row()) {
                $tableName = $row[0];
                
                // Get columns
                $columns = [];
                $colResult = $conn->query("SHOW FULL COLUMNS FROM `$tableName`");
                if ($colResult) {
                    while ($col = $colResult->fetch_assoc()) {
                        $columns[] = $col;
                    }
                }
                
                // Get indexes
                $indexes = [];
                $idxResult = $conn->query("SHOW INDEXES FROM `$tableName`");
                if ($idxResult) {
                    while ($idx = $idxResult->fetch_assoc()) {
                        $indexes[] = $idx;
                    }
                }
                
                $moduleData['tables'][$tableName] = [
                    'columns' => $columns,
                    'indexes' => $indexes
                ];
            }
        }
    }
}
?>

<h2>📊 Database Schema</h2>
<p>Detailed view of all database tables, columns, and indexes.</p>

<?php if (empty($dbConnection) || !$dbConnection): ?>
    <div class="message error">Database connection is required to view schema.</div>
<?php elseif (empty($moduleData['tables'])): ?>
    <div class="message warning">No tables found in the database.</div>
<?php else: ?>
    
    <div style="margin-bottom: 20px;">
        <select id="table-selector" style="padding: 8px; font-size: 16px; width: 100%; max-width: 400px;" onchange="showTable(this.value)">
            <option value="">-- Select a Table to View Schema --</option>
            <?php foreach ($moduleData['tables'] as $tableName => $tableData): ?>
                <option value="<?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?> (<?= count($tableData['columns']) ?> columns)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php foreach ($moduleData['tables'] as $tableName => $tableData): ?>
        <div id="table-<?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?>" class="schema-table-container" style="display: none; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px;">Table: <code><?= htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') ?></code></h3>
            
            <h4>Columns</h4>
            <div style="overflow-x: auto; margin-bottom: 20px;">
                <table class="debug-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background-color: #f9f9f9;">
                            <th style="padding: 8px; border: 1px solid #ddd;">Field</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Type</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Collation</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Null</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Key</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Default</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Extra</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData['columns'] as $col): ?>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;"><strong><?= htmlspecialchars($col['Field'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><code><?= htmlspecialchars($col['Type'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><small><?= htmlspecialchars($col['Collation'] ?? 'NULL', ENT_QUOTES, 'UTF-8') ?></small></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($col['Null'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><strong><?= htmlspecialchars($col['Key'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><code><?= htmlspecialchars($col['Default'] ?? 'NULL', ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($col['Extra'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><small><?= htmlspecialchars($col['Comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($tableData['indexes'])): ?>
                <h4>Indexes</h4>
                <div style="overflow-x: auto;">
                    <table class="debug-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background-color: #f9f9f9;">
                                <th style="padding: 8px; border: 1px solid #ddd;">Key Name</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Seq</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Column</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Unique</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Type</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableData['indexes'] as $idx): ?>
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><strong><?= htmlspecialchars($idx['Key_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($idx['Seq_in_index'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><code><?= htmlspecialchars($idx['Column_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><?= (isset($idx['Non_unique']) && $idx['Non_unique'] == 0) ? 'Yes' : 'No' ?></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><?= htmlspecialchars($idx['Index_type'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="padding: 8px; border: 1px solid #ddd;"><small><?= htmlspecialchars($idx['Index_comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <script>
        function showTable(tableName) {
            // Hide all tables
            document.querySelectorAll('.schema-table-container').forEach(function(el) {
                el.style.display = 'none';
            });
            
            if (tableName) {
                // Show selected table
                var tableEl = document.getElementById('table-' + tableName);
                if (tableEl) {
                    tableEl.style.display = 'block';
                }
            } else {
                // Show all if nothing is selected or keep hidden if requested
            }
        }
    </script>
<?php endif; ?>
