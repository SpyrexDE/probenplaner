<?php
/**
 * Modern Promises Dashboard Wrapper
 * 
 * Enterprise-level dashboard interface for orchestra attendance management.
 * Provides overview metrics, filtering, and modern card-based section display.
 */

// Calculate overall statistics across all rehearsals
$totalRehearsals = count($rehearsals ?? []);
$totalPromises = 0;
$totalAttending = 0;
$totalNotAttending = 0;
$totalNoResponse = 0;

// Critical sections counter
$criticalSections = 0;
$warningSections = 0;

foreach ($rehearsals ?? [] as $rehearsal) {
    $rehearsalId = $rehearsal['id'];
    if (isset($stats[$rehearsalId])) {
        $totalAttending += $stats[$rehearsalId]['attending'] ?? 0;
        $totalNotAttending += $stats[$rehearsalId]['not_attending'] ?? 0;
        $totalNoResponse += $stats[$rehearsalId]['no_response'] ?? 0;
        $totalPromises += ($stats[$rehearsalId]['attending'] ?? 0) + ($stats[$rehearsalId]['not_attending'] ?? 0) + ($stats[$rehearsalId]['no_response'] ?? 0);
    }
}

// Calculate percentages
$overallAttendanceRate = $totalPromises > 0 ? ($totalAttending / $totalPromises) * 100 : 0;
$overallResponseRate = $totalPromises > 0 ? (($totalAttending + $totalNotAttending) / $totalPromises) * 100 : 0;

// Determine trends (this would ideally come from historical data)
$attendanceTrend = 'positive'; // This should be calculated from historical data
$responseTrend = 'positive';

?>

<!-- Include the dashboard CSS and ApexCharts -->
<link rel="stylesheet" href="<?= '/assets/css/promises-dashboard.css' ?>">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<?php
// Generate time-based data for charts
$attendanceHistory = [];
$responseHistory = [];
$rehearsalDates = [];
$currentRehearsalIndex = 0;

foreach ($rehearsals ?? [] as $rehearsal) {
    $rehearsalId = $rehearsal['id'];
    $rehearsalDates[] = $rehearsal['date'];
    
    if (isset($stats[$rehearsalId])) {
        $attending = $stats[$rehearsalId]['attending'] ?? 0;
        $notAttending = $stats[$rehearsalId]['not_attending'] ?? 0;
        $noResponse = $stats[$rehearsalId]['no_response'] ?? 0;
        $total = $attending + $notAttending + $noResponse;
        
        $attendanceRate = $total > 0 ? ($attending / $total) * 100 : 0;
        $responseRate = $total > 0 ? (($attending + $notAttending) / $total) * 100 : 0;
        
        $attendanceHistory[] = round($attendanceRate, 1);
        $responseHistory[] = round($responseRate, 1);
    } else {
        $attendanceHistory[] = 0;
        $responseHistory[] = 0;
    }
    
    $currentRehearsalIndex++;
}

// Calculate critical sections
$criticalSectionsCount = 0;
$warningSectionsCount = 0;

foreach ($rehearsals ?? [] as $rehearsal) {
    $rehearsalId = $rehearsal['id'];
    if (!empty($membersBySection[$rehearsalId]['all'])) {
        $groupManager = new \App\Core\GroupManager();
        $sectionPlayers = [];

        foreach ($membersBySection[$rehearsalId]['all'] as $member) {
            $userType = $member['type'];
            $resolvedType = $groupManager->resolveAlias($userType);
            $sectionInfo = $groupManager->getSectionForInstrument($resolvedType);
            $sectionKey = $sectionInfo['section'] ?? 'Andere';
            
            if (!isset($sectionPlayers[$sectionKey])) {
                $sectionPlayers[$sectionKey] = [];
            }
            $sectionPlayers[$sectionKey][] = $member;
        }
        
        foreach ($sectionPlayers as $sectionId => $players) {
            $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
            $total = count($players);
            $attendanceRate = $total > 0 ? ($attending / $total) * 100 : 0;
            
            if ($attendanceRate < 50) {
                $criticalSectionsCount++;
            } elseif ($attendanceRate < 70) {
                $warningSectionsCount++;
            }
        }
    }
}
?>

<div class="promises-dashboard">
    <!-- Compact Analytics Overview -->
    <div class="analytics-overview">
        <div class="analytics-card">
            <div class="analytics-header">
                <h3 class="analytics-title">Gesamtteilnahme</h3>
                <div class="analytics-value"><?= number_format($overallAttendanceRate, 1) ?>%</div>
            </div>
            <div class="analytics-chart" id="attendance-chart"></div>
            <div class="analytics-subtitle">Entwicklung über alle Proben</div>
        </div>
        
        <div class="analytics-card">
            <div class="analytics-header">
                <h3 class="analytics-title">Rücklaufquote</h3>
                <div class="analytics-value"><?= number_format($overallResponseRate, 1) ?>%</div>
            </div>
            <div class="analytics-chart" id="response-chart"></div>
            <div class="analytics-subtitle">Antwortverhalten über Zeit</div>
        </div>
    </div>
    
    <!-- Rehearsals Container -->
    <div class="rehearsals-container">
        <?php if (empty($rehearsals)): ?>
            <?php 
                $title = 'Keine Termine gefunden';
                $message = 'Es gibt aktuell keine geplanten Proben.';
                include __DIR__ . '/empty-state.php';
            ?>
        <?php else: ?>
            <?php foreach ($rehearsals as $rehearsal): ?>
                <?php 
                    $rehearsalId = $rehearsal['id'];
                    $attendingCount = $stats[$rehearsalId]['attending'] ?? 0;
                    $notAttendingCount = $stats[$rehearsalId]['not_attending'] ?? 0;
                    $noResponseCount = $stats[$rehearsalId]['no_response'] ?? 0;
                    $totalCount = $attendingCount + $notAttendingCount + $noResponseCount;
                    
                    // Calculate rehearsal attendance rate
                    $rehearsalAttendanceRate = $totalCount > 0 ? ($attendingCount / $totalCount) * 100 : 0;
                    
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
                    
                    // Determine rehearsal color
                    $rehearsalColor = $rehearsal['color'] ?? null;
                ?>
                
                <div class="rehearsal-compact" data-rehearsal-id="<?= $rehearsalId ?>">
                    <!-- Compact Rehearsal Header -->
                    <div class="rehearsal-compact-header <?= $rehearsalColor ? 'has-color' : '' ?>" 
                         <?= $rehearsalColor ? 'style="--rehearsal-color: ' . $rehearsalColor . '"' : '' ?>>
                        <div class="rehearsal-compact-title">
                            <h3><?= htmlspecialchars($rehearsal['type'] ?? 'Probe') ?></h3>
                            <span class="rehearsal-date"><?= date('d.m.Y', strtotime($rehearsal['date'])) ?></span>
                        </div>
                        
                        <div class="rehearsal-compact-right">
                            <div class="rehearsal-compact-meta">
                                <span><i class="fas fa-clock"></i> <?= isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, 5) : '??:??' ?> - <?= isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, 5) : '??:??' ?></span>
                                <?php if (!empty($rehearsal['location'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($rehearsal['location']) ?></span>
                                <?php endif; ?>
                                <span>
                                    <i class="fas fa-users"></i>
                                    <?php
                                    $smartDisplay = new \App\Core\SmartGroupDisplay();
                                    echo htmlspecialchars($smartDisplay->generateDescription($rehearsal['groups'] ?? []));
                                    ?>
                                </span>
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
                    // Calculate critical sections for this rehearsal
                    $rehearsalCriticalSections = [];
                    $rehearsalSmartDeviations = [];
                    
                    foreach ($sectionPlayers as $sectionId => $players) {
                        $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
                        $notAttending = count(array_filter($players, function($m) { return $m['status'] === 'not_attending'; }));
                        $noResponse = count(array_filter($players, function($m) { return $m['status'] === 'no_response'; }));
                        $total = count($players);
                        
                        if ($total > 0) {
                            $attendanceRate = ($attending / $total) * 100;
                            $responseRate = (($attending + $notAttending) / $total) * 100;
                            
                            // Critical sections (attendance < 50%)
                            if ($attendanceRate < 50) {
                                $rehearsalCriticalSections[] = [
                                    'name' => $sectionId,
                                    'rate' => $attendanceRate,
                                    'total' => $total
                                ];
                            }
                            
                            // Smart deviations detection
                            // For demo purposes, flagging sections with very low response rates or unusual patterns
                            if ($responseRate < 40) {
                                $rehearsalSmartDeviations[] = [
                                    'type' => 'low_response',
                                    'section' => $sectionId,
                                    'value' => $responseRate,
                                    'message' => "Sehr niedrige Rücklaufquote ({$responseRate}%) in {$sectionId}"
                                ];
                            }
                            
                            // Flag sections with very low attendance specifically for strings
                            if (strpos($sectionId, 'Violine') !== false && $attendanceRate < 30) {
                                $rehearsalSmartDeviations[] = [
                                    'type' => 'critical_strings',
                                    'section' => $sectionId,
                                    'value' => $attendanceRate,
                                    'message' => "{$sectionId}: Kritisch niedrige Teilnahme ({$attendanceRate}%)"
                                ];
                            }
                        }
                    }
                    
                    // Sort critical sections by attendance rate
                    usort($rehearsalCriticalSections, function($a, $b) {
                        return $a['rate'] - $b['rate'];
                    });
                    
                    // Take top 3 most critical
                    $rehearsalCriticalSections = array_slice($rehearsalCriticalSections, 0, 3);
                    ?>
                    
                    <!-- Critical Sections & Smart Insights -->
                    <?php if (!empty($rehearsalCriticalSections) || !empty($rehearsalSmartDeviations)): ?>
                    <div class="rehearsal-insights">
                        <?php if (!empty($rehearsalCriticalSections)): ?>
                        <div class="critical-sections">
                            <h4><i class="fas fa-exclamation-triangle"></i> Kritische Register</h4>
                            <div class="critical-list">
                                <?php foreach ($rehearsalCriticalSections as $critical): ?>
                                    <div class="critical-item">
                                        <span class="critical-name"><?= htmlspecialchars($critical['name']) ?></span>
                                        <span class="critical-percentage <?= $critical['rate'] < 25 ? 'danger' : 'warning' ?>">
                                            <?= number_format($critical['rate'], 0) ?>%
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($rehearsalSmartDeviations)): ?>
                        <div class="smart-deviations">
                            <h4><i class="fas fa-brain"></i> Auffälligkeiten</h4>
                            <div class="deviation-list">
                                <?php foreach ($rehearsalSmartDeviations as $deviation): ?>
                                    <div class="deviation-item <?= $deviation['type'] ?>">
                                        <i class="fas fa-<?= $deviation['type'] === 'low_response' ? 'reply' : 'users' ?>"></i>
                                        <span><?= htmlspecialchars($deviation['message']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Tree View -->
                    <div class="rehearsal-tree-view">
                        <div class="tree-view">
                            <ul class="tree-list">
                                <?php
                                // Get the root group dynamically
                                $rootGroup = $groupManager->getGroup('tutti');
                                $rootDisplayName = $rootGroup['display_name'] ?? 'Tutti';
                                ?>
                                <!-- Main root node (tutti) -->
                                <li class="tree-node tree-depth-0">
                                    <button class="tree-node-header" data-toggle="collapse" data-target="#tutti-<?= $rehearsalId ?>" aria-expanded="false" aria-controls="tutti-<?= $rehearsalId ?>">
                                        <i class="tree-node-icon fas fa-chevron-right"></i>
                                        
                                        <div class="tree-node-title">
                                            <span class="tree-node-title-text"><?= htmlspecialchars($rootDisplayName) ?></span>
                                        </div>
                                        
                                        <div class="tree-node-stats">
                                            <div class="tree-node-stat">
                                                <i class="tree-node-stat-icon fas fa-question-circle status-no-response"></i>
                                                <span><?= $noResponseCount ?></span>
                                            </div>
                                            <div class="tree-node-stat">
                                                <i class="tree-node-stat-icon fas fa-check-circle status-attending"></i>
                                                <span><?= $attendingCount ?></span>
                                            </div>
                                            <div class="tree-node-stat">
                                                <i class="tree-node-stat-icon fas fa-times-circle status-not-attending"></i>
                                                <span><?= $notAttendingCount ?></span>
                                            </div>
                                        </div>
                                    </button>
                                    
                                    <div id="tutti-<?= $rehearsalId ?>" class="tree-node-content collapse">
                                        <ul class="tree-list">
                                            <?php foreach ($sectionPlayers as $sectionId => $players): ?>
                                                <?php
                                                    // Only include sections that have players
                                                    if (!empty($players)) {
                                                        // Include the tree-style dynamic section component
                                                        include __DIR__ . '/dynamic-section-component.php';
                                                    }
                                                ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Dashboard interaction handlers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize ApexCharts
    initializeCharts();
    
    // Initialize tree view functionality
    initializeTreeView();
});

function initializeCharts() {
    // Attendance chart
    const attendanceOptions = {
        series: [{
            name: 'Teilnahme %',
            data: <?= json_encode($attendanceHistory) ?>
        }],
        chart: {
            type: 'line',
            height: 60,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2,
            colors: ['#10b981']
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 100]
            }
        },
        markers: {
            size: 3,
            colors: ['#10b981'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 5
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val.toFixed(1) + '%';
                }
            }
        }
    };
    
    const attendanceChart = new ApexCharts(document.querySelector("#attendance-chart"), attendanceOptions);
    attendanceChart.render();
    
    // Response rate chart
    const responseOptions = {
        series: [{
            name: 'Rücklaufquote %',
            data: <?= json_encode($responseHistory) ?>
        }],
        chart: {
            type: 'line',
            height: 60,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2,
            colors: ['#3b82f6']
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1,
                stops: [0, 100]
            }
        },
        markers: {
            size: 3,
            colors: ['#3b82f6'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 5
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    return val.toFixed(1) + '%';
                }
            }
        }
    };
    
    const responseChart = new ApexCharts(document.querySelector("#response-chart"), responseOptions);
    responseChart.render();
}

function initializeTreeView() {
    // Handle arrow rotation for tree view elements
    document.querySelectorAll('.tree-node-header[data-toggle="collapse"]').forEach(function(button) {
        const icon = button.querySelector('.tree-node-icon');
        let targetSelector = button.getAttribute('data-target') || button.getAttribute('href');
        
        if (targetSelector && icon) {
            const target = document.querySelector(targetSelector);
            if (target) {
                // Set initial state - everything starts collapsed
                icon.classList.remove('expanded');
                target.classList.remove('show');
                
                // Add click handler to update arrow - ONLY toggle, don't double-check
                button.addEventListener('click', function(e) {
                    // Toggle arrow state immediately and permanently
                    if (target.classList.contains('show')) {
                        // Currently expanded, will collapse
                        icon.classList.remove('expanded');
                    } else {
                        // Currently collapsed, will expand
                        icon.classList.add('expanded');
                    }
                });
            }
        }
    });
}


</script>

<style>
/* Compact view styles */
.promises-dashboard.compact-view .sections-grid {
    grid-template-columns: 1fr;
}

.promises-dashboard.compact-view .section-card {
    display: flex;
    align-items: center;
    padding: var(--space-4);
}

.promises-dashboard.compact-view .section-header {
    flex: 1;
    padding: 0;
    border-bottom: none;
    background: none;
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.promises-dashboard.compact-view .section-content {
    display: none;
}

.promises-dashboard.compact-view .section-stats-row {
    flex-direction: row;
    align-items: center;
    min-width: 200px;
}
</style>
