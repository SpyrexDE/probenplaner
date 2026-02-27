<?php

/**
 * Test Users Generator View
 */

if (!isset($db) || !$db) {
    try {
        $db = \App\Core\Database::getInstance();
        $conn = $db->getConnection();
    } catch (\Exception $e) {
        echo '<div class="message error">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        die();
    }
}

$orchestras = [];
$result = $conn->query("SELECT id, name FROM orchestras ORDER BY name");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orchestras[$row['id']] = $row['name'];
    }
}

$userTypes = [
    "Streicher" => ["Violine_1", "Violine_2", "Bratsche", "Cello", "Kontrabass"],
    "Holzbläser" => ["Flöte", "Oboe", "Klarinette", "Fagott"],
    "Blechbläser" => ["Trompete", "Posaune", "Tuba", "Horn"],
    "Andere" => ["Schlagwerk", "Andere"]
];
?>

<h2><?= $modules[$currentModule]['icon'] ?> <?= htmlspecialchars($modules[$currentModule]['name']) ?></h2>
<p><?= htmlspecialchars($modules[$currentModule]['description']) ?></p>

<!-- ── Full Setup Results ──────────────────────────────────────────── -->
<?php if (!empty($moduleData) && isset($moduleData['full_setup'])): ?>
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">🏗️ Setup Result</div>
        <div class="card-body">
            <ul style="list-style: none; padding: 0; margin: 0 0 15px 0;">
                <?php foreach ($moduleData['summary'] as $line): ?>
                    <li style="padding: 4px 0;"><?= $line ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if (!empty($moduleData['credentials'])): ?>
                <h4 style="margin-top: 15px;">🔑 Test-Logins</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moduleData['credentials'] as $cred): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($cred['username']) ?></code></td>
                                <td><code><?= htmlspecialchars($cred['password']) ?></code></td>
                                <td><?= htmlspecialchars($cred['role']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ── Complete Test Setup Card ─────────────────────────────────────── -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">🏗️ Complete Test Setup</div>
    <div class="card-body">
        <p style="margin-bottom: 10px;">
            Create a full realistic test database with <strong>one click</strong>:
        </p>
        <ul style="margin: 0 0 15px 20px; line-height: 1.8;">
            <li>🏛️ Organization + Org-Admin</li>
            <li>🎻 2 Orchestras (Sinfonie + Kammer)</li>
            <li>👥 ~30 Users (conductors, section leaders, members, inactive, small-group)</li>
            <li>📅 12 Rehearsals (Probe, Registerprobe, Konzert, Generalprobe, Konzertreise)</li>
            <li>📋 Schedule items &amp; 📝 emoji info notes</li>
            <li>✅ Mixed attendances (yes/no/gray/no-response + notes)</li>
        </ul>
        <form method="post" action="?module=test_users">
            <button type="submit" name="action" value="generate_full_setup" class="btn-base btn-primary"
                onclick="return confirm('This will insert a full test dataset. Continue?')">
                🚀 Generate Full Test Setup
            </button>
        </form>
    </div>
</div>

<!-- ── Individual User Generation ──────────────────────────────────── -->
<?php if (empty($orchestras)): ?>
    <div class="message warning">No orchestras found in the database. Please create an orchestra first or use the Full Setup above.</div>
<?php else: ?>

    <div class="card">
        <div class="card-header">Generate Test Users</div>
        <div class="card-body">
            <form method="post" action="?module=test_users">
                <div class="form-group">
                    <label for="orchestra_id">Select Orchestra:</label>
                    <select name="orchestra_id" id="orchestra_id" class="form-input" required>
                        <option value="">-- Select Orchestra --</option>
                        <?php foreach ($orchestras as $id => $name): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Username Prefix (will be appended with number):</label>
                    <input type="text" name="username_prefix" class="form-input" value="tester" required>
                    <small class="form-text text-muted">Both username and password will be set to the same value.</small>
                </div>

                <div class="form-group">
                    <label>Max Users Per Section (random 0-10):</label>
                    <input type="number" name="max_users" class="form-input" value="10" min="1" max="20" required>
                </div>

                <button type="submit" name="action" value="generate_users" class="btn-base btn-primary">Generate Test Users</button>
            </form>
        </div>
    </div>

    <?php if (!empty($moduleData) && isset($moduleData['users_generated'])): ?>
        <div class="card" style="margin-top: 15px;">
            <div class="card-header">Generated Users</div>
            <div class="card-body">
                <p>Total users generated: <strong><?= $moduleData['users_generated'] ?></strong></p>

                <?php if (!empty($moduleData['users'])): ?>
                    <table>
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