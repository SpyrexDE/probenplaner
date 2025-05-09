<?php 
/**
 * Database Module View
 */

// Prepare data if not already passed
if (empty($moduleData)) {
    $moduleData = [
        'connection' => $dbConnection,
        'error' => $dbError ?? '',
        'info' => []
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
    }
}
?>

<h2>💾 Database Information</h2>
<p>View the current database connection status and schema information</p>

<!-- Connection Status -->
<div>
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