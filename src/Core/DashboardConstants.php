<?php

namespace App\Core;

/**
 * Dashboard Constants
 * 
 * Centralizes all magic numbers and thresholds used in the dashboard
 * for better maintainability and consistency.
 */
class DashboardConstants
{
    // Attendance thresholds
    const CRITICAL_ATTENDANCE_THRESHOLD = 65; // Show as critical if attendance < 65%
    const WARNING_ATTENDANCE_THRESHOLD = 65; // Show as warning if attendance < 65%
    const DANGER_ATTENDANCE_THRESHOLD = 25; // Show as danger (red) if attendance < 25%

    // Response rate thresholds
    const LOW_RESPONSE_RATE_THRESHOLD = 60; // Consider low response rate if < 60%
    const CRITICAL_RESPONSE_RATE_THRESHOLD = 40; // Critical response rate if < 40%

    // Statistical analysis thresholds
    const MIN_DATA_POINTS_FOR_ANALYSIS = 5; // Minimum rehearsals needed for statistical analysis
    const SIGNIFICANCE_THRESHOLD = 0.05; // 5% significance level for deviations
    const Z_SCORE_THRESHOLD = 2.0; // Z-score threshold for anomaly detection
    const CRITICAL_Z_SCORE_THRESHOLD = 3.0; // Critical Z-score threshold
    const PERCENTAGE_DIFFERENCE_THRESHOLD = 20; // Only show significant deviations (>20% difference)
    const TREND_CHANGE_THRESHOLD = 15; // Trend change detection threshold
    const GROUP_DEVIATION_Z_SCORE = 1.5; // Z-score for group deviation detection
    const GROUP_PERFORMANCE_THRESHOLD = 50; // Group performance critical threshold
    const GROUP_DEVIATION_MIN_RATE = 30; // Minimum rate for group deviation analysis

    // Chart configuration
    const CHART_HEIGHT = 200;
    const CHART_STROKE_WIDTH = 2; // Stroke width for chart lines
    const CHART_MARKER_SIZE = 3; // Size of chart markers
    const CHART_HOVER_MARKER_SIZE = 5; // Size of markers on hover

    // UI configuration
    const MAX_CRITICAL_SECTIONS_DISPLAY = 3; // Maximum number of critical sections to show
    const DEVIATION_LIST_MAX_HEIGHT = 200; // Max height for deviation list in pixels
    const TREE_VIEW_ANIMATION_TIMEOUT = 350; // Timeout for tree view animations in ms

    // Date/time formats
    const DISPLAY_DATE_FORMAT = 'd.m.Y'; // Date format for display
    const DISPLAY_TIME_FORMAT = 'H:i'; // Time format for display (HH:MM)
    const TIME_SUBSTRING_LENGTH = 5; // Length to substring time (HH:MM)

    // Chart colors
    const CHART_ATTENDANCE_COLOR = '#10b981'; // Green for attendance
    const CHART_RESPONSE_COLOR = '#3b82f6'; // Blue for response rate
    const CHART_STROKE_COLOR = '#fff'; // White for chart stroke

    // CSS classes
    const CSS_DANGER_CLASS = 'danger';
    const CSS_WARNING_CLASS = 'warning';
    const CSS_ATTENDING_CLASS = 'attending';
    const CSS_NOT_ATTENDING_CLASS = 'not-attending';
    const CSS_NO_RESPONSE_CLASS = 'no-response';
}
