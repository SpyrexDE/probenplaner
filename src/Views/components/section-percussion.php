<?php
/**
 * Percussion section component for sectional view
 * @param array $percussionPlayers - Array of percussion players with status
 * @param int $rehearsalId - Rehearsal ID for unique element IDs
 */
?>

<li>
    <span class="tree-item-span">
        <a class="text-black no-underline" data-toggle="collapse" href="#Schlagwerk<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Schlagwerk<?= $rehearsalId ?>">
            <i class="collapsed"><i ><?= icon('folder', 'text-gray-600') ?>></i></i>
            <i class="expanded"><i class="far fa-folder-open"></i></i> Schlagwerk
        </a>
        <?php 
            $percAttending = count(array_filter($percussionPlayers, function($m) { return $m['status'] === 'attending'; }));
            $percNotAttending = count(array_filter($percussionPlayers, function($m) { return $m['status'] === 'not_attending'; }));
            $percNoResponse = count(array_filter($percussionPlayers, function($m) { return $m['status'] === 'no_response'; }));
        ?>
        <a class="rightfloatet"><?= $percNotAttending ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('times-circle', 'text-gray-600') ?>></i>
        <a class="rightfloatet"><?= $percAttending ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('check-circle', 'text-gray-600') ?>></i>
        <a class="rightfloatet"><?= $percNoResponse ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('question-circle', 'text-gray-600') ?>></i>
    </span>
    
    <div id="Schlagwerk<?= $rehearsalId ?>" class="collapse">
        <ul>
            <?php foreach ($percussionPlayers as $player): ?>
                <?php 
                    $member = $player;
                    $status = $player['status'];
                    include __DIR__ . '/user-item.php'; 
                ?>
            <?php endforeach; ?>
        </ul>
    </div>
</li>
