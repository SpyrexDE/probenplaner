<?php $this->layout('layouts/default', ['title' => 'Rückmeldungen', 'currentPage' => $currentPage ?? 'admin']) ?>

<?php include __DIR__ . '/../components/promises-resources.php'; ?>

<div class="container-app mt-6">
    <?php if (empty($rehearsals)): ?>
        <?php 
            $title = 'Keine Termine gefunden';
            $message = 'Es gibt aktuell keine geplanten Proben.';
            include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <?php foreach ($rehearsals as $rehearsal): ?>
            <?php 
                $rehearsalId = $rehearsal['id'];
                $attendingCount = $stats[$rehearsalId]['attending'] ?? 0;
                $notAttendingCount = $stats[$rehearsalId]['not_attending'] ?? 0;
                $noResponseCount = $stats[$rehearsalId]['no_response'] ?? 0;
                $collapseTarget = "Orchester" . $rehearsalId;
                $isAdmin = true; // Admin view

                // Group members dynamically using GroupManager
                $groupManager = new \App\Core\GroupManager();
                $sectionPlayers = [];

                if (!empty($membersBySection[$rehearsalId]['all'])) {
                    // Get only the top-level sections under 'tutti' to avoid showing subsections at root level
                    $tuttiGroup = $groupManager->getGroup('tutti');
                    $topLevelSections = [];
                    
                    if ($tuttiGroup && isset($tuttiGroup['children'])) {
                        foreach ($tuttiGroup['children'] as $childKey => $child) {
                            if (($child['type'] ?? '') === 'section') {
                                $topLevelSections[$child['id']] = $child;
                            }
                        }
                    }
                    
                    // Group players by top-level section only
                    foreach ($topLevelSections as $sectionId => $sectionData) {
                        $sectionPlayers[$sectionId] = [];
                        
                        foreach ($membersBySection[$rehearsalId]['all'] as $member) {
                            if ($groupManager->isUserInGroup($member['type'], $sectionId)) {
                                $sectionPlayers[$sectionId][] = $member;
                            }
                        }
                    }
                }
            ?>
            
            <div class="tree-view">
                <ul class="tree-list">
                    <li class="tree-node tree-depth-0">
                        <?php include __DIR__ . '/../components/rehearsal-header.php'; ?>
                        
                        <div id="Orchester<?= $rehearsalId ?>" class="tree-node-content collapsed">
                            <ul class="tree-list">
                                        <?php 
                                // Use dynamic section components
                                foreach ($sectionPlayers as $sectionId => $players) {
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
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/assets/js/promises-shared.js"></script> 
