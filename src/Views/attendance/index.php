<?php
/**
 * Attendance Panel View
 *
 * Displays rehearsal attendance documentation with:
 * - Horizontal rehearsal timeline
 * - Member list with dual-ring status indicators
 * - Bulk confirm and filtering actions
 */

$groupManager = \App\Core\GroupManager::getInstance();
$basePath = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');

// Build rehearsal data for JS
$rehearsalData = [];
foreach ($rehearsals as $r) {
    $start = new DateTime($r['start'] ?? $r['date'] ?? '');
    $rehearsalData[] = [
        'id'         => (int)$r['id'],
        'date'       => $start->format('d.m'),
        'dateShort'  => $start->format('d.m'),
        'dateFull'   => $start->format('d.m.Y'),
        'weekday'    => ['So','Mo','Di','Mi','Do','Fr','Sa'][$start->format('w')],
        'timestamp'  => $start->getTimestamp(),
        'isPast'     => $start <= new DateTime(),
        'documented' => in_array((int)$r['id'], $documentedIds),
    ];
}

// Group members by section using getFlattenedSections to skip root-level wrappers like "tutti"
$membersBySection = [];
$sectionOrder = [];

if ($scope === 'all') {
    $flatSections = $groupManager->getFlattenedSections();
    $membersByType = [];
    foreach ($members as $member) {
        $memberType = $groupManager->resolveAlias($member['type'] ?? '');
        $membersByType[$memberType][] = $member;
    }

    foreach ($flatSections as $parentId => $instrumentIds) {
        $sectionMembers = [];
        foreach ($instrumentIds as $instrId) {
            foreach ($membersByType[$instrId] ?? [] as $m) {
                $sectionMembers[] = $m;
            }
        }
        if (!empty($sectionMembers)) {
            $label = $parentId ? $groupManager->getDisplayName($parentId) : '';
            $membersBySection[$parentId ?: '_ungrouped'] = $sectionMembers;
            $sectionOrder[$parentId ?: '_ungrouped'] = $label;
        }
    }

    // Catch members not in any flatSection (e.g. unresolved types)
    $placed = array_merge(...array_values($flatSections));
    foreach ($membersByType as $type => $ms) {
        if (!in_array($type, $placed)) {
            foreach ($ms as $m) {
                $key = '_other';
                $membersBySection[$key][] = $m;
                $sectionOrder[$key] = $sectionOrder[$key] ?? 'Sonstige';
            }
        }
    }
} else {
    // Section leaders: group by instrument type
    foreach ($members as $member) {
        $type = $groupManager->resolveAlias($member['type'] ?? '');
        $membersBySection[$type][] = $member;
        if (!isset($sectionOrder[$type])) {
            $sectionOrder[$type] = $groupManager->getPluralName($type);
        }
    }
}

// Build promise lookup for initial rehearsal
$initialPromises = [];
foreach ($members as $member) {
    $uid = (int)($member['user_id'] ?? $member['id']);
    $initialPromises[$uid] = $initialRehearsalId ? ($allPromises[$uid][$initialRehearsalId] ?? null) : null;
}
?>

<style>
/* === ATTENDANCE PAGE === */
#page-content-wrapper {
    overflow-x: hidden;
}

.att-page {
    max-width: 800px;
    margin: 0 auto;
    padding: var(--space-4);
}

#att-member-list {
    max-width: 100%;
    overflow: hidden;
}

.att-header {
    margin-bottom: var(--space-4);
}

.att-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-text-primary);
    margin: 0 0 var(--space-3) 0;
}

.att-actions {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
    margin-bottom: var(--space-4);
}

/* === TIMELINE === */
.att-timeline-wrap {
    position: relative;
    margin-bottom: var(--space-4);
}

.att-timeline {
    gap: var(--space-2);
    padding: var(--space-2) var(--space-6) var(--space-3) var(--space-6);
}



.att-timeline-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border);
    cursor: pointer;
    transition: all var(--transition-base);
    min-width: 56px;
    position: relative;
    flex-shrink: 0;
}

.att-timeline-pill:hover {
    border-color: var(--color-primary);
    box-shadow: var(--shadow-sm);
}

.att-timeline-pill.active {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    border-color: var(--color-primary);
    color: white;
    box-shadow: var(--shadow-md);
    transform: scale(1.07);
}

.att-timeline-pill.future {
    opacity: 0.35;
    cursor: default;
    pointer-events: none;
}

.att-timeline-pill .pill-weekday {
    font-size: 10px;
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
}

.att-timeline-pill .pill-date {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-bold);
    line-height: 1.2;
    margin-top: 2px;
}

.att-timeline-pill .pill-dot {
    width: 6px;
    height: 6px;
    border-radius: var(--radius-full);
    background: var(--color-success);
    margin-top: 4px;
}

.att-timeline-pill.active .pill-dot {
    background: white;
}

/* === SECTION HEADERS === */
.att-section-header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin: var(--space-4) 0 var(--space-2);
}

.att-section-header:first-child {
    margin-top: 0;
}

.att-section-line {
    flex: 1;
    height: 1px;
    background: var(--color-border);
}

.att-section-label {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.att-section-count {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-bold);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    white-space: nowrap;
}

.att-section-count.complete {
    background: var(--color-success-100);
    color: var(--color-success-dark);
}

.att-section-count.warning {
    background: var(--color-warning-100);
    color: var(--color-warning-dark);
}

.att-section-count.absent {
    background: var(--color-error-100);
    color: var(--color-error-dark);
}

/* === MEMBER ROWS === */
.att-member-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    background: var(--color-bg-primary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-2);
    transition: all var(--transition-base);
    cursor: pointer;
    max-width: 100%;
    box-sizing: border-box;
}

.att-member-row.deviated {
    border-color: var(--color-warning-200);
    background: var(--color-warning-50);
}

.att-member-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.att-member-name {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
}

.att-member-instrument {
    color: var(--color-text-muted);
    font-weight: var(--font-weight-normal);
}

/* === COMMENTS === */
.att-comment {
    display: flex;
    align-items: flex-start;
    gap: var(--space-1);
    margin-top: var(--space-1);
    font-size: var(--font-size-sm);
    line-height: 1.4;
}

.att-comment-icon {
    font-size: 10px;
    margin-top: 3px;
    flex-shrink: 0;
}

.att-comment-member {
    color: var(--color-text-muted);
    font-style: italic;
}

.att-comment-admin {
    color: var(--color-text-secondary);
    background: var(--color-warning-50);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-style: normal;
}

/* === DUAL-RING INDICATOR === */
.att-indicator {
    width: 44px;
    height: 44px;
    border-radius: var(--radius-full);
    border: 3px solid;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-base);
    cursor: pointer;
    flex-shrink: 0;
    position: relative;
    -webkit-tap-highlight-color: transparent;
}

.att-indicator:active {
    transform: scale(0.92);
    transition: transform 80ms ease;
}

.att-indicator:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

.att-indicator-icon {
    font-size: 12px;
    z-index: 1;
}

/* Inner dot via background circle behind icon */
.att-indicator::after {
    content: '';
    position: absolute;
    width: 24px;
    height: 24px;
    border-radius: var(--radius-full);
    transition: all var(--transition-base);
}

/* Promise states (inner dot color) */
.att-indicator.promise-yes::after   { background: var(--color-success-200); }
.att-indicator.promise-no::after    { background: var(--color-error-200); }
.att-indicator.promise-none::after  { background: var(--color-gray-200); }

.att-indicator.promise-yes .att-indicator-icon  { color: var(--color-success-dark); }
.att-indicator.promise-no .att-indicator-icon   { color: var(--color-error-dark); }
.att-indicator.promise-none .att-indicator-icon { color: var(--color-gray-500); }

/* Attendance states (outer ring) */
.att-indicator.att-present  { border-color: var(--color-success); border-style: solid; }
.att-indicator.att-absent   { border-color: var(--color-error); border-style: dashed; }
.att-indicator.att-unset    { border-color: var(--color-gray-300); border-style: dotted; }

/* Deviation highlight */
.att-indicator.deviated {
    box-shadow: 0 0 0 3px var(--color-warning-200);
}

/* === FILTER === */
.att-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    border: 1px solid var(--color-border);
    background: var(--color-bg-primary);
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-base);
}

.att-filter-btn:hover, .att-filter-btn.active {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

/* === SAVE INDICATOR === */
.att-save-indicator {
    position: fixed;
    bottom: var(--space-4);
    right: var(--space-4);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    z-index: var(--z-toast);
    transition: all var(--transition-base);
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
}

.att-save-indicator.visible {
    opacity: 1;
    transform: translateY(0);
}

.att-save-indicator.saving {
    background: var(--color-primary-100);
    color: var(--color-primary-dark);
}

.att-save-indicator.saved {
    background: var(--color-success-100);
    color: var(--color-success-dark);
}

.att-save-indicator.error {
    background: var(--color-error-100);
    color: var(--color-error-dark);
}

/* === DENSE TABLE VIEW === */
.att-title-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin: 0 0 var(--space-3) 0;
}

.att-title-row .att-title {
    margin: 0;
}

.att-view-toggle {
    margin-left: auto;
    gap: var(--space-2);
    font-size: var(--font-size-sm);
}

.att-view-toggle i {
    font-size: var(--font-size-base);
}

.att-table-filters {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
    flex-wrap: wrap;
}

.att-range-group {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
}

.att-range-group-label {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.att-range-stepper {
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--color-bg-primary);
}

.att-range-stepper button {
    background: transparent;
    border: none;
    width: 28px;
    align-self: stretch;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--color-text-primary);
    font-size: var(--font-size-xs);
    transition: all var(--transition-base);
    padding: 0;
}

.att-range-stepper button:hover:not(:disabled) {
    background: var(--color-gray-200);
}

.att-range-stepper button:disabled {
    opacity: 0.2;
    cursor: default;
}

.att-range-sep {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}

/* Keyboard focus (global focus-removal.css strips outlines → use background) */
.att-range-stepper button:focus-visible {
    background: var(--color-primary-100);
    color: var(--color-primary);
}

/* Date as text with a transparent native date input on top that opens the picker
   (same pattern as the inline edit on rehearsal cards) */
.att-range-date-field {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    align-self: stretch;
    padding: var(--space-1) var(--space-2);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    font-variant-numeric: tabular-nums;
    color: var(--color-text-primary);
    border-left: 1px solid var(--color-border);
    border-right: 1px solid var(--color-border);
    white-space: nowrap;
    cursor: pointer;
}

.att-range-date-field input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    border: 0;
    padding: 0;
    margin: 0;
    font-size: 16px; /* no zoom on focus in iOS */
    cursor: pointer;
    -webkit-appearance: none;
    appearance: none;
}

.att-range-date-field:hover,
.att-range-date-field:focus-within {
    background: var(--color-primary-50);
}

.att-range-date-field.is-disabled {
    opacity: 0.5;
    background: transparent;
    cursor: default;
}

.att-range-date-field.is-disabled input {
    cursor: default;
}

.att-range-stepper .att-range-num-input {
    width: 3.5em;
    align-self: stretch;
    border: none;
    border-left: 1px solid var(--color-border);
    border-right: 1px solid var(--color-border);
    border-radius: 0;
    background: transparent;
    padding: var(--space-1) 0;
    font: inherit;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    font-variant-numeric: tabular-nums;
    text-align: center;
    color: var(--color-text-primary);
    -moz-appearance: textfield;
    appearance: textfield;
}

.att-range-num-input::-webkit-outer-spin-button,
.att-range-num-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.att-range-stepper .att-range-num-input:focus {
    outline: none;
    background: var(--color-primary-50);
}

@media (hover: none) and (pointer: coarse) {
    .att-range-stepper .att-range-num-input {
        font-size: 16px; /* no zoom on focus in iOS */
    }
}

.att-range-count {
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-secondary);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-full);
    padding: 2px var(--space-2);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 640px) {
    /* Larger touch targets, one full-width row per bound */
    .att-table-filters {
        gap: var(--space-2) var(--space-3);
    }
    .att-range-group.att-range-bound {
        width: 100%;
    }
    .att-range-bound .att-range-group-label {
        width: 2.5em;
        flex-shrink: 0;
    }
    .att-range-bound .att-range-stepper {
        flex: 1;
    }
    .att-range-bound .att-range-stepper button {
        width: 44px;
        min-height: 40px;
    }
    .att-range-bound .att-range-stepper .att-range-date-field {
        flex: 1;
    }
    .att-range-sep {
        display: none;
    }
}

.att-table-wrap {
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    background: var(--color-bg-primary);
}

.att-table-scroll {
    overflow-x: auto;
}

.att-dense-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--font-size-sm);
    white-space: nowrap;
}

.att-dense-table th,
.att-dense-table td {
    padding: var(--space-1) var(--space-2);
    border: 1px solid var(--color-border);
    text-align: center;
    vertical-align: middle;
}

/* Remove outer-facing borders so they don't double up with the wrapper border */
.att-dense-table tr:first-child th { border-top: none; }
.att-dense-table tr:last-child td { border-bottom: none; }
.att-dense-table th:first-child,
.att-dense-table td:first-child { border-left: none; }
.att-dense-table th:last-child,
.att-dense-table td:last-child { border-right: none; }

.att-dense-table thead th {
    position: sticky;
    top: 0;
    background: var(--color-bg-secondary);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-secondary);
    font-size: var(--font-size-xs);
    z-index: 2;
}

.att-dense-table thead th:first-child {
    left: 0;
    z-index: 3;
}

.att-dense-table .att-name-col {
    position: sticky;
    left: 0;
    background: var(--color-bg-primary);
    text-align: left;
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    min-width: 140px;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    z-index: 1;
}

.att-dense-table thead .att-name-col {
    background: var(--color-bg-secondary);
}

.att-section-row td {
    background: var(--color-gray-200);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-secondary);
    text-transform: uppercase;
    font-size: var(--font-size-xs);
    letter-spacing: 0.5px;
    text-align: left;
    padding: var(--space-2) var(--space-2);
}

.att-section-row .att-name-col {
    background: var(--color-gray-200);
}

.att-section-row .att-col-total {
    text-align: center;
}

.att-cell-present {
    background: var(--color-success-100);
    color: var(--color-success-dark);
}

.att-cell-absent {
    background: var(--color-error-100);
    color: var(--color-error-dark);
}

.att-cell-deviation {
    background: var(--color-warning-200) !important;
    color: var(--color-error-dark);
}

.att-cell-positive-dev {
    background: var(--color-success-100);
    color: var(--color-warning-dark);
}

.att-cell-unset {
    color: var(--color-text-muted);
}

.att-legend {
    display: flex;
    gap: var(--space-4);
    padding: var(--space-2) var(--space-1);
    margin-top: var(--space-2);
    flex-wrap: wrap;
}

.att-legend-item {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--font-size-xs);
    color: var(--color-text-secondary);
}

.att-legend-swatch {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    font-size: 10px;
    font-weight: var(--font-weight-bold);
    border: 1px solid var(--color-border);
}

.att-col-total {
    font-weight: var(--font-weight-bold);
    background: var(--color-bg-secondary);
    position: sticky;
    right: 0;
    z-index: 1;
}

thead .att-col-total {
    z-index: 3;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
    .att-page {
        padding: 0;
    }
    .att-header {
        margin-bottom: var(--space-2);
    }
    .att-title {
        font-size: var(--font-size-xl);
    }
    .att-actions {
        flex-direction: column;
        gap: var(--space-2);
    }
    .att-filter-group {
        width: 100%;
    }
    .att-filter-btn {
        flex: 1;
        justify-content: center;
        text-align: center;
    }
    .att-timeline {
        margin-bottom: var(--space-2);
    }
    .att-timeline-pill {
        padding: var(--space-1) var(--space-2);
        min-width: 46px;
    }
    .att-timeline-pill .pill-weekday {
        font-size: 9px;
    }
    .att-timeline-pill .pill-date {
        font-size: var(--font-size-xs);
    }
    .att-member-row {
        padding: var(--space-2);
        gap: var(--space-2);
        border-radius: var(--radius-md);
    }
    .att-member-name {
        font-size: var(--font-size-sm);
    }
    .att-comment {
        font-size: var(--font-size-xs);
    }
    .att-indicator {
        width: 36px;
        height: 36px;
        border-width: 2.5px;
    }
    .att-indicator::after {
        width: 18px;
        height: 18px;
    }
    .att-indicator-icon {
        font-size: 9px;
    }
    .att-section-header {
        margin-top: var(--space-3);
        gap: var(--space-2);
    }
    #att-bulk-confirm {
        width: 100%;
        justify-content: center;
        white-space: normal;
    }
    .att-member-row {
        max-width: 100%;
    }
}

/* === PRINT === */
.att-print-only {
    display: none;
}

@media print {
    .att-print-only {
        display: block !important;
    }

    .top-nav,
    nav,
    header,
    .sidebar,
    #sidebar-wrapper,
    .sidebar-overlay,
    .btn,
    button,
    .fab,
    .att-table-filters,
    .att-title-row,
    .att-actions,
    .att-timeline-wrap,
    #att-member-list,
    .att-legend,
    .page-header,
    .breadcrumbs,
    .alert,
    .toast,
    .modal,
    .dropdown {
        display: none !important;
    }

    * {
        box-shadow: none !important;
        text-shadow: none !important;
    }

    body,
    html {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        font-family: "Segoe UI", system-ui, -apple-system, sans-serif !important;
        font-size: 10pt !important;
        line-height: 1.3 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    #wrapper,
    #page-content-wrapper {
        display: block !important;
        position: static !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-content-inner,
    .max-w-7xl {
        display: block !important;
        margin: 0 !important;
        padding: 0 20px !important;
        max-width: none !important;
    }

    .att-page {
        max-width: none !important;
        padding: 0 !important;
    }

    /* Print header */
    .att-print-header {
        margin-bottom: 20px !important;
        padding-bottom: 14px !important;
        border-bottom: 2px solid #e5e7eb !important;
    }

    .att-print-header-main {
        text-align: center !important;
        margin-bottom: 10px !important;
    }

    .att-print-title {
        font-size: 18pt !important;
        font-weight: bold !important;
        margin: 0 0 4px 0 !important;
        color: #111827 !important;
    }

    .att-print-subtitle {
        font-size: 11pt !important;
        color: #6b7280 !important;
        margin: 0 !important;
    }

    .att-print-info {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        font-size: 9pt !important;
        color: #9ca3af !important;
    }

    /* Table */
    .att-table-wrap {
        overflow: visible !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 6px !important;
        background: white !important;
    }

    .att-table-scroll {
        overflow: visible !important;
    }

    .att-dense-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 9pt !important;
    }

    .att-dense-table th,
    .att-dense-table td {
        padding: 6px 8px !important;
        border: 1px solid #e5e7eb !important;
        color: #111827 !important;
    }

    .att-dense-table thead th {
        background: #f3f4f6 !important;
        font-weight: 600 !important;
        color: #374151 !important;
        position: static !important;
    }

    .att-dense-table .att-name-col {
        position: static !important;
        text-align: left !important;
    }

    .att-section-row td {
        background: #e5e7eb !important;
    }

    .att-cell-present { background: #d1fae5 !important; }
    .att-cell-absent { background: #fee2e2 !important; }
    .att-cell-deviation { background: #fde68a !important; }
    .att-cell-positive-dev { background: #d1fae5 !important; }

    .att-dense-table td.att-cell-present { color: #065f46 !important; }
    .att-dense-table td.att-cell-absent { color: #991b1b !important; }
    .att-dense-table td.att-cell-deviation { color: #991b1b !important; }
    .att-dense-table td.att-cell-positive-dev { color: #92400e !important; }
    .att-dense-table td.att-cell-unset { color: #9ca3af !important; }
    .att-col-total { background: #f3f4f6 !important; position: static !important; }

    @page {
        margin: 1.2cm !important;
        size: A4 landscape !important;
    }

    .att-dense-table thead {
        display: table-header-group !important;
    }

    .att-dense-table tfoot {
        display: table-row-group !important;
    }
}
</style>

<div class="att-page">

    <!-- Print Header (table view only) -->
    <div class="att-print-only att-print-header" id="att-print-header">
        <div class="att-print-header-main">
            <div class="att-print-title">Anwesenheiten</div>
            <div class="att-print-subtitle"><?= htmlspecialchars($_SESSION['current_orchestra_name'] ?? 'Orchester') ?></div>
        </div>
        <div class="att-print-info">
            <div>Stand: <?= date('d.m.Y, H:i') ?> Uhr</div>
            <div id="att-print-range"></div>
        </div>
    </div>

    <!-- Header -->
    <div class="att-header">
        <div class="att-title-row">
            <h1 class="att-title">Anwesenheiten</h1>
            <button type="button" id="att-view-toggle" class="btn-modern btn-outline btn-sm att-view-toggle" title="Tabellenansicht">
                <i class="fas fa-table"></i> Tabelle
            </button>
        </div>

        <div class="att-actions" <?= !$initialRehearsalId ? 'style="display: none;"' : '' ?>>
            <button type="button" id="att-bulk-confirm" class="btn-modern btn-primary btn-sm">
                <i class="fas fa-check-double"></i> Alle wie gemeldet bestätigen
            </button>

            <div class="att-filter-group" style="display: inline-flex; gap: var(--space-1);">
                <button type="button" class="att-filter-btn active" data-filter="all">Alle</button>
                <button type="button" class="att-filter-btn" data-filter="absent">Abwesend</button>
                <button type="button" class="att-filter-btn" data-filter="deviated">Abweichungen</button>
                <button type="button" class="att-filter-btn" data-filter="unset">Unbestätigt</button>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="att-timeline-wrap">
        <?php
        $hScrollId    = 'att-timeline';
        $hScrollClass = 'att-timeline';
        $hScrollStep  = 200;
        include __DIR__ . '/../components/h-scroll-begin.php';
        foreach ($rehearsalData as $r): ?>
            <div class="att-timeline-pill <?= $r['id'] === $initialRehearsalId ? 'active' : '' ?> <?= !$r['isPast'] ? 'future' : '' ?>"
                 data-rehearsal-id="<?= $r['id'] ?>"
                 title="<?= $r['weekday'] ?>, <?= $r['dateFull'] ?>">
                <span class="pill-weekday"><?= $r['weekday'] ?></span>
                <span class="pill-date"><?= $r['dateShort'] ?></span>
                <?php if ($r['documented']): ?>
                    <span class="pill-dot"></span>
                <?php endif; ?>
            </div>
        <?php endforeach;
        include __DIR__ . '/../components/h-scroll-end.php';
        ?>
    </div>

    <!-- Member List -->
    <div id="att-member-list">
        <?php if (!$initialRehearsalId): ?>
            <div style="margin-top: var(--space-4);">
                <?php
                $title = 'Keine aktive Probe';
                $message = 'Es gibt aktuell keine vergangenen Proben, für die Anwesenheiten erfasst werden können.';
                include __DIR__ . '/../components/empty-state.php';
                ?>
            </div>
        <?php elseif (empty($members)): ?>
            <?php
            $title = 'Keine Mitglieder';
            $message = 'Es wurden keine Mitglieder für diesen Bereich gefunden.';
            include __DIR__ . '/../components/empty-state.php';
            ?>
        <?php else: ?>
            <?php foreach ($membersBySection as $sectionId => $sectionMembers): ?>
                <?php $sectionName = $sectionOrder[$sectionId] ?? $sectionId; ?>
                <?php if ($sectionName !== '' && $sectionId !== 'own'): ?>
                    <?php
                    $total = count($sectionMembers);
                    $documentedCount = 0;
                    $deviations = 0;
                    $absentCount = 0;
                    foreach ($sectionMembers as $m) {
                        $uid = (int)($m['user_id'] ?? $m['id']);
                        $att = $attendanceRecords[$uid] ?? null;
                        $prm = $initialPromises[$uid] ?? null;
                        if ($att) {
                            $documentedCount++;
                            $promisedYes = $prm && $prm['status'] === 'yes';
                            $wasPresent = (bool)$att['present'];
                            if ($prm && ($promisedYes !== $wasPresent)) $deviations++;
                            if (!$att['present']) $absentCount++;
                        } else {
                            if ($prm && $prm['status'] !== 'yes') $absentCount++;
                        }
                    }
                    $allDocumented = ($documentedCount === $total && $total > 0);
                    ?>
                    <div class="att-section-header" data-section="<?= htmlspecialchars($sectionId) ?>">
                        <span class="att-section-line"></span>
                        <span class="att-section-label"><?= htmlspecialchars($sectionName) ?></span>
                        <?php if ($absentCount > 0): ?>
                            <span class="att-section-count absent">
                                <?= $absentCount ?> ✕
                            </span>
                        <?php endif; ?>
                        <?php if ($deviations > 0): ?>
                            <span class="att-section-count warning">
                                ⚠ <?= $deviations ?>
                            </span>
                        <?php endif; ?>
                        <span class="att-section-line"></span>
                    </div>
                <?php endif; ?>

                <?php foreach ($sectionMembers as $member):
                    $uid = (int)($member['user_id'] ?? $member['id']);
                    $promise = $initialPromises[$uid] ?? null;
                    $attendance = $attendanceRecords[$uid] ?? null;
                    $showInstrument = ($sectionId === 'own');

                    // In section context, show instrument only if section has mixed instruments
                    if ($sectionId !== 'own') {
                        $memberResolvedType = $groupManager->resolveAlias($member['type'] ?? '');
                        $showInstrument = ($memberResolvedType !== $sectionId);
                    }

                    include __DIR__ . '/../components/attendance-member-row.php';
                endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Table View Filters (hidden by default) -->
    <div id="att-table-filters" class="att-table-filters" style="display: none;">
        <div class="att-range-group att-range-bound">
            <label class="att-range-group-label" for="att-from-date">Von</label>
            <div class="att-range-stepper">
                <button type="button" id="att-from-minus" aria-label="Start eine Probe früher"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                <span class="att-range-date-field is-disabled"><span id="att-from-text">–</span><input type="date" id="att-from-date" disabled></span>
                <button type="button" id="att-from-plus" aria-label="Start eine Probe später"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
            </div>
        </div>
        <span class="att-range-sep" aria-hidden="true">–</span>
        <div class="att-range-group att-range-bound">
            <label class="att-range-group-label" for="att-to-date">Bis</label>
            <div class="att-range-stepper">
                <button type="button" id="att-to-minus" aria-label="Ende eine Probe früher"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                <span class="att-range-date-field is-disabled"><span id="att-to-text">–</span><input type="date" id="att-to-date" disabled></span>
                <button type="button" id="att-to-plus" aria-label="Ende eine Probe später"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
            </div>
        </div>
        <span class="att-range-count" id="att-range-count" hidden></span>
        <div class="att-range-group">
            <label class="att-range-group-label" for="att-abs-input">Mind.</label>
            <div class="att-range-stepper">
                <button type="button" id="att-abs-minus" aria-label="Mindestanzahl Fehlzeiten verringern"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                <input type="number" class="att-range-num-input" id="att-abs-input" min="0" step="1" inputmode="numeric" value="0">
                <button type="button" id="att-abs-plus" aria-label="Mindestanzahl Fehlzeiten erhöhen"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
            </div>
            <span class="att-range-group-label">Fehlzeiten</span>
        </div>
        <div class="sr-only" id="att-range-live" aria-live="polite" aria-atomic="true"></div>
    </div>

    <!-- Dense Table View (hidden by default) -->
    <div id="att-table-view" style="display: none;"></div>

</div>

<?php
// Print FAB (shown only in table mode via JS)
$renderComponent = true;
$icon = 'print';
$onclick = 'window.print()';
$title = 'Drucken';
$id = 'att-print-btn';
include __DIR__ . '/../components/fab.php';
?>

<!-- Save Indicator -->
<div class="att-save-indicator" id="att-save-indicator" role="status" aria-live="polite" aria-atomic="true"></div>

<script>
(function() {
    'use strict';

    const BASE_URL = <?= json_encode($basePath) ?>;
    const CSRF_TOKEN = <?= json_encode($csrfToken) ?>;
    const INITIAL_REHEARSAL_ID = <?= json_encode($initialRehearsalId) ?>;

    // All promises data for timeline navigation
    const ALL_PROMISES = <?= json_encode($allPromises) ?>;
    const MEMBERS = <?= json_encode(array_map(function($m) {
        return [
            'id' => (int)($m['user_id'] ?? $m['id']),
            'display_name' => $m['display_name'] ?? '',
            'type' => $m['type'] ?? '',
        ];
    }, $members)) ?>;

    let currentRehearsalId = INITIAL_REHEARSAL_ID;
    let currentFilter = 'all';
    let saveTimeout = null;
    let onDataChange = null;

    // ── Save Indicator ──
    function showSave(text, type) {
        const el = document.getElementById('att-save-indicator');
        el.textContent = text;
        el.className = 'att-save-indicator visible ' + type;
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            el.classList.remove('visible');
        }, 2000);
    }

    // ── AJAX Helper ──
    function ajaxPost(url, data) {
        const fd = new FormData();
        fd.append('csrf_token', CSRF_TOKEN);
        for (const k in data) fd.append(k, data[k]);

        showSave('Speichern…', 'saving');

        return fetch(BASE_URL + url, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showSave('Gespeichert', 'saved');
                if (onDataChange) onDataChange();
            } else {
                showSave(res.error || 'Fehler', 'error');
            }
            return res;
        })
        .catch(() => {
            showSave('Netzwerkfehler', 'error');
            return { success: false };
        });
    }

    // ── Toggle Status ──
    function handleToggle(indicator) {
        const userId = indicator.dataset.userId;
        const row = indicator.closest('.att-member-row');
        const currentStatus = row.dataset.attStatus;

        ajaxPost('/attendance/update', {
            rehearsal_id: currentRehearsalId,
            user_id: userId,
            action: currentStatus === 'absent' ? 'delete' : 'toggle'
        }).then(res => {
            if (!res.success) return;
            updateRowStatus(row, indicator, res.status);
        });
    }

    function updateRowStatus(row, indicator, newStatus) {
        row.dataset.attStatus = newStatus;

        // Update ring classes
        indicator.classList.remove('att-present', 'att-absent', 'att-unset');
        indicator.classList.add('att-' + newStatus);

        // Deviation: only when an explicit promise was given
        const promiseStatus = row.dataset.promiseStatus;
        let deviated = false;
        if (newStatus !== 'unset' && promiseStatus !== 'none') {
            const promisedYes = (promiseStatus === 'yes');
            const wasPresent = (newStatus === 'present');
            deviated = (promisedYes !== wasPresent);
        }

        row.classList.toggle('deviated', deviated);
        indicator.classList.toggle('deviated', deviated);

        updateSectionCounts();
    }

    // ── Comment Input ──
    function handleCommentClick(row) {
        const existing = row.querySelector('.att-comment-text');
        openNoteDialog(row, existing ? existing.textContent : '', '');
    }

    // `present` is NOT NULL in the DB: without a recorded status the note is saved
    // together with a status, so the dialog then offers "Anwesend" / "Abwesend".
    // If saving fails, the dialog reopens with the typed text and the error inside it.
    function openNoteDialog(row, text, errorMessage) {
        if (typeof Swal === 'undefined') return;
        const indicator = row.querySelector('.att-indicator');
        const userId = indicator.dataset.userId;
        const rehearsalId = currentRehearsalId;
        const needsStatus = row.dataset.attStatus === 'unset';
        let draft = text;
        const keepDraft = () => {
            const input = Swal.getInput();
            if (input) draft = input.value;
        };

        const options = {
            title: 'Anwesenheitsnotiz',
            input: 'textarea',
            inputValue: text,
            inputPlaceholder: 'z.B. "30 Min. verspätet"',
            showCancelButton: true,
            confirmButtonText: 'Speichern',
            cancelButtonText: 'Abbrechen',
            customClass: {
                popup: 'swal2-popup-modern',
            },
            preConfirm: keepDraft,
            didOpen: () => {
                const input = Swal.getInput();
                if (input) input.addEventListener('input', keepDraft);
                // showValidationMessage renders HTML → escape
                if (errorMessage) Swal.showValidationMessage(escapeHtml(errorMessage));
            }
        };
        if (needsStatus) {
            Object.assign(options, {
                text: 'Noch keine Anwesenheit erfasst – die Notiz wird zusammen mit dem gewählten Status gespeichert.',
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-check" aria-hidden="true"></i> Anwesend',
                denyButtonText: '<i class="fas fa-times" aria-hidden="true"></i> Abwesend',
                preDeny: keepDraft,
            });
        }

        Swal.fire(options).then(result => {
            if (!result.isConfirmed && !result.isDenied) return;
            const comment = draft;
            const data = {
                rehearsal_id: rehearsalId,
                user_id: userId,
                action: 'comment',
                comment: comment
            };
            if (needsStatus) data.status = result.isDenied ? 'absent' : 'present';

            ajaxPost('/attendance/update', data).then(res => {
                if (rehearsalId !== currentRehearsalId) return; // another rehearsal is shown now
                if (!res.success) {
                    // Server has no record (e.g. reset meanwhile) → ask for a status
                    if (res.needs_status) updateRowStatus(row, indicator, 'unset');
                    openNoteDialog(row, comment, res.error || 'Speichern fehlgeschlagen – bitte erneut versuchen.');
                    return;
                }
                if (res.status && res.status !== row.dataset.attStatus) {
                    updateRowStatus(row, indicator, res.status);
                }
                // Update comment display (server-trimmed text)
                const saved = typeof res.comment === 'string' ? res.comment : comment;
                let adminComment = row.querySelector('.att-comment-admin');
                if (saved) {
                    if (!adminComment) {
                        adminComment = document.createElement('div');
                        adminComment.className = 'att-comment att-comment-admin';
                        adminComment.innerHTML = '<i class="fa-solid fa-pen att-comment-icon"></i><span class="att-comment-text"></span>';
                        adminComment.querySelector('.att-comment-text').dataset.userId = userId;
                        row.querySelector('.att-member-info').appendChild(adminComment);
                    }
                    adminComment.querySelector('.att-comment-text').textContent = saved;
                } else if (adminComment) {
                    adminComment.remove();
                }
            });
        });
    }

    // ── Bulk Confirm ──
    document.getElementById('att-bulk-confirm').addEventListener('click', function() {
        function doConfirm() {
            ajaxPost('/attendance/bulk-confirm', {
                rehearsal_id: currentRehearsalId
            }).then(res => {
                if (!res.success) return;
                // Update all rows from response
                const records = res.records || {};
                document.querySelectorAll('.att-member-row').forEach(row => {
                    const userId = row.querySelector('.att-indicator').dataset.userId;
                    const record = records[userId];
                    const indicator = row.querySelector('.att-indicator');

                    if (record) {
                        updateRowStatus(row, indicator, record.present ? 'present' : 'absent');
                    }
                });
                // Update timeline pill dot
                const activePill = document.querySelector('.att-timeline-pill.active');
                if (activePill && !activePill.querySelector('.pill-dot')) {
                    const dot = document.createElement('span');
                    dot.className = 'pill-dot';
                    activePill.appendChild(dot);
                }
            });
        }

        doConfirm();
    });

    // ── Timeline Navigation ──
    document.querySelectorAll('.att-timeline-pill:not(.future)').forEach(pill => {
        pill.addEventListener('click', function() {
            const rehearsalId = parseInt(this.dataset.rehearsalId);
            if (rehearsalId === currentRehearsalId) return;

            // Update active pill
            document.querySelectorAll('.att-timeline-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            currentRehearsalId = rehearsalId;

            // Load attendance data
            showSave('Laden…', 'saving');
            fetch(BASE_URL + '/attendance/load-rehearsal?rehearsal_id=' + rehearsalId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    showSave('Fehler beim Laden', 'error');
                    return;
                }
                showSave('Geladen', 'saved');
                const records = res.records || {};

                document.querySelectorAll('.att-member-row').forEach(row => {
                    const indicator = row.querySelector('.att-indicator');
                    const userId = indicator.dataset.userId;

                    // Update promise display
                    const promise = (ALL_PROMISES[userId] || {})[rehearsalId];
                    const promiseStatus = promise ? (promise.status === 'yes' ? 'yes' : 'no') : 'none';
                    row.dataset.promiseStatus = promiseStatus;

                    indicator.classList.remove('promise-yes', 'promise-no', 'promise-none');
                    indicator.classList.add('promise-' + promiseStatus);

                    const icon = indicator.querySelector('.att-indicator-icon');
                    icon.className = 'fas att-indicator-icon ' +
                        (promiseStatus === 'yes' ? 'fa-check' : promiseStatus === 'no' ? 'fa-times' : 'fa-minus');

                    // Update promise note
                    const memberComment = row.querySelector('.att-comment-member');
                    const promiseNote = promise ? (promise.note || '') : '';
                    if (promiseNote && !memberComment) {
                        const noteEl = document.createElement('div');
                        noteEl.className = 'att-comment att-comment-member';
                        noteEl.innerHTML = '<i class="fa-solid fa-quote-left att-comment-icon"></i><span>' +
                            promiseNote.replace(/</g, '&lt;') + '</span>';
                        row.querySelector('.att-member-info').insertBefore(noteEl, row.querySelector('.att-comment-admin'));
                    } else if (memberComment) {
                        if (promiseNote) {
                            memberComment.querySelector('span').textContent = promiseNote;
                        } else {
                            memberComment.remove();
                        }
                    }

                    // Update attendance
                    const record = records[userId];
                    const attStatus = record ? (record.present ? 'present' : 'absent') : 'unset';
                    updateRowStatus(row, indicator, attStatus);

                    // Update admin comment
                    const adminComment = row.querySelector('.att-comment-admin');
                    const attComment = record ? (record.comment || '') : '';
                    if (attComment && !adminComment) {
                        const commentEl = document.createElement('div');
                        commentEl.className = 'att-comment att-comment-admin';
                        commentEl.innerHTML = '<i class="fa-solid fa-pen att-comment-icon"></i><span class="att-comment-text" data-user-id="' + userId + '">' +
                            attComment.replace(/</g, '&lt;') + '</span>';
                        row.querySelector('.att-member-info').appendChild(commentEl);
                    } else if (adminComment) {
                        if (attComment) {
                            adminComment.querySelector('.att-comment-text').textContent = attComment;
                        } else {
                            adminComment.remove();
                        }
                    }
                });

                applyFilter();
                updateSectionCounts();
            })
            .catch(() => showSave('Netzwerkfehler', 'error'));
        });
    });

    // ── Filters ──
    document.querySelectorAll('.att-filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.att-filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            applyFilter();
        });
    });

    function applyFilter() {
        document.querySelectorAll('.att-member-row').forEach(row => {
            let show = true;
            if (currentFilter === 'deviated') {
                show = row.classList.contains('deviated');
            } else if (currentFilter === 'unset') {
                show = row.dataset.attStatus === 'unset';
            } else if (currentFilter === 'absent') {
                show = row.dataset.attStatus === 'absent' || (row.dataset.promiseStatus === 'no' && row.dataset.attStatus === 'unset');
            }
            row.style.display = show ? '' : 'none';
        });

        // Hide section headers if all their rows are hidden
        document.querySelectorAll('.att-section-header').forEach(header => {
            let nextEl = header.nextElementSibling;
            let hasVisibleRow = false;
            while (nextEl && !nextEl.classList.contains('att-section-header')) {
                if (nextEl.classList.contains('att-member-row') && nextEl.style.display !== 'none') {
                    hasVisibleRow = true;
                    break;
                }
                nextEl = nextEl.nextElementSibling;
            }
            header.style.display = hasVisibleRow ? '' : 'none';
        });
    }

    // ── Section Counts ──
    function updateSectionCounts() {
        document.querySelectorAll('.att-section-header').forEach(header => {
            let total = 0, documented = 0, deviations = 0, absentCount = 0;
            let nextEl = header.nextElementSibling;
            while (nextEl && !nextEl.classList.contains('att-section-header')) {
                if (nextEl.classList.contains('att-member-row')) {
                    total++;
                    const att = nextEl.dataset.attStatus;
                    if (att !== 'unset') {
                        documented++;
                        if (att === 'absent') absentCount++;
                    } else if (nextEl.dataset.promiseStatus === 'no') {
                        absentCount++;
                    }
                    if (nextEl.classList.contains('deviated')) deviations++;
                }
                nextEl = nextEl.nextElementSibling;
            }

            const allDocumented = (documented === total && total > 0);

            // Absent badge
            let absBadge = header.querySelector('.att-section-count.absent');
            if (absentCount > 0) {
                if (!absBadge) {
                    absBadge = document.createElement('span');
                    absBadge.className = 'att-section-count absent';
                    const label = header.querySelector('.att-section-label');
                    label.after(absBadge);
                }
                absBadge.textContent = absentCount + ' \u2715';
            } else if (absBadge) {
                absBadge.remove();
            }

            // Deviation badge
            let statusBadge = header.querySelector('.att-section-count.warning');
            if (deviations > 0) {
                if (!statusBadge) {
                    statusBadge = document.createElement('span');
                    statusBadge.className = 'att-section-count';
                    header.insertBefore(statusBadge, header.lastElementChild);
                }
                statusBadge.textContent = '\u26a0 ' + deviations;
                statusBadge.className = 'att-section-count warning';
            } else if (statusBadge) {
                statusBadge.remove();
            }
        });
    }

    // ── Event Delegation ──
    document.getElementById('att-member-list').addEventListener('click', function(e) {
        const indicator = e.target.closest('.att-indicator');
        if (indicator) {
            e.preventDefault();
            handleToggle(indicator);
            return;
        }

        const row = e.target.closest('.att-member-row');
        if (row) {
            const ind = row.querySelector('.att-indicator');
            const rect = ind.getBoundingClientRect();
            const margin = 16;
            if (e.clientX >= rect.left - margin && e.clientX <= rect.right + margin &&
                e.clientY >= rect.top - margin && e.clientY <= rect.bottom + margin) {
                e.preventDefault();
                handleToggle(ind);
                return;
            }
            handleCommentClick(row);
        }
    });

    // Keyboard support for indicators
    document.getElementById('att-member-list').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            const indicator = e.target.closest('.att-indicator');
            if (indicator) {
                e.preventDefault();
                handleToggle(indicator);
            }
        }
    });

    // Scroll timeline to active pill on load
    const activePill = document.querySelector('.att-timeline-pill.active');
    if (activePill) {
        activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }



    // ── Table View Toggle ──
    const viewToggle = document.getElementById('att-view-toggle');
    const cardView = document.getElementById('att-member-list');
    const tableContainer = document.getElementById('att-table-view');
    const tableFilters = document.getElementById('att-table-filters');
    const timelineWrap = document.querySelector('.att-timeline-wrap');
    const actionsBar = document.querySelector('.att-actions');
    let tableMode = false;
    let tableDataCache = null;

    // Hide print FAB until table mode is active
    const printFabInit = document.getElementById('att-print-btn');
    if (printFabInit) printFabInit.style.display = 'none';

    function invalidateTableCache() {
        tableDataCache = null;
    }

    onDataChange = invalidateTableCache;

    viewToggle.addEventListener('click', function() {
        tableMode = !tableMode;
        this.blur();
        const icon = this.querySelector('i');

        const printBtn = document.getElementById('att-print-btn');

        const attPage = document.querySelector('.att-page');

        if (tableMode) {
            icon.className = 'fas fa-list';
            this.childNodes[this.childNodes.length - 1].textContent = ' Liste';
            this.title = 'Listenansicht';
            cardView.style.display = 'none';
            timelineWrap.style.display = 'none';
            actionsBar.style.display = 'none';
            tableContainer.style.display = '';
            tableFilters.style.display = '';
            if (printBtn) printBtn.style.display = '';
            if (attPage) attPage.style.paddingBottom = '80px';

            if (!tableDataCache) {
                loadTableData();
            }
        } else {
            icon.className = 'fas fa-table';
            this.childNodes[this.childNodes.length - 1].textContent = ' Tabelle';
            this.title = 'Tabellenansicht';
            cardView.style.display = '';
            timelineWrap.style.display = '';
            actionsBar.style.display = '';
            tableContainer.style.display = 'none';
            tableFilters.style.display = 'none';
            if (printBtn) printBtn.style.display = 'none';
            if (attPage) attPage.style.paddingBottom = '';
        }
    });

    function loadTableData() {
        updateRangeUI();
        tableContainer.innerHTML = '<div style="text-align:center; padding:var(--space-6); color:var(--color-text-muted)"><i class="fas fa-spinner fa-spin"></i> Laden…</div>';
        fetch(BASE_URL + '/attendance/table-data', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                tableContainer.innerHTML = '<div style="text-align:center; padding:var(--space-6); color:var(--color-error)">Fehler beim Laden</div>';
                return;
            }
            tableDataCache = data;
            resetRange();
            updateRangeUI();
            renderTable();
        })
        .catch(() => {
            tableContainer.innerHTML = '<div style="text-align:center; padding:var(--space-6); color:var(--color-error)">Netzwerkfehler</div>';
        });
    }

    // ── Range (Von / Bis) ──
    // The table only contains rehearsals that have already started (see
    // AttendanceController::tableData). By default the LAST 10 are shown.
    // Von/Bis are date fields; the arrows step to the previous/next rehearsal.
    const DEFAULT_VISIBLE = 10;
    const WEEKDAYS_LONG = { So: 'Sonntag', Mo: 'Montag', Di: 'Dienstag', Mi: 'Mittwoch', Do: 'Donnerstag', Fr: 'Freitag', Sa: 'Samstag' };

    const fromMinus = document.getElementById('att-from-minus');
    const fromPlus = document.getElementById('att-from-plus');
    const toMinus = document.getElementById('att-to-minus');
    const toPlus = document.getElementById('att-to-plus');
    const fromInput = document.getElementById('att-from-date');
    const toInput = document.getElementById('att-to-date');
    const fromText = document.getElementById('att-from-text');
    const toText = document.getElementById('att-to-text');
    const rangeCountEl = document.getElementById('att-range-count');
    const rangeLive = document.getElementById('att-range-live');
    let fromDate = ''; // Y-m-d
    let toDate = '';
    let startIdx = 0;  // first/last rehearsal within [fromDate, toDate]
    let endIdx = -1;
    let rangeAnchor = null; // user-chosen {from, to}; survives table reloads

    // Pure helpers (no DOM access) ----------------------------------------
    function defaultRange(list) {
        if (!list.length) return { from: '', to: '' };
        return { from: list[Math.max(0, list.length - DEFAULT_VISIBLE)].dateFull, to: list[list.length - 1].dateFull };
    }

    // The list is sorted by date; several rehearsals can share one date.
    function rehearsalDateBefore(list, date) {
        for (let i = list.length - 1; i >= 0; i--) {
            if (list[i].dateFull < date) return list[i].dateFull;
        }
        return null;
    }

    function rehearsalDateAfter(list, date) {
        for (let i = 0; i < list.length; i++) {
            if (list[i].dateFull > date) return list[i].dateFull;
        }
        return null;
    }

    function dateParts(r) {
        const p = String(r.dateFull || '').split('-');
        return p.length === 3 ? { y: p[0], m: p[1], d: p[2] } : null;
    }

    function shortDate(date) { // Sa 22.08.26
        const p = String(date).split('-');
        if (p.length !== 3) return '–';
        const weekday = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][new Date(+p[0], +p[1] - 1, +p[2]).getDay()];
        return weekday + ' ' + p[2] + '.' + p[1] + '.' + p[0].slice(-2);
    }

    function longDate(r) { // 13.10.2025
        const p = dateParts(r);
        return p ? p.d + '.' + p.m + '.' + p.y : r.date;
    }

    function spokenDate(r) { // Montag, 13.10.2025, 19:00 Uhr
        return (WEEKDAYS_LONG[r.weekday] || r.weekday) + ', ' + longDate(r) + (r.time ? ', ' + r.time + ' Uhr' : '');
    }

    function safeColor(c) {
        return (typeof c === 'string' && /^#[0-9a-f]{3,8}$/i.test(c)) ? c : null;
    }

    function rehearsalCount(n) {
        return n === 1 ? '1 Probe' : n + ' Proben';
    }

    // Range state ----------------------------------------------------------
    function applyDates(list, from, to) {
        fromDate = from;
        toDate = to;
        startIdx = list.findIndex(r => r.dateFull >= from);
        if (startIdx === -1) startIdx = list.length;
        endIdx = -1;
        for (let i = list.length - 1; i >= 0; i--) {
            if (list[i].dateFull <= to) { endIdx = i; break; }
        }
    }

    function resetRange() {
        const list = tableDataCache ? tableDataCache.rehearsals : [];
        const r = rangeAnchor || defaultRange(list);
        applyDates(list, r.from, r.to);
    }

    function setRange(from, to, opts) {
        const list = tableDataCache ? tableDataCache.rehearsals : [];
        if (!list.length) return;
        rangeAnchor = { from: from, to: to };
        applyDates(list, from, to);
        updateRangeUI();
        renderTable();
        if (opts && opts.announce) announceRange();
    }

    function announceRange() {
        rangeLive.textContent = 'Zeitraum ' + longDate({ dateFull: fromDate }) + ' bis ' + longDate({ dateFull: toDate }) +
            ', ' + rehearsalCount(Math.max(0, endIdx - startIdx + 1));
    }

    function updateRangeUI() {
        const list = tableDataCache ? tableDataCache.rehearsals : [];
        const total = list.length;
        const has = total > 0 && fromDate !== '' && toDate !== '';
        const empty = endIdx < startIdx;

        fromInput.disabled = !has;
        toInput.disabled = !has;
        fromInput.parentElement.classList.toggle('is-disabled', !has);
        toInput.parentElement.classList.toggle('is-disabled', !has);
        if (fromInput.value !== fromDate) fromInput.value = fromDate;
        if (toInput.value !== toDate) toInput.value = toDate;
        fromText.textContent = has ? shortDate(fromDate) : '–';
        toText.textContent = has ? shortDate(toDate) : '–';
        if (has) {
            fromInput.min = list[0].dateFull;
            fromInput.max = toDate;
            toInput.min = fromDate;
            toInput.max = list[total - 1].dateFull;
        }

        const afterFirst = has && !empty ? rehearsalDateAfter(list, list[startIdx].dateFull) : null;
        const beforeLast = has && !empty ? rehearsalDateBefore(list, list[endIdx].dateFull) : null;
        fromMinus.disabled = !has || rehearsalDateBefore(list, fromDate) === null;
        fromPlus.disabled = afterFirst === null || afterFirst > toDate;
        toMinus.disabled = beforeLast === null || beforeLast < fromDate;
        toPlus.disabled = !has || rehearsalDateAfter(list, toDate) === null;

        if (has) {
            const n = Math.max(0, endIdx - startIdx + 1);
            rangeCountEl.textContent = (n === total && total > 1) ? 'Alle ' + total + ' Proben' : n + ' von ' + rehearsalCount(total);
            rangeCountEl.title = 'Fehlzeiten zählen nur im gewählten Zeitraum';
            rangeCountEl.hidden = false;
        } else {
            rangeCountEl.hidden = true;
        }
    }

    function stepRange(bound, delta) {
        if (!tableDataCache) return;
        const list = tableDataCache.rehearsals;
        let next;
        if (bound === 'from') {
            next = delta < 0 ? rehearsalDateBefore(list, fromDate) : rehearsalDateAfter(list, list[startIdx].dateFull);
            if (next !== null && next <= toDate) setRange(next, toDate, { announce: true });
        } else {
            next = delta > 0 ? rehearsalDateAfter(list, toDate) : rehearsalDateBefore(list, list[endIdx].dateFull);
            if (next !== null && next >= fromDate) setRange(fromDate, next, { announce: true });
        }
    }

    fromMinus.addEventListener('click', () => stepRange('from', -1));
    fromPlus.addEventListener('click', () => stepRange('from', 1));
    toMinus.addEventListener('click', () => stepRange('to', -1));
    toPlus.addEventListener('click', () => stepRange('to', 1));

    // Typing fires change for half-typed years (0002, 0020 …): only complete dates within
    // min/max are applied; leaving the field restores the current range.
    function onDateChange(input) {
        const v = input.value;
        if (!v || (input.min && v < input.min) || (input.max && v > input.max)) return;
        if (input === fromInput) setRange(v, toDate); else setRange(fromDate, v);
    }

    [fromInput, toInput].forEach(input => {
        // Clicking the text part of a date input does not open the picker in every browser
        input.addEventListener('click', () => {
            if (typeof input.showPicker === 'function') {
                try { input.showPicker(); } catch (_) { }
            }
        });
        input.addEventListener('change', () => onDateChange(input));
        input.addEventListener('blur', updateRangeUI);
    });

    // Min-absences filter stepper
    const absMinus = document.getElementById('att-abs-minus');
    const absPlus = document.getElementById('att-abs-plus');
    const absInput = document.getElementById('att-abs-input');
    let minAbsences = 0;

    function updateAbsUI() {
        if (absInput.value !== String(minAbsences)) absInput.value = minAbsences;
        absMinus.disabled = (minAbsences <= 0);
    }

    function setMinAbsences(n) {
        minAbsences = n;
        updateAbsUI();
        if (tableDataCache) renderTable();
    }

    absMinus.addEventListener('click', () => {
        if (minAbsences > 0) setMinAbsences(minAbsences - 1);
    });
    absPlus.addEventListener('click', () => setMinAbsences(minAbsences + 1));
    // Typed values apply right away; leaving the field restores the last valid number
    absInput.addEventListener('input', () => {
        const n = parseInt(absInput.value, 10);
        if (String(n) !== absInput.value.trim() || n < 0) return;
        minAbsences = n;
        absMinus.disabled = (n <= 0);
        if (tableDataCache) renderTable();
    });
    absInput.addEventListener('blur', updateAbsUI);
    absInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') absInput.blur();
    });
    updateAbsUI();

    function renderTable() {
        const data = tableDataCache;
        if (!data) return;
        const { sections, attendance, promises } = data;

        const rehearsals = data.rehearsals.slice(startIdx, endIdx + 1);

        const allMembers = sections.flatMap(s => s.members);
        if (!rehearsals.length || !allMembers.length) {
            tableContainer.innerHTML = '<div style="text-align:center; padding:var(--space-6); color:var(--color-text-muted)">Keine Daten im gewählten Zeitraum</div>';
            return;
        }

        const colCount = rehearsals.length + 2;
        const colAbsences = new Array(rehearsals.length).fill(0);
        let totalAbsences = 0;

        let html = '<div class="att-table-wrap"><div class="att-table-scroll"><table class="att-dense-table"><thead><tr>';
        html += '<th class="att-name-col">Name</th>';
        rehearsals.forEach(r => {
            const color = safeColor(r.color);
            const thStyle = color ? ' style="background:' + color + '26;border-bottom:2px solid ' + color + '40;"' : '';
            html += '<th title="' + escapeHtml(spokenDate(r)) + '"' + thStyle + '>' + r.weekday + '<br>' + r.date + '</th>';
        });
        html += '<th class="att-col-total" title="Fehlzeiten im gewählten Zeitraum">Fehlzeiten</th>';
        html += '</tr></thead><tbody>';

        sections.forEach(section => {
            const filteredMembers = minAbsences > 0 ? section.members.filter(m => {
                const uid = String(m.id);
                const userAtt = attendance[uid] || {};
                let cnt = 0;
                for (const r of rehearsals) {
                    const att = userAtt[String(r.id)];
                    if (att !== undefined && att !== 1) cnt++;
                }
                return cnt >= minAbsences;
            }) : section.members;

            if (!filteredMembers.length) return;

            let sectionMissed = 0;
            let memberRowsHtml = '';

            filteredMembers.forEach(m => {
                const uid = String(m.id);
                const userAtt = attendance[uid] || {};
                const userProm = promises[uid] || {};
                let missed = 0;

                memberRowsHtml += '<tr><td class="att-name-col" title="' + escapeHtml(m.name) + '">' + escapeHtml(m.name) + '</td>';

                rehearsals.forEach((r, ci) => {
                    const rid = String(r.id);
                    const att = userAtt[rid];
                    const prom = userProm[rid];
                    let cls = '';
                    let icon = '';

                    if (att !== undefined) {
                        if (att === 1) {
                            if (prom === 'no') {
                                cls = 'att-cell-positive-dev';
                                icon = '✓';
                            } else {
                                cls = 'att-cell-present';
                                icon = '✓';
                            }
                        } else {
                            missed++;
                            colAbsences[ci]++;
                            if (prom === 'yes') {
                                cls = 'att-cell-deviation';
                                icon = '✕';
                            } else {
                                cls = 'att-cell-absent';
                                icon = '✕';
                            }
                        }
                    } else {
                        cls = 'att-cell-unset';
                        icon = '–';
                    }

                    memberRowsHtml += '<td class="' + cls + '">' + icon + '</td>';
                });

                totalAbsences += missed;
                sectionMissed += missed;
                memberRowsHtml += '<td class="att-col-total">' + (missed > 0 ? missed : '–') + '</td>';
                memberRowsHtml += '</tr>';
            });

            if (section.label) {
                html += '<tr class="att-section-row">';
                html += '<td class="att-name-col">' + escapeHtml(section.label) + '</td>';
                rehearsals.forEach(() => { html += '<td></td>'; });
                html += '<td class="att-col-total">' + (sectionMissed > 0 ? sectionMissed : '–') + '</td>';
                html += '</tr>';
            }

            html += memberRowsHtml;
        });

        html += '</tbody><tfoot><tr><td class="att-name-col" style="background:var(--color-gray-200);font-weight:var(--font-weight-semibold)">Gesamt</td>';
        colAbsences.forEach(c => {
            html += '<td>' + (c > 0 ? c : '–') + '</td>';
        });
        html += '<td class="att-col-total">' + (totalAbsences > 0 ? totalAbsences : '–') + '</td>';
        html += '</tr></tfoot></table></div></div>';

        html += '<div class="att-legend">';
        html += '<span class="att-legend-item"><span class="att-legend-swatch att-cell-present">✓</span> Anwesend</span>';
        html += '<span class="att-legend-item"><span class="att-legend-swatch att-cell-absent">✕</span> Abwesend</span>';
        html += '<span class="att-legend-item"><span class="att-legend-swatch att-cell-deviation" style="color:var(--color-error-dark)">✕</span> Zugesagt, aber abwesend</span>';
        html += '<span class="att-legend-item"><span class="att-legend-swatch att-cell-positive-dev">✓</span> Abgesagt, aber anwesend</span>';
        html += '<span class="att-legend-item"><span class="att-legend-swatch att-cell-unset">–</span> Nicht erfasst</span>';
        html += '</div>';
        tableContainer.innerHTML = html;

        // Update print range label
        const rangeEl = document.getElementById('att-print-range');
        if (rangeEl && rehearsals.length) {
            const first = rehearsals[0];
            const last = rehearsals[rehearsals.length - 1];
            const from = first.weekday + ' ' + longDate(first);
            const to = last.weekday + ' ' + longDate(last);
            rangeEl.textContent = from === to ? from : from + ' – ' + to;
        }
    }

    // Safe for text AND attribute values (quotes are escaped too — names are free text)
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
})();
</script>
