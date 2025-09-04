<?php

class SmartDeviationDetector {
    private $db;
    private $groupManager;
    private $minDataPoints;
    private $significanceThreshold;
    private $zScoreThreshold;
    
    public function __construct($db) {
        $this->db = $db;
        $this->groupManager = new \App\Core\GroupManager();
        $this->minDataPoints = \App\Core\DashboardConstants::MIN_DATA_POINTS_FOR_ANALYSIS;
        $this->significanceThreshold = \App\Core\DashboardConstants::SIGNIFICANCE_THRESHOLD;
        $this->zScoreThreshold = \App\Core\DashboardConstants::Z_SCORE_THRESHOLD;
    }
    
    /**
     * Analyze a rehearsal for deviations across all sections
     */
    public function analyzeRehearsal($rehearsalId) {
        $results = [
            'deviations' => [],
            'insufficient_data' => [],
            'summary' => []
        ];
        
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
        
        // Analyze parent group patterns
        $parentGroupAnalysis = $this->analyzeParentGroupPatterns($rehearsalId);
        if (!empty($parentGroupAnalysis['deviations'])) {
            $results['deviations'] = array_merge($results['deviations'], $parentGroupAnalysis['deviations']);
        }
        
        return $results;
    }
    
    /**
     * Analyze a specific section for deviations
     */
    private function analyzeSection($sectionId, $currentRehearsalId) {
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
    private function analyzeParentGroupPatterns($rehearsalId) {
        $deviations = [];
        
        // Calculate overall tutti attendance instead of individual parent groups
        $overallAttendance = $this->calculateOverallAttendance($rehearsalId);
        
        if ($overallAttendance['total_people'] > 0) {
            $attendanceRate = ($overallAttendance['total_attending'] / $overallAttendance['total_people']) * 100;
            
            if ($attendanceRate < \App\Core\DashboardConstants::GROUP_PERFORMANCE_THRESHOLD) {
                $deviations[] = [
                    'type' => 'overall_performance',
                    'severity' => 'critical',
                    'attendance_rate' => $attendanceRate,
                    'message' => "Nur " . number_format($attendanceRate, 0) . "% Teilnahme in allen Registern"
                ];
            }
        }
        
        return ['deviations' => $deviations];
    }
    
    /**
     * Calculate overall attendance across all sections in a rehearsal
     */
    private function calculateOverallAttendance($rehearsalId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(up.id) as total_people,
                SUM(CASE WHEN up.status = 'yes' THEN 1 ELSE 0 END) as total_attending
            FROM user_promises up
            WHERE up.rehearsal_id = ?
        ");
        
        $stmt->bind_param('i', $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        return [
            'total_people' => (int)$data['total_people'],
            'total_attending' => (int)$data['total_attending']
        ];
    }
    
    /**
     * Get historical attendance data for a section
     */
    private function getHistoricalAttendanceData($sectionId, $excludeRehearsalId) {
        $stmt = $this->db->prepare("
            SELECT 
                r.id as rehearsal_id,
                r.date,
                COUNT(up.id) as total_players,
                SUM(CASE WHEN up.status = 'yes' THEN 1 ELSE 0 END) as attending,
                SUM(CASE WHEN up.status = 'no' THEN 1 ELSE 0 END) as not_attending,
                SUM(CASE WHEN up.status = 'maybe' THEN 1 ELSE 0 END) as no_response
            FROM rehearsals r
            JOIN user_promises up ON r.id = up.rehearsal_id
            JOIN users u ON up.user_id = u.id
            WHERE u.type = ? 
            AND r.id != ?
            AND r.date < (SELECT date FROM rehearsals WHERE id = ?)
            AND r.date >= DATE_SUB((SELECT date FROM rehearsals WHERE id = ?), INTERVAL 6 MONTH)
            GROUP BY r.id, r.date
            ORDER BY r.date DESC
        ");
        
        $stmt->bind_param('siii', $sectionId, $excludeRehearsalId, $excludeRehearsalId, $excludeRehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get current attendance data for a section
     */
    private function getCurrentAttendanceData($sectionId, $rehearsalId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(up.id) as total_players,
                SUM(CASE WHEN up.status = 'yes' THEN 1 ELSE 0 END) as attending,
                SUM(CASE WHEN up.status = 'no' THEN 1 ELSE 0 END) as not_attending,
                SUM(CASE WHEN up.status = 'maybe' THEN 1 ELSE 0 END) as no_response
            FROM user_promises up
            JOIN users u ON up.user_id = u.id
            WHERE u.type = ? AND up.rehearsal_id = ?
        ");
        
        $stmt->bind_param('si', $sectionId, $rehearsalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $total = $data['total_players'];
        $attendanceRate = $total > 0 ? ($data['attending'] / $total) * 100 : 0;
        $responseRate = $total > 0 ? (($data['attending'] + $data['not_attending']) / $total) * 100 : 0;
        
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
    private function calculateStatistics($historicalData) {
        $attendanceRates = array_map(function($row) {
            return $row['total_players'] > 0 ? ($row['attending'] / $row['total_players']) * 100 : 0;
        }, $historicalData);
        
        $responseRates = array_map(function($row) {
            return $row['total_players'] > 0 ? (($row['attending'] + $row['not_attending']) / $row['total_players']) * 100 : 0;
        }, $historicalData);
        
        // Calculate attendance statistics
        $attendanceMean = array_sum($attendanceRates) / count($attendanceRates);
        $attendanceVariance = array_sum(array_map(function($x) use ($attendanceMean) {
            return pow($x - $attendanceMean, 2);
        }, $attendanceRates)) / count($attendanceRates);
        $attendanceStd = sqrt($attendanceVariance);
        
        // Calculate response rate statistics
        $responseMean = array_sum($responseRates) / count($responseRates);
        $responseVariance = array_sum(array_map(function($x) use ($responseMean) {
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
    private function detectAttendanceDeviations($currentData, $stats, $sectionId) {
        $deviations = [];
        
        if ($stats['std'] > 0) {
            $zScore = abs(($currentData['attendance_rate'] - $stats['mean']) / $stats['std']);
            
            if ($zScore > $this->zScoreThreshold) {
                $direction = $currentData['attendance_rate'] > $stats['mean'] ? 'höher' : 'niedriger';
                $percentageDiff = abs($currentData['attendance_rate'] - $stats['mean']);
                // Only show significant deviations
                if ($percentageDiff > \App\Core\DashboardConstants::PERCENTAGE_DIFFERENCE_THRESHOLD) {
                    $deviations[] = [
                        'type' => 'statistical_anomaly',
                        'severity' => $zScore > \App\Core\DashboardConstants::CRITICAL_Z_SCORE_THRESHOLD ? 'critical' : 'warning',
                        'z_score' => $zScore,
                        'current_rate' => $currentData['attendance_rate'],
                        'historical_mean' => $stats['mean'],
                        'section' => $sectionId,
                        'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% " . ($direction === 'niedriger' ? 'weniger' : 'mehr') . " Teilnahme als üblich"
                    ];
                }
            }
        }
        
        // Detect if attendance is below historical minimum
        if ($currentData['attendance_rate'] < $stats['min']) {
            $percentageDiff = $stats['min'] - $currentData['attendance_rate'];
                            $deviations[] = [
                    'type' => 'below_historical_minimum',
                    'severity' => 'warning',
                    'current_rate' => $currentData['attendance_rate'],
                    'historical_min' => $stats['min'],
                    'section' => $sectionId,
                    'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% weniger Teilnahme als je zuvor"
                ];
        }
        
        return $deviations;
    }
    
    /**
     * Detect response rate deviations using statistical tests
     */
    private function detectResponseRateDeviations($currentData, $stats, $sectionId) {
        $deviations = [];
        
        if ($stats['response_std'] > 0) {
            $zScore = abs(($currentData['response_rate'] - $stats['response_mean']) / $stats['response_std']);
            
            if ($zScore > $this->zScoreThreshold) {
                $direction = $currentData['response_rate'] > $stats['response_mean'] ? 'höher' : 'niedriger';
                $percentageDiff = abs($currentData['response_rate'] - $stats['response_mean']);
                // Only show significant deviations
                if ($percentageDiff > \App\Core\DashboardConstants::PERCENTAGE_DIFFERENCE_THRESHOLD) {
                    $deviations[] = [
                        'type' => 'response_rate_anomaly',
                        'severity' => $zScore > \App\Core\DashboardConstants::CRITICAL_Z_SCORE_THRESHOLD ? 'critical' : 'warning',
                        'z_score' => $zScore,
                        'current_rate' => $currentData['response_rate'],
                        'historical_mean' => $stats['response_mean'],
                        'section' => $sectionId,
                        'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% " . ($direction === 'niedriger' ? 'weniger' : 'mehr') . " Rückmeldungen als üblich"
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
                'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($percentageDiff, 0) . "% weniger Rückmeldungen als je zuvor"
            ];
        }
        
        return $deviations;
    }
    
    /**
     * Detect pattern deviations (trends, seasonality, etc.)
     */
    private function detectPatternDeviations($currentData, $historicalData, $sectionId) {
        $deviations = [];
        
        // Detect trend changes
        if (count($historicalData) >= 8) {
            $recentData = array_slice($historicalData, 0, 4);
            $olderData = array_slice($historicalData, 4, 4);
            
            $recentAvg = array_sum(array_map(function($row) {
                return $row['total_players'] > 0 ? ($row['attending'] / $row['total_players']) * 100 : 0;
            }, $recentData)) / count($recentData);
            
            $olderAvg = array_sum(array_map(function($row) {
                return $row['total_players'] > 0 ? ($row['attending'] / $row['total_players']) * 100 : 0;
            }, $olderData)) / count($olderData);
            
            $trendChange = abs($recentAvg - $olderAvg);
            if ($trendChange > \App\Core\DashboardConstants::TREND_CHANGE_THRESHOLD) {
                $direction = $recentAvg > $olderAvg ? 'steigend' : 'fallend';
                $deviations[] = [
                    'type' => 'trend_change',
                    'severity' => 'info',
                    'trend_change' => $trendChange,
                    'recent_avg' => $recentAvg,
                    'older_avg' => $olderAvg,
                    'section' => $sectionId,
                    'message' => $this->groupManager->getDisplayName($sectionId) . ": " . number_format($trendChange, 0) . "% " . ($direction === 'steigend' ? 'mehr' : 'weniger') . " Teilnahme als früher"
                ];
            }
        }
        
        return $deviations;
    }
    
    /**
     * Get all sections for a rehearsal (only sections that are supposed to participate AND have users in this rehearsal)
     */
    private function getRehearsalSections($rehearsalId) {
        // First, get the groups that are supposed to participate in this rehearsal
        $rehearsalModel = new \App\Models\Rehearsal($this->db);
        $rehearsalGroups = $rehearsalModel->getGroupsAsAssoc($rehearsalId);
        $groupManager = new \App\Core\GroupManager();
        
        // Get all sections that have users in this rehearsal
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.type as id, u.type as name, COUNT(up.id) as user_count
            FROM user_promises up
            JOIN users u ON up.user_id = u.id
            WHERE up.rehearsal_id = ?
            GROUP BY u.type
            HAVING user_count > 0
            ORDER BY u.type
        ");
        
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
