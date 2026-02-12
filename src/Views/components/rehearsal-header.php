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

use App\Core\RehearsalTypeManager;

$rehearsalId = $rehearsal['id'];
$date = $rehearsal['date_formatted'];
$start_time = substr($rehearsal['start_time'], 0, 5);
$end_time = substr($rehearsal['end_time'], 0, 5);
$time_display = $start_time . ' - ' . $end_time;

// Get rehearsal type using modern system
$rehearsalType = RehearsalTypeManager::getRehearsalType($rehearsal);

// Generate groups display with integrated Kleingruppe handling
$groupKeys = $rehearsal['groups'] ?? [];
$smartDisplay = new \App\Core\SmartGroupDisplay();
$groupsText = $smartDisplay->generateDescription(
    $groupKeys, 
    $rehearsal, 
    isset($isAdmin) && $isAdmin
);
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
            <?= htmlspecialchars($date) ?> - <?= htmlspecialchars($time_display) ?>
        </span>
        <?php if (!empty($rehearsalType)): ?>
            <span class="tree-node-subtitle"><?= htmlspecialchars($rehearsalType) ?></span>
        <?php endif; ?>
    </div>
    
    <div class="tree-node-stats">
        <div class="tree-node-stat">
            <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
            <span><?= $attendingCount ?></span>
        </div>
        <div class="tree-node-stat">
            <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
            <span><?= $notAttendingCount ?></span>
        </div>
        <div class="tree-node-stat">
            <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
            <span><?= $noResponseCount ?></span>
        </div>
    </div>
</button>
