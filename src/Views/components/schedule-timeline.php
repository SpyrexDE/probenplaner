<?php

/**
 * SCHEDULE TIMELINE (read-only display)
 *
 * Shows an expandable rehearsal schedule on cards.
 * Include inside rehearsal-card.php when $rehearsal['schedule_items'] is non-empty.
 *
 * @param array $rehearsal  Full rehearsal row (needs start, end, schedule_items)
 */

$scheduleItems = $rehearsal['schedule_items'] ?? [];
if (empty($scheduleItems)) return;

$rehearsalId = $rehearsal['id'];
$timelineId = 'schedule-timeline-' . $rehearsalId;
$toggleId = 'schedule-toggle-' . $rehearsalId;

$rehearsalDate = date('Y-m-d', strtotime($rehearsal['start']));
$now = new DateTime();
$today = $now->format('Y-m-d');
$currentTime = $now->format('H:i');

$isToday = ($rehearsalDate === $today);
$isPast = ($rehearsalDate < $today);
?>

<style>
    .schedule-toggle {
        position: absolute;
        bottom: 2px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;

        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 20px;

        cursor: pointer;
        opacity: 1;
        transition: all 0.2s ease;
        pointer-events: none;
    }



    /* Create a distinct click area/visual anchor if needed, or just the icon */
    .schedule-toggle-icon {
        color: var(--color-text-muted);
        font-size: 12px;
        transition: transform 0.25s ease;
    }

    .schedule-toggle.expanded .schedule-toggle-icon {
        transform: rotate(180deg);
    }



    .schedule-toggle.expanded .schedule-toggle-icon {
        transform: rotate(180deg);
    }

    .schedule-timeline {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .schedule-timeline.open {
        max-height: 600px;
    }

    .schedule-timeline-inner {
        padding: var(--space-3) var(--space-2) var(--space-1) var(--space-2);
    }

    .schedule-item {
        display: flex;
        align-items: flex-start;
        gap: var(--space-3);
        position: relative;
    }

    .schedule-item-dot-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 12px;
        flex-shrink: 0;
        position: relative;
        margin-top: 4px;
        align-self: stretch;
        /* Center with text */
    }

    .schedule-item-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--color-text-muted);
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }

    .schedule-item-line {
        position: absolute;
        top: 0;
        bottom: -10px;
        /* Extend to next item */
        left: 50%;
        width: 2px;
        background: var(--color-border);
        transform: translateX(-50%);
        z-index: 0;
    }

    .schedule-item:first-child .schedule-item-line {
        top: 4px;
        /* Start from center of dot */
    }

    .schedule-item:last-child .schedule-item-line {
        display: none;
        /* No line after last dot */
    }

    /* Elapsed */
    .schedule-item.elapsed .schedule-item-dot {
        background: var(--color-text-muted);
        opacity: 0.45;
    }

    .schedule-item.elapsed .schedule-item-line {
        opacity: 0.35;
    }

    .schedule-item.elapsed .schedule-item-time,
    .schedule-item.elapsed .schedule-item-label {
        opacity: 0.45;
    }

    /* Active */
    .schedule-item.active .schedule-item-dot {
        background: var(--color-success);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        animation: schedule-pulse 2s ease-in-out infinite;
    }

    @keyframes schedule-pulse {

        0%,
        100% {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.1);
        }
    }

    .schedule-item-time {
        font-size: 12px;
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-secondary);
        font-family: 'Kantumruy Pro', 'SF Mono', 'Monaco', monospace;
        white-space: nowrap;
        line-height: 1;
        padding-top: 1px;
        min-width: 36px;
    }

    .schedule-item-label {
        font-size: 12px;
        color: var(--color-text-primary);
        line-height: 1.4;
        padding-top: 0;
        flex: 1;
        min-width: 0;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .schedule-item-content {
        display: flex;
        align-items: baseline;
        gap: var(--space-2);
        padding-bottom: 6px;
        flex: 1;
        min-width: 0;
    }
</style>

<!-- Expand toggle -->
<div class="schedule-toggle" id="<?= $toggleId ?>">
    <i class="fas fa-chevron-down schedule-toggle-icon"></i>
</div>

<!-- Timeline -->
<div class="schedule-timeline" id="<?= $timelineId ?>" data-date="<?= $rehearsalDate ?>">
    <div class="schedule-timeline-inner">
        <?php foreach ($scheduleItems as $index => $item):
            $itemTime = substr($item['time'], 0, 5);
            $isLast = ($index === count($scheduleItems) - 1);

            // Note: Initial PHP status logic is kept for server-side rendering, 
            // but JS will take over for live updates.
            // ... (original PHP logic kept in variable determination) ...

            // Determine next item time for "active" range detection
            $nextTime = null;
            if (!$isLast && isset($scheduleItems[$index + 1])) {
                $nextTime = substr($scheduleItems[$index + 1]['time'], 0, 5);
            }

            // Status logic
            if ($isPast) {
                $status = 'elapsed';
            } elseif ($isToday) {
                if ($nextTime !== null && $currentTime >= $itemTime && $currentTime < $nextTime) {
                    $status = 'active';
                } elseif ($isLast && $currentTime >= $itemTime) {
                    $status = 'elapsed';
                } elseif ($currentTime >= $itemTime && $nextTime !== null) {
                    $status = 'elapsed';
                } else {
                    $status = ($currentTime < $itemTime) ? 'upcoming' : 'elapsed';
                }
            } else {
                $status = 'upcoming';
            }
        ?>
            <div class="schedule-item <?= $status ?>" data-time="<?= $itemTime ?>">
                <div class="schedule-item-dot-col">
                    <div class="schedule-item-dot"></div>
                    <?php if (!$isLast): ?>
                        <div class="schedule-item-line"></div>
                    <?php endif; ?>
                </div>
                <div class="schedule-item-content">
                    <span class="schedule-item-time"><?= htmlspecialchars($itemTime) ?></span>
                    <span class="schedule-item-label"><?= htmlspecialchars($item['label']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    (function() {
        if (window.scheduleAutoUpdater) return; // Prevent duplicate intervals if multiple cards exist
        window.scheduleAutoUpdater = true;

        function updateSchedules() {
            const now = new Date();
            // Format YYYY-MM-DD
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const todayStr = `${year}-${month}-${day}`;

            // Format HH:MM
            const currentHm = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

            document.querySelectorAll('.schedule-timeline').forEach(timeline => {
                const rDate = timeline.dataset.date;
                const items = Array.from(timeline.querySelectorAll('.schedule-item'));

                // Date Check
                if (rDate < todayStr) {
                    items.forEach(i => setStatus(i, 'elapsed'));
                    return;
                }
                if (rDate > todayStr) {
                    items.forEach(i => setStatus(i, 'upcoming'));
                    return;
                }

                // Time Check (Today)
                items.forEach((item, index) => {
                    const time = item.dataset.time;
                    const nextItem = items[index + 1];
                    const nextTime = nextItem ? nextItem.dataset.time : null;

                    let status = 'upcoming';

                    if (nextTime && currentHm >= time && currentHm < nextTime) {
                        status = 'active';
                    } else if (!nextTime && currentHm >= time) {
                        // Last item is elapsed immediately after start (per existing logic)
                        status = 'elapsed';
                    } else if (nextTime && currentHm >= time) {
                        // Passed item
                        status = 'elapsed';
                    } else {
                        // Before item
                        status = (currentHm < time) ? 'upcoming' : 'elapsed';
                    }

                    setStatus(item, status);
                });
            });
        }

        function setStatus(el, status) {
            // Optimized class update
            if (el.classList.contains(status)) return;
            el.classList.remove('active', 'upcoming', 'elapsed');
            el.classList.add(status);
        }

        // Run every 5 seconds
        setInterval(updateSchedules, 5000);
        // initial run
        updateSchedules();
    })();
</script>