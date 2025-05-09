<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'admin']) ?>

<!-- Custom styling for admin view -->
<style>
/* Make elements unselectable */
* {
    -webkit-touch-callout: none; /* iOS Safari */
    -webkit-user-select: none;   /* Safari */
    -khtml-user-select: none;    /* Konqueror HTML */
    -moz-user-select: none;      /* Firefox */
    -ms-user-select: none;       /* Internet Explorer/Edge */
    user-select: none;           /* Non-prefixed version, currently supported by Chrome and Opera */
}

/* Allow selection only in input/textarea elements */
input, textarea {
    -webkit-touch-callout: text;
    -webkit-user-select: text;
    -khtml-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
    user-select: text;
}

/* Style the user spans to look more clickable */
.userSpan {
    cursor: pointer;
    padding: 2px 0;
}

.userSpan:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

/* Fix the icon colors to match exactly */
.fa-check-circle {
    color: #50dc36 !important;
}

.fa-times-circle {
    color: #dc3836 !important;
}

/* Adjust tree styling for better visibility */
.tree {
    margin-bottom: 20px;
}

.tree ul {
    list-style-type: none;
}

.tree-item-span {
    display: block;
    padding: 3px 0;
}

.tree-item-span:hover {
    background-color: rgba(0, 0, 0, 0.03);
}
</style>

<div class="container-fluid mt-4">
    <?php if (empty($rehearsals)): ?>
        <div class="alert alert-info">
            Keine Termine gefunden.
        </div>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $date = $rehearsal['date'];
                $time = $rehearsal['time'];
                $location = $rehearsal['location'] ?? 'TBA';

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
                    $rehearsalType .= ' (Kleingruppenprobe)';
                }

                // Get counts for each status
                $attendingCount = $stats[$rehearsalId]['attending'] ?? 0;
                $notAttendingCount = $stats[$rehearsalId]['not_attending'] ?? 0;
                $noResponseCount = $stats[$rehearsalId]['no_response'] ?? 0;

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
                        <span class="tree-item-span">
                            <a style="color:#000; text-decoration:none; background-color: <?= !empty($rehearsal['color']) ? $rehearsal['color'] : 'white' ?>;" data-toggle="collapse" href="#Orchester<?= $rehearsalId ?>" aria-expanded="false" aria-controls="Orchester<?= $rehearsalId ?>">
                                <i class="collapsed"><i class="fas fa-folder"></i></i>
                                <i class="expanded"><i class="far fa-folder-open"></i></i> 
                                <?= htmlspecialchars($date) ?> - <?= htmlspecialchars($time) ?>
                                <?php if (!empty($rehearsalType)): ?>
                                    - <?= htmlspecialchars($rehearsalType) ?>
                                <?php endif; ?>
                            </a>
                            <a class="rightfloatet"><?= $notAttendingCount ?></a>
                            <i class="fas fa-times-circle treeIcon rightfloatet"></i>
                            <a class="rightfloatet"><?= $attendingCount ?></a>
                            <i class="fas fa-check-circle treeIcon rightfloatet"></i>
                            <a class="rightfloatet"><?= $noResponseCount ?></a>
                            <i class="fas fa-question-circle treeIcon rightfloatet"></i>
                        </span>
                        
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
                                                        <li>
                                                            <span class="userSpan">
                                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                                <?= htmlspecialchars($player['username']) ?>
                                                                <?php if (!empty($player['note'])): ?>
                                                                    - <?= htmlspecialchars($player['note']) ?>
                                                                <?php endif; ?>
                                                                <?php if ($player['status'] === 'attending'): ?>
                                                                    <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                                                <?php elseif ($player['status'] === 'not_attending'): ?>
                                                                    <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                                                <?php endif; ?>
                                                            </span>
                                                        </li>
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
                                                                    <li>
                                                                        <span class="userSpan">
                                                                            <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                                            <?= htmlspecialchars($player['username']) ?>
                                                                            <?php if (!empty($player['note'])): ?>
                                                                                - <?= htmlspecialchars($player['note']) ?>
                                                                            <?php endif; ?>
                                                                            <?php if ($player['status'] === 'attending'): ?>
                                                                                <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                                                            <?php elseif ($player['status'] === 'not_attending'): ?>
                                                                                <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                                                            <?php else: ?>
                                                                                <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </li>
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
                                                                    <li>
                                                                        <span class="userSpan">
                                                                            <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                                            <?= htmlspecialchars($player['username']) ?>
                                                                            <?php if (!empty($player['note'])): ?>
                                                                                - <?= htmlspecialchars($player['note']) ?>
                                                                            <?php endif; ?>
                                                                            <?php if ($player['status'] === 'attending'): ?>
                                                                                <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                                                            <?php elseif ($player['status'] === 'not_attending'): ?>
                                                                                <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                                                            <?php else: ?>
                                                                                <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                                                            <?php endif; ?>
                                                                        </span>
                                                                    </li>
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
                                            <li>
                                                <span class="userSpan">
                                                    <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                    <?= htmlspecialchars($player['username']) ?>
                                                    <?php if (!empty($player['note'])): ?>
                                                        - <?= htmlspecialchars($player['note']) ?>
                                                    <?php endif; ?>
                                                    <?php if ($player['status'] === 'attending'): ?>
                                                        <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                                    <?php elseif ($player['status'] === 'not_attending'): ?>
                                                        <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </li>
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
                                            <li>
                                                <span class="userSpan">
                                                    <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                    <?= htmlspecialchars($player['username']) ?> (<?= str_replace('_', ' ', $player['type']) ?>)
                                                    <?php if (!empty($player['note'])): ?>
                                                        - <?= htmlspecialchars($player['note']) ?>
                                                    <?php endif; ?>
                                                    <?php if ($player['status'] === 'attending'): ?>
                                                        <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                                    <?php elseif ($player['status'] === 'not_attending'): ?>
                                                        <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                                    <?php endif; ?>
                                                </span>
                                            </li>
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

<script>
// Remove showOldRehearsals event handler as it's now handled by the history icon

// Initialize collapse controls
document.addEventListener('DOMContentLoaded', function() {
    // Expand/collapse behavior for folder icons
    const folderIcons = document.querySelectorAll('.tree a[data-toggle="collapse"]');
    folderIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
        });
    });
    
    // Add click handler for userSpan elements
    const userSpans = document.querySelectorAll('.userSpan');
    userSpans.forEach(span => {
        span.style.cursor = 'pointer';
        
        span.addEventListener('click', function(e) {
            // Prevent click from affecting parent elements
            e.stopPropagation();
            
            // Extract user information
            const username = this.innerText.split('-')[0].trim();
            
            // Get user attendance statistics
            const getUserStats = () => {
                // Find all instances of this username in the document
                const userSpans = Array.from(document.querySelectorAll('.userSpan')).filter(span => 
                    span.textContent.includes(username)
                );
                
                // Count each status type
                let attending = 0;
                let notAttending = 0;
                let noResponse = 0;
                
                userSpans.forEach(span => {
                    if (span.querySelector('.fa-check-circle')) {
                        attending++;
                    } else if (span.querySelector('.fa-times-circle')) {
                        notAttending++;
                    } else if (span.querySelector('.fa-question-circle')) {
                        noResponse++;
                    }
                });
                
                return { attending, notAttending, noResponse };
            };
            
            const stats = getUserStats();
            
            // Show SweetAlert with user statistics
            Swal.fire({
                title: username,
                html: `
                    <div style="text-align: center; margin-bottom: 15px;">
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-check-circle" style="color: #50dc36; font-size: 24px;"></i>
                            <div><strong>${stats.attending}</strong></div>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-times-circle" style="color: #dc3836; font-size: 24px;"></i>
                            <div><strong>${stats.notAttending}</strong></div>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <i class="fas fa-question-circle" style="color: gray; font-size: 24px;"></i>
                            <div><strong>${stats.noResponse}</strong></div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Passwort zurücksetzen',
                confirmButtonColor: '#3085d6',
                denyButtonText: 'Account löschen',
                denyButtonColor: '#d33',
                cancelButtonText: 'Abbrechen',
            }).then((result) => {
                if (result.isDenied) {
                    deleteAcc(username);
                } else if (result.isConfirmed) {
                    resetPW(username);
                }
            });
        });
    });
    
    // Helper function to delete an account
    function deleteAcc(username) {
        Swal.fire({
            title: "Account Löschen",
            html: "Willst du den Account von " + username + " wirklich löschen?<br>Wir können keine Daten wiederherstellen!",
            showCancelButton: true,
            confirmButtonText: "Löschen",
            confirmButtonColor: '#d33', // Red button for deletion
            cancelButtonText: "Abbrechen",
            icon: 'warning'
        }).then((result) => {
            if (result.isConfirmed) {
                // Use the MVC controller endpoint
                fetch('/user/deleteUser?username=' + encodeURIComponent(username))
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.error || 'Server returned ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Show success message
                        // Use toast notification for success
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didClose: () => {
                                // Reload the page to reflect the account deletion
                                window.location.reload();
                            }
                        });
                        
                        Toast.fire({
                            icon: "success",
                            title: data.message
                        });
                    })
                    .catch(error => {
                        console.error('Error deleting account:', error);
                        // Use toast notification for error
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: "error",
                            title: error.message || "Die Anfrage konnte nicht verarbeitet werden."
                        });
                    });
            }
        });
    }
    
    // Helper function to reset a password
    function resetPW(username) {
        Swal.fire({
            title: "Passwort zurücksetzen",
            text: "Willst du das Passwort von " + username + " wirklich zurücksetzen?\nWir können keine Daten wiederherstellen!",
            showCancelButton: true,
            confirmButtonText: "Zurücksetzen",
            confirmButtonColor: '#3085d6',
            cancelButtonText: "Abbrechen",
        }).then((result) => {
            if (result.isConfirmed) {
                // Use the MVC controller endpoint
                fetch('/user/resetPassword?username=' + encodeURIComponent(username))
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.error || 'Server returned ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Use toast notification for success
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 10000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: "success",
                            title: data.message
                        });
                    })
                    .catch(error => {
                        console.error('Error resetting password:', error);
                        // Use toast notification for error
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 10000,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: "error",
                            title: error.message || "Die Anfrage konnte nicht verarbeitet werden."
                        });
                    });
            }
        });
    }
});
</script> 