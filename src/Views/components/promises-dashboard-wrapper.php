<?php

/**
 * Modern Promises Dashboard Wrapper
 * 
 * Enterprise-level dashboard interface for orchestra attendance management.
 * Provides overview metrics, filtering, and modern card-based section display.
 */

use App\Core\DashboardConstants;
use App\Core\Utilities;

if (!function_exists('renderUserItem')) {
    function renderUserItem(array $member, string $status, string $additionalInfo = ''): void
    {
        $displayName = htmlspecialchars($member['display_name'] ?? $member['email'] ?? '');
        $note = !empty($member['note']) ? htmlspecialchars($member['note']) : '';
        $memberStatus = $status ?: ($member['status'] ?? 'no_response');
        $userLabels = Utilities::generateUserLabels($member);

        $iconClass = 'fas fa-question-circle';
        switch ($memberStatus) {
            case 'attending':
                $iconClass = 'fas fa-check-circle';
                break;
            case 'not_attending':
                $iconClass = 'fas fa-times-circle';
                break;
        }
        $userId = $member['user_id'] ?? $member['id'] ?? '';
        echo '<li class="tree-user-item userSpan" data-user-id="' . $userId . '">';
        echo '<i class="tree-user-item-icon fas fa-user"></i>';
        echo '<div class="tree-user-item-content">';
        echo '<span class="tree-user-item-name">' . $displayName . $userLabels . '</span>';
        if ($additionalInfo) {
            echo '<span class="tree-user-item-info">' . $additionalInfo . '</span>';
        }
        if ($note) {
            echo '<span class="tree-user-item-note">' . icon('quote-left', 'tree-user-note-icon') . ' ' . $note . '</span>';
        }
        echo '</div>';
        echo '<div class="tree-user-item-status"><i class="tree-user-item-status-icon ' . $iconClass . ' status-' . $memberStatus . '"></i></div>';
        echo '</li>';
    }
}

if (!function_exists('sortPlayersByStatus')) {
    function sortPlayersByStatus(array &$players): void
    {
        usort($players, function ($a, $b) {
            static $order = ['not_attending' => 0, 'attending' => 1, 'no_response' => 2];
            $d = ($order[$a['status']] ?? 3) - ($order[$b['status']] ?? 3);
            return $d !== 0 ? $d : strcasecmp($a['display_name'] ?? '', $b['display_name'] ?? '');
        });
    }
}

$lazyPartial = $lazyPartial ?? false;

// Shared card variable prep — used by both lazy partial and main loop
$groupManager = \App\Core\GroupManager::getInstance();
$prepareCardVars = function (array $rehearsal) use ($stats, $membersBySection, $groupManager) {
    $id = $rehearsal['id'];
    $a = $stats[$id]['attending'] ?? 0;
    $na = $stats[$id]['not_attending'] ?? 0;
    $nr = $stats[$id]['no_response'] ?? 0;
    $total = $a + $na + $nr;

    $sectionPlayers = [];
    if (!empty($membersBySection[$id]['all'])) {
        $topLevelSections = [];
        foreach ($groupManager->getRootNodes() as $root) {
            if (!empty($root['children'])) {
                foreach ($root['children'] as $child) {
                    $topLevelSections[$child['id']] = $child;
                }
            } else {
                $topLevelSections[$root['id']] = $root;
            }
        }
        if (empty($topLevelSections)) {
            foreach ($groupManager->getAllGroups() as $group) {
                $topLevelSections[$group['id']] = $group;
            }
        }
        foreach ($topLevelSections as $sectionId => $sectionData) {
            $sectionPlayers[$sectionId] = [];
            foreach ($membersBySection[$id]['all'] as $member) {
                if ($groupManager->isUserInGroup($member['type'], $sectionId)) {
                    $sectionPlayers[$sectionId][] = $member;
                }
            }
        }
    }

    return [
        'rehearsalId' => $id,
        'rehearsalDate' => $rehearsal['date'],
        'rehearsalStartTime' => $rehearsal['start_time'],
        'rehearsalEndTime' => $rehearsal['end_time'],
        'attendingCount' => $a,
        'notAttendingCount' => $na,
        'noResponseCount' => $nr,
        'totalCount' => $total,
        'rehearsalAttendanceRate' => $total > 0 ? ($a / $total) * 100 : 0,
        'sectionPlayers' => $sectionPlayers,
        'rehearsalColor' => $rehearsal['color'] ?? null,
    ];
};

// Lazy partial: raw cards only — no wrapper, analytics, or scripts
if ($lazyPartial) {
    $smartDisplay = new \App\Core\SmartGroupDisplay();
    foreach ($rehearsals as $rehearsal) {
        extract($prepareCardVars($rehearsal));
        include __DIR__ . '/promises-dashboard-card.php';
    }
    return;
}


// Variable initialization
$overallAttendanceRate = 0;
$overallResponseRate = 0;
$attendanceTrend = 'neutral';
$responseTrend = 'neutral';
$attendanceTrendValue = 0;
$responseTrendValue = 0;


// Overall statistics (last 10, independent of lazy loading)
    $statsRehearsals = $analyticsRehearsals ?? $rehearsals ?? [];
    $statsData = $analyticsStats ?? $stats;
    $rehearsalsForStats = array_slice($statsRehearsals, -10);
    $analyticsCount = count($rehearsalsForStats);
    $totalPromises = 0;
    $totalAttending = 0;
    $totalNotAttending = 0;
    $totalNoResponse = 0;

    // Section counters
    $criticalSections = 0;
    $warningSections = 0;

    foreach ($rehearsalsForStats as $rehearsal) {
        $rehearsalId = $rehearsal['id'];
        if (isset($statsData[$rehearsalId])) {
            $totalAttending += $statsData[$rehearsalId]['attending'] ?? 0;
            $totalNotAttending += $statsData[$rehearsalId]['not_attending'] ?? 0;
            $totalNoResponse += $statsData[$rehearsalId]['no_response'] ?? 0;
            $totalPromises += ($statsData[$rehearsalId]['attending'] ?? 0) + ($statsData[$rehearsalId]['not_attending'] ?? 0) + ($statsData[$rehearsalId]['no_response'] ?? 0);
        }
    }

    // Percentage calculation
    $overallAttendanceRate = $totalPromises > 0 ? ($totalAttending / $totalPromises) * 100 : 0;
    $overallResponseRate = $totalPromises > 0 ? (($totalAttending + $totalNotAttending) / $totalPromises) * 100 : 0;

    // Trend calculation
    $attendanceTrend = 'neutral';
    $responseTrend = 'neutral';
    $attendanceTrendValue = 0;
    $responseTrendValue = 0;

    // Historical data (last 20)
    $rehearsalsForTrends = array_slice($statsRehearsals, -20);
    $currentPeriodRehearsals = array_slice($rehearsalsForTrends, -10); // Last 10
    $previousPeriodRehearsals = array_slice($rehearsalsForTrends, 0, 10); // Previous 10

    if (count($currentPeriodRehearsals) >= 5 && count($previousPeriodRehearsals) >= 5) {
        // Current period averages
        $currentAttendanceTotal = 0;
        $currentResponseTotal = 0;
        $currentTotalPromises = 0;

        foreach ($currentPeriodRehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            if (isset($statsData[$rehearsalId])) {
                $attending = $statsData[$rehearsalId]['attending'] ?? 0;
                $notAttending = $statsData[$rehearsalId]['not_attending'] ?? 0;
                $noResponse = $statsData[$rehearsalId]['no_response'] ?? 0;
                $total = $attending + $notAttending + $noResponse;

                if ($total > 0) {
                    $currentAttendanceTotal += ($attending / $total) * 100;
                    $currentResponseTotal += (($attending + $notAttending) / $total) * 100;
                    $currentTotalPromises++;
                }
            }
        }

        // Previous period averages
        $previousAttendanceTotal = 0;
        $previousResponseTotal = 0;
        $previousTotalPromises = 0;

        foreach ($previousPeriodRehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            if (isset($statsData[$rehearsalId])) {
                $attending = $statsData[$rehearsalId]['attending'] ?? 0;
                $notAttending = $statsData[$rehearsalId]['not_attending'] ?? 0;
                $noResponse = $statsData[$rehearsalId]['no_response'] ?? 0;
                $total = $attending + $notAttending + $noResponse;

                if ($total > 0) {
                    $previousAttendanceTotal += ($attending / $total) * 100;
                    $previousResponseTotal += (($attending + $notAttending) / $total) * 100;
                    $previousTotalPromises++;
                }
            }
        }

        // Trend values
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

<!-- Assets -->
<link rel="stylesheet" href="<?= '/assets/css/promises-dashboard.css' ?>">
<?php if (!($isLeader ?? false)): ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts" defer></script>
<?php endif; ?>

<?php
// Chart data generation
$attendanceHistory = [];
$responseHistory = [];
$rehearsalDates = [];
$currentRehearsalIndex = 0;

// Chart data processing
    // Last 10 rehearsals for charts
    $rehearsalsForCharts = array_slice($statsRehearsals, -10);

    foreach ($rehearsalsForCharts as $rehearsal) {
        $rehearsalId = $rehearsal['id'];
        $rehearsalDates[] = $rehearsal['date'];

        if (isset($statsData[$rehearsalId])) {
            $attending = $statsData[$rehearsalId]['attending'] ?? 0;
            $notAttending = $statsData[$rehearsalId]['not_attending'] ?? 0;
            $noResponse = $statsData[$rehearsalId]['no_response'] ?? 0;
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

// Critical sections calculation
$criticalSectionsCount = 0;
$warningSectionsCount = 0;

foreach ($rehearsals ?? [] as $rehearsal) {
    $rehearsalId = $rehearsal['id'];
    if (!empty($membersBySection[$rehearsalId]['all'])) {
        $groupManager = \App\Core\GroupManager::getInstance();
        $sectionPlayers = [];

        foreach ($membersBySection[$rehearsalId]['all'] as $member) {
            $userType = $member['type'];
            $resolvedType = $groupManager->resolveAlias($userType);
            $sectionKey = $groupManager->getSectionForInstrument($resolvedType) ?? $resolvedType;

            if (!isset($sectionPlayers[$sectionKey])) {
                $sectionPlayers[$sectionKey] = [];
            }
            $sectionPlayers[$sectionKey][] = $member;
        }

        foreach ($sectionPlayers as $sectionId => $players) {
            $attending = count(array_filter($players, function ($m) {
                return $m['status'] === 'attending';
            }));
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
    <!-- Rehearsals Container -->
    <div class="rehearsals-container">
        <!-- Date Separator and Load Past Button (Universal) -->
        <?php if (!$lazyPartial && (!empty($rehearsals) || ($hasPastRehearsals ?? false))): ?>
            <?php 
            $pastEndpoint = ($isAdmin ?? false) ? 'admin-past' : 'leader-past';
            $pastLazyUrl = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '') . '/promises/' . $pastEndpoint . '?offset=0';
            $pastLazyUrl .= (($currentlyViewingAll ?? false) ? '&viewAll=1' : '');
            include __DIR__ . '/date-separator.php'; 
            ?>
        <?php endif; ?>

        <!-- Analytics Overview - below HEUTE divider -->
        <?php if (!$lazyPartial && !($isLeader ?? false)): ?>
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
        <?php endif; ?>

        <?php if (empty($rehearsals)): ?>
            <?php
            $title = 'Keine Termine gefunden';
            $message = 'Es gibt aktuell keine geplanten Proben.';
            include __DIR__ . '/empty-state.php';
            ?>
        <?php else: ?>
            <?php $smartDisplay = new \App\Core\SmartGroupDisplay(); ?>
            <?php foreach ($rehearsals as $rehearsal): ?>
                <?php extract($prepareCardVars($rehearsal)); ?>
                <?php include __DIR__ . '/promises-dashboard-card.php'; ?>
            <?php endforeach; ?>

            <?php if (!$lazyPartial && ($hasMoreRehearsals ?? false)): ?>
                <?php
                $lazyBase = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
                $lazyEndpoint = ($isAdmin ?? false) ? 'admin-lazy' : 'leader-lazy';
                $lazyUrl = $lazyBase . '/promises/' . $lazyEndpoint . '?offset=' . count($rehearsals);
                $lazyUrl .= (($currentlyViewingAll ?? false) ? '&viewAll=1' : '');
                $lazyId = ($isAdmin ?? false) ? 'admin-rehearsals' : 'leader-rehearsals';
                $lazyType = 'cards';
                $lazyCount = min(3, ($totalRehearsals ?? 0) - count($rehearsals));
                include __DIR__ . '/lazy-section.php';
                ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php if (!$lazyPartial): ?>
<script src="/assets/js/promises-shared.js?v=<?= \App\Core\Version::getVersion() ?>"></script>
<script>
    // Interactions
    document.addEventListener('DOMContentLoaded', function() {

        // Resolve theme colors from CSS variables
        var rootStyle = getComputedStyle(document.documentElement);
        var successColor = rootStyle.getPropertyValue('--color-success').trim() || '#10b981';
        var successLight = rootStyle.getPropertyValue('--color-success-light').trim() || '#34d399';
        var primaryColor = rootStyle.getPropertyValue('--color-primary').trim() || '#3b82f6';
        var primaryLight = rootStyle.getPropertyValue('--color-primary-light').trim() || '#60a5fa';

        initializeCharts();

        function initializeCharts() {

            // Attendance chart
            const attendanceOptions = {
                series: [{
                    name: 'Zusagen %',
                    data: <?= json_encode($attendanceHistory) ?>
                }],
                colors: [successColor],
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
                    categories: <?= json_encode(array_map(function ($rehearsal) {
                                    return ($rehearsal['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL) . ' ' . date('d.m', strtotime($rehearsal['date']));
                                }, $rehearsalsForCharts ?? [])) ?>
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    colors: [successColor]
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 0.3,
                        opacityFrom: 0.2,
                        opacityTo: 0.05,
                        stops: [0, 100],
                        colorStops: [{
                                offset: 0,
                                color: successColor,
                                opacity: 0.2
                            },
                            {
                                offset: 100,
                                color: successLight,
                                opacity: 0.05
                            }
                        ]
                    }
                },
                markers: {
                    size: 4,
                    colors: [successColor],
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
                            const categories = <?= json_encode(array_map(function ($rehearsal) {
                                                    return ($rehearsal['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL) . ' ' . date('d.m', strtotime($rehearsal['date']));
                                                }, $rehearsalsForCharts ?? [])) ?>;
                            return categories[opts.dataPointIndex] || val;
                        }
                    },
                    y: {
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    }
                }
            };

            if (document.querySelector("#attendance-chart")) {
                const attendanceChart = new ApexCharts(document.querySelector("#attendance-chart"), attendanceOptions);
                attendanceChart.render();
            }

            // Response rate chart
            const responseOptions = {
                series: [{
                    name: 'Rückmeldungen %',
                    data: <?= json_encode($responseHistory) ?>
                }],
                colors: [primaryColor],
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
                    colors: [primaryColor]
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 0.3,
                        opacityFrom: 0.2,
                        opacityTo: 0.05,
                        stops: [0, 100],
                        colorStops: [{
                                offset: 0,
                                color: primaryColor,
                                opacity: 0.2
                            },
                            {
                                offset: 100,
                                color: primaryLight,
                                opacity: 0.05
                            }
                        ]
                    }
                },
                markers: {
                    size: 4,
                    colors: [primaryColor],
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
                            const categories = <?= json_encode(array_map(function ($rehearsal) {
                                                    return ($rehearsal['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL) . ' ' . date('d.m', strtotime($rehearsal['date']));
                                                }, $rehearsalsForCharts ?? [])) ?>;
                            return categories[opts.dataPointIndex] || val;
                        }
                    },
                    y: {
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    }
                }
            };

            if (document.querySelector("#response-chart")) {
                const responseChart = new ApexCharts(document.querySelector("#response-chart"), responseOptions);
                responseChart.render();
            }
        }

        // Event delegation: handles tree-node collapse/expand for all cards (including lazy-loaded)
    var container = document.querySelector('.rehearsals-container');
    if (container) {
        container.addEventListener('click', function(e) {
            var button = e.target.closest('.tree-node-header[data-toggle="collapse"]');
            if (!button) return;
            e.preventDefault();

            var icon = button.querySelector('.tree-node-icon');
            var targetSelector = button.getAttribute('data-target') || button.getAttribute('href');
            if (!targetSelector || !icon) return;

            var target = document.querySelector(targetSelector);
            if (!target) return;

            icon.classList.toggle('expanded');
            target.classList.toggle('show');

            var isExpanded = icon.classList.contains('expanded');
            button.setAttribute('aria-expanded', isExpanded);

            setTimeout(function() {
                var actualExpanded = target.classList.contains('show');
                if (actualExpanded !== isExpanded) {
                    icon.classList.toggle('expanded', actualExpanded);
                    button.setAttribute('aria-expanded', actualExpanded);
                }
            }, <?= DashboardConstants::TREE_VIEW_ANIMATION_TIMEOUT ?>);
        });
    }
    
    }); // End DOMContentLoaded
</script>

<?php
/**
 * Merge participation messages for the same section to avoid duplication.
 * Uses comparison_kind (usual, all_time, trend) when present; falls back to parsing message.
 */
function mergeParticipationMessages($messages)
{
    $sectionGroups = [];
    $otherMessages = [];
    $priorityByKind = ['all_time' => 3, 'trend' => 2, 'usual' => 1];
    $kindToDisplay = ['usual' => 'üblich', 'all_time' => 'je zuvor', 'trend' => 'früher'];
    $legacyPriority = ['je zuvor' => 3, 'früher' => 2, 'üblich' => 1];

    foreach ($messages as $deviation) {
        $message = $deviation['message'];
        $hasKind = isset($deviation['comparison_kind']);

        if ($hasKind && preg_match('/^([^:]+):\s*(\d+)%\s*(mehr|weniger)\s+(Teilnahme|Rückmeldungen)\s+als\s+/', $message, $matches)) {
            $section = $matches[1];
            $percentage = intval($matches[2]);
            $direction = $matches[3];
            $type = $matches[4];
            $comparisonKey = $deviation['comparison_kind'];
            $comparisonDisplay = $kindToDisplay[$comparisonKey] ?? $comparisonKey;
        } elseif (preg_match('/^([^:]+):\s*(\d+)%\s*(mehr|weniger)\s+(Teilnahme|Rückmeldungen)\s+als\s+(üblich|je zuvor|früher)/', $message, $matches)) {
            $section = $matches[1];
            $percentage = intval($matches[2]);
            $direction = $matches[3];
            $type = $matches[4];
            $comparisonKey = $matches[5];
            $comparisonDisplay = $comparisonKey;
        } else {
            $otherMessages[] = $deviation;
            continue;
        }

        if (!isset($sectionGroups[$section])) {
            $sectionGroups[$section] = [];
        }
        if (!isset($sectionGroups[$section][$type])) {
            $sectionGroups[$section][$type] = [];
        }
        if (!isset($sectionGroups[$section][$type][$direction])) {
            $sectionGroups[$section][$type][$direction] = [];
        }
        $sectionGroups[$section][$type][$direction][] = [
            'deviation' => $deviation,
            'percentage' => $percentage,
            'comparison_key' => $comparisonKey,
            'comparison_display' => $comparisonDisplay,
            'message' => $message
        ];
    }

    $mergedMessages = [];
    foreach ($sectionGroups as $section => $types) {
        foreach ($types as $type => $directions) {
            foreach ($directions as $direction => $comparisons) {
                if (count($comparisons) > 1) {
                    $priority = $priorityByKind + $legacyPriority;
                    usort($comparisons, function ($a, $b) use ($priority) {
                        return ($priority[$b['comparison_key']] ?? 0) - ($priority[$a['comparison_key']] ?? 0);
                    });
                    $primary = $comparisons[0];
                    $additionalInfo = [];
                    for ($i = 1; $i < count($comparisons); $i++) {
                        $additional = $comparisons[$i];
                        $additionalInfo[] = $additional['percentage'] . '% ' . $direction . ' als ' . $additional['comparison_display'];
                    }
                    $mergedMessage = $section . ': ' . $primary['percentage'] . '% ' . $direction . ' ' . $type . ' als ' . $primary['comparison_display'];
                    if (!empty($additionalInfo)) {
                        $mergedMessage .= ' (' . implode(', ', $additionalInfo) . '!)';
                    }
                    $mergedDeviation = $primary['deviation'];
                    $mergedDeviation['message'] = $mergedMessage;
                    $mergedMessages[] = $mergedDeviation;
                } else {
                    $mergedMessages[] = $comparisons[0]['deviation'];
                }
            }
        }
    }
    return array_merge($mergedMessages, $otherMessages);
}

/**
 * Helper function to get appropriate icon for deviation types
 */
function getDeviationIcon($type)
{
    $iconMap = [

        // Negative deviation types
        'negative_statistical_anomaly' => 'chart-line',
        'negative_response_rate_anomaly' => 'reply',
        'negative_trend_change' => 'arrow-down',
        'below_historical_minimum' => 'arrow-down',
        'below_historical_response_minimum' => 'reply',
        'low_response_rate' => 'reply',
        'group_deviation' => 'users',
        'group_performance' => 'exclamation-triangle',

        // Positive deviation types  
        'positive_statistical_anomaly' => 'arrow-up',
        'positive_response_rate_anomaly' => 'reply',
        'positive_trend_change' => 'arrow-up',
        'above_historical_maximum' => 'arrow-up',
        'above_historical_response_maximum' => 'reply'
    ];

    return $iconMap[$type] ?? 'info-circle';
}

/**
 * Helper function to get appropriate CSS class for deviation severity levels
 */
function getDeviationCssClass($severity)
{
    $cssMap = [
        'critical' => \App\Core\DashboardConstants::CSS_DANGER_CLASS,
        'warning' => \App\Core\DashboardConstants::CSS_WARNING_CLASS,
        'info' => \App\Core\DashboardConstants::CSS_WARNING_CLASS,
        'positive' => 'positive'
    ];

    return $cssMap[$severity] ?? \App\Core\DashboardConstants::CSS_WARNING_CLASS;
}
?>

<style>
    /* Modern rehearsal date display */
    .rehearsal-modern-title {
        flex: 1;
        min-width: 35%;
        max-width: 40%;
    }

    .rehearsal-date-display {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 10px 0;
    }

    .weekday-letter {
        font-size: 56px;
        font-weight: 900;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
        position: relative;
        text-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
    }

    .weekday-letter::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
        opacity: 0.6;
    }

    .date-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
    }

    .date-text {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
        line-height: 1.2;
    }

    .date-subtitle {
        font-size: 16px;
        font-weight: 600;
        color: #667eea;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
        line-height: 1.2;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .rehearsal-date-display {
            gap: 15px;
        }

        .weekday-letter {
            font-size: 44px;
        }

        .date-text {
            font-size: 20px;
        }

        .date-subtitle {
            font-size: 14px;
        }

        .rehearsal-modern-title {
            min-width: 40%;
            max-width: 45%;
        }
    }

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
<?php endif; /* !$lazyPartial */ ?>