<?php
/**
 * Other section component for sectional view
 * @param array $otherPlayers - Array of other players with status
 * @param int $rehearsalId - Rehearsal ID for unique element IDs
 */
?>

<li>
    <span class="tree-item-span">
        <a class="text-black no-underline" data-toggle="collapse" href="#Andere<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Andere<?= $rehearsalId ?>">
            <i class="collapsed"><i ><?= icon('folder', 'text-gray-600') ?>></i></i>
            <i class="expanded"><i class="far fa-folder-open"></i></i> Andere
        </a>
        <?php 
            $otherAttending = count(array_filter($otherPlayers, function($m) { return $m['status'] === 'attending'; }));
            $otherNotAttending = count(array_filter($otherPlayers, function($m) { return $m['status'] === 'not_attending'; }));
            $otherNoResponse = count(array_filter($otherPlayers, function($m) { return $m['status'] === 'no_response'; }));
        ?>
        <a class="rightfloatet"><?= $otherNotAttending ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('times-circle', 'text-gray-600') ?>></i>
        <a class="rightfloatet"><?= $otherAttending ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('check-circle', 'text-gray-600') ?>></i>
        <a class="rightfloatet"><?= $otherNoResponse ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('question-circle', 'text-gray-600') ?>></i>
    </span>
    
    <div id="Andere<?= $rehearsalId ?>" class="collapse">
        <ul>
            <?php foreach ($otherPlayers as $player): ?>
                <?php 
                    $member = $player;
                    $status = $player['status'];
                    $additionalInfo = ' (' . str_replace('_', ' ', $player['type']) . ')';
                    include __DIR__ . '/user-item.php'; 
                ?>
            <?php endforeach; ?>
        </ul>
    </div>
</li>
