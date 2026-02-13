<?php
/**
 * Modern Section Component - Enterprise Dashboard Style
 * 
 * Card-based section component for orchestra attendance management.
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
$sectionTotal = count($players);

// Calculate percentages
$attendanceRate = $sectionTotal > 0 ? ($sectionAttending / $sectionTotal) * 100 : 0;
$responseRate = $sectionTotal > 0 ? (($sectionAttending + $sectionNotAttending) / $sectionTotal) * 100 : 0;

// Determine section health status
$sectionStatus = 'good';
if ($attendanceRate < 50) {
    $sectionStatus = 'critical';
} elseif ($attendanceRate < 70) {
    $sectionStatus = 'warning';
}

// Generate unique element ID
$sectionElementId = 'section-' . preg_replace('/[^a-zA-Z0-9]/', '', $sectionId) . '-' . $rehearsalId;
?>

<div class="section-card <?= $sectionStatus ?> expandable" data-section="<?= htmlspecialchars($sectionId) ?>">
    <div class="section-header" onclick="toggleSection('<?= $sectionElementId ?>')">
        <div class="section-title-row">
            <h3 class="section-name"><?= htmlspecialchars($sectionDisplayName) ?></h3>
            <div class="section-expand-icon">
                <i class="fas fa-chevron-right"></i>
            </div>
        </div>
        
        <div class="section-stats-row">
            <div class="section-progress-container">
                <div class="section-progress">
                    <?php if ($sectionTotal > 0): ?>
                        <div class="progress-segment attending" style="width: <?= ($sectionAttending / $sectionTotal) * 100 ?>%"></div>
                        <div class="progress-segment not-attending" style="width: <?= ($sectionNotAttending / $sectionTotal) * 100 ?>%"></div>
                        <div class="progress-segment no-response" style="width: <?= ($sectionNoResponse / $sectionTotal) * 100 ?>%"></div>
                    <?php endif; ?>
                </div>
                
                <div class="section-stats-numbers">
                    <div class="section-stat-item">
                        <div class="section-stat-dot attending"></div>
                        <span><?= $sectionAttending ?></span>
                    </div>
                    <div class="section-stat-item">
                        <div class="section-stat-dot not-attending"></div>
                        <span><?= $sectionNotAttending ?></span>
                    </div>
                    <div class="section-stat-item">
                        <div class="section-stat-dot no-response"></div>
                        <span><?= $sectionNoResponse ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="<?= $sectionElementId ?>" class="section-content">
        <div class="players-container">
            <?php
            // Get section structure to organize instruments
            $sectionTree = $groupManager->getTreeForComponent($sectionId);
            
            if (isset($sectionTree['children']) && is_array($sectionTree['children']) && !empty($sectionTree['children'])) {
                // This section has sub-sections (hierarchical structure)
                
                // Group instruments by subsection
                $subsections = [];
                
                foreach ($sectionTree['children'] as $subSectionKey => $subSection) {
                    if ($subSection['type'] === 'section' && isset($subSection['children'])) {
                        // Filter players for this sub-section
                        $subSectionInstruments = array_map(fn($inst) => $inst['id'], $subSection['children']);
                        $subSectionPlayers = array_filter($players, function($p) use ($subSectionInstruments, $groupManager) {
                            return in_array($groupManager->resolveAlias($p['type']), $subSectionInstruments);
                        });
                        
                        if (!empty($subSectionPlayers)) {
                            $subsections[$subSection['display_name']] = [
                                'players' => $subSectionPlayers,
                                'instruments' => $subSection['children']
                            ];
                        }
                    } elseif ($subSection['type'] === 'instrument') {
                        // Direct instrument under this section
                        $instrumentPlayers = array_filter($players, function($p) use ($subSection, $groupManager) {
                            return $groupManager->resolveAlias($p['type']) === $subSection['id'];
                        });
                        
                        if (!empty($instrumentPlayers)) {
                            $subsections[$subSection['display_name']] = [
                                'players' => $instrumentPlayers,
                                'instruments' => [$subSection]
                            ];
                        }
                    }
                }
                
                // Display subsections
                foreach ($subsections as $subsectionName => $subsectionData):
                    $subsectionPlayers = $subsectionData['players'];
                    $instruments = $subsectionData['instruments'];
                    
                    // Group players by instrument within subsection
                    $instrumentGroups = [];
                    foreach ($instruments as $instrument) {
                        if ($instrument['type'] === 'instrument') {
                            $instrumentPlayers = array_filter($subsectionPlayers, function($p) use ($instrument, $groupManager) {
                                return $groupManager->resolveAlias($p['type']) === $instrument['id'];
                            });
                            
                            if (!empty($instrumentPlayers)) {
                                $instrumentGroups[$instrument['display_name']] = $instrumentPlayers;
                            }
                        }
                    }
                    
                    if (!empty($instrumentGroups)):
                ?>
                
                <div class="instrument-group">
                    <div class="instrument-header">
                        <h4 class="instrument-name"><?= htmlspecialchars($subsectionName) ?></h4>
                        <span class="instrument-count"><?= count($subsectionPlayers) ?> Spieler</span>
                    </div>
                    
                    <?php foreach ($instrumentGroups as $instrumentName => $instrumentPlayers): 
                        // Sort players by status: not_attending first, then attending, then no_response
                        usort($instrumentPlayers, function($a, $b) {
                            $statusOrder = ['not_attending' => 0, 'attending' => 1, 'no_response' => 2];
                            $aOrder = $statusOrder[$a['status']] ?? 3;
                            $bOrder = $statusOrder[$b['status']] ?? 3;
                            if ($aOrder === $bOrder) {
                                return strcasecmp($a['username'] ?? '', $b['username'] ?? '');
                            }
                            return $aOrder - $bOrder;
                        });
                    ?>
                    
                    <?php if (count($instrumentGroups) > 1): ?>
                        <div class="sub-instrument-header">
                            <h5 class="sub-instrument-name"><?= htmlspecialchars($instrumentName) ?></h5>
                        </div>
                    <?php endif; ?>
                    
                    <div class="players-list">
                        <?php foreach ($instrumentPlayers as $player): ?>
                            <div class="player-item">
                                <div class="player-status-icon <?= $player['status'] ?>">
                                    <?php if ($player['status'] === 'attending'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif ($player['status'] === 'not_attending'): ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-question-circle"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="player-info">
                                    <div class="player-name">
                                        <?= htmlspecialchars($player['username'] ?? $player['name'] ?? 'Unbekannt') ?>
                                        <?php if (!empty($player['badges'])): ?>
                                            <?php foreach ($player['badges'] as $badge): ?>
                                                <span class="user-badge">
                                                    <i class="<?= $badge['icon'] ?>"></i>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($player['note'])): ?>
                                        <div class="player-additional">
                                            Notiz: <?= htmlspecialchars($player['note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php endforeach; ?>
                </div>
                
                <?php 
                    endif;
                endforeach;
            } else {
                // Simple section with direct instruments (like Schlagwerk, Andere)
                // Sort all players by status
                usort($players, function($a, $b) {
                    $statusOrder = ['not_attending' => 0, 'attending' => 1, 'no_response' => 2];
                    $aOrder = $statusOrder[$a['status']] ?? 3;
                    $bOrder = $statusOrder[$b['status']] ?? 3;
                    if ($aOrder === $bOrder) {
                        return strcasecmp($a['username'] ?? '', $b['username'] ?? '');
                    }
                    return $aOrder - $bOrder;
                });
                ?>
                
                <div class="instrument-group">
                    <div class="instrument-header">
                        <h4 class="instrument-name"><?= htmlspecialchars($sectionDisplayName) ?></h4>
                        <span class="instrument-count"><?= count($players) ?> Spieler</span>
                    </div>
                    
                    <div class="players-list">
                        <?php foreach ($players as $player): ?>
                            <div class="player-item">
                                <div class="player-status-icon <?= $player['status'] ?>">
                                    <?php if ($player['status'] === 'attending'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php elseif ($player['status'] === 'not_attending'): ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-question-circle"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="player-info">
                                    <div class="player-name">
                                        <?= htmlspecialchars($player['username'] ?? $player['name'] ?? 'Unbekannt') ?>
                                        <?php if ($sectionId === 'Andere' && !empty($player['type'])): ?>
                                            <span class="player-instrument">(<?= str_replace('_', ' ', htmlspecialchars($player['type'])) ?>)</span>
                                        <?php endif; ?>
                                        <?php if (!empty($player['badges'])): ?>
                                            <?php foreach ($player['badges'] as $badge): ?>
                                                <span class="user-badge">
                                                    <i class="<?= $badge['icon'] ?>"></i>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($player['note'])): ?>
                                        <div class="player-additional">
                                            Notiz: <?= htmlspecialchars($player['note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php
            }
            ?>
        </div>
    </div>
</div>

<script>
// Add this to handle section expansion
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const card = section.closest('.section-card');
    
    if (card.classList.contains('expanded')) {
        card.classList.remove('expanded');
        section.style.maxHeight = '0px';
    } else {
        card.classList.add('expanded');
        section.style.maxHeight = section.scrollHeight + 'px';
        
        // Auto-adjust when content changes
        setTimeout(() => {
            section.style.maxHeight = section.scrollHeight + 'px';
        }, 300);
    }
}

// Handle responsive section heights
window.addEventListener('resize', function() {
    document.querySelectorAll('.section-card.expanded .section-content').forEach(section => {
        section.style.maxHeight = section.scrollHeight + 'px';
    });
});
</script>

<style>
/* Additional styles specific to this component */
.sub-instrument-header {
    margin: var(--space-4) 0 var(--space-3) 0;
    padding-left: var(--space-3);
    border-left: 3px solid var(--color-primary-200);
}

.sub-instrument-name {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-secondary);
    margin: 0;
}

.player-instrument {
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    font-weight: var(--font-weight-normal);
    margin-left: var(--space-1);
}
</style>
