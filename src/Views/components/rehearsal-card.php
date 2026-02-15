<?php

/**
 * REHEARSAL CARD
 * 
 * Features:
 * - Context-aware: 'promises', 'rehearsals', custom
 * - Smart group display via SmartGroupDisplay service
 * - Rehearsal type badges via RehearsalTypeManager
 * - Conditional UI based on user role and context
 * - Date/time formatting
 * - Uses Tailwind utility classes + minimal custom CSS
 * 
 * Usage for AI:
 * $context = 'promises|rehearsals';
 * $options = ['status' => '...', 'showButtons' => bool];
 * include __DIR__ . '/../components/rehearsal-card.php';
 * 
 * @param array $rehearsal - The rehearsal data
 * @param string $context - Context: 'promises' or 'rehearsals'
 * @param array $options - Additional options for customization
 *   - status: string - For promises context (attending/not_attending/pending)
 *   - note: string - Note text for promises
 *   - showButtons: bool - Whether to show action buttons (default: true)
 *   - buttons: array - Custom button configuration
 */

// Component Styles - Only sophisticated effects that Tailwind can't handle
?>
<style>
    /* Rehearsal Card */
    .rehearsal-card {
        background-color: var(--color-bg-primary);
        border-color: var(--color-border);
        border-left-color: var(--color-gray-300);
        box-shadow: var(--shadow-sm);
    }


    .rehearsal-card.status-pending {
        border-left-color: var(--color-gray-400);
    }

    .rehearsal-card:focus-within {
        box-shadow: var(--shadow-sm);
    }

    /* Weekday styling */
    .rehearsal-weekday {
        font-size: 24px;
        font-weight: 900;
        color: var(--color-primary);
        line-height: 1;
        min-width: 40px;
        text-align: center;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        text-shadow: 0 2px 4px rgba(71, 140, 244, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-3);
    }

    .rehearsal-weekday::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 2px;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        border-radius: 1px;
        opacity: 0.6;
    }

    /* Action Button */
    .action-btn {
        background-color: var(--color-bg-primary);
        border-color: var(--color-border);
        transition: all var(--transition-base);
    }

    .action-btn:hover {
        border-color: var(--color-primary);
        background-color: var(--color-primary-50);
        box-shadow: var(--shadow-md);
    }

    .action-btn:active {
        transform: translateY(-1px);
        transition: all 100ms ease;
    }


    .action-btn.selected i {
        filter: brightness(1) saturate(1.2);
    }

    .action-btn.deselected {
        opacity: 0.4;
        background-color: var(--color-bg-tertiary);
        border-color: var(--color-border);
        box-shadow: none;
    }

    .action-btn.deselected:hover {
        opacity: 0.7;
        background-color: var(--color-primary-50);
        border-color: var(--color-primary-200);
        box-shadow: var(--shadow-sm);
        transform: translateY(-1px);
    }

    .action-btn.deselected i {
        filter: grayscale(100%) brightness(0.7);
    }

    .action-btn.deselected:hover i {
        filter: grayscale(50%) brightness(0.9);
    }

    /* Check Button (attending) */
    .checkBtn {
        color: var(--color-success);
        background-color: var(--color-bg-primary);
    }

    .checkBtn i {
        color: var(--color-success-icon);
    }

    .checkBtn:not(.deselected) {
        border-color: var(--color-success);
        background: linear-gradient(135deg, var(--color-success-50) 0%, var(--color-success-100) 100%);
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
        opacity: 1;
    }

    .checkBtn.deselected {
        border-color: var(--color-border);
        background-color: var(--color-bg-tertiary);
        box-shadow: none;
        opacity: 0.4;
        color: var(--color-text-muted);
    }

    .checkBtn.deselected i {
        color: var(--color-text-muted);
    }

    /* Cross Button (not attending) */
    .crossBtn {
        color: var(--color-error);
        background-color: var(--color-bg-primary);
    }

    .crossBtn i {
        color: var(--color-error-icon);
    }

    .crossBtn:not(.deselected) {
        border-color: var(--color-error);
        background: linear-gradient(135deg, var(--color-error-50) 0%, var(--color-error-100) 100%);
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
        opacity: 1;
    }

    .crossBtn.deselected {
        border-color: var(--color-border);
        background-color: var(--color-bg-tertiary);
        box-shadow: none;
        opacity: 0.4;
        color: var(--color-text-muted);
    }

    .crossBtn.deselected i {
        color: var(--color-text-muted);
    }

    /* Badges */
    .rehearsal-type-badge {
        color: #7c3aed;
        background-color: rgba(124, 58, 237, 0.15);
        border: 1px solid rgba(124, 58, 237, 0.25);
    }

    .rehearsal-section-badge,
    .rehearsal-location-badge {
        color: var(--color-text-secondary);
        background-color: var(--color-bg-tertiary);
        border: 1px solid var(--color-border);
    }

    /* Note styling */
    .rehearsal-note-icon,
    .rehearsal-note-text {
        color: var(--color-text-muted);
    }

    /* === REHEARSAL GRID LAYOUT === */
    .rehearsal-grid {
        display: flex;
        flex-direction: column;
        gap: var(--space-4);
    }

    /* === STATUS COLORS === */
    .rehearsal-card.status-attending {
        border-left-color: var(--color-success);
    }

    .rehearsal-card.status-not-attending {
        border-left-color: var(--color-error);
    }

    .rehearsal-card.status-pending {
        border-left-color: var(--color-gray-400);
    }
</style>

<?php

// Default configuration
$context = $context ?? 'rehearsals';
$options = $options ?? [];
$status = $options['status'] ?? 'pending';
$note = $options['note'] ?? '';
$showButtons = $options['showButtons'] ?? true;
$buttons = $options['buttons'] ?? [];

// Extract rehearsal details
$rehearsalId = $rehearsal['id'];
$groupArray = $rehearsal['groups'] ?? [];

$smartDisplay = $smartDisplay ?? new \App\Core\SmartGroupDisplay();
$groupsText = $smartDisplay->generateDescription(
    $groupArray,
    $rehearsal,
    false
);

$start_time = substr($rehearsal['start_time'], 0, 5);
$end_time = substr($rehearsal['end_time'], 0, 5);
$time_display = $start_time . ' - ' . $end_time;

$germanWeekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
$dateForWeekday = $rehearsal['start'];
$dayOfWeek = $dateForWeekday ? (int)date('w', strtotime($dateForWeekday)) : 0;
$weekdayShort = $germanWeekdays[$dayOfWeek];

// Resolve rehearsal type
$rehearsalType = \App\Core\RehearsalTypeManager::getRehearsalType($rehearsal);

// Determine badge visibility
$showRehearsalType = \App\Core\RehearsalTypeManager::shouldDisplayType($rehearsalType);

// Highlight non-standard locations
$normalLocation = 'Probenraum'; // Default normal rehearsal location
$showLocation = !empty($rehearsal['location']) &&
    $rehearsal['location'] !== $normalLocation &&
    strtolower($rehearsal['location']) !== strtolower($normalLocation);

$cardClasses = 'rehearsal-card';
if (!empty($status)) {
    $cardClasses .= ' status-' . $status;
}
?>

<!-- REHEARSAL CARD -->
<div class="<?= $cardClasses ?> border border-l-4 my-2"
    style="border-radius: var(--radius-lg); padding: var(--space-3) var(--space-4); <?= !empty($rehearsal['color']) ? 'border-left-color: ' . $rehearsal['color'] . ';' : '' ?>">

    <!-- Card Content: Flexbox layout with Tailwind -->
    <div class="flex items-center w-full gap-4">

        <!-- Card Info: Flex-grow for main content -->
        <div class="flex-1 min-w-0 flex flex-col gap-0">

            <!-- Card Header: Relative positioning -->
            <div class="relative">

                <!-- Main Info Container -->
                <div class="flex flex-col flex-1">

                    <!-- Content Row: Weekday + Date/Time -->
                    <div class="rehearsal-content-row flex items-center gap-2" style="margin-bottom: 8px;">
                        <?php
                        $startDt = new DateTime($rehearsal['start']);
                        $endDt = new DateTime($rehearsal['end']);
                        $isSameDay = $startDt->format('Y-m-d') === $endDt->format('Y-m-d');

                        $germanWeekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
                        $dayOfWeek = $startDt->format('w');
                        $weekdayShort = $germanWeekdays[$dayOfWeek];

                        if ($isSameDay) {
                            $dateDisplay = \App\Core\Utilities::formatDate($startDt->format('Y-m-d'));
                            $timeDisplay = $startDt->format('H:i') . ' - ' . $endDt->format('H:i');
                        } else {
                            $dateDisplay = \App\Core\Utilities::formatDate($startDt->format('Y-m-d')) . ' - ' . \App\Core\Utilities::formatDate($endDt->format('Y-m-d'));
                            $timeDisplay = $startDt->format('H:i') . ' - ' . $endDt->format('H:i');
                        }
                        ?>
                        <!-- Weekday underline effect -->
                        <div class="rehearsal-weekday"><?= strtoupper($weekdayShort) ?></div>

                        <!-- Date/Time Block -->
                        <div class="flex flex-col gap-0">
                            <div class="rehearsal-date" style="font-size: var(--font-size-lg); font-weight: var(--font-weight-bold); color: var(--color-text-primary); line-height: 1.2; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; width: fit-content;">
                                <?= htmlspecialchars($dateDisplay) ?>
                            </div>
                            <div style="height: 18px; overflow: hidden; position: relative; margin-top: -2px; width: 100%;">
                                <div style="font-size: var(--font-size-sm); color: var(--color-text-secondary); font-weight: var(--font-weight-medium); line-height: 1; font-family: 'Kantumruy Pro', 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace; white-space: nowrap; position: absolute; top: 50%; left: 0; transform: translateY(-50%); transform-origin: left center;">
                                    <?= htmlspecialchars($timeDisplay) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Badges Row -->
                    <div class="rehearsal-badges flex items-center gap-1 flex-wrap" style="margin-top: 4px;">
                        <?php if ($showRehearsalType): ?>
                            <div class="rehearsal-type-badge" style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1);">
                                <?= htmlspecialchars($rehearsalType) ?>
                            </div>
                        <?php endif; ?>
                        <div class="rehearsal-section-badge" style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1);">
                            <?= $groupsText ?>
                        </div>
                        <?php if ($showLocation): ?>
                            <span style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1); color: var(--color-text-secondary);">
                                <?= htmlspecialchars($rehearsal['location']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>

        <!-- Actions: Flex-shrink-0 to prevent compression -->
        <?php if ($showButtons): ?>
            <div class="flex gap-2 flex-shrink-0">
                <?php if ($context === 'promises'): ?>
                    <!-- Promise Buttons: Check/Cross with sophisticated states -->
                    <button type="button" id="<?= $rehearsalId ?>"
                        class="checkBtn action-btn w-[52px] h-[52px] border-2 flex items-center justify-center cursor-pointer <?= $status !== 'attending' ? 'deselected' : 'selected' ?>"
                        style="border-radius: var(--radius-lg);">
                        <i class="fas fa-check-square text-[28px] transition-all duration-200"></i>
                    </button>
                    <button type="button" id="<?= $rehearsalId ?>"
                        class="crossBtn action-btn cross-btn w-[52px] h-[52px] border-2 flex items-center justify-center cursor-pointer <?= $status !== 'not_attending' ? 'deselected' : 'selected' ?>"
                        style="border-radius: var(--radius-lg);">
                        <i class="fas fa-times-square text-[28px] transition-all duration-200"></i>
                    </button>
                <?php elseif ($context === 'rehearsals'): ?>
                    <!-- Rehearsal Management Buttons: Using existing button component styles -->
                    <button type="button" id="<?= $rehearsalId ?>"
                        class="btn-base btn-icon btn-outline edit-btn inline-flex items-center justify-center w-12 h-12 border-2 transition-all duration-200"
                        style="border-radius: var(--radius-md);">
                        <i><?= icon('edit', 'text-gray-600') ?></i>
                    </button>
                    <button type="button" id="<?= $rehearsalId ?>"
                        class="btn-modern btn-danger delete-btn inline-flex items-center justify-center w-12 h-12 border-0 transition-all duration-200"
                        style="border-radius: var(--radius-md);">
                        <i><?= icon('trash', 'text-white') ?></i>
                    </button>
                <?php else: ?>
                    <!-- Custom Buttons -->
                    <?php foreach ($buttons as $button): ?>
                        <button
                            type="button"
                            id="<?= $rehearsalId ?>"
                            class="<?= $button['class'] ?? 'btn-primary' ?>"
                            <?= !empty($button['data_attrs']) ? implode(' ', array_map(function ($k, $v) {
                                return 'data-' . $k . '="' . htmlspecialchars($v) . '"';
                            }, array_keys($button['data_attrs']), $button['data_attrs'])) : '' ?>>
                            <?= $button['content'] ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Note Tag for Promises Context -->
    <?php if ($context === 'promises' && !empty($note)): ?>
        <div class="rehearsal-note-tag">
            <i class="fa-solid fa-quote-left rehearsal-note-icon"></i>
            <span class="rehearsal-note-text"><?= htmlspecialchars($note) ?></span>
        </div>
    <?php endif; ?>

    <!-- Hidden Note Input for JavaScript -->
    <?php if ($context === 'promises'): ?>
        <input type="hidden" id="note<?= $rehearsalId ?>" value="<?= htmlspecialchars($note) ?>">
    <?php endif; ?>

</div>