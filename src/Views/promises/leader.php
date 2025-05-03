<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'leader']) ?>

<div class="container-fluid mt-4">

    <?php if (empty($rehearsals)): ?>
        <div class="alert alert-info">
            Keine Termine für deine Gruppe gefunden.
        </div>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $date = $rehearsal['date'];
                $time = $rehearsal['time'];
                $location = $rehearsal['location'] ?? 'TBA';

                // Determine rehearsal type
                $groups = json_decode($rehearsal['groups_data'] ?? '{}', true);
                $groupKeys = array_keys($groups);
                $rehearsalType = '';
                
                if (in_array('Stimmprobe', $groupKeys)) {
                    $rehearsalType = 'Stimmprobe';
                } elseif (in_array('Konzert', $groupKeys)) {
                    $rehearsalType = 'Konzert';
                } elseif (in_array('Generalprobe', $groupKeys)) {
                    $rehearsalType = 'Generalprobe';
                } elseif (in_array('Konzertreise', $groupKeys)) {
                    $rehearsalType = 'Konzertreise';
                }
                
                $isSmallGroup = strpos(implode(',', $groupKeys), '*') !== false;
                if ($isSmallGroup) {
                    $rehearsalType = 'Kleingruppenprobe';
                }

                $attendingCount = count($memberPromises[$rehearsalId]['attending'] ?? []);
                $notAttendingCount = count($memberPromises[$rehearsalId]['not_attending'] ?? []);
                $noResponseCount = count($memberPromises[$rehearsalId]['no_response'] ?? []);
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
                                <?php if (!empty($memberPromises[$rehearsalId]['not_attending'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['not_attending'] as $member): ?>
                                        <li>
                                            <span class="userSpan">
                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                <?= htmlspecialchars($member['username']) ?>
                                                <?php if (!empty($member['note'])): ?>
                                                    - <?= htmlspecialchars($member['note']) ?>
                                                <?php endif; ?>
                                                <i class="fas fa-times-circle smallTreeIcon rightfloatet" style="color: red;"></i>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($memberPromises[$rehearsalId]['attending'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['attending'] as $member): ?>
                                        <li>
                                            <span class="userSpan">
                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                <?= htmlspecialchars($member['username']) ?>
                                                <?php if (!empty($member['note'])): ?>
                                                    - <?= htmlspecialchars($member['note']) ?>
                                                <?php endif; ?>
                                                <i class="fas fa-check-circle smallTreeIcon rightfloatet" style="color: green;"></i>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($memberPromises[$rehearsalId]['no_response'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['no_response'] as $member): ?>
                                        <li>
                                            <span class="userSpan">
                                                <i class="fas fa-user" style="zoom: 0.8; margin-right: 5px;"></i> 
                                                <?= htmlspecialchars($member['username']) ?>
                                                <i class="fas fa-question-circle smallTreeIcon rightfloatet" style="color: gray;"></i>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
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
});
</script> 