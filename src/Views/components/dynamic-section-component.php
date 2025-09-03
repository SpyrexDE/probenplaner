<?php
/**
 * Dynamic Section Component
 * 
 * Renders a section component dynamically based on GroupManager configuration.
 * This replaces the hardcoded section-specific components.
 * 
 * @param array $players - Array of players with status for this section
 * @param int $rehearsalId - Rehearsal ID for unique element IDs
 * @param string $sectionId - The section ID from the dynamic configuration
 */

use App\Core\GroupManager;

$groupManager = new GroupManager();
$section = $groupManager->getGroup($sectionId);

if (!$section) {
    return; // Invalid section ID
}

$sectionDisplayName = $section['display_name'] ?? $sectionId;

// Calculate totals for this section
$sectionAttending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
$sectionNotAttending = count(array_filter($players, function($m) { return $m['status'] === 'not_attending'; }));
$sectionNoResponse = count(array_filter($players, function($m) { return $m['status'] === 'no_response'; }));

// Generate unique element ID
$sectionElementId = $sectionId . $rehearsalId;
?>

<li class="tree-node tree-depth-1">
    <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $sectionElementId ?>" aria-expanded="false" aria-controls="<?= $sectionElementId ?>">
        <i class="tree-node-icon fas fa-chevron-right"></i>
        
        <div class="tree-node-title">
            <span class="tree-node-title-text"><?= htmlspecialchars($sectionDisplayName) ?></span>
        </div>
        
        <div class="tree-node-stats">
            <div class="tree-node-stat">
                <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
                <span><?= $sectionNoResponse ?></span>
            </div>
            <div class="tree-node-stat">
                <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
                <span><?= $sectionAttending ?></span>
            </div>
            <div class="tree-node-stat">
                <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
                <span><?= $sectionNotAttending ?></span>
            </div>
        </div>
    </button>
    
    <div id="<?= $sectionElementId ?>" class="tree-node-content collapse">
        <ul class="tree-list">
            <?php
            // Get section structure to organize instruments
            $sectionTree = $groupManager->getTreeForComponent($sectionId);
            
            // Debug: check what we got for this section
            // error_log("Section $sectionId tree: " . print_r($sectionTree, true));
            
            if (isset($sectionTree['children']) && is_array($sectionTree['children']) && !empty($sectionTree['children'])) {
                // This section has sub-sections (hierarchical structure)
                foreach ($sectionTree['children'] as $subSectionKey => $subSection):
                    if ($subSection['type'] === 'section' && isset($subSection['children'])):
                        // Filter players for this sub-section
                        $subSectionInstruments = array_map(fn($inst) => $inst['id'], $subSection['children']);
                        $subSectionPlayers = array_filter($players, function($p) use ($subSectionInstruments, $groupManager) {
                            return in_array($groupManager->resolveAlias($p['type']), $subSectionInstruments);
                        });
                        
                        if (!empty($subSectionPlayers)):
                            $subSectionAttending = count(array_filter($subSectionPlayers, function($m) { return $m['status'] === 'attending'; }));
                            $subSectionNotAttending = count(array_filter($subSectionPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                            $subSectionNoResponse = count(array_filter($subSectionPlayers, function($m) { return $m['status'] === 'no_response'; }));
                            $subSectionElementId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $subSection['id']) . $rehearsalId;
                ?>
                <li class="tree-node tree-depth-2">
                    <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $subSectionElementId ?>" aria-expanded="false" aria-controls="<?= $subSectionElementId ?>">
                        <i class="tree-node-icon fas fa-chevron-right"></i>
                        
                        <div class="tree-node-title">
                            <span class="tree-node-title-text"><?= htmlspecialchars($subSection['display_name']) ?></span>
                        </div>
                        
                        <div class="tree-node-stats">
                            <div class="tree-node-stat">
                                <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
                                <span><?= $subSectionNoResponse ?></span>
                            </div>
                            <div class="tree-node-stat">
                                <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
                                <span><?= $subSectionAttending ?></span>
                            </div>
                            <div class="tree-node-stat">
                                <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
                                <span><?= $subSectionNotAttending ?></span>
                            </div>
                        </div>
                    </button>
                    
                    <div id="<?= $subSectionElementId ?>" class="tree-node-content collapse">
                        <ul class="tree-list">
                            <?php
                            // Group players by instrument type
                            $instrumentGroups = [];
                            foreach ($subSection['children'] as $instrumentKey => $instrument) {
                                if ($instrument['type'] === 'instrument') {
                                    $instrumentPlayers = array_filter($subSectionPlayers, function($p) use ($instrument, $groupManager) {
                                        return $groupManager->resolveAlias($p['type']) === $instrument['id'];
                                    });
                                    
                                    if (!empty($instrumentPlayers)) {
                                        $instrumentGroups[$instrument['display_name']] = $instrumentPlayers;
                                    }
                                }
                            }
                            
                            foreach ($instrumentGroups as $instrumentName => $instrumentPlayers):
                                $attending = count(array_filter($instrumentPlayers, function($m) { return $m['status'] === 'attending'; }));
                                $notAttending = count(array_filter($instrumentPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                                $noResponse = count(array_filter($instrumentPlayers, function($m) { return $m['status'] === 'no_response'; }));
                                $instrumentElementId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $instrumentName) . $rehearsalId;
                            ?>
                            <li class="tree-node tree-depth-3">
                                <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $instrumentElementId ?>" aria-expanded="false" aria-controls="<?= $instrumentElementId ?>">
                                    <i class="tree-node-icon fas fa-chevron-right"></i>
                                    
                                    <div class="tree-node-title">
                                        <span class="tree-node-title-text"><?= htmlspecialchars($instrumentName) ?></span>
                                    </div>
                                    
                                    <div class="tree-node-stats">
                                        <div class="tree-node-stat">
                                            <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
                                            <span><?= $noResponse ?></span>
                                        </div>
                                        <div class="tree-node-stat">
                                            <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
                                            <span><?= $attending ?></span>
                                        </div>
                                        <div class="tree-node-stat">
                                            <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
                                            <span><?= $notAttending ?></span>
                                        </div>
                                    </div>
                                </button>
                                
                                <div id="<?= $instrumentElementId ?>" class="tree-node-content collapse">
                                    <ul class="tree-list">
                                        <?php 
                                        // Sort users by status: not_attending first, then attending, then no_response
                                        usort($instrumentPlayers, function($a, $b) {
                                            $statusOrder = ['not_attending' => 0, 'attending' => 1, 'no_response' => 2];
                                            $aOrder = $statusOrder[$a['status']] ?? 3;
                                            $bOrder = $statusOrder[$b['status']] ?? 3;
                                            if ($aOrder === $bOrder) {
                                                return strcasecmp($a['username'] ?? '', $b['username'] ?? ''); // Secondary sort by username
                                            }
                                            return $aOrder - $bOrder;
                                        });
                                        
                                        foreach ($instrumentPlayers as $player): ?>
                                            <?php 
                                                $member = $player;
                                                $status = $player['status'];
                                                include __DIR__ . '/user-item.php'; 
                                            ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
                <?php 
                        endif;
                    elseif ($subSection['type'] === 'instrument'):
                        // Direct instrument under this section - create a collapsible instrument node
                        $instrumentPlayers = array_filter($players, function($p) use ($subSection, $groupManager) {
                            return $groupManager->resolveAlias($p['type']) === $subSection['id'];
                        });
                        
                        if (!empty($instrumentPlayers)):
                            $attending = count(array_filter($instrumentPlayers, function($m) { return $m['status'] === 'attending'; }));
                            $notAttending = count(array_filter($instrumentPlayers, function($m) { return $m['status'] === 'not_attending'; }));
                            $noResponse = count(array_filter($instrumentPlayers, function($m) { return $m['status'] === 'no_response'; }));
                            $instrumentElementId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $subSection['id']) . $rehearsalId;
                        ?>
                        <li class="tree-node tree-depth-2">
                            <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $instrumentElementId ?>" aria-expanded="false" aria-controls="<?= $instrumentElementId ?>">
                                <i class="tree-node-icon fas fa-chevron-right"></i>
                                
                                <div class="tree-node-title">
                                    <span class="tree-node-title-text"><?= htmlspecialchars($subSection['display_name']) ?></span>
                                </div>
                                
                                <div class="tree-node-stats">
                                    <div class="tree-node-stat">
                                        <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
                                        <span><?= $noResponse ?></span>
                                    </div>
                                    <div class="tree-node-stat">
                                        <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
                                        <span><?= $attending ?></span>
                                    </div>
                                    <div class="tree-node-stat">
                                        <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
                                        <span><?= $notAttending ?></span>
                                    </div>
                                </div>
                            </button>
                            
                            <div id="<?= $instrumentElementId ?>" class="tree-node-content collapse">
                                <ul class="tree-list">
                                    <?php
                                    // Sort users by status: not_attending first, then attending, then no_response
                                    usort($instrumentPlayers, function($a, $b) {
                                        $statusOrder = ['not_attending' => 0, 'attending' => 1, 'no_response' => 2];
                                        $aOrder = $statusOrder[$a['status']] ?? 3;
                                        $bOrder = $statusOrder[$b['status']] ?? 3;
                                        if ($aOrder === $bOrder) {
                                            return strcasecmp($a['username'] ?? '', $b['username'] ?? ''); // Secondary sort by username
                                        }
                                        return $aOrder - $bOrder;
                                    });
                                    
                                    foreach ($instrumentPlayers as $player): ?>
                                        <?php 
                                            $member = $player;
                                            $status = $player['status'];
                                            $additionalInfo = ''; // Clear any previous additionalInfo
                                            include __DIR__ . '/user-item.php'; 
                                        ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                        <?php endif;
                    endif;
                endforeach;
            } else {
                // Simple section with direct instruments (like Schlagwerk, Andere)
                // Sort users by status: not_attending first, then attending, then no_response
                usort($players, function($a, $b) {
                    $statusOrder = ['not_attending' => 0, 'attending' => 1, 'no_response' => 2];
                    $aOrder = $statusOrder[$a['status']] ?? 3;
                    $bOrder = $statusOrder[$b['status']] ?? 3;
                    if ($aOrder === $bOrder) {
                        return strcasecmp($a['username'] ?? '', $b['username'] ?? ''); // Secondary sort by username
                    }
                    return $aOrder - $bOrder;
                });
                
                // Simple section - show users directly
                foreach ($players as $player):
                    $member = $player;
                    $status = $player['status'];
                    $additionalInfo = '';
                    include __DIR__ . '/user-item.php';
                endforeach;
            }
            ?>
        </ul>
    </div>
</li>
