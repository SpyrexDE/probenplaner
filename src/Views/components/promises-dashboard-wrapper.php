<?php
/**
 * Modern Promises Dashboard Wrapper
 * 
 * Enterprise-level dashboard interface for orchestra attendance management.
 * Provides overview metrics, filtering, and modern card-based section display.
 */

use App\Core\DashboardConstants;

// Calculate overall statistics across last 10 rehearsals
$rehearsalsForStats = array_slice($rehearsals ?? [], -10);
$totalRehearsals = count($rehearsalsForStats);
$totalPromises = 0;
$totalAttending = 0;
$totalNotAttending = 0;
$totalNoResponse = 0;

// Critical sections counter
$criticalSections = 0;
$warningSections = 0;

foreach ($rehearsalsForStats as $rehearsal) {
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

// Calculate real trends by comparing current period vs previous period
$attendanceTrend = 'neutral';
$responseTrend = 'neutral';
$attendanceTrendValue = 0;
$responseTrendValue = 0;

// Get more historical data for trend calculation (last 20 rehearsals)
$rehearsalsForTrends = array_slice($rehearsals ?? [], -20);
$currentPeriodRehearsals = array_slice($rehearsalsForTrends, -10); // Last 10
$previousPeriodRehearsals = array_slice($rehearsalsForTrends, 0, 10); // Previous 10

if (count($currentPeriodRehearsals) >= 5 && count($previousPeriodRehearsals) >= 5) {
    // Calculate current period averages
    $currentAttendanceTotal = 0;
    $currentResponseTotal = 0;
    $currentTotalPromises = 0;
    
    foreach ($currentPeriodRehearsals as $rehearsal) {
        $rehearsalId = $rehearsal['id'];
        if (isset($stats[$rehearsalId])) {
            $attending = $stats[$rehearsalId]['attending'] ?? 0;
            $notAttending = $stats[$rehearsalId]['not_attending'] ?? 0;
            $noResponse = $stats[$rehearsalId]['no_response'] ?? 0;
            $total = $attending + $notAttending + $noResponse;
            
            if ($total > 0) {
                $currentAttendanceTotal += ($attending / $total) * 100;
                $currentResponseTotal += (($attending + $notAttending) / $total) * 100;
                $currentTotalPromises++;
            }
        }
    }
    
    // Calculate previous period averages
    $previousAttendanceTotal = 0;
    $previousResponseTotal = 0;
    $previousTotalPromises = 0;
    
    foreach ($previousPeriodRehearsals as $rehearsal) {
        $rehearsalId = $rehearsal['id'];
        if (isset($stats[$rehearsalId])) {
            $attending = $stats[$rehearsalId]['attending'] ?? 0;
            $notAttending = $stats[$rehearsalId]['not_attending'] ?? 0;
            $noResponse = $stats[$rehearsalId]['no_response'] ?? 0;
            $total = $attending + $notAttending + $noResponse;
            
            if ($total > 0) {
                $previousAttendanceTotal += ($attending / $total) * 100;
                $previousResponseTotal += (($attending + $notAttending) / $total) * 100;
                $previousTotalPromises++;
            }
        }
    }
    
    // Calculate trend values
    if ($currentTotalPromises > 0 && $previousTotalPromises > 0) {
        $currentAttendanceAvg = $currentAttendanceTotal / $currentTotalPromises;
        $previousAttendanceAvg = $previousAttendanceTotal / $previousTotalPromises;
        $currentResponseAvg = $currentResponseTotal / $currentTotalPromises;
        $previousResponseAvg = $previousResponseTotal / $previousTotalPromises;
        
        // Attendance trend
        $attendanceTrendValue = $currentAttendanceAvg - $previousAttendanceAvg;
        if (abs($attendanceTrendValue) > 0.5) { // Only show trend if change is significant
            $attendanceTrend = $attendanceTrendValue > 0 ? 'positive' : 'negative';
        }
        
        // Response trend
        $responseTrendValue = $currentResponseAvg - $previousResponseAvg;
        if (abs($responseTrendValue) > 0.5) { // Only show trend if change is significant
            $responseTrend = $responseTrendValue > 0 ? 'positive' : 'negative';
        }
    }
}

?>

<!-- Include the dashboard CSS and ApexCharts -->
<link rel="stylesheet" href="<?= '/assets/css/promises-dashboard.css' ?>">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<?php
// Generate time-based data for charts (last 10 rehearsals only)
$attendanceHistory = [];
$responseHistory = [];
$rehearsalDates = [];
$currentRehearsalIndex = 0;

// Get only the last 10 rehearsals for chart data
$rehearsalsForCharts = array_slice($rehearsals ?? [], -10);

foreach ($rehearsalsForCharts as $rehearsal) {
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
            
            if ($attendanceRate < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD) {
                $criticalSectionsCount++;
            } elseif ($attendanceRate < DashboardConstants::WARNING_ATTENDANCE_THRESHOLD) {
                $warningSectionsCount++;
            }
        }
    }
}
?>

<div class="promises-dashboard">
    <!-- Modern Analytics Overview -->
    <div class="analytics-overview">
        <div class="analytics-card attendance-card">
            <div class="analytics-card-background"></div>
            <div class="analytics-card-content">
                <div class="analytics-header">
                    <div class="analytics-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="analytics-info">
                        <h3 class="analytics-title">Zusagendurchschnitt</h3>
                        <div class="analytics-value"><?= number_format($overallAttendanceRate, 1) ?>%</div>
                    </div>
                </div>
                <div class="analytics-chart" id="attendance-chart"></div>
                <div class="analytics-subtitle"><?= ($showOld ?? false) ? 'Durchschnittliche Teilnahme der letzten 10 eingetragenen Proben' : 'Durchschnittliche Teilnahme für die anstehenden 10 Proben' ?></div>
                <div class="analytics-trend">
                    <?php if ($attendanceTrend !== 'neutral'): ?>
                        <i class="fas fa-arrow-<?= $attendanceTrend === 'positive' ? 'up' : 'down' ?>"></i>
                        <span><?= $attendanceTrend === 'positive' ? '+' : '' ?><?= number_format($attendanceTrendValue, 1) ?>% vs. vorherigen 10 Proben</span>
                    <?php else: ?>
                        <i class="fas fa-minus"></i>
                        <span>Änderung <0.5% vs. vorherigen 10 Proben</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="analytics-card response-card">
            <div class="analytics-card-background"></div>
            <div class="analytics-card-content">
                <div class="analytics-header">
                    <div class="analytics-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                    <div class="analytics-info">
                        <h3 class="analytics-title">Rückmeldungsquote</h3>
                        <div class="analytics-value"><?= number_format($overallResponseRate, 1) ?>%</div>
                    </div>
                </div>
                <div class="analytics-chart" id="response-chart"></div>
                <div class="analytics-subtitle"><?= ($showOld ?? false) ? 'Durchschnittliche Rückmeldungsquote der letzten 10 eingetragenen Proben' : 'Durchschnittliche Rückmeldungsquote für die anstehenden 10 Proben' ?></div>
                <div class="analytics-trend">
                    <?php if ($responseTrend !== 'neutral'): ?>
                        <i class="fas fa-arrow-<?= $responseTrend === 'positive' ? 'up' : 'down' ?>"></i>
                        <span><?= $responseTrend === 'positive' ? '+' : '' ?><?= number_format($responseTrendValue, 1) ?>% vs. vorherigen 10 Proben</span>
                    <?php else: ?>
                        <i class="fas fa-minus"></i>
                        <span>Änderung <0.5% vs. vorherigen 10 Proben</span>
                    <?php endif; ?>
                </div>
            </div>
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
                            <span class="rehearsal-date"><?= date(DashboardConstants::DISPLAY_DATE_FORMAT, strtotime($rehearsal['date'])) ?></span>
                        </div>
                        
                        <div class="rehearsal-compact-right">
                                                    <div class="rehearsal-compact-meta">
                            <span><i class="fas fa-clock"></i> <?= isset($rehearsal['start_time']) ? substr($rehearsal['start_time'], 0, DashboardConstants::TIME_SUBSTRING_LENGTH) : '??:??' ?> - <?= isset($rehearsal['end_time']) ? substr($rehearsal['end_time'], 0, DashboardConstants::TIME_SUBSTRING_LENGTH) : '??:??' ?></span>
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
                    // Use smart deviation detection system
                    require_once __DIR__ . '/../../Core/SmartDeviationDetector.php';
                    $deviationDetector = new SmartDeviationDetector(\App\Core\Database::getInstance());
                    $deviationAnalysis = $deviationDetector->analyzeRehearsal($rehearsal['id']);
                    
                    // Get rehearsal groups to determine which sections should participate
                    $rehearsalModel = new \App\Models\Rehearsal(\App\Core\Database::getInstance());
                    $rehearsalGroups = $rehearsalModel->getGroupsAsAssoc($rehearsalId);
                    
                    // Calculate critical sections (show individual instruments with low attendance, fallback to sections)
                    $rehearsalCriticalSections = [];
                    
                    // First check individual instruments for more specific critical items
                    $individualInstruments = [];
                    foreach ($membersBySection[$rehearsalId]['all'] ?? [] as $member) {
                        $instrumentId = $member['type'];
                        if (!isset($individualInstruments[$instrumentId])) {
                            $individualInstruments[$instrumentId] = [];
                        }
                        $individualInstruments[$instrumentId][] = $member;
                    }
                    
                    // Calculate attendance for individual instruments
                    foreach ($individualInstruments as $instrumentId => $players) {
                        $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
                        $total = count($players);
                        if ($total > 0) {
                            $attendanceRate = ($attending / $total) * 100;
                            if ($attendanceRate < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD) { // Only show if attendance is low
                                $rehearsalCriticalSections[] = [
                                    'name' => $groupManager->getDisplayName($instrumentId),
                                    'rate' => $attendanceRate,
                                    'total' => $total,
                                    'attending' => $attending
                                ];
                            }
                        }
                    }
                    
                    // If no individual instruments with low attendance, check top-level sections
                    if (empty($rehearsalCriticalSections)) {
                        foreach ($sectionPlayers as $sectionId => $players) {
                            $attending = count(array_filter($players, function($m) { return $m['status'] === 'attending'; }));
                            $total = count($players);
                                                    if ($total > 0) {
                            $attendanceRate = ($attending / $total) * 100;
                            if ($attendanceRate < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD) { // Only show if attendance is low
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
                    

                    
                    // Sort by attendance rate and take top most critical
                    usort($rehearsalCriticalSections, function($a, $b) {
                        return $a['rate'] - $b['rate'];
                    });
                    $rehearsalCriticalSections = array_slice($rehearsalCriticalSections, 0, DashboardConstants::MAX_CRITICAL_SECTIONS_DISPLAY);
                    
                    // Only show critical sections if they actually have low attendance
                    $rehearsalCriticalSections = array_filter($rehearsalCriticalSections, function($critical) {
                        return $critical['rate'] < DashboardConstants::CRITICAL_ATTENDANCE_THRESHOLD;
                    });
                    
                    // Get critical section names for filtering
                    $criticalSectionNames = array_map(function($critical) {
                        return $critical['name'];
                    }, $rehearsalCriticalSections);
                    
                    // Filter out deviations that are already shown in critical sections and only show warning/critical ones
                    $rehearsalSmartDeviations = array_filter($deviationAnalysis['deviations'], function($deviation) use ($criticalSectionNames) {
                        // Only show warning and critical severity (skip info)
                        if (($deviation['severity'] ?? 'info') === 'info') {
                            return false;
                        }
                        // Keep deviations that don't have a specific section
                        if (!isset($deviation['section'])) {
                            return true;
                        }
                        // Filter out deviations for sections already in critical list
                        return !in_array($deviation['section'], $criticalSectionNames);
                    });
                    
                    // Remove duplicate messages and group similar ones
                    $uniqueDeviations = [];
                    $seenMessages = [];
                    $groupedMessages = [];
                    
                    // Separate group performance messages to handle them specially
                    $groupPerformanceMessages = [];
                    $otherMessages = [];
                    
                    foreach ($rehearsalSmartDeviations as $deviation) {
                        if ($deviation['type'] === 'group_performance') {
                            $groupPerformanceMessages[] = $deviation;
                        } else {
                            $otherMessages[] = $deviation;
                        }
                    }
                    
                    // Keep only the most critical group performance message
                    if (!empty($groupPerformanceMessages)) {
                        usort($groupPerformanceMessages, function($a, $b) {
                            return $a['mean_rate'] - $b['mean_rate']; // Lowest rate first (most critical)
                        });
                        $uniqueDeviations[] = $groupPerformanceMessages[0]; // Only the most critical one
                    }
                    
                    // Handle other messages
                    foreach ($otherMessages as $deviation) {
                        $messageKey = $deviation['message'];
                        
                        // Check if this is a similar message pattern
                        $isSimilar = false;
                        foreach ($seenMessages as $seen) {
                            if (strpos($messageKey, 'mehr Teilnahme als üblich') !== false && 
                                strpos($seen, 'mehr Teilnahme als üblich') !== false) {
                                $isSimilar = true;
                                break;
                            }
                            if (strpos($messageKey, 'weniger Teilnahme als üblich') !== false && 
                                strpos($seen, 'weniger Teilnahme als üblich') !== false) {
                                $isSimilar = true;
                                break;
                            }
                        }
                        
                        if (!$isSimilar && !in_array($messageKey, $seenMessages)) {
                            $uniqueDeviations[] = $deviation;
                            $seenMessages[] = $messageKey;
                        }
                    }
                    $rehearsalSmartDeviations = $uniqueDeviations;
                    
                    // Sort deviations by severity (critical first, then warning, then info)
                    usort($rehearsalSmartDeviations, function($a, $b) {
                        $severityOrder = ['critical' => 3, 'warning' => 2, 'info' => 1];
                        $aSeverity = $severityOrder[$a['severity'] ?? 'info'] ?? 1;
                        $bSeverity = $severityOrder[$b['severity'] ?? 'info'] ?? 1;
                        return $bSeverity - $aSeverity; // Critical first
                    });
                    $insufficientData = $deviationAnalysis['insufficient_data'];
                    ?>
                    
                    <!-- Critical Sections & Smart Insights -->
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
                                        <span class="critical-percentage <?= DashboardConstants::CSS_WARNING_CLASS ?>">
                                            <i class="fas fa-<?= getDeviationIcon($deviation['type']) ?>"></i>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (!empty($insufficientData)): ?>
                                    <div class="critical-item">
                                        <span class="critical-name">Fehlende Daten für Vergleichsanalyse</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (empty($rehearsalSmartDeviations) && empty($insufficientData)): ?>
                                    <div class="critical-item">
                                        <span class="critical-name">Keine Auffälligkeiten</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
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
                                        <i class="tree-node-stat-icon fas fa-question-circle status-<?= DashboardConstants::CSS_NO_RESPONSE_CLASS ?>"></i>
                                        <span><?= $noResponseCount ?></span>
                                    </div>
                                    <div class="tree-node-stat">
                                        <i class="tree-node-stat-icon fas fa-check-circle status-<?= DashboardConstants::CSS_ATTENDING_CLASS ?>"></i>
                                        <span><?= $attendingCount ?></span>
                                    </div>
                                    <div class="tree-node-stat">
                                        <i class="tree-node-stat-icon fas fa-times-circle status-<?= DashboardConstants::CSS_NOT_ATTENDING_CLASS ?>"></i>
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

<script src="/assets/js/promises-shared.js"></script>
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
            name: 'Zusagen %',
            data: <?= json_encode($attendanceHistory) ?>
        }],
        colors: ['#10b981'],
        chart: {
            type: 'area',
            height: <?= DashboardConstants::CHART_HEIGHT ?>,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        xaxis: {
            categories: <?= json_encode(array_map(function($rehearsal) {
                return ($rehearsal['type'] ?? 'Probe') . ' ' . date('d.m', strtotime($rehearsal['date']));
            }, $rehearsalsForCharts)) ?>
        },
        stroke: {
            curve: 'smooth',
            width: 3,
            colors: ['#10b981'],
            gradient: {
                enabled: true,
                type: 'horizontal',
                shadeIntensity: 0.5,
                opacityFrom: 1,
                opacityTo: 0.8,
                stops: [0, 100]
            }
        },
        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 0.3,
                opacityFrom: 0.2,
                opacityTo: 0.05,
                stops: [0, 100],
                colorStops: [
                    { offset: 0, color: '#10b981', opacity: 0.2 },
                    { offset: 100, color: '#34d399', opacity: 0.05 }
                ]
            }
        },
        markers: {
            size: 4,
            colors: ['#10b981'],
            strokeColors: '#ffffff',
            strokeWidth: 2,
            hover: {
                size: 6
            }
        },
        tooltip: {
            theme: 'light',
            x: {
                formatter: function(val, opts) {
                    const categories = <?= json_encode(array_map(function($rehearsal) {
                        return ($rehearsal['type'] ?? 'Probe') . ' ' . date('d.m', strtotime($rehearsal['date']));
                    }, $rehearsalsForCharts)) ?>;
                    return categories[opts.dataPointIndex] || val;
                }
            },
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
            name: 'Rückmeldungen %',
            data: <?= json_encode($responseHistory) ?>
        }],
        colors: ['#3b82f6'],
        chart: {
            type: 'area',
            height: <?= DashboardConstants::CHART_HEIGHT ?>,
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
            width: 3,
            colors: ['#3b82f6'],
            gradient: {
                enabled: true,
                type: 'horizontal',
                shadeIntensity: 0.5,
                opacityFrom: 1,
                opacityTo: 0.8,
                stops: [0, 100]
            }
        },
        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 0.3,
                opacityFrom: 0.2,
                opacityTo: 0.05,
                stops: [0, 100],
                colorStops: [
                    { offset: 0, color: '#3b82f6', opacity: 0.2 },
                    { offset: 100, color: '#60a5fa', opacity: 0.05 }
                ]
            }
        },
        markers: {
            size: 4,
            colors: ['#3b82f6'],
            strokeColors: '#ffffff',
            strokeWidth: 2,
            hover: {
                size: 6
            }
        },
        tooltip: {
            theme: 'light',
            x: {
                formatter: function(val, opts) {
                    const categories = <?= json_encode(array_map(function($rehearsal) {
                        return ($rehearsal['type'] ?? 'Probe') . ' ' . date('d.m', strtotime($rehearsal['date']));
                    }, $rehearsalsForCharts)) ?>;
                    return categories[opts.dataPointIndex] || val;
                }
            },
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
                
                // Let Bootstrap handle collapse, but make arrow immediate
                button.addEventListener('click', function(e) {
                    // Toggle arrow state immediately
                    icon.classList.toggle('expanded');
                    
                    // Set aria-expanded immediately
                    const isExpanded = icon.classList.contains('expanded');
                    button.setAttribute('aria-expanded', isExpanded);
                    
                    // Let the default collapse behavior handle the content
                    // but sync arrow state after transition completes
                    setTimeout(() => {
                        const actualExpanded = target.classList.contains('show');
                        if (actualExpanded !== isExpanded) {
                            // Sync arrow with actual state if they don't match
                            if (actualExpanded) {
                                icon.classList.add('expanded');
                            } else {
                                icon.classList.remove('expanded');
                            }
                            button.setAttribute('aria-expanded', actualExpanded);
                        }
                    }, <?= DashboardConstants::TREE_VIEW_ANIMATION_TIMEOUT ?>); // Match the collapse.js timeout
                });
            }
        }
    });
}


</script>

<?php
/**
 * Helper function to get appropriate icon for deviation types
 */
function getDeviationIcon($type) {
    $iconMap = [
        'statistical_anomaly' => 'chart-line',
        'below_historical_minimum' => 'arrow-down',
        'low_response_rate' => 'reply',
        'trend_change' => 'chart-line',
        'group_deviation' => 'users',
        'group_performance' => 'exclamation-triangle'
    ];
    
    return $iconMap[$type] ?? 'info-circle';
}
?>

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
