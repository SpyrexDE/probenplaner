<?php use App\Core\DashboardConstants; ?>
                <div class="rehearsal-compact" data-rehearsal-id="<?= $rehearsalId ?>">
                    <!-- Compact Rehearsal Header -->
                    <div class="rehearsal-compact-header <?= ($rehearsalColor) ? 'has-color' : '' ?>"
                        <?= ($rehearsalColor) ? 'style="--rehearsal-color: ' . $rehearsalColor . '"' : '' ?>>
                        <div class="rehearsal-modern-title">
                            <?php
                            $germanWeekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
                            $dayOfWeek = date('w', strtotime($rehearsalDate));
                            $weekdayShort = $germanWeekdays[$dayOfWeek];
                            ?>
                            <div class="rehearsal-date-display">
                                <div class="weekday-letter"><?= strtoupper($weekdayShort) ?></div>
                                <div class="date-info">
                                    <div class="date-text"><?= date('d.m.Y', strtotime($rehearsalDate)) ?></div>
                                    <div class="date-subtitle">
                                        <?= htmlspecialchars($rehearsal['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rehearsal-compact-right">
                            <div class="rehearsal-compact-meta">
                                <span><i class="fas fa-clock"></i> <?= $rehearsalStartTime ? substr($rehearsalStartTime, 0, DashboardConstants::TIME_SUBSTRING_LENGTH) : '??:??' ?> - <?= $rehearsalEndTime ? substr($rehearsalEndTime, 0, DashboardConstants::TIME_SUBSTRING_LENGTH) : '??:??' ?></span>
                                <?php if (!empty($rehearsal['location'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($rehearsal['location']) ?></span>
                                <?php endif; ?>
                                <span>
                                    <i class="fas fa-users"></i>
                                    <?php
                                    echo htmlspecialchars($smartDisplay->generateDescription($rehearsal['groups'] ?? [], $rehearsal, false));
                                    ?>
                                </span>
                                <?php if (!empty($rehearsal['roles']) || !empty($rehearsal['infos'])): ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
                                        <?php foreach ($rehearsal['roles'] ?? [] as $role): ?>
                                            <?= \App\Core\Utilities::renderRoleTag($role) ?>
                                        <?php endforeach; ?>
                                        <?php foreach ($rehearsal['infos'] ?? [] as $info): ?>
                                            <span style="font-size: 11px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; background-color: transparent; border: 1px solid var(--color-border); color: var(--color-text-primary);">
                                                <?= htmlspecialchars($info['emoji']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="rehearsal-stats-container">
                                <div class="rehearsal-stats-numbers">
                                    <div class="rehearsal-stats-item">
                                        <div class="rehearsal-stats-dot attending"></div>
                                        <span><?= $attendingCount ?></span>
                                    </div>
                                    <div class="rehearsal-stats-item">
                                        <div class="rehearsal-stats-dot not-attending"></div>
                                        <span><?= $notAttendingCount ?></span>
                                    </div>
                                    <div class="rehearsal-stats-item">
                                        <div class="rehearsal-stats-dot no-response"></div>
                                        <span><?= $noResponseCount ?></span>
                                    </div>
                                </div>
                                <div class="rehearsal-stats-bar">
                                    <?php if ($totalCount > 0): ?>
                                        <div class="rehearsal-stats-segment attending" style="width: <?= ($attendingCount / $totalCount) * 100 ?>%"></div>
                                        <div class="rehearsal-stats-segment not-attending" style="width: <?= ($notAttendingCount / $totalCount) * 100 ?>%"></div>
                                        <div class="rehearsal-stats-segment no-response" style="width: <?= ($noResponseCount / $totalCount) * 100 ?>%"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    $rehearsalSmartDeviations = [];
                    $insufficientData = [];
                    $rehearsalCriticalSections = [];

                    if (!($isLeader ?? false) && ($showRehearsalInsights ?? false)) {
                        // Use pre-computed deviation data from controller
                        $deviationAnalysis = $deviationData[$rehearsalId] ?? ['deviations' => [], 'insufficient_data' => []];

                        $individualInstruments = [];
                        foreach ($membersBySection[$rehearsalId]['all'] ?? [] as $member) {
                            $instrumentId = $member['type'];
                            if (!isset($individualInstruments[$instrumentId])) {
                                $individualInstruments[$instrumentId] = [];
                            }
                            $individualInstruments[$instrumentId][] = $member;
                        }

                        foreach ($individualInstruments as $instrumentId => $players) {
                            $attending = count(array_filter($players, function ($m) {
                                return $m['status'] === 'attending';
                            }));
                            $total = count($players);
                            if ($total > 0) {
                                $attendanceRate = ($attending / $total) * 100;
                                if ($attendanceRate < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD) {
                                    $rehearsalCriticalSections[] = [
                                        'name' => $groupManager->getDisplayName($instrumentId),
                                        'rate' => $attendanceRate,
                                        'total' => $total,
                                        'attending' => $attending
                                    ];
                                }
                            }
                        }

                        if (empty($rehearsalCriticalSections)) {
                            foreach ($sectionPlayers as $sectionId => $players) {
                                $attending = count(array_filter($players, function ($m) {
                                    return $m['status'] === 'attending';
                                }));
                                $total = count($players);
                                if ($total > 0) {
                                    $attendanceRate = ($attending / $total) * 100;
                                    if ($attendanceRate < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD) {
                                        $rehearsalCriticalSections[] = [
                                            'name' => $groupManager->getDisplayName($sectionId),
                                            'rate' => $attendanceRate,
                                            'total' => $total,
                                            'attending' => $attending
                                        ];
                                    }
                                }
                            }
                        }

                        usort($rehearsalCriticalSections, function ($a, $b) {
                            return $a['rate'] - $b['rate'];
                        });
                        $rehearsalCriticalSections = array_slice($rehearsalCriticalSections, 0, DashboardConstants::MAX_CRITICAL_SECTIONS_DISPLAY);

                        $rehearsalCriticalSections = array_filter($rehearsalCriticalSections, function ($critical) {
                            return $critical['rate'] < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD;
                        });

                        $criticalSectionNames = array_map(function ($critical) {
                            return $critical['name'];
                        }, $rehearsalCriticalSections);

                        $rehearsalSmartDeviations = array_filter($deviationAnalysis['deviations'], function ($deviation) use ($criticalSectionNames) {
                            if (($deviation['severity'] ?? 'info') === 'info') {
                                return false;
                            }
                            if (!isset($deviation['section'])) {
                                return true;
                            }
                            return !in_array($deviation['section'], $criticalSectionNames);
                        });

                        $uniqueDeviations = [];
                        $seenMessages = [];
                        $groupPerformanceMessages = [];
                        $otherMessages = [];

                        foreach ($rehearsalSmartDeviations as $deviation) {
                            if ($deviation['type'] === 'group_performance') {
                                $groupPerformanceMessages[] = $deviation;
                            } else {
                                $otherMessages[] = $deviation;
                            }
                        }

                        if (!empty($groupPerformanceMessages)) {
                            usort($groupPerformanceMessages, function ($a, $b) {
                                return $a['mean_rate'] - $b['mean_rate'];
                            });
                            $uniqueDeviations[] = $groupPerformanceMessages[0];
                        }

                        $mergedMessages = mergeParticipationMessages($otherMessages);

                        foreach ($mergedMessages as $deviation) {
                            $messageKey = $deviation['message'];
                            if (!in_array($messageKey, $seenMessages)) {
                                $uniqueDeviations[] = $deviation;
                                $seenMessages[] = $messageKey;
                            }
                        }
                        $rehearsalSmartDeviations = $uniqueDeviations;

                        usort($rehearsalSmartDeviations, function ($a, $b) {
                            $severityOrder = ['critical' => 3, 'warning' => 2, 'info' => 1];
                            $aSeverity = $severityOrder[$a['severity'] ?? 'info'] ?? 1;
                            $bSeverity = $severityOrder[$b['severity'] ?? 'info'] ?? 1;
                            return $bSeverity - $aSeverity;
                        });
                        $insufficientData = $deviationAnalysis['insufficient_data'];
                    }
                    ?>

                    <!-- Critical Sections & Smart Insights - Hidden for leaders and when disabled -->
                    <?php if (!($isLeader ?? false) && ($showRehearsalInsights ?? false)): ?>
                        <div class="rehearsal-insights">
                            <div class="critical-sections">
                                <h4><i class="fas fa-exclamation-triangle"></i> Kritische Register</h4>
                                <div class="critical-list">
                                    <?php if (!empty($rehearsalCriticalSections)): ?>
                                        <?php foreach ($rehearsalCriticalSections as $critical): ?>
                                            <div class="critical-item">
                                                <span class="critical-name"><?= htmlspecialchars($critical['name']) ?></span>
                                                <span class="critical-percentage <?= $critical['rate'] < DashboardConstants::DANGER_ATTENDANCE_THRESHOLD ? DashboardConstants::CSS_DANGER_CLASS : DashboardConstants::CSS_WARNING_CLASS ?>">
                                                    <?= number_format($critical['rate'], 0) ?>%
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="critical-item">
                                            <span class="critical-name">Keine kritischen Register</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="smart-deviations">
                                <h4><i class="fas fa-brain"></i> Auffälligkeiten</h4>
                                <div class="deviation-list" style="max-height: <?= DashboardConstants::DEVIATION_LIST_MAX_HEIGHT ?>px; overflow-y: auto;">
                                    <?php foreach ($rehearsalSmartDeviations as $deviation): ?>
                                        <div class="critical-item">
                                            <span class="critical-name"><?= htmlspecialchars($deviation['message']) ?></span>
                                            <span class="critical-percentage <?= getDeviationCssClass($deviation['severity'] ?? 'warning') ?>">
                                                <i class="fas fa-<?= getDeviationIcon($deviation['type']) ?>"></i>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (empty($rehearsalSmartDeviations)): ?>
                                        <div class="critical-item">
                                            <span class="critical-name">Keine Auffälligkeiten</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tree View -->
                    <div class="rehearsal-tree-view">
                        <div class="tree-view">
                            <ul class="tree-list">
                                <?php
                                // Build pruned tree based on present instruments
                                $presentInstrumentIds = [];
                                if (!empty($membersBySection[$rehearsalId]['all'])) {
                                    foreach ($membersBySection[$rehearsalId]['all'] as $member) {
                                        $resolved = $groupManager->resolveAlias($member['type']);
                                        $presentInstrumentIds[$resolved] = true;
                                    }
                                }
                                $prunedTree = $groupManager->pruneTree(array_keys($presentInstrumentIds));

                                // Leader view check
                                $isLeaderOnlyView = isset($isLeaderOnlyView) && $isLeaderOnlyView;

                                if (isset($sectionPlayers['all'])) {
                                } else {
                                }

                                // Leader section root
                                if ($isLeaderOnlyView && !empty($leaderResolvedType)) {
                                    $groupManager = \App\Core\GroupManager::getInstance();
                                    $rootDisplayName = $groupManager->getDisplayName($leaderResolvedType);

                                    // Find player section
                                    $players = [];
                                    $sectionId = 'all';

                                    // Try different possible keys where the filtered players might be stored
                                    if (!empty($sectionPlayers['all'])) {
                                        $players = $sectionPlayers['all'];
                                        $sectionId = 'all';
                                    } else {
                                        // Use leader's section id from context (no hardcoded section name)
                                        $found = false;
                                        if (!empty($leaderSection) && isset($sectionPlayers[$leaderSection]) && is_array($sectionPlayers[$leaderSection])) {
                                            $players = $sectionPlayers[$leaderSection];
                                            $sectionId = $leaderSection;
                                            $found = true;
                                        }
                                        if (!$found && !empty($leaderSectionNames) && is_array($leaderSectionNames)) {
                                            foreach ($leaderSectionNames as $candidateId) {
                                                if (!empty($sectionPlayers[$candidateId]) && is_array($sectionPlayers[$candidateId])) {
                                                    $players = $sectionPlayers[$candidateId];
                                                    $sectionId = $candidateId;
                                                    $found = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if (!$found) {
                                            foreach ($sectionPlayers as $key => $sectionData) {
                                                if (!empty($sectionData) && is_array($sectionData)) {
                                                    $players = $sectionData;
                                                    $sectionId = $key;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if (!empty($players)) {

                                        // Section statistics
                                        $sectionAttending = count(array_filter($players, function ($m) {
                                            return $m['status'] === 'attending';
                                        }));
                                        $sectionNotAttending = count(array_filter($players, function ($m) {
                                            return $m['status'] === 'not_attending';
                                        }));
                                        $sectionNoResponse = count(array_filter($players, function ($m) {
                                            return $m['status'] === 'no_response';
                                        }));
                                ?>
                                        <!-- Leader section as root node -->
                                        <li class="tree-node tree-depth-0">
                                            <button class="tree-node-header" data-toggle="collapse" data-target="#leader-root-<?= $rehearsalId ?>" aria-expanded="false" aria-controls="leader-root-<?= $rehearsalId ?>">
                                                <i class="tree-node-icon fas fa-chevron-right"></i>

                                                <div class="tree-node-title">
                                                    <span class="tree-node-title-text"><?= htmlspecialchars($rootDisplayName) ?></span>
                                                </div>

                                                <div class="tree-node-stats">
                                                    <div class="tree-node-stat">
                                                        <i class="tree-node-stat-icon fas fa-check-circle status-<?= DashboardConstants::CSS_ATTENDING_CLASS ?>"></i>
                                                        <span><?= $sectionAttending ?></span>
                                                    </div>
                                                    <div class="tree-node-stat">
                                                        <i class="tree-node-stat-icon fas fa-times-circle status-<?= DashboardConstants::CSS_NOT_ATTENDING_CLASS ?>"></i>
                                                        <span><?= $sectionNotAttending ?></span>
                                                    </div>
                                                    <div class="tree-node-stat">
                                                        <i class="tree-node-stat-icon fas fa-question-circle status-<?= DashboardConstants::CSS_NO_RESPONSE_CLASS ?>"></i>
                                                        <span><?= $sectionNoResponse ?></span>
                                                    </div>
                                                </div>
                                            </button>

                                            <div id="leader-root-<?= $rehearsalId ?>" class="tree-node-content collapse">
                                                <ul class="tree-list">
                                                    <?php
                                                    if (!empty($players)) {
                                                        sortPlayersByStatus($players);

                                                        foreach ($players as $player): ?>
                                                            <?php renderUserItem($player, $player['status']); ?>
                                                    <?php endforeach;
                                                    } else {
                                                        echo '<li class="tree-user-item">Keine Mitglieder gefunden</li>';
                                                    } ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php
                                    } else {
                                        // Empty state
                                    ?>
                                        <li class="tree-node tree-depth-0">
                                            <div class="tree-node-header">
                                                <div class="tree-node-title">
                                                    <span class="tree-node-title-text"><?= htmlspecialchars($rootDisplayName) ?> - Keine Mitglieder</span>
                                                </div>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                } else {
                                    // Unwrap single-root wrappers (e.g. tutti) to avoid exceeding the component's rendering depth
                                    $renderRoots = $prunedTree;
                                    if (count($renderRoots) === 1) {
                                        $onlyRoot = reset($renderRoots);
                                        if (!empty($onlyRoot['children'])) {
                                            $renderRoots = $onlyRoot['children'];
                                        }
                                    }

                                    foreach ($renderRoots as $rootKey => $rootNode):
                                        $rootDisplayName = $rootNode['display_name'] ?? $rootNode['id'] ?? 'Gruppe';
                                        $rootNodeId = $rootNode['id'] ?? $rootKey;

                                        // Collect members for this root
                                        $rootPlayers = [];
                                        if (!empty($membersBySection[$rehearsalId]['all'])) {
                                            foreach ($membersBySection[$rehearsalId]['all'] as $member) {
                                                if ($groupManager->isUserInGroup($member['type'], $rootNodeId)) {
                                                    $rootPlayers[] = $member;
                                                }
                                            }
                                        }

                                        $rootAttending = count(array_filter($rootPlayers, fn($m) => $m['status'] === 'attending'));
                                        $rootNotAttending = count(array_filter($rootPlayers, fn($m) => $m['status'] === 'not_attending'));
                                        $rootNoResponse = count(array_filter($rootPlayers, fn($m) => $m['status'] === 'no_response'));

                                        if (empty($rootPlayers)) continue;

                                        // If leaf node, render directly with members
                                        if (empty($rootNode['children'])):
                                        ?>
                                            <li class="tree-node tree-depth-0">
                                                <button class="tree-node-header" data-toggle="collapse" data-target="#pruned-<?= $rootKey . $rehearsalId ?>" aria-expanded="false">
                                                    <i class="tree-node-icon fas fa-chevron-right"></i>
                                                    <div class="tree-node-title"><span class="tree-node-title-text"><?= htmlspecialchars($rootDisplayName) ?></span></div>
                                                    <div class="tree-node-stats">
                                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-check-circle status-<?= DashboardConstants::CSS_ATTENDING_CLASS ?>"></i><span><?= $rootAttending ?></span></div>
                                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-times-circle status-<?= DashboardConstants::CSS_NOT_ATTENDING_CLASS ?>"></i><span><?= $rootNotAttending ?></span></div>
                                                        <div class="tree-node-stat"><i class="tree-node-stat-icon fas fa-question-circle status-<?= DashboardConstants::CSS_NO_RESPONSE_CLASS ?>"></i><span><?= $rootNoResponse ?></span></div>
                                                    </div>
                                                </button>
                                                <div id="pruned-<?= $rootKey . $rehearsalId ?>" class="tree-node-content collapse">
                                                    <ul class="tree-list">
                                                        <?php
                                                        sortPlayersByStatus($rootPlayers);
                                                        foreach ($rootPlayers as $player):
                                                            renderUserItem($player, $player['status']);
                                                        endforeach;
                                                        ?>
                                                    </ul>
                                                </div>
                                            </li>
                                        <?php else:
                                            // Section with children — render as expandable root with sub-sections
                                            $sectionId = $rootNodeId;
                                            $players = $rootPlayers;
                                            $prunedSubtree = $rootNode;
                                        ?>
                                            <?php include __DIR__ . '/dynamic-section-component.php'; ?>
                                <?php endif;
                                    endforeach;
                                } ?>
                            </ul>
                        </div>
                    </div>
                </div>
