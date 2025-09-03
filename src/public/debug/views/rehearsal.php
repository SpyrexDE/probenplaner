<?php
/**
 * Rehearsal Testing View
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

// Define test patterns
$testPatterns = [
    'normal' => [
        'name' => 'Normal Distribution',
        'description' => 'Random attendance with normal distribution (70-90% average)',
        'attendance_range' => [70, 90],
        'std_dev' => 10
    ],
    'low_attendance' => [
        'name' => 'Low Attendance',
        'description' => 'Consistently low attendance across all sections (30-50% average)',
        'attendance_range' => [30, 50],
        'std_dev' => 8
    ],
    'high_attendance' => [
        'name' => 'High Attendance',
        'description' => 'Consistently high attendance across all sections (85-95% average)',
        'attendance_range' => [85, 95],
        'std_dev' => 5
    ],
    'string_problem' => [
        'name' => 'String Section Problem',
        'description' => 'String sections have very low attendance while others are normal',
        'attendance_range' => [70, 90],
        'std_dev' => 10,
        'problem_sections' => ['Violine_1', 'Violine_2', 'Bratsche', 'Cello', 'Kontrabass'],
        'problem_range' => [20, 40]
    ],
    'trend_decline' => [
        'name' => 'Declining Trend',
        'description' => 'Attendance gradually declining over time (90% → 60%)',
        'attendance_range' => [60, 90],
        'std_dev' => 8,
        'trend' => 'decline'
    ],
    'trend_improve' => [
        'name' => 'Improving Trend',
        'description' => 'Attendance gradually improving over time (50% → 85%)',
        'attendance_range' => [50, 85],
        'std_dev' => 8,
        'trend' => 'improve'
    ],
    'volatile' => [
        'name' => 'Volatile Attendance',
        'description' => 'High variance in attendance (30-95% with high standard deviation)',
        'attendance_range' => [30, 95],
        'std_dev' => 25
    ],
    'no_response' => [
        'name' => 'Low Response Rate',
        'description' => 'Many users not responding to rehearsals (high "maybe" rate)',
        'attendance_range' => [60, 80],
        'std_dev' => 10,
        'no_response_rate' => 0.4
    ]
];
?>

<h2><?= $modules[$currentModule]['icon'] ?> <?= htmlspecialchars($modules[$currentModule]['name']) ?></h2>
<p><?= htmlspecialchars($modules[$currentModule]['description']) ?></p>

<?php if (empty($orchestras)): ?>
<div class="message warning">No orchestras found in the database. Please create an orchestra first.</div>
<?php else: ?>

<div class="card">
    <div class="card-header">Generate Test Rehearsals</div>
    <div class="card-body">
        <form method="post" action="?module=rehearsal">
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
                <label for="pattern">Test Pattern:</label>
                <select name="pattern" id="pattern" class="form-input" required>
                    <option value="">-- Select Pattern --</option>
                    <?php foreach ($testPatterns as $key => $pattern): ?>
                    <option value="<?= $key ?>"><?= htmlspecialchars($pattern['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted" id="pattern-description"></small>
            </div>
            
            <div class="form-group">
                <label>Number of Rehearsals:</label>
                <input type="number" name="num_rehearsals" class="form-input" value="10" min="1" max="20" required>
                <small class="form-text text-muted">Generate 1-20 rehearsals for testing (more = better statistics)</small>
            </div>
            
            <div class="form-group">
                <label>Start Date:</label>
                <input type="date" name="start_date" class="form-input" value="<?= date('Y-m-d', strtotime('-2 months')) ?>" required>
                <small class="form-text text-muted">Rehearsals will be created from this date onwards</small>
            </div>
            
            <div class="form-group">
                <label>Days Between Rehearsals:</label>
                <input type="number" name="days_between" class="form-input" value="7" min="1" max="14" required>
                <small class="form-text text-muted">Spacing between rehearsals (1-14 days)</small>
            </div>
            
            <button type="submit" name="action" value="generate_rehearsals" class="btn-base btn-primary">Generate Test Rehearsals</button>
    </form>
    </div>
</div>

<!-- Pattern Descriptions -->
<div class="card mt-4">
    <div class="card-header">Test Pattern Details</div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($testPatterns as $key => $pattern): ?>
            <div class="pattern-card" data-pattern="<?= $key ?>">
                <h4><?= htmlspecialchars($pattern['name']) ?></h4>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($pattern['description']) ?></p>
                <div class="text-xs text-gray-500 mt-2">
                    <strong>Attendance Range:</strong> <?= $pattern['attendance_range'][0] ?>-<?= $pattern['attendance_range'][1] ?>%<br>
                    <strong>Std Dev:</strong> <?= $pattern['std_dev'] ?>%
                    <?php if (isset($pattern['problem_sections'])): ?>
                    <br><strong>Problem Sections:</strong> <?= implode(', ', $pattern['problem_sections']) ?>
                    <?php endif; ?>
                    <?php if (isset($pattern['no_response_rate'])): ?>
                    <br><strong>No Response Rate:</strong> <?= $pattern['no_response_rate'] * 100 ?>%
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (!empty($moduleData) && isset($moduleData['rehearsals_generated'])): ?>
<div class="card mt-4">
    <div class="card-header">Generated Rehearsals</div>
    <div class="card-body">
        <p>Total rehearsals generated: <strong><?= $moduleData['rehearsals_generated'] ?></strong></p>
        
        <?php if (!empty($moduleData['rehearsals'])): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>Location</th>
                    <th>Total Users</th>
                    <th>Attending</th>
                    <th>Not Attending</th>
                    <th>No Response</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($moduleData['rehearsals'] as $rehearsal): ?>
                <tr>
                    <td><?= htmlspecialchars($rehearsal['date']) ?></td>
                    <td><?= htmlspecialchars($rehearsal['start_time']) ?></td>
                    <td><?= htmlspecialchars($rehearsal['location']) ?></td>
                    <td><?= $rehearsal['total_users'] ?></td>
                    <td class="text-green-600"><?= $rehearsal['attending'] ?></td>
                    <td class="text-red-600"><?= $rehearsal['not_attending'] ?></td>
                    <td class="text-yellow-600"><?= $rehearsal['no_response'] ?></td>
                    <td class="font-bold"><?= number_format($rehearsal['attendance_rate'], 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Update pattern description when selection changes
document.getElementById('pattern').addEventListener('change', function() {
    const patternKey = this.value;
    const descriptionElement = document.getElementById('pattern-description');
    
    if (patternKey) {
        const patterns = <?= json_encode($testPatterns) ?>;
        const pattern = patterns[patternKey];
        descriptionElement.textContent = pattern.description;
    } else {
        descriptionElement.textContent = '';
    }
});
</script>

<?php endif; ?> 
