<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'leader']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<div class="container-app mt-6">
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
                
                // Group by sections dynamically using GroupManager
                $groupManager = new \App\Core\GroupManager();
                $allSections = $groupManager->getAllSections();
                $sectionPlayers = [];
                
                // Initialize section arrays dynamically
                foreach ($allSections as $sectionId => $sectionData) {
                    $sectionPlayers[$sectionId] = [];
                }
                
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
                    
                    // Dynamically determine which sections this member belongs to
                    $userType = $groupManager->resolveAlias($member['type'] ?? '');
                    
                    foreach ($allSections as $sectionId => $sectionData) {
                        if ($groupManager->isUserInGroup($userType, $sectionId)) {
                            $sectionPlayers[$sectionId][] = $memberWithStatus;
                        }
                    }
                }
            ?>
            
            <!-- Simple View (Default) -->
            <div class="simple-view tree">
                <ul class="pl-1">
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
            <div class="sectional-view tree hidden">
                <ul class="pl-1">
                    <li>
                        <?php 
                            $collapseTarget = "Orchester" . $rehearsalId . "Sec";
                            include __DIR__ . '/../components/rehearsal-header.php'; 
                        ?>
                        
                        <div id="Orchester<?= $rehearsalId ?>Sec" class="collapse">
                            <ul>
                                <?php 
                                // Use the dynamically grouped section players from above
                                foreach ($sectionPlayers as $sectionId => $players) {
                                    // Only render section if there are players
                                    if (!empty($players)) {
                                        include __DIR__ . '/../components/dynamic-section-component.php';
                                    }
                                }
                                ?>
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