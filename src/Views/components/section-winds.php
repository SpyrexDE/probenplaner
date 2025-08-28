<?php
/**
 * Winds section component for sectional view
 * @param array $woodwindPlayers - Array of woodwind players with status
 * @param array $brassPlayers - Array of brass players with status
 * @param int $rehearsalId - Rehearsal ID for unique element IDs
 */
?>

<li>
    <span class="tree-item-span">
        <a class="text-black no-underline" data-toggle="collapse" href="#Bläser<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Bläser<?= $rehearsalId ?>">
            <i class="collapsed"><i class="fas fa-folder"></i></i>
            <i class="expanded"><i class="far fa-folder-open"></i></i> Bläser
        </a>
        <?php 
            $allWinds = array_merge($woodwindPlayers, $brassPlayers);
            $windsAttending = count(array_filter($allWinds, function($m) { return $m['status'] === 'attending'; }));
            $windsNotAttending = count(array_filter($allWinds, function($m) { return $m['status'] === 'not_attending'; }));
            $windsNoResponse = count(array_filter($allWinds, function($m) { return $m['status'] === 'no_response'; }));
        ?>
        <a class="rightfloatet"><?= $windsNotAttending ?></a>
        <i class="fas fa-times-circle treeIcon rightfloatet"></i>
        <a class="rightfloatet"><?= $windsAttending ?></a>
        <i class="fas fa-check-circle treeIcon rightfloatet"></i>
        <a class="rightfloatet"><?= $windsNoResponse ?></a>
        <i class="fas fa-question-circle treeIcon rightfloatet"></i>
    </span>
    
    <div id="Bläser<?= $rehearsalId ?>" class="collapse">
        <ul>
            <?php if (!empty($woodwindPlayers)): ?>
            <li>
                <span class="tree-item-span">
                    <a class="text-black no-underline" data-toggle="collapse" href="#Holzbläser<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Holzbläser<?= $rehearsalId ?>">
                        <i class="collapsed"><i class="fas fa-folder"></i></i>
                        <i class="expanded"><i class="far fa-folder-open"></i></i> Holzbläser
                    </a>
                    <?php 
                        $woodAttending = count(array_filter($woodwindPlayers, function($m) { return $m['status'] === 'attending'; }));
                        $woodNotAttending = count(array_filter($woodwindPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                        $woodNoResponse = count(array_filter($woodwindPlayers, function($m) { return $m['status'] === 'no_response'; }));
                    ?>
                    <a class="rightfloatet"><?= $woodNotAttending ?></a>
                    <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                    <a class="rightfloatet"><?= $woodAttending ?></a>
                    <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                    <a class="rightfloatet"><?= $woodNoResponse ?></a>
                    <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                </span>
                
                <div id="Holzbläser<?= $rehearsalId ?>" class="collapse">
                    <ul>
                        <?php
                        $flutes = array_filter($woodwindPlayers, function($m) { return $m['type'] === 'Flöte'; });
                        $oboes = array_filter($woodwindPlayers, function($m) { return $m['type'] === 'Oboe'; });
                        $clarinets = array_filter($woodwindPlayers, function($m) { return $m['type'] === 'Klarinette'; });
                        $bassoons = array_filter($woodwindPlayers, function($m) { return $m['type'] === 'Fagott'; });
                        
                        $instrumentGroups = [
                            'Flöten' => $flutes,
                            'Oboen' => $oboes,
                            'Klarinetten' => $clarinets,
                            'Fagotte' => $bassoons
                        ];
                        
                        foreach ($instrumentGroups as $instrumentName => $players):
                            if (!empty($players)):
                                $instrumentId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $instrumentName);
                                $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
                                $notAttending = count(array_filter($players, function($m) { return $m['status'] === 'not_attending'; }));
                                $noResponse = count(array_filter($players, function($m) { return $m['status'] === 'no_response'; }));
                        ?>
                        <li>
                            <span class="tree-item-span">
                                <a class="text-black no-underline" data-toggle="collapse" href="#<?= $instrumentId . $rehearsalId ?>" aria-expanded="false" aria-controls="<?= $instrumentId . $rehearsalId ?>">
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
            <?php endif; ?>
            
            <?php if (!empty($brassPlayers)): ?>
            <li>
                <span class="tree-item-span">
                    <a class="text-black no-underline" data-toggle="collapse" href="#Blechbläser<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Blechbläser<?= $rehearsalId ?>">
                        <i class="collapsed"><i class="fas fa-folder"></i></i>
                        <i class="expanded"><i class="far fa-folder-open"></i></i> Blechbläser
                    </a>
                    <?php 
                        $brassAttending = count(array_filter($brassPlayers, function($m) { return $m['status'] === 'attending'; }));
                        $brassNotAttending = count(array_filter($brassPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                        $brassNoResponse = count(array_filter($brassPlayers, function($m) { return $m['status'] === 'no_response'; }));
                    ?>
                    <a class="rightfloatet"><?= $brassNotAttending ?></a>
                    <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                    <a class="rightfloatet"><?= $brassAttending ?></a>
                    <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                    <a class="rightfloatet"><?= $brassNoResponse ?></a>
                    <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                </span>
                
                <div id="Blechbläser<?= $rehearsalId ?>" class="collapse">
                    <ul>
                        <?php
                        $horns = array_filter($brassPlayers, function($m) { return $m['type'] === 'Horn'; });
                        $trumpets = array_filter($brassPlayers, function($m) { return $m['type'] === 'Trompete'; });
                        $trombones = array_filter($brassPlayers, function($m) { return $m['type'] === 'Posaune'; });
                        $tubas = array_filter($brassPlayers, function($m) { return $m['type'] === 'Tuba'; });
                        
                        $instrumentGroups = [
                            'Hörner' => $horns,
                            'Trompeten' => $trumpets,
                            'Posaunen' => $trombones,
                            'Tuben' => $tubas
                        ];
                        
                        foreach ($instrumentGroups as $instrumentName => $players):
                            if (!empty($players)):
                                $instrumentId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $instrumentName);
                                $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
                                $notAttending = count(array_filter($players, function($m) { return $m['status'] === 'not_attending'; }));
                                $noResponse = count(array_filter($players, function($m) { return $m['status'] === 'no_response'; }));
                        ?>
                        <li>
                            <span class="tree-item-span">
                                <a class="text-black no-underline" data-toggle="collapse" href="#<?= $instrumentId . $rehearsalId ?>" aria-expanded="false" aria-controls="<?= $instrumentId . $rehearsalId ?>">
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
            <?php endif; ?>
        </ul>
    </div>
</li>
