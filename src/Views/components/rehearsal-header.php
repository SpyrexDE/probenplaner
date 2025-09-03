<?php
/**
 * Rehearsal header component for promise views
 * 
 * @param array $rehearsal - Rehearsal data
 * @param int $attendingCount - Number of attending members
 * @param int $notAttendingCount - Number of not attending members  
 * @param int $noResponseCount - Number of members with no response
 * @param string $collapseTarget - Target ID for collapse functionality
 */

$rehearsalId = $rehearsal['id'];
$date = $rehearsal['date_formatted'] ?? $rehearsal['date'];
$start_time = isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??';
$end_time = isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??';
$time_display = $start_time . ' - ' . $end_time;

// Determine rehearsal type
$groupKeys = $rehearsal['groups'] ?? [];
$rehearsalType = '';

// Add * suffix to group names if it's a small group
$isSmallGroup = isset($rehearsal['is_small_group']) && $rehearsal['is_small_group'] == 1;
if ($isSmallGroup) {
    foreach ($groupKeys as &$group) {
        $group .= '*';
    }
}

if (in_array('Registerprobe', $groupKeys)) {
    $rehearsalType = 'Registerprobe';
} elseif (in_array('Konzert', $groupKeys)) {
    $rehearsalType = 'Konzert';
} elseif (in_array('Generalprobe', $groupKeys)) {
    $rehearsalType = 'Generalprobe';
} elseif (in_array('Konzertreise', $groupKeys)) {
    $rehearsalType = 'Konzertreise';
}

if ($isSmallGroup) {
    $rehearsalType .= isset($isAdmin) && $isAdmin ? ' (Kleingruppenprobe)' : ' (Kleingruppe)';
}
?>

<button class="tree-node-header" 
        data-toggle="collapse" 
        href="#<?= htmlspecialchars($collapseTarget) ?>" 
        aria-expanded="false" 
        aria-controls="<?= htmlspecialchars($collapseTarget) ?>">
    <?php if (!empty($rehearsal['color'])): ?>
        <div class="tree-node-color" style="background-color: <?= htmlspecialchars($rehearsal['color']) ?>;"></div>
    <?php endif; ?>
    
    <i class="tree-node-icon fas fa-chevron-right"></i>
    
    <div class="tree-node-title">
        <span class="tree-node-title-text">
            <?= htmlspecialchars($rehearsal['date_formatted'] ?? $date) ?> - <?= htmlspecialchars($time_display) ?>
        </span>
        <?php if (!empty($rehearsalType)): ?>
            <span class="tree-node-subtitle"><?= htmlspecialchars($rehearsalType) ?></span>
        <?php endif; ?>
    </div>
    
    <div class="tree-node-stats">
        <div class="tree-node-stat">
            <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
            <span><?= $noResponseCount ?></span>
        </div>
        <div class="tree-node-stat">
            <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
            <span><?= $attendingCount ?></span>
        </div>
        <div class="tree-node-stat">
            <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
            <span><?= $notAttendingCount ?></span>
        </div>
    </div>
</button>
