<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'admin']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<div class="container-fluid mt-4">
    <?php if (empty($rehearsals)): ?>
        <div class="alert alert-info">
            Keine Termine gefunden.
        </div>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $attendingCount = $stats[$rehearsalId]['attending'] ?? 0;
                $notAttendingCount = $stats[$rehearsalId]['not_attending'] ?? 0;
                $noResponseCount = $stats[$rehearsalId]['no_response'] ?? 0;
                $collapseTarget = "Orchester" . $rehearsalId;
                $isAdmin = true; // Admin view

                // Group members by instrument family
                $stringPlayers = [];
                $woodwindPlayers = [];
                $brassPlayers = [];
                $percussionPlayers = [];
                $otherPlayers = [];

                if (!empty($membersBySection[$rehearsalId]['all'])) {
                    foreach ($membersBySection[$rehearsalId]['all'] as $member) {
                        $instrument = $member['type'];
                        switch ($instrument) {
                            case 'Violine_1':
                            case 'Violine_2':
                            case 'Bratsche':
                            case 'Cello':
                            case 'Kontrabass':
                                $stringPlayers[] = $member;
                                break;
                            case 'Flöte':
                            case 'Oboe':
                            case 'Klarinette':
                            case 'Fagott':
                                $woodwindPlayers[] = $member;
                                break;
                            case 'Horn':
                            case 'Trompete':
                            case 'Posaune':
                            case 'Tuba':
                                $brassPlayers[] = $member;
                                break;
                            case 'Schlagwerk':
                                $percussionPlayers[] = $member;
                                break;
                            default:
                                $otherPlayers[] = $member;
                                break;
                        }
                    }
                }
            ?>
            
            <div class="tree">
                <ul style="padding-left: 5px;">
                    <li>
                        <?php include __DIR__ . '/../components/rehearsal-header.php'; ?>
                        
                        <div id="Orchester<?= $rehearsalId ?>" class="collapse">
                            <ul>
                                <?php if (!empty($stringPlayers)): ?>
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
                                                                include __DIR__ . '/../components/user-item.php'; 
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
                                
                                <?php if (!empty($woodwindPlayers) || !empty($brassPlayers)): ?>
                                <li>
                                    <span class="tree-item-span">
                                        <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#Bläser<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Bläser<?= $rehearsalId ?>">
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
                                                    <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#Holzbläser<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Holzbläser<?= $rehearsalId ?>">
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
                                                        // Handle individual woodwind instruments
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
                                                                            include __DIR__ . '/../components/user-item.php'; 
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
                                                    <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#Blechbläser<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Blechbläser<?= $rehearsalId ?>">
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
                                                        // Handle individual brass instruments
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
                                                                            include __DIR__ . '/../components/user-item.php'; 
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
                                <?php endif; ?>
                                
                                <?php if (!empty($percussionPlayers)): ?>
                                <li>
                                    <span class="tree-item-span">
                                        <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#Schlagwerk<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Schlagwerk<?= $rehearsalId ?>">
                                            <i class="collapsed"><i class="fas fa-folder"></i></i>
                                            <i class="expanded"><i class="far fa-folder-open"></i></i> Schlagwerk
                                        </a>
                                        <?php 
                                            $percAttending = count(array_filter($percussionPlayers, function($m) { return $m['status'] === 'attending'; }));
                                            $percNotAttending = count(array_filter($percussionPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                                            $percNoResponse = count(array_filter($percussionPlayers, function($m) { return $m['status'] === 'no_response'; }));
                                        ?>
                                        <a class="rightfloatet"><?= $percNotAttending ?></a>
                                        <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                                        <a class="rightfloatet"><?= $percAttending ?></a>
                                        <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                                        <a class="rightfloatet"><?= $percNoResponse ?></a>
                                        <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                                    </span>
                                    
                                    <div id="Schlagwerk<?= $rehearsalId ?>" class="collapse">
                                        <ul>
                                            <?php foreach ($percussionPlayers as $player): ?>
                                                <?php 
                                                    $member = $player;
                                                    $status = $player['status'];
                                                    include __DIR__ . '/../components/user-item.php'; 
                                                ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (!empty($otherPlayers)): ?>
                                <li>
                                    <span class="tree-item-span">
                                        <a style="color:#000; text-decoration:none;" data-toggle="collapse" href="#Andere<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Andere<?= $rehearsalId ?>">
                                            <i class="collapsed"><i class="fas fa-folder"></i></i>
                                            <i class="expanded"><i class="far fa-folder-open"></i></i> Andere
                                        </a>
                                        <?php 
                                            $otherAttending = count(array_filter($otherPlayers, function($m) { return $m['status'] === 'attending'; }));
                                            $otherNotAttending = count(array_filter($otherPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                                            $otherNoResponse = count(array_filter($otherPlayers, function($m) { return $m['status'] === 'no_response'; }));
                                        ?>
                                        <a class="rightfloatet"><?= $otherNotAttending ?></a>
                                        <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                                        <a class="rightfloatet"><?= $otherAttending ?></a>
                                        <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                                        <a class="rightfloatet"><?= $otherNoResponse ?></a>
                                        <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                                    </span>
                                    
                                    <div id="Andere<?= $rehearsalId ?>" class="collapse">
                                        <ul>
                                            <?php foreach ($otherPlayers as $player): ?>
                                                <?php 
                                                    $member = $player;
                                                    $status = $player['status'];
                                                    $additionalInfo = ' (' . str_replace('_', ' ', $player['type']) . ')';
                                                    include __DIR__ . '/../components/user-item.php'; 
                                                ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/assets/js/promises-shared.js"></script> 