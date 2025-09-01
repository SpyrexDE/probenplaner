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

<li>
    <span class="tree-item-span">
        <a class="text-black no-underline" data-toggle="collapse" href="#<?= $sectionElementId ?>" aria-expanded="false" aria-controls="<?= $sectionElementId ?>">
            <i class="collapsed"><i ><?= icon('folder', 'text-gray-600') ?>></i></i>
            <i class="expanded"><i class="far fa-folder-open"></i></i> <?= htmlspecialchars($sectionDisplayName) ?>
        </a>
        <a class="rightfloatet"><?= $sectionNotAttending ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('times-circle', 'text-gray-600') ?>></i>
        <a class="rightfloatet"><?= $sectionAttending ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('check-circle', 'text-gray-600') ?>></i>
        <a class="rightfloatet"><?= $sectionNoResponse ?></a>
        <i class=" treeIcon rightfloatet"><?= icon('question-circle', 'text-gray-600') ?>></i>
    </span>
    
    <div id="<?= $sectionElementId ?>" class="collapse">
        <ul>
            <?php
            // Get section structure to organize instruments
            $sectionTree = $groupManager->getTreeForComponent($sectionId);
            
            if (isset($sectionTree['children']) && is_array($sectionTree['children'])) {
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
                <li>
                    <span class="tree-item-span">
                        <a class="text-black no-underline" data-toggle="collapse" href="#<?= $subSectionElementId ?>" aria-expanded="false" aria-controls="<?= $subSectionElementId ?>">
                            <i class="collapsed"><i ><?= icon('folder', 'text-gray-600') ?>></i></i>
                            <i class="expanded"><i class="far fa-folder-open"></i></i> <?= htmlspecialchars($subSection['display_name']) ?>
                        </a>
                        <a class="rightfloatet"><?= $subSectionNotAttending ?></a>
                        <i class=" treeIcon rightfloatet"><?= icon('times-circle', 'text-gray-600') ?>></i>
                        <a class="rightfloatet"><?= $subSectionAttending ?></a>
                        <i class=" treeIcon rightfloatet"><?= icon('check-circle', 'text-gray-600') ?>></i>
                        <a class="rightfloatet"><?= $subSectionNoResponse ?></a>
                        <i class=" treeIcon rightfloatet"><?= icon('question-circle', 'text-gray-600') ?>></i>
                    </span>
                    
                    <div id="<?= $subSectionElementId ?>" class="collapse">
                        <ul>
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
                            <li>
                                <span class="tree-item-span">
                                    <a class="text-black no-underline" data-toggle="collapse" href="#<?= $instrumentElementId ?>" aria-expanded="false" aria-controls="<?= $instrumentElementId ?>">
                                        <i class="collapsed"><i ><?= icon('folder', 'text-gray-600') ?>></i></i>
                                        <i class="expanded"><i class="far fa-folder-open"></i></i> <?= htmlspecialchars($instrumentName) ?>
                                    </a>
                                    <a class="rightfloatet"><?= $notAttending ?></a>
                                    <i class=" treeIcon rightfloatet"><?= icon('times-circle', 'text-gray-600') ?>></i>
                                    <a class="rightfloatet"><?= $attending ?></a>
                                    <i class=" treeIcon rightfloatet"><?= icon('check-circle', 'text-gray-600') ?>></i>
                                    <a class="rightfloatet"><?= $noResponse ?></a>
                                    <i class=" treeIcon rightfloatet"><?= icon('question-circle', 'text-gray-600') ?>></i>
                                </span>
                                
                                <div id="<?= $instrumentElementId ?>" class="collapse">
                                    <ul>
                                        <?php foreach ($instrumentPlayers as $player): ?>
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
                        // Direct instrument under this section
                        $instrumentPlayers = array_filter($players, function($p) use ($subSection, $groupManager) {
                            return $groupManager->resolveAlias($p['type']) === $subSection['id'];
                        });
                        
                        foreach ($instrumentPlayers as $player):
                            $member = $player;
                            $status = $player['status'];
                            include __DIR__ . '/user-item.php';
                        endforeach;
                    endif;
                endforeach;
            } else {
                // Simple section with direct instruments (like Schlagwerk, Andere)
                if ($sectionId === 'Andere') {
                    // For "Andere", show instrument type in parentheses
                    foreach ($players as $player):
                        $member = $player;
                        $status = $player['status'];
                        $additionalInfo = ' (' . str_replace('_', ' ', $player['type']) . ')';
                        include __DIR__ . '/user-item.php';
                    endforeach;
                } else {
                    // Regular simple section
                    foreach ($players as $player):
                        $member = $player;
                        $status = $player['status'];
                        include __DIR__ . '/user-item.php';
                    endforeach;
                }
            }
            ?>
        </ul>
    </div>
</li>
