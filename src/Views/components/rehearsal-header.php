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

<span class="tree-item-span">
    <a class="text-black no-underline" 
       style="border-left-color: <?= !empty($rehearsal['color']) ? $rehearsal['color'] : '#ffffff' ?>;" 
       data-toggle="collapse" 
       href="#<?= htmlspecialchars($collapseTarget) ?>" 
       aria-expanded="false" 
       aria-controls="<?= htmlspecialchars($collapseTarget) ?>">
        <i class="collapsed"><i ><?= icon('folder', 'text-gray-600') ?>></i></i>
        <i class="expanded"><i class="far fa-folder-open"></i></i> 
        <?= htmlspecialchars($rehearsal['date_formatted'] ?? $date) ?> - <?= htmlspecialchars($time_display) ?>
        <?php if (!empty($rehearsalType)): ?>
            - <?= htmlspecialchars($rehearsalType) ?>
        <?php endif; ?>
    </a>
    <a class="rightfloatet"><?= $notAttendingCount ?></a>
    <i class=" treeIcon rightfloatet"><?= icon('times-circle', 'text-gray-600') ?>></i>
    <a class="rightfloatet"><?= $attendingCount ?></a>
    <i class=" treeIcon rightfloatet"><?= icon('check-circle', 'text-gray-600') ?>></i>
    <a class="rightfloatet"><?= $noResponseCount ?></a>
    <i class=" treeIcon rightfloatet"><?= icon('question-circle', 'text-gray-600') ?>></i>
</span>
