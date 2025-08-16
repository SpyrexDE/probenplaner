<?php
/**
 * Strings section component for sectional view
 * @param array $stringPlayers - Array of string players with status
 * @param int $rehearsalId - Rehearsal ID for unique element IDs
 */
?>

<li>
    <span class="tree-item-span">
        <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#Streicher<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Streicher<?= $rehearsalId ?>">
            <i class="collapsed"><i class="fas fa-folder"></i></i>
            <i class="expanded"><i class="far fa-folder-open"></i></i> Streicher
        </a>
        <?php 
            $stringsAttending = count(array_filter($stringPlayers, function($m) { return $m['status'] === 'attending'; }));
            $stringsNotAttending = count(array_filter($stringPlayers, function($m) { return $m['status'] === 'not_attending'; }));
            $stringsNoResponse = count(array_filter($stringPlayers, function($m) { return $m['status'] === 'no_response'; }));
        ?>
        <a class="rightfloatet"><?= $stringsNotAttending ?></a>
        <i class="fas fa-times-circle treeIcon rightfloatet"></i>
        <a class="rightfloatet"><?= $stringsAttending ?></a>
        <i class="fas fa-check-circle treeIcon rightfloatet"></i>
        <a class="rightfloatet"><?= $stringsNoResponse ?></a>
        <i class="fas fa-question-circle treeIcon rightfloatet"></i>
    </span>
    
    <div id="Streicher<?= $rehearsalId ?>" class="collapse">
        <ul>
            <?php
            $violins1 = array_filter($stringPlayers, function($m) { return $m['type'] === 'Violine_1'; });
            $violins2 = array_filter($stringPlayers, function($m) { return $m['type'] === 'Violine_2'; });
            $violas = array_filter($stringPlayers, function($m) { return $m['type'] === 'Bratsche'; });
            $cellos = array_filter($stringPlayers, function($m) { return $m['type'] === 'Cello'; });
            $basses = array_filter($stringPlayers, function($m) { return $m['type'] === 'Kontrabass'; });
            
            $instrumentGroups = [
                'Violine 1' => $violins1,
                'Violine 2' => $violins2,
                'Bratsche' => $violas,
                'Cello' => $cellos,
                'Kontrabass' => $basses
            ];
            
            foreach ($instrumentGroups as $instrumentName => $players):
                if (!empty($players)):
                    $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
                    $notAttending = count(array_filter($players, function($m) { return $m['status'] === 'not_attending'; }));
                    $noResponse = count(array_filter($players, function($m) { return $m['status'] === 'no_response'; }));
                    $instrumentId = str_replace(' ', '', $instrumentName);
            ?>
            <li>
                <span class="tree-item-span">
                    <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#<?= $instrumentId . $rehearsalId ?>" aria-expanded="false" aria-controls="<?= $instrumentId . $rehearsalId ?>">
                        <i class="collapsed"><i class="fas fa-folder"></i></i>
                        <i class="expanded"><i class="far fa-folder-open"></i></i> <?= $instrumentName ?>
                    </a>
                    <a class="rightfloatet"><?= $notAttending ?></a>
                    <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                    <a class="rightfloatet"><?= $attending ?></a>
                    <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                    <a class="rightfloatet"><?= $noResponse ?></a>
                    <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                </span>
                
                <div id="<?= $instrumentId . $rehearsalId ?>" class="collapse">
                    <ul>
                        <?php foreach ($players as $player): ?>
                            <?php 
                                $member = $player;
                                $status = $player['status'];
                                include __DIR__ . '/user-item.php'; 
                            ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
            <?php 
                endif;
            endforeach; 
            ?>
        </ul>
    </div>
</li>
