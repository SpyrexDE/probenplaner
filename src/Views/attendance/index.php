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
    $initialPromises[$uid] = $allPromises[$uid][$initialRehearsalId] ?? null;
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
    display: flex;
    gap: var(--space-2);
    overflow-x: auto;
    padding: var(--space-2) var(--space-6) var(--space-3) var(--space-6);
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

.att-timeline::-webkit-scrollbar {
    display: none;
}

.att-timeline-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border-radius: var(--radius-full);
    border: 1px solid var(--color-border);
    background: var(--color-bg-primary);
    color: var(--color-text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
    box-shadow: var(--shadow-sm);
    transition: all var(--transition-base);
    font-size: 12px;
    opacity: 0;
    pointer-events: none;
}

.att-timeline-arrow.visible {
    opacity: 1;
    pointer-events: auto;
}

.att-timeline-arrow:hover {
    background: var(--color-gray-100);
    box-shadow: var(--shadow-md);
}

.att-timeline-arrow.left  { left: 0; }
.att-timeline-arrow.right { right: 0; }

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
</style>

<div class="att-page">

    <!-- Header -->
    <div class="att-header">
        <h1 class="att-title">Anwesenheiten</h1>

        <div class="att-actions">
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
        <button type="button" class="att-timeline-arrow left" id="att-arrow-left" aria-label="Frühere Proben">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="att-timeline" id="att-timeline">
            <?php foreach ($rehearsalData as $r): ?>
                <div class="att-timeline-pill <?= $r['id'] === $initialRehearsalId ? 'active' : '' ?> <?= !$r['isPast'] ? 'future' : '' ?>"
                     data-rehearsal-id="<?= $r['id'] ?>"
                     title="<?= $r['weekday'] ?>, <?= $r['dateFull'] ?>">
                    <span class="pill-weekday"><?= $r['weekday'] ?></span>
                    <span class="pill-date"><?= $r['dateShort'] ?></span>
                    <?php if ($r['documented']): ?>
                        <span class="pill-dot"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="att-timeline-arrow right" id="att-arrow-right" aria-label="Spätere Proben">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Member List -->
    <div id="att-member-list">
        <?php if (empty($members)): ?>
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

</div>

<!-- Save Indicator -->
<div class="att-save-indicator" id="att-save-indicator"></div>

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
        const indicator = row.querySelector('.att-indicator');
        const userId = indicator.dataset.userId;

        const existing = row.querySelector('.att-comment-text');
        const currentComment = existing ? existing.textContent : '';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Anwesenheitsnotiz',
                input: 'textarea',
                inputValue: currentComment,
                inputPlaceholder: 'z.B. "30 Min. verspätet"',
                showCancelButton: true,
                confirmButtonText: 'Speichern',
                cancelButtonText: 'Abbrechen',
                customClass: {
                    popup: 'swal2-popup-modern',
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const comment = result.value || '';
                    ajaxPost('/attendance/update', {
                        rehearsal_id: currentRehearsalId,
                        user_id: userId,
                        action: 'comment',
                        comment: comment
                    }).then(res => {
                        if (!res.success) return;
                        // Update comment display
                        let adminComment = row.querySelector('.att-comment-admin');
                        if (comment) {
                            if (!adminComment) {
                                adminComment = document.createElement('div');
                                adminComment.className = 'att-comment att-comment-admin';
                                adminComment.innerHTML = '<i class="fa-solid fa-pen att-comment-icon"></i><span class="att-comment-text" data-user-id="' + userId + '"></span>';
                                row.querySelector('.att-member-info').appendChild(adminComment);
                            }
                            adminComment.querySelector('.att-comment-text').textContent = comment;
                        } else if (adminComment) {
                            adminComment.remove();
                        }
                    });
                }
            });
        }
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

    // Timeline arrow navigation
    const timeline = document.getElementById('att-timeline');
    const arrowLeft = document.getElementById('att-arrow-left');
    const arrowRight = document.getElementById('att-arrow-right');
    const SCROLL_STEP = 200;

    function updateArrows() {
        const atStart = timeline.scrollLeft <= 5;
        const atEnd = timeline.scrollLeft + timeline.clientWidth >= timeline.scrollWidth - 5;
        arrowLeft.classList.toggle('visible', !atStart);
        arrowRight.classList.toggle('visible', !atEnd);
    }

    arrowLeft.addEventListener('click', () => { timeline.scrollLeft -= SCROLL_STEP; });
    arrowRight.addEventListener('click', () => { timeline.scrollLeft += SCROLL_STEP; });
    timeline.addEventListener('scroll', updateArrows);
    setTimeout(updateArrows, 100);
})();
</script>
