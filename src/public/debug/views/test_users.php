<?php
/**
 * Test Users Generator View
 */

// Initialize database connection if not already done
if (!isset($db) || !$db) {
    try {
        $db = \App\Core\Database::getInstance();
        $conn = $db->getConnection();
    } catch (\Exception $e) {
        echo '<div class="message error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        die();
    }
}

// Get list of orchestras
$orchestras = [];
$result = $conn->query("SELECT id, name FROM orchestras ORDER BY name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orchestras[$row['id']] = $row['name'];
    }
}

// Get user types/instruments structure
$userTypes = [
    "Streicher" => [
        "Violine_1",
        "Violine_2",
        "Bratsche",
        "Cello",
        "Kontrabass"
    ],
    "Holzbläser" => [
        "Flöte",
        "Oboe",
        "Klarinette",
        "Fagott"
    ],
    "Blechbläser" => [
        "Trompete",
        "Posaune",
        "Tuba",
        "Horn"
    ],
    "Andere" => [
        "Schlagwerk",
        "Andere"
    ]
];
?>

<h2><?= $modules[$currentModule]['icon'] ?> <?= htmlspecialchars($modules[$currentModule]['name']) ?></h2>
<p><?= htmlspecialchars($modules[$currentModule]['description']) ?></p>

<?php if (empty($orchestras)): ?>
<div class="message warning">No orchestras found in the database. Please create an orchestra first.</div>
<?php else: ?>

<div class="card">
    <div class="card-header">Generate Test Users</div>
    <div class="card-body">
        <form method="post" action="?module=test_users">
            <div class="form-group">
                <label for="orchestra_id">Select Orchestra:</label>
                <select name="orchestra_id" id="orchestra_id" class="form-control" required>
                    <option value="">-- Select Orchestra --</option>
                    <?php foreach ($orchestras as $id => $name): ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Username Prefix (will be appended with number):</label>
                <input type="text" name="username_prefix" class="form-control" value="tester" required>
                <small class="form-text text-muted">Both username and password will be set to the same value.</small>
            </div>
            
            <div class="form-group">
                <label>Max Users Per Section (random 0-10):</label>
                <input type="number" name="max_users" class="form-control" value="10" min="1" max="20" required>
            </div>
            
            <button type="submit" name="action" value="generate_users" class="btn btn-primary">Generate Test Users</button>
        </form>
    </div>
</div>

<?php if (!empty($moduleData) && isset($moduleData['users_generated'])): ?>
<div class="card mt-4">
    <div class="card-header">Generated Users</div>
    <div class="card-body">
        <p>Total users generated: <strong><?= $moduleData['users_generated'] ?></strong></p>
        
        <?php if (!empty($moduleData['users'])): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moduleData['users'] as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['password']) ?></td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $user['type'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?> 