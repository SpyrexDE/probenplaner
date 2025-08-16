<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'leader']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<div class="container-fluid mt-4">
    <!-- View Toggle (Leader Only) -->
    <?php if (!empty($rehearsals) && isset($memberPromises) && !empty($_SESSION['role']) && $_SESSION['role'] === 'leader'): ?>
    <div class="d-flex justify-content-end">
        <div class="view-toggle-container" title="<?php echo empty($leadersCanViewAllSections) ? 'Nicht verfügbar: vom Dirigenten deaktiviert' : ''; ?>">
            <span class="toggle-label">Alle Register</span>
            <label class="view-toggle">
                <input type="checkbox" id="viewToggle" <?php echo empty($leadersCanViewAllSections) ? 'disabled' : ''; ?> />
                <span class="toggle-slider"></span>
            </label>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($rehearsals)): ?>
        <?php 
            $title = 'Keine Termine gefunden';
            $message = 'Für deine Gruppe sind aktuell keine Proben geplant.';
            include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $attendingCount = count($memberPromises[$rehearsalId]['attending'] ?? []);
                $notAttendingCount = count($memberPromises[$rehearsalId]['not_attending'] ?? []);
                $noResponseCount = count($memberPromises[$rehearsalId]['no_response'] ?? []);
                $collapseTarget = "Orchester" . $rehearsalId;
                $isAdmin = false; // Leader view
                
                // Prepare data for sectional view - group all members by instrument
                $allMembers = array_merge(
                    $memberPromises[$rehearsalId]['attending'] ?? [],
                    $memberPromises[$rehearsalId]['not_attending'] ?? [], 
                    $memberPromises[$rehearsalId]['no_response'] ?? []
                );
                
                // Group by instrument family (same logic as admin)
                $stringPlayers = [];
                $woodwindPlayers = [];
                $brassPlayers = [];
                $percussionPlayers = [];
                $otherPlayers = [];
                
                foreach ($allMembers as $member) {
                    // Add status to member data for sectional view
                    $memberStatus = 'no_response'; // default
                    $memberUsername = $member['username'] ?? '';
                    
                    // Check which status list this member belongs to
                    foreach (($memberPromises[$rehearsalId]['attending'] ?? []) as $attendingMember) {
                        if (($attendingMember['username'] ?? '') === $memberUsername) {
                            $memberStatus = 'attending';
                            break;
                        }
                    }
                    
                    if ($memberStatus === 'no_response') {
                        foreach (($memberPromises[$rehearsalId]['not_attending'] ?? []) as $notAttendingMember) {
                            if (($notAttendingMember['username'] ?? '') === $memberUsername) {
                                $memberStatus = 'not_attending';
                                break;
                            }
                        }
                    }
                    
                    $memberWithStatus = array_merge($member, ['status' => $memberStatus]);
                    
                    $instrument = $member['type'] ?? '';
                    switch ($instrument) {
                        case 'Violine_1':
                        case 'Violine_2':
                        case 'Bratsche':
                        case 'Cello':
                        case 'Kontrabass':
                            $stringPlayers[] = $memberWithStatus;
                            break;
                        case 'Flöte':
                        case 'Oboe':
                        case 'Klarinette':
                        case 'Fagott':
                            $woodwindPlayers[] = $memberWithStatus;
                            break;
                        case 'Horn':
                        case 'Trompete':
                        case 'Posaune':
                        case 'Tuba':
                            $brassPlayers[] = $memberWithStatus;
                            break;
                        case 'Schlagwerk':
                            $percussionPlayers[] = $memberWithStatus;
                            break;
                        default:
                            $otherPlayers[] = $memberWithStatus;
                            break;
                    }
                }
            ?>
            
            <!-- Simple View (Default) -->
            <div class="simple-view tree">
                <ul style="padding-left: 5px;">
                    <li>
                        <?php include __DIR__ . '/../components/rehearsal-header.php'; ?>
                        
                        <div id="Orchester<?= $rehearsalId ?>" class="collapse">
                            <ul>
                                <?php if (!empty($memberPromises[$rehearsalId]['not_attending'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['not_attending'] as $member): ?>
                                        <?php 
                                            $status = 'not_attending';
                                            include __DIR__ . '/../components/user-item.php'; 
                                        ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($memberPromises[$rehearsalId]['attending'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['attending'] as $member): ?>
                                        <?php 
                                            $status = 'attending';
                                            include __DIR__ . '/../components/user-item.php'; 
                                        ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($memberPromises[$rehearsalId]['no_response'])): ?>
                                    <?php foreach($memberPromises[$rehearsalId]['no_response'] as $member): ?>
                                        <?php 
                                            $status = 'no_response';
                                            include __DIR__ . '/../components/user-item.php'; 
                                        ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
            
            <?php if (!empty($leadersCanViewAllSections)): ?>
            <!-- Sectional View (Hidden by default) -->
            <div class="sectional-view tree" style="display: none;">
                <ul style="padding-left: 5px;">
                    <li>
                        <?php 
                            $collapseTarget = "Orchester" . $rehearsalId . "Sec";
                            include __DIR__ . '/../components/rehearsal-header.php'; 
                        ?>
                        
                        <div id="Orchester<?= $rehearsalId ?>Sec" class="collapse">
                            <ul>
                                <?php if (!empty($stringPlayers)): ?>
                                    <?php 
                                        $membersBySection = [$rehearsalId => ['all' => $stringPlayers]];
                                        include __DIR__ . '/../components/section-strings.php'; 
                                    ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($woodwindPlayers) || !empty($brassPlayers)): ?>
                                    <?php 
                                        include __DIR__ . '/../components/section-winds.php'; 
                                    ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($percussionPlayers)): ?>
                                    <?php 
                                        include __DIR__ . '/../components/section-percussion.php'; 
                                    ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($otherPlayers)): ?>
                                    <?php 
                                        include __DIR__ . '/../components/section-other.php'; 
                                    ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/assets/js/promises-shared.js"></script> 