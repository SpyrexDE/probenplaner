<?php

/**
 * Dynamic Section Component
 * 
 * Renders a section tree node. Accepts an optional $prunedSubtree
 * for intelligent tree rendering (empty branches already stripped).
 * 
 * @param array $players - Players with status for this section
 * @param int $rehearsalId - Rehearsal ID for unique element IDs
 * @param string $sectionId - Section ID from config
 * @param array|null $prunedSubtree - Pre-pruned subtree (optional)
 */

use App\Core\GroupManager;

$groupManager = GroupManager::getInstance();
$section = $groupManager->getGroup($sectionId);

if (!$section) {
    return;
}

$sectionDisplayName = $section['display_name'] ?? $sectionId;

$sectionAttending = count(array_filter($players, fn($m) => $m['status'] === 'attending'));
$sectionNotAttending = count(array_filter($players, fn($m) => $m['status'] === 'not_attending'));
$sectionNoResponse = count(array_filter($players, fn($m) => $m['status'] === 'no_response'));

$sectionElementId = $sectionId . $rehearsalId;
$sectionTree = isset($prunedSubtree) ? $prunedSubtree : $groupManager->getTreeForComponent($sectionId);
?>

<li class="tree-node tree-depth-0">
    <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $sectionElementId ?>" aria-expanded="false">
        <i class="tree-node-icon fas fa-chevron-right"></i>
        <div class="tree-node-title">
            <span class="tree-node-title-text"><?= htmlspecialchars($sectionDisplayName) ?></span>
        </div>
        <div class="tree-node-stats">
            <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-check-circle status-attending"></i><span><?= $sectionAttending ?></span></div>
            <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i><span><?= $sectionNotAttending ?></span></div>
            <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i><span><?= $sectionNoResponse ?></span></div>
        </div>
    </button>

    <div id="<?= $sectionElementId ?>" class="tree-node-content collapse">
        <ul class="tree-list">
            <?php
            if (isset($sectionTree['children']) && !empty($sectionTree['children'])) {
                foreach ($sectionTree['children'] as $subSection):
                    if (($subSection['type'] ?? '') === 'section' && isset($subSection['children'])):
                        // Sub-section with instruments
                        $subSectionInstruments = array_map(fn($inst) => $inst['id'], $subSection['children']);
                        $subSectionPlayers = array_filter($players, function ($p) use ($subSectionInstruments, $groupManager) {
                            return in_array($groupManager->resolveAlias($p['type']), $subSectionInstruments);
                        });

                        if (!empty($subSectionPlayers)):
                            $subSectionAttending = count(array_filter($subSectionPlayers, fn($m) => $m['status'] === 'attending'));
                            $subSectionNotAttending = count(array_filter($subSectionPlayers, fn($m) => $m['status'] === 'not_attending'));
                            $subSectionNoResponse = count(array_filter($subSectionPlayers, fn($m) => $m['status'] === 'no_response'));
                            $subSectionElementId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $subSection['id']) . $rehearsalId;
            ?>
                            <li class="tree-node tree-depth-1">
                                <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $subSectionElementId ?>" aria-expanded="false">
                                    <i class="tree-node-icon fas fa-chevron-right"></i>
                                    <div class="tree-node-title"><span class="tree-node-title-text"><?= htmlspecialchars($subSection['display_name']) ?></span></div>
                                    <div class="tree-node-stats">
                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-check-circle status-attending"></i><span><?= $subSectionAttending ?></span></div>
                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i><span><?= $subSectionNotAttending ?></span></div>
                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i><span><?= $subSectionNoResponse ?></span></div>
                                    </div>
                                </button>

                                <div id="<?= $subSectionElementId ?>" class="tree-node-content collapse">
                                    <ul class="tree-list">
                                        <?php
                                        $instrumentGroups = [];
                                        foreach ($subSection['children'] as $instrument) {
                                            if (($instrument['type'] ?? '') === 'instrument') {
                                                $instrumentPlayers = array_filter($subSectionPlayers, function ($p) use ($instrument, $groupManager) {
                                                    return $groupManager->resolveAlias($p['type']) === $instrument['id'];
                                                });
                                                if (!empty($instrumentPlayers)) {
                                                    $instrumentGroups[$instrument['display_name']] = $instrumentPlayers;
                                                }
                                            }
                                        }

                                        foreach ($instrumentGroups as $instrumentName => $instrumentPlayers):
                                            $attending = count(array_filter($instrumentPlayers, fn($m) => $m['status'] === 'attending'));
                                            $notAttending = count(array_filter($instrumentPlayers, fn($m) => $m['status'] === 'not_attending'));
                                            $noResponse = count(array_filter($instrumentPlayers, fn($m) => $m['status'] === 'no_response'));
                                            $instrumentElementId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $instrumentName) . $rehearsalId;
                                        ?>
                                            <li class="tree-node tree-depth-2">
                                                <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $instrumentElementId ?>" aria-expanded="false">
                                                    <i class="tree-node-icon fas fa-chevron-right"></i>
                                                    <div class="tree-node-title"><span class="tree-node-title-text"><?= htmlspecialchars($instrumentName) ?></span></div>
                                                    <div class="tree-node-stats">
                                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-check-circle status-attending"></i><span><?= $attending ?></span></div>
                                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i><span><?= $notAttending ?></span></div>
                                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i><span><?= $noResponse ?></span></div>
                                                    </div>
                                                </button>

                                                <div id="<?= $instrumentElementId ?>" class="tree-node-content collapse">
                                                    <ul class="tree-list">
                                                        <?php
                                                        sortPlayersByStatus($instrumentPlayers);
                                                        foreach ($instrumentPlayers as $player):
                                                            renderUserItem($player, $player['status']);
                                                        endforeach;
                                                        ?>
                                                    </ul>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </li>
                        <?php
                        endif;
                    elseif (($subSection['type'] ?? '') === 'instrument'):
                        // Direct instrument under this section
                        $instrumentPlayers = array_filter($players, function ($p) use ($subSection, $groupManager) {
                            return $groupManager->resolveAlias($p['type']) === $subSection['id'];
                        });

                        if (!empty($instrumentPlayers)):
                            $attending = count(array_filter($instrumentPlayers, fn($m) => $m['status'] === 'attending'));
                            $notAttending = count(array_filter($instrumentPlayers, fn($m) => $m['status'] === 'not_attending'));
                            $noResponse = count(array_filter($instrumentPlayers, fn($m) => $m['status'] === 'no_response'));
                            $instrumentElementId = str_replace(['ö', 'ü', 'ä', ' '], ['oe', 'ue', 'ae', ''], $subSection['id']) . $rehearsalId;
                        ?>
                            <li class="tree-node tree-depth-1">
                                <button class="tree-node-header" data-toggle="collapse" data-target="#<?= $instrumentElementId ?>" aria-expanded="false">
                                    <i class="tree-node-icon fas fa-chevron-right"></i>
                                    <div class="tree-node-title"><span class="tree-node-title-text"><?= htmlspecialchars($subSection['display_name']) ?></span></div>
                                    <div class="tree-node-stats">
                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-check-circle status-attending"></i><span><?= $attending ?></span></div>
                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i><span><?= $notAttending ?></span></div>
                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i><span><?= $noResponse ?></span></div>
                                    </div>
                                </button>

                                <div id="<?= $instrumentElementId ?>" class="tree-node-content collapse">
                                    <ul class="tree-list">
                                        <?php
                                        sortPlayersByStatus($instrumentPlayers);
                                        foreach ($instrumentPlayers as $player):
                                            renderUserItem($player, $player['status']);
                                        endforeach;
                                        ?>
                                    </ul>
                                </div>
                            </li>
            <?php endif;
                    endif;
                endforeach;
            } else {
                // Simple section with direct instruments
                sortPlayersByStatus($players);
                foreach ($players as $player):
                    renderUserItem($player, $player['status']);
                endforeach;
            }
            ?>
        </ul>
    </div>
</li>