<?php

class SmartDeviationDetector
{
    private $db;
    private $groupManager;
    private $minDataPoints;
    private $significanceThreshold;
    private $zScoreThreshold;

    public function __construct($db)
    {
        $this->db = $db;
        $this->groupManager = \App\Core\GroupManager::getInstance();
        $this->minDataPoints = \App\Core\DashboardConstants::MIN_DATA_POINTS_FOR_ANALYSIS;
        $this->significanceThreshold = \App\Core\DashboardConstants::SIGNIFICANCE_THRESHOLD;
        $this->zScoreThreshold = \App\Core\DashboardConstants::Z_SCORE_THRESHOLD;
    }

    /**
     * Analyze a rehearsal for deviations across all sections
     */
    public function analyzeRehearsal($rehearsalId)
    {
        $results = [
            'deviations' => [],
            'insufficient_data' => [],
            'summary' => []
        ];

        // Analyze parent group patterns first (general overall info)
        $parentGroupAnalysis = $this->analyzeParentGroupPatterns($rehearsalId);
        if (!empty($parentGroupAnalysis['deviations'])) {
            $results['deviations'] = array_merge($results['deviations'], $parentGroupAnalysis['deviations']);
        }

        // Get all sections for this rehearsal
        $sections = $this->getRehearsalSections($rehearsalId);

        foreach ($sections as $section) {
            $analysis = $this->analyzeSection($section['id'], $rehearsalId);

            if ($analysis['has_sufficient_data']) {
                if (!empty($analysis['deviations'])) {
                    $results['deviations'] = array_merge($results['deviations'], $analysis['deviations']);
                }
                $results['summary'][] = $analysis['summary'];
            } else {
                $results['insufficient_data'][] = [
                    'section' => $section['name'],
                    'data_points' => $analysis['data_points'],
                    'required' => $this->minDataPoints
                ];
            }
        }

        return $results;
    }

    /**
     * Analyze a rehearsal using pre-loaded data to avoid per-rehearsal DB queries.
     *
     * @param array $rehearsal Rehearsal row with 'id', 'start', 'groups'
     * @param array $stats Pre-computed ['attending' => int, 'not_attending' => int, 'no_response' => int]
     * @param array $members Pre-loaded members for this rehearsal (each with 'type', 'status')
     * @return array Same shape as analyzeRehearsal()
     */
    public function analyzeRehearsalFromData(array $rehearsal, array $stats, array $members): array
    {
        $results = [
            'deviations' => [],
            'insufficient_data' => [],
            'summary' => []
        ];

        $parentGroupAnalysis = $this->analyzeParentGroupPatternsFromData($rehearsal, $stats);
        if (!empty($parentGroupAnalysis['deviations'])) {
            $results['deviations'] = array_merge($results['deviations'], $parentGroupAnalysis['deviations']);
        }

        // Derive sections from pre-loaded members
        $groups = [];
        foreach ($rehearsal['groups'] ?? [] as $name) {
            $groups[$name] = 0;
        }

        // Group members by section type and compute current attendance from memory
        $sectionMembers = [];
        foreach ($members as $member) {
            $sectionId = $member['type'];
            if (!isset($sectionMembers[$sectionId])) {
                $shouldParticipate = false;
                foreach (array_keys($groups) as $rehearsalGroup) {
                    if ($this->groupManager->isUserInGroup($sectionId, $rehearsalGroup)) {
                        $shouldParticipate = true;
                        break;
                    }
                }
                if (!$shouldParticipate) continue;
                $sectionMembers[$sectionId] = [];
            }
            if (isset($sectionMembers[$sectionId])) {
                $sectionMembers[$sectionId][] = $member;
            }
        }

        if (empty($sectionMembers)) {
            return $results;
        }

        // Batch-load historical data for ALL sections in one query
        $sectionIds = array_keys($sectionMembers);
        $historicalBySection = $this->batchGetHistoricalAttendanceData($sectionIds, $rehearsal['id']);

        foreach ($sectionMembers as $sectionId => $sMembers) {
            // Compute current attendance from pre-loaded members (zero DB queries)
            $total = count($sMembers);
            $attending = 0;
            $notAttending = 0;
            foreach ($sMembers as $m) {
                if ($m['status'] === 'attending') $attending++;
                elseif ($m['status'] === 'not_attending') $notAttending++;
            }
            $noResponse = $total - $attending - $notAttending;
            $totalResponded = $attending + $notAttending;
            $attendanceRate = $totalResponded > 0 ? ($attending / $totalResponded) * 100 : 0;
            $responseRate = $total > 0 ? ($totalResponded / $total) * 100 : 0;

            $currentData = [
                'total' => $total,
                'attending' => $attending,
                'not_attending' => $notAttending,
                'no_response' => $noResponse,
                'attendance_rate' => $attendanceRate,
                'response_rate' => $responseRate
            ];

            $historicalData = $historicalBySection[$sectionId] ?? [];

            if (count($historicalData) < $this->minDataPoints) {
                $results['insufficient_data'][] = [
                    'section' => $sectionId,
                    'data_points' => count($historicalData),
                    'required' => $this->minDataPoints
                ];
                continue;
            }

            $histStats = $this->calculateStatistics($historicalData);
            $deviations = [];
            $deviations = array_merge($deviations, $this->detectAttendanceDeviations($currentData, $histStats, $sectionId));
            $deviations = array_merge($deviations, $this->detectResponseRateDeviations($currentData, $histStats, $sectionId));
            $deviations = array_merge($deviations, $this->detectPatternDeviations($currentData, $historicalData, $sectionId));

            if (!empty($deviations)) {
                $results['deviations'] = array_merge($results['deviations'], $deviations);
            }
            $results['summary'][] = [
                'section_id' => $sectionId,
                'current_attendance_rate' => $currentData['attendance_rate'],
                'historical_mean' => $histStats['mean'],
                'historical_std' => $histStats['std']
            ];
        }

        return $results;
    }

    /**
     * Analyze a specific section for deviations
     */
    private function analyzeSection($sectionId, $currentRehearsalId)
    {
        // Get historical attendance data for this section
        $historicalData = $this->getHistoricalAttendanceData($sectionId, $currentRehearsalId);

        if (count($historicalData) < $this->minDataPoints) {
            return [
                'has_sufficient_data' => false,
                'data_points' => count($historicalData)
            ];
        }

        $currentData = $this->getCurrentAttendanceData($sectionId, $currentRehearsalId);
        $deviations = [];

        // Calculate statistical measures
        $stats = $this->calculateStatistics($historicalData);

        // Test for various types of deviations
        $deviations = array_merge($deviations, $this->detectAttendanceDeviations($currentData, $stats, $sectionId));
        $deviations = array_merge($deviations, $this->detectResponseRateDeviations($currentData, $stats, $sectionId));
        $deviations = array_merge($deviations, $this->detectPatternDeviations($currentData, $historicalData, $sectionId));



        return [
            'has_sufficient_data' => true,
            'deviations' => $deviations,
            'summary' => [
                'section_id' => $sectionId,
                'current_attendance_rate' => $currentData['attendance_rate'],
                'historical_mean' => $stats['mean'],
                'historical_std' => $stats['std']
            ]
        ];
    }

    /**
     * Analyze patterns across parent groups (e.g., "Streicher", "Bläser")
     */
    private function analyzeParentGroupPatterns($rehearsalId)
    {
        $deviations = [];

        // Calculate overall tutti attendance and response rates
        $overallData = $this->calculateOverallAttendance($rehearsalId);

        if ($overallData['total_people'] > 0) {
            $rehearsalModel = new \App\Models\Rehearsal();
            $rehearsal = $rehearsalModel->findById($rehearsalId);
            $contextText = "in allen Registern";

            $rehearsalDate = new \DateTime($rehearsal['start']);
            $today = new \DateTime();
            $daysDifference = $today->diff($rehearsalDate)->days;
            $isFutureRehearsal = $rehearsalDate > $today;

            // Skip response rate analysis for rehearsals more than 14 days in the future
            // as people typically haven't been notified or expected to respond yet
            $skipResponseRateAnalysis = $isFutureRehearsal && $daysDifference > 14;

            // Check overall attendance rate
            if ($overallData['attendance_rate'] < \App\Core\DashboardConstants::GROUP_PERFORMANCE_THRESHOLD) {
                $deviations[] = [
                    'type' => 'overall_performance',
                    'severity' => 'critical',
                    'attendance_rate' => $overallData['attendance_rate'],
                    'message' => "Nur " . number_format($overallData['attendance_rate'], 0) . "% Teilnahme " . $contextText
                ];
            }

            // Check overall response rate (but skip for distant future rehearsals)
            if (!$skipResponseRateAnalysis && $overallData['response_rate'] < \App\Core\DashboardConstants::LOW_RESPONSE_RATE_THRESHOLD) {
                $severity = $overallData['response_rate'] < \App\Core\DashboardConstants::CRITICAL_RESPONSE_RATE_THRESHOLD ? 'critical' : 'warning';
                $deviations[] = [
                    'type' => 'overall_response_rate',
                    'severity' => $severity,
                    'response_rate' => $overallData['response_rate'],
                    'message' => "Nur " . number_format($overallData['response_rate'], 0) . "% Rückmeldungen " . $contextText
                ];
            }
        }

        return ['deviations' => $deviations];
    }

    /**
     * Analyze parent group patterns using pre-loaded stats.
     */
    private function analyzeParentGroupPatternsFromData(array $rehearsal, array $stats): array
    {
        $deviations = [];

        $total = ($stats['attending'] ?? 0) + ($stats['not_attending'] ?? 0) + ($stats['no_response'] ?? 0);
        if ($total <= 0) {
            return ['deviations' => $deviations];
        }

        $attendanceRate = ($stats['attending'] / $total) * 100;
        $responseRate = (($stats['attending'] + $stats['not_attending']) / $total) * 100;
        $contextText = "in allen Registern";

        $rehearsalDate = new \DateTime($rehearsal['start']);
        $today = new \DateTime();
        $daysDifference = $today->diff($rehearsalDate)->days;
        $isFutureRehearsal = $rehearsalDate > $today;
        $skipResponseRateAnalysis = $isFutureRehearsal && $daysDifference > 14;

        if ($attendanceRate < \App\Core\DashboardConstants::GROUP_PERFORMANCE_THRESHOLD) {
            $deviations[] = [
                'type' => 'overall_performance',
                'severity' => 'critical',
                'attendance_rate' => $attendanceRate,
                'message' => "Nur " . number_format($attendanceRate, 0) . "% Teilnahme " . $contextText
            ];
        }

        if (!$skipResponseRateAnalysis && $responseRate < \App\Core\DashboardConstants::LOW_RESPONSE_RATE_THRESHOLD) {
            $severity = $responseRate < \App\Core\DashboardConstants::CRITICAL_RESPONSE_RATE_THRESHOLD ? 'critical' : 'warning';
            $deviations[] = [
                'type' => 'overall_response_rate',
                'severity' => $severity,
                'response_rate' => $responseRate,
                'message' => "Nur " . number_format($responseRate, 0) . "% Rückmeldungen " . $contextText
            ];
        }

        return ['deviations' => $deviations];
    }

    /**
     * Calculate overall attendance and response rates across all sections in a rehearsal
     * Now considers small group restrictions properly
     */
    private function calculateOverallAttendance($rehearsalId)
    {
        // Get rehearsal details to check if it's a small group rehearsal
        $rehearsalModel = new \App\Models\Rehearsal();
        $rehearsal = $rehearsalModel->findById($rehearsalId);

        if (!$rehearsal) {
            return [
                'total_people' => 0,
                'total_attending' => 0,
                'total_responded' => 0,
                'attendance_rate' => 0,
                'response_rate' => 0
            ];
        }

        // Use the UserPromise model's getPromiseStats which properly handles small group logic
        $userPromiseModel = new \App\Models\UserPromise();
        $stats = $userPromiseModel->getPromiseStats($rehearsalId, $rehearsal['orchestra_id']);

        return [
            'total_people' => $stats['total'],
            'total_attending' => $stats['attending'],
            'total_responded' => $stats['attending'] + $stats['not_attending'],
            'attendance_rate' => $stats['total'] > 0 ? ($stats['attending'] / $stats['total']) * 100 : 0,
            'response_rate' => $stats['total'] > 0 ? (($stats['attending'] + $stats['not_attending']) / $stats['total']) * 100 : 0
        ];
    }

    /**
     * Get historical attendance data for a section
     */
    private function getHistoricalAttendanceData($sectionId, $excludeRehearsalId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                r.id as rehearsal_id,
                r.start as date,
                COUNT(up.id) as total_players,
                SUM(CASE WHEN up.status = 'yes' THEN 1 ELSE 0 END) as attending,
                SUM(CASE WHEN up.status = 'no' THEN 1 ELSE 0 END) as not_attending,
                SUM(CASE WHEN up.status = 'maybe' THEN 1 ELSE 0 END) as no_response
            FROM rehearsals r
            JOIN user_promises up ON r.id = up.rehearsal_id
            JOIN users u ON up.user_id = u.id
            JOIN user_orchestras uo ON u.id = uo.user_id
            WHERE uo.type = ? AND uo.orchestra_id = r.orchestra_id AND uo.is_active = 1
            AND r.id != ?
            AND r.start < (SELECT start FROM rehearsals WHERE id = ?)
            AND r.start >= DATE_SUB((SELECT start FROM rehearsals WHERE id = ?), INTERVAL 6 MONTH)
            GROUP BY r.id, r.start
            ORDER BY r.start DESC
        ");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('siii', $sectionId, $excludeRehearsalId, $excludeRehearsalId, $excludeRehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Batch-load historical attendance data for multiple sections in one query.
     *
     * @param string[] $sectionIds Section type IDs
     * @param int $excludeRehearsalId Current rehearsal to exclude
     * @return array<string, array> Keyed by section ID
     */
    private function batchGetHistoricalAttendanceData(array $sectionIds, int $excludeRehearsalId): array
    {
        if (empty($sectionIds)) return [];

        $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
        $types = str_repeat('s', count($sectionIds)) . 'iii';

        $sql = "
            SELECT 
                uo.type as section_id,
                r.id as rehearsal_id,
                r.start as date,
                COUNT(up.id) as total_players,
                SUM(CASE WHEN up.status = 'yes' THEN 1 ELSE 0 END) as attending,
                SUM(CASE WHEN up.status = 'no' THEN 1 ELSE 0 END) as not_attending,
                SUM(CASE WHEN up.status = 'maybe' THEN 1 ELSE 0 END) as no_response
            FROM rehearsals r
            JOIN user_promises up ON r.id = up.rehearsal_id
            JOIN users u ON up.user_id = u.id
            JOIN user_orchestras uo ON u.id = uo.user_id
            WHERE uo.type IN ({$placeholders}) AND uo.orchestra_id = r.orchestra_id AND uo.is_active = 1
            AND r.id != ?
            AND r.start < (SELECT start FROM rehearsals WHERE id = ?)
            AND r.start >= DATE_SUB((SELECT start FROM rehearsals WHERE id = ?), INTERVAL 6 MONTH)
            GROUP BY uo.type, r.id, r.start
            ORDER BY r.start DESC
        ";

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) return [];

        $params = [...$sectionIds, $excludeRehearsalId, $excludeRehearsalId, $excludeRehearsalId];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $bySection = [];
        while ($row = $result->fetch_assoc()) {
            $sid = $row['section_id'];
            unset($row['section_id']);
            $bySection[$sid][] = $row;
        }
        $stmt->close();
        return $bySection;
    }

    /**
     * Get current attendance data for a section
     */
    private function getCurrentAttendanceData($sectionId, $rehearsalId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(up.id) as total_players,
                SUM(CASE WHEN up.status = 'yes' THEN 1 ELSE 0 END) as attending,
                SUM(CASE WHEN up.status = 'no' THEN 1 ELSE 0 END) as not_attending,
                SUM(CASE WHEN up.status = 'maybe' THEN 1 ELSE 0 END) as no_response
            FROM user_promises up
            JOIN users u ON up.user_id = u.id
            JOIN user_orchestras uo ON u.id = uo.user_id
            JOIN rehearsals r ON up.rehearsal_id = r.id
            WHERE uo.type = ? AND up.rehearsal_id = ? AND uo.orchestra_id = r.orchestra_id AND uo.is_active = 1
        ");
        if ($stmt === false) {
            return [
                'total' => 0,
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0,
                'attendance_rate' => 0,
                'response_rate' => 0
            ];
        }
        $stmt->bind_param('si', $sectionId, $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_assoc() : null;
        if (!$data) {
            return [
                'total' => 0,
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0,
                'attendance_rate' => 0,
                'response_rate' => 0
            ];
        }
        $total = (int) $data['total_players'];
        $totalResponded = (int) $data['attending'] + (int) $data['not_attending'];

        // Attendance rate should only consider people who actually responded (yes/no)
        // People with no response or "maybe" are not counted in attendance rate
        $attendanceRate = $totalResponded > 0 ? ($data['attending'] / $totalResponded) * 100 : 0;
        $responseRate = $total > 0 ? ($totalResponded / $total) * 100 : 0;

        return [
            'total' => $total,
            'attending' => $data['attending'],
            'not_attending' => $data['not_attending'],
            'no_response' => $data['no_response'],
            'attendance_rate' => $attendanceRate,
            'response_rate' => $responseRate
        ];
    }

    /**
     * Calculate statistical measures from historical data
     */
    private function calculateStatistics($historicalData)
    {
        $attendanceRates = array_map(function ($row) {
            $totalResponded = $row['attending'] + $row['not_attending'];
            return $totalResponded > 0 ? ($row['attending'] / $totalResponded) * 100 : 0;
        }, $historicalData);

        $responseRates = array_map(function ($row) {
            $totalResponded = $row['attending'] + $row['not_attending'];
            return $row['total_players'] > 0 ? ($totalResponded / $row['total_players']) * 100 : 0;
        }, $historicalData);

        // Calculate attendance statistics
        $attendanceMean = array_sum($attendanceRates) / count($attendanceRates);
        $attendanceVariance = array_sum(array_map(function ($x) use ($attendanceMean) {
            return pow($x - $attendanceMean, 2);
        }, $attendanceRates)) / count($attendanceRates);
        $attendanceStd = sqrt($attendanceVariance);

        // Calculate response rate statistics
        $responseMean = array_sum($responseRates) / count($responseRates);
        $responseVariance = array_sum(array_map(function ($x) use ($responseMean) {
            return pow($x - $responseMean, 2);
        }, $responseRates)) / count($responseRates);
        $responseStd = sqrt($responseVariance);

        return [
            'mean' => $attendanceMean,
            'std' => $attendanceStd,
            'variance' => $attendanceVariance,
            'count' => count($attendanceRates),
            'min' => min($attendanceRates),
            'max' => max($attendanceRates),
            'response_mean' => $responseMean,
            'response_std' => $responseStd,
            'response_variance' => $responseVariance,
            'response_min' => min($responseRates),
            'response_max' => max($responseRates)
        ];
    }

    /**
     * Detect attendance rate deviations using statistical tests
     */
    private function detectAttendanceDeviations($currentData, $stats, $sectionId)
    {
        $deviations = [];

        if ($stats['std'] > 0) {
            $zScore = abs(($currentData['attendance_rate'] - $stats['mean']) / $stats['std']);

            if ($zScore > $this->zScoreThreshold) {
                $isPositive = $currentData['attendance_rate'] > $stats['mean'];
                $direction = $isPositive ? 'higher' : 'lower';
                $percentageDiff = abs($currentData['attendance_rate'] - $stats['mean']);
                // Only show significant deviations
                if ($percentageDiff > \App\Core\DashboardConstants::PERCENTAGE_DIFFERENCE_THRESHOLD) {
                    $deviations[] = [
                        'type' => $isPositive ? 'positive_statistical_anomaly' : 'negative_statistical_anomaly',
                        'severity' => $isPositive ? 'positive' : ($zScore > \App\Core\DashboardConstants::CRITICAL_Z_SCORE_THRESHOLD ? 'critical' : 'warning'),
                        'z_score' => $zScore,
                        'current_rate' => $currentData['attendance_rate'],
                        'historical_mean' => $stats['mean'],
                        'section' => $sectionId,
                        'comparison_kind' => 'usual',
                        'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% " . ($direction === 'lower' ? 'weniger' : 'mehr') . " Teilnahme als üblich"
                    ];
                }
            }
        }

        if ($currentData['attendance_rate'] < $stats['min']) {
            $percentageDiff = $stats['min'] - $currentData['attendance_rate'];
            $deviations[] = [
                'type' => 'below_historical_minimum',
                'severity' => 'warning',
                'current_rate' => $currentData['attendance_rate'],
                'historical_min' => $stats['min'],
                'section' => $sectionId,
                'comparison_kind' => 'all_time',
                'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% weniger Teilnahme als je zuvor"
            ];
        }

        // Detect if attendance is above historical maximum
        if ($currentData['attendance_rate'] > $stats['max']) {
            $percentageDiff = $currentData['attendance_rate'] - $stats['max'];
            $deviations[] = [
                'type' => 'above_historical_maximum',
                'severity' => 'positive',
                'current_rate' => $currentData['attendance_rate'],
                'historical_max' => $stats['max'],
                'section' => $sectionId,
                'comparison_kind' => 'all_time',
                'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% mehr Teilnahme als je zuvor"
            ];
        }

        return $deviations;
    }

    /**
     * Detect response rate deviations using statistical tests
     */
    private function detectResponseRateDeviations($currentData, $stats, $sectionId)
    {
        $deviations = [];

        if ($stats['response_std'] > 0) {
            $zScore = abs(($currentData['response_rate'] - $stats['response_mean']) / $stats['response_std']);

            if ($zScore > $this->zScoreThreshold) {
                $isPositive = $currentData['response_rate'] > $stats['response_mean'];
                $direction = $isPositive ? 'higher' : 'lower';
                $percentageDiff = abs($currentData['response_rate'] - $stats['response_mean']);
                // Only show significant deviations
                if ($percentageDiff > \App\Core\DashboardConstants::PERCENTAGE_DIFFERENCE_THRESHOLD) {
                    $deviations[] = [
                        'type' => $isPositive ? 'positive_response_rate_anomaly' : 'negative_response_rate_anomaly',
                        'severity' => $isPositive ? 'positive' : ($zScore > \App\Core\DashboardConstants::CRITICAL_Z_SCORE_THRESHOLD ? 'critical' : 'warning'),
                        'z_score' => $zScore,
                        'current_rate' => $currentData['response_rate'],
                        'historical_mean' => $stats['response_mean'],
                        'section' => $sectionId,
                        'comparison_kind' => 'usual',
                        'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% " . ($direction === 'lower' ? 'weniger' : 'mehr') . " Rückmeldungen als üblich"
                    ];
                }
            }
        }

        // Detect if response rate is below historical minimum
        if ($currentData['response_rate'] < $stats['response_min']) {
            $percentageDiff = $stats['response_min'] - $currentData['response_rate'];
            $deviations[] = [
                'type' => 'below_historical_response_minimum',
                'severity' => 'warning',
                'current_rate' => $currentData['response_rate'],
                'historical_min' => $stats['response_min'],
                'section' => $sectionId,
                'comparison_kind' => 'all_time',
                'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% weniger Rückmeldungen als je zuvor"
            ];
        }

        // Detect if response rate is above historical maximum
        if ($currentData['response_rate'] > $stats['response_max']) {
            $percentageDiff = $currentData['response_rate'] - $stats['response_max'];
            $deviations[] = [
                'type' => 'above_historical_response_maximum',
                'severity' => 'positive',
                'current_rate' => $currentData['response_rate'],
                'historical_max' => $stats['response_max'],
                'section' => $sectionId,
                'comparison_kind' => 'all_time',
                'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% mehr Rückmeldungen als je zuvor"
            ];
        }

        return $deviations;
    }

    /**
     * Detect pattern deviations (trends, seasonality, etc.)
     */
    private function detectPatternDeviations($currentData, $historicalData, $sectionId)
    {
        $deviations = [];

        // Detect trend changes - now properly including current rehearsal in the analysis
        if (count($historicalData) >= 7) { // Need at least 7 historical + 1 current = 8 total
            // Include current rehearsal in recent data for accurate trend analysis
            $recentData = array_slice($historicalData, 0, 3); // Take 3 most recent historical
            $olderData = array_slice($historicalData, 3, 4);  // Take next 4 older historical

            // Calculate recent average including current rehearsal
            $recentRates = array_map(function ($row) {
                $totalResponded = $row['attending'] + $row['not_attending'];
                return $totalResponded > 0 ? ($row['attending'] / $totalResponded) * 100 : 0;
            }, $recentData);
            $recentRates[] = $currentData['attendance_rate']; // Add current rehearsal
            $recentAvg = array_sum($recentRates) / count($recentRates);

            // Calculate older average
            $olderAvg = array_sum(array_map(function ($row) {
                $totalResponded = $row['attending'] + $row['not_attending'];
                return $totalResponded > 0 ? ($row['attending'] / $totalResponded) * 100 : 0;
            }, $olderData)) / count($olderData);

            $trendChange = abs($recentAvg - $olderAvg);
            if ($trendChange > \App\Core\DashboardConstants::TREND_CHANGE_THRESHOLD) {
                $isPositive = $recentAvg > $olderAvg;
                $direction = $isPositive ? 'rising' : 'falling';

                // Don't show positive trend messages when current attendance is 0%
                // This prevents misleading messages like "38% more attendance than before"
                // when the current rehearsal actually has no attendees
                $currentHasZeroAttendance = $currentData['attendance_rate'] == 0;
                $shouldSkipPositiveTrend = $isPositive && $currentHasZeroAttendance;

                if (!$shouldSkipPositiveTrend) {
                    $deviations[] = [
                        'type' => $isPositive ? 'positive_trend_change' : 'negative_trend_change',
                        'severity' => $isPositive ? 'positive' : 'info',
                        'trend_change' => $trendChange,
                        'recent_avg' => $recentAvg,
                        'older_avg' => $olderAvg,
                        'section' => $sectionId,
                        'comparison_kind' => 'trend',
                        'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($trendChange, 0) . "% " . ($direction === 'rising' ? 'mehr' : 'weniger') . " Teilnahme als früher"
                    ];
                }
            }
        }

        return $deviations;
    }

    /**
     * Get all sections for a rehearsal (only sections that are supposed to participate AND have users in this rehearsal)
     */
    private function getRehearsalSections($rehearsalId)
    {
        // First, get the groups that are supposed to participate in this rehearsal
        $rehearsalModel = new \App\Models\Rehearsal();
        $rehearsalGroups = $rehearsalModel->getGroupsAsAssoc($rehearsalId);
        $groupManager = \App\Core\GroupManager::getInstance();

        // Get all sections that have users in this rehearsal
        $stmt = $this->db->prepare("
            SELECT DISTINCT uo.type as id, uo.type as name, COUNT(up.id) as user_count
            FROM user_promises up
            JOIN users u ON up.user_id = u.id
            JOIN user_orchestras uo ON u.id = uo.user_id
            JOIN rehearsals r ON up.rehearsal_id = r.id
            WHERE up.rehearsal_id = ? AND uo.orchestra_id = r.orchestra_id AND uo.is_active = 1
            GROUP BY uo.type
            HAVING user_count > 0
            ORDER BY uo.type
        ");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $allSections = $result->fetch_all(MYSQLI_ASSOC);

        // Filter to only include sections that are supposed to participate in this rehearsal
        $relevantSections = [];
        foreach ($allSections as $section) {
            $sectionId = $section['id'];

            // Check if this section is supposed to participate in the rehearsal
            $shouldParticipate = false;
            foreach (array_keys($rehearsalGroups) as $rehearsalGroup) {
                if ($groupManager->isUserInGroup($sectionId, $rehearsalGroup)) {
                    $shouldParticipate = true;
                    break;
                }
            }

            if ($shouldParticipate) {
                $relevantSections[] = $section;
            }
        }

        return $relevantSections;
    }
}
