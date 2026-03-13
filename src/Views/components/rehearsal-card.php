<?php

/**
 * REHEARSAL CARD
 *
 * Contexts:
 * - 'promises': Member attendance view with check/cross buttons
 * - 'rehearsals': Legacy management with edit/delete buttons
 * - 'inline-edit': Click to expand, existing elements become editable in-place
 *
 * @param array $rehearsal
 * @param string $context
 * @param array $options
 * @param array $availableRoles (inline-edit)
 */
?>
<?php if (!defined('REHEARSAL_CARD_STYLES_LOADED')): define('REHEARSAL_CARD_STYLES_LOADED', true); ?>
<style>
    /* === Base card === */
    .rehearsal-card {
        background-color: var(--color-bg-primary);
        border-color: var(--color-border);
        border-left-color: var(--color-gray-300);
        box-shadow: var(--shadow-sm);
        position: relative;
    }
    .rehearsal-card.status-pending { border-left-color: var(--color-gray-400); }
    .rehearsal-card.status-attending { border-left-color: var(--color-success); }
    .rehearsal-card.status-not-attending { border-left-color: var(--color-error); }

    .rehearsal-weekday {
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
        min-width: 40px;
        text-align: center;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-3);
        transition: font-size 0.25s ease, min-width 0.25s ease;
        flex-shrink: 0;
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

    .rehearsal-grid { display: flex; flex-direction: column; gap: var(--space-4); }

    /* === Promise buttons === */
    .action-btn {
        background-color: var(--color-bg-primary);
        border-color: var(--color-border);
        transition: all var(--transition-base);
    }
    .action-btn:hover { border-color: var(--color-primary); background-color: var(--color-primary-50); box-shadow: var(--shadow-md); }
    .action-btn:active { transform: translateY(-1px); }
    .action-btn.deselected { opacity: 0.4; background-color: var(--color-bg-tertiary); border-color: var(--color-border); box-shadow: none; }
    .action-btn.deselected:hover { opacity: 0.7; }
    .action-btn.deselected i { filter: grayscale(100%) brightness(0.7); }

    .checkBtn { color: var(--color-success); }
    .checkBtn i { color: var(--color-success-icon); }
    .checkBtn:not(.deselected) { border-color: var(--color-success); background: linear-gradient(135deg, var(--color-success-50) 0%, var(--color-success-100) 100%); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25); }
    .checkBtn.deselected { border-color: var(--color-border); background-color: var(--color-bg-tertiary); }
    .checkBtn.deselected i { color: var(--color-text-muted); }

    .crossBtn { color: var(--color-error); }
    .crossBtn i { color: var(--color-error-icon); }
    .crossBtn:not(.deselected) { border-color: var(--color-error); background: linear-gradient(135deg, var(--color-error-50) 0%, var(--color-error-100) 100%); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25); }
    .crossBtn.deselected { border-color: var(--color-border); background-color: var(--color-bg-tertiary); }
    .crossBtn.deselected i { color: var(--color-text-muted); }

    /* === Badges === */
    .rehearsal-type-badge { color: #7c3aed; background-color: rgba(124, 58, 237, 0.15); border: 1px solid rgba(124, 58, 237, 0.25); }
    .rehearsal-section-badge, .rehearsal-location-badge { color: var(--color-text-secondary); background-color: var(--color-bg-tertiary); border: 1px solid var(--color-border); }
    .rehearsal-note-icon, .rehearsal-note-text { color: var(--color-text-muted); }

    /* ══════════════ INLINE EDIT MODE ══════════════ */
    .rehearsal-card.ie-card {
        cursor: pointer;
        transition: box-shadow 0.25s ease, padding 0.3s ease, border-color 0.3s ease;
    }

    @media (hover: hover) {
        .rehearsal-card.ie-card:not(.ie-expanded):hover {
            box-shadow: var(--shadow-md);
        }
        .rehearsal-card.ie-card:not(.ie-expanded):hover .ie-chevron {
            opacity: 0.8;
            transform: translateX(2px);
        }
    }

    /* ── Edit toggle button ── */
    .ie-edit-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: var(--radius-full, 50%);
        color: var(--color-text-muted);
        font-size: 14px;
        flex-shrink: 0;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
    }
    @media (hover: hover) {
        .ie-edit-toggle:hover {
            color: var(--color-primary);
            background: rgba(71, 140, 244, 0.08);
        }
    }
    .ie-expanded .ie-edit-toggle {
        color: var(--color-primary);
        background: rgba(71, 140, 244, 0.12);
    }
    .ie-expanded .ie-edit-toggle .fa-pen::before {
        content: '\f00c';
    }

    /* Hide empty/placeholder fields on non-expanded cards */
    .ie-card:not(.ie-expanded) .ie-editable[style*="opacity: 0.4"] { display: none !important; }
    .ie-card:not(.ie-expanded) .ie-editable[style*="dashed"] { display: none !important; }
    .ie-card:not(.ie-expanded) [data-ie-color] { display: none !important; }
    .ie-card:not(.ie-expanded) .ie-role-add { display: none !important; }
    .ie-card:not(.ie-expanded) .ie-role-remove { display: none !important; }

    /* ── EXPANDED (edit mode) ── */
    .rehearsal-card.ie-expanded {
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.3), 0 0 12px rgba(71, 140, 244, 0.15), 0 0 24px rgba(71, 140, 244, 0.08);
        cursor: default;
        padding: var(--space-4) var(--space-5) !important;
        border-left-width: 5px;
    }
    .ie-expanded .rehearsal-weekday { font-size: 30px; min-width: 48px; }
    .ie-expanded .ie-date-text { font-size: var(--font-size-xl) !important; }
    .ie-expanded .ie-time-text { font-size: var(--font-size-base) !important; }

    /* Badges swell */
    .ie-expanded .ie-editable {
        padding: 6px 12px !important;
        font-size: 12px !important;
        min-height: 32px;
        display: inline-flex !important;
        align-items: center;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    @media (hover: hover) {
        .ie-expanded .ie-editable:hover {
            border-color: var(--color-primary-200);
            background-color: var(--color-bg-secondary);
        }
    }
    .ie-expanded .ie-editable:active {
        background-color: var(--color-primary-50);
        border-color: var(--color-primary-300);
    }
    .ie-editable.ie-editing {
        border-color: var(--color-primary) !important;
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.15);
    }
    .ie-expanded .rehearsal-badges {
        gap: var(--space-2) !important;
        margin-top: var(--space-2) !important;
    }
    .ie-expanded .ie-info-tag { display: none !important; }
    .ie-editable.ie-editing { opacity: 1 !important; border-style: solid !important; }

    /* ── Inline input ── */
    .ie-inline-input {
        font: inherit;
        color: inherit;
        background: transparent;
        border: none;
        outline: none;
        padding: 0;
        margin: 0;
        width: 100%;
        min-width: 60px;
    }
    .ie-inline-input::placeholder { color: var(--color-text-muted); font-style: italic; }

    /* ── Popover ── */
    .ie-popover {
        position: absolute;
        z-index: 50;
        background: var(--color-bg-primary);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: var(--space-2);
        box-shadow: var(--shadow-lg);
        margin-top: var(--space-1);
        max-height: 300px;
        overflow-y: auto;
    }
    .ie-popover label { min-height: 36px; padding: var(--space-1) var(--space-2); }

    /* ── Inline role tags ── */
    .ie-role-tag { position: relative; }
    .ie-role-remove {
        background: none;
        border: none;
        cursor: pointer;
        -webkit-text-fill-color: var(--role-color);
        color: var(--role-color);
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        padding: 0 0 0 4px;
        opacity: 0.7;
        transition: opacity 0.15s ease;
    }
    .ie-role-remove:hover { opacity: 1; }
    .ie-role-add {
        -webkit-text-fill-color: var(--color-text-muted) !important;
        -webkit-background-clip: unset !important;
        background-clip: unset !important;
        background: none !important;
        color: var(--color-text-muted) !important;
        border: 1px dashed var(--color-border) !important;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .ie-role-add::before { display: none !important; }
    .ie-role-add:hover {
        background: color-mix(in srgb, var(--color-text-muted) 10%, transparent) !important;
    }

    /* ── Expanded sections ── */
    .ie-section {
        margin-top: var(--space-3);
        padding-top: var(--space-3);
        animation: ie-fade-in 0.3s ease;
    }
    .ie-expanded .ie-section { display: block !important; }
    @keyframes ie-fade-in {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── Groups dialog ── */
    .ie-groups-dialog {
        border: none;
        padding: 0;
        background: transparent;
        max-width: 480px;
        width: calc(100% - var(--space-6));
        max-height: 80vh;
        border-radius: var(--radius-lg);
        overflow: visible;
    }
    .ie-groups-dialog::backdrop {
        background: rgba(0, 0, 0, 0.4);
        animation: ie-fade-in 0.15s ease;
    }
    .ie-groups-dialog .ie-groups-panel {
        background: var(--color-bg-primary);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl, 0 20px 60px rgba(0,0,0,0.3));
        display: flex;
        flex-direction: column;
        max-height: 80vh;
        animation: ie-scale-in 0.2s ease;
    }
    @keyframes ie-scale-in {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .ie-groups-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-4);
        border-bottom: 1px solid var(--color-border);
        flex-shrink: 0;
    }
    .ie-groups-header h3 {
        margin: 0;
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
    }
    .ie-groups-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: var(--space-2);
        border-radius: var(--radius-sm);
        color: var(--color-text-muted);
        font-size: 18px;
        line-height: 1;
        transition: color 0.15s ease;
    }
    .ie-groups-close:hover { color: var(--color-text-primary); }
    .ie-groups-body {
        padding: var(--space-4);
        overflow-y: auto;
        flex: 1;
    }

    /* ── Footer actions ── */
    .ie-footer {
        display: none;
        justify-content: space-between;
        align-items: center;
        margin-top: var(--space-3);
        padding-top: var(--space-2);
        border-top: 1px solid var(--color-border);
    }
    .ie-expanded .ie-footer { display: flex; }

    .ie-footer-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        transition: opacity 0.15s ease, color 0.15s ease, background 0.15s ease;
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        gap: var(--space-1);
    }
    .ie-footer-edit {
        color: var(--color-text-secondary);
        opacity: 0.6;
    }
    .ie-footer-edit:hover, .ie-footer-edit:active { opacity: 1; color: var(--color-primary); }
    .ie-footer-delete {
        color: var(--color-text-muted);
        opacity: 0.5;
    }
    .ie-footer-delete:hover, .ie-footer-delete:active { opacity: 1; color: var(--color-error); }

    /* ── Mobile ── */
    @media (max-width: 480px) {
        .rehearsal-card.ie-expanded { padding: var(--space-3) var(--space-3) !important; }
        .ie-expanded .ie-editable { padding: 8px 12px !important; font-size: 13px !important; min-height: 36px; }
        .ie-expanded .rehearsal-weekday { font-size: 26px; min-width: 42px; }
    }
</style>
<?php endif; ?>
<?php
$context = $context ?? 'rehearsals';
$options = $options ?? [];
$status = $options['status'] ?? 'pending';
$note = $options['note'] ?? '';
$showButtons = $options['showButtons'] ?? true;
$buttons = $options['buttons'] ?? [];

$rehearsalId = $rehearsal['id'];
$groupArray = $rehearsal['groups'] ?? [];

$smartDisplay = $smartDisplay ?? new \App\Core\SmartGroupDisplay();
$groupsText = $smartDisplay->generateDescription($groupArray, $rehearsal, false);

$startDt = new DateTime($rehearsal['start']);
$endDt = new DateTime($rehearsal['end']);
$isSameDay = $startDt->format('Y-m-d') === $endDt->format('Y-m-d');
$germanWeekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
$weekdayShort = $germanWeekdays[$startDt->format('w')];

if ($isSameDay) {
    $dateDisplay = \App\Core\Utilities::formatDate($startDt->format('Y-m-d'));
    $timeDisplay = $startDt->format('H:i') . ' - ' . $endDt->format('H:i');
} else {
    $dateDisplay = \App\Core\Utilities::formatDate($startDt->format('Y-m-d')) . ' - ' . \App\Core\Utilities::formatDate($endDt->format('Y-m-d'));
    $timeDisplay = $startDt->format('H:i') . ' - ' . $endDt->format('H:i');
}

$rehearsalType = \App\Core\RehearsalTypeManager::getRehearsalType($rehearsal);
$showRehearsalType = \App\Core\RehearsalTypeManager::shouldDisplayType($rehearsalType);
$normalLocation = 'Probenraum';
$showLocation = !empty($rehearsal['location']) && strtolower($rehearsal['location']) !== strtolower($normalLocation);

$isInlineEdit = ($context === 'inline-edit');
$cardClasses = 'rehearsal-card';
if (!empty($status) && $context === 'promises') $cardClasses .= ' status-' . $status;
if ($isInlineEdit) {
    $cardClasses .= ' ie-card';
    if (!empty($options['expanded'])) $cardClasses .= ' ie-expanded';
}

$orchestraBase = ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
$hasExpandableContent = !empty($rehearsal['schedule_items']) || !empty($rehearsal['infos']);
?>

<div class="<?= $cardClasses ?> border border-l-4 my-2 transition-all duration-200"
    <?php if ($isInlineEdit): ?>
        data-rehearsal-id="<?= $rehearsalId ?>"
        data-api-url="/<?= $orchestraBase ?>/api/settings/rehearsal/<?= $rehearsalId ?>"
        data-start="<?= htmlspecialchars($rehearsal['start']) ?>"
        data-end="<?= htmlspecialchars($rehearsal['end']) ?>"
        data-location="<?= htmlspecialchars($rehearsal['location'] ?? '') ?>"
        data-type="<?= htmlspecialchars($rehearsalType) ?>"
        data-color="<?= htmlspecialchars($rehearsal['color'] ?? '#e5e7eb') ?>"
        data-groups="<?= htmlspecialchars(json_encode(array_column($groupArray, 'id'))) ?>"
        onclick="window.IEM && window.IEM.onCardClick(this, event)"
    <?php elseif ($hasExpandableContent): ?>
        onclick="(function(e){
            if(e.target.closest('button') || e.target.closest('a')) return;
            const tl = document.getElementById('schedule-timeline-<?= $rehearsalId ?>');
            const tg = document.getElementById('schedule-toggle-<?= $rehearsalId ?>');
            if(tl && tg) { tl.classList.toggle('open'); tg.classList.toggle('expanded'); }
        })(event)"
    <?php endif; ?>
    style="border-radius: var(--radius-lg); padding: var(--space-3) var(--space-4); <?= !empty($rehearsal['color']) ? 'border-left-color: ' . $rehearsal['color'] . ';' : '' ?>">

    <div class="flex items-start w-full gap-3">
        <div class="flex-1 min-w-0 flex flex-col">

            <!-- Row 1: Weekday + Date/Time + Edit Toggle -->
            <div class="flex items-center gap-2 flex-wrap" style="margin-bottom: 8px;">
                    <div class="rehearsal-weekday" data-ie-weekday><?= strtoupper($weekdayShort) ?></div>

                    <div class="flex flex-col gap-0"
                         style="padding: 2px 6px; transition: all 0.25s ease;"
                         <?php if ($isInlineEdit): ?>
                             data-ie-field="datetime"
                         <?php endif; ?>>
                        <div class="ie-date-text" data-ie-date style="font-size: var(--font-size-lg); font-weight: var(--font-weight-bold); color: var(--color-text-primary); line-height: 1.2; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; width: fit-content; transition: font-size 0.25s ease;">
                            <?= htmlspecialchars($dateDisplay) ?>
                        </div>
                        <div class="ie-time-text" data-ie-time style="font-size: var(--font-size-sm); color: var(--color-text-secondary); font-weight: var(--font-weight-medium); line-height: 1.4; font-family: 'Kantumruy Pro', 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace; white-space: nowrap; margin-top: -2px; transition: font-size 0.25s ease;">
                            <?= htmlspecialchars($timeDisplay) ?>
                        </div>
                </div>

                <?php if ($isInlineEdit): ?>
                    <button type="button" class="ie-edit-toggle" data-ie-toggle
                        onclick="event.stopPropagation(); window.IEM && window.IEM.toggleEdit(this)"
                        title="Bearbeiten" style="margin-left: auto;">
                        <i class="fas fa-pen"></i>
                    </button>
                <?php endif; ?>
            </div>

                <!-- Row 2: Badges -->
                <div class="rehearsal-badges flex items-center gap-1 flex-wrap" style="margin-top: 4px; transition: gap 0.25s ease, margin 0.25s ease;">
                    <?php if ($showRehearsalType || $isInlineEdit): ?>
                        <div class="rehearsal-type-badge <?= $isInlineEdit ? 'ie-editable' : '' ?>"
                             data-ie-type
                             style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1); transition: all 0.25s ease; <?= (!$showRehearsalType && $isInlineEdit) ? 'opacity: 0.4; border-style: dashed;' : '' ?>"
                             <?php if ($isInlineEdit): ?>
                                 data-ie-field="type"
                                 onclick="if(!window.IEM?._guard(event))return; window.IEM.editType(this)"
                             <?php endif; ?>>
                            <?= htmlspecialchars($showRehearsalType ? $rehearsalType : 'Typ…') ?>
                        </div>
                    <?php endif; ?>

                    <div class="rehearsal-section-badge <?= $isInlineEdit ? 'ie-editable' : '' ?>"
                         data-ie-groups
                         style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1); transition: all 0.25s ease; position: relative;"
                         <?php if ($isInlineEdit): ?>
                             data-ie-field="groups"
                             onclick="if(!window.IEM?._guard(event))return; window.IEM.editGroups(this)"
                         <?php endif; ?>>
                        <?= $groupsText ?>
                    </div>

                    <?php if ($isInlineEdit): ?>
                        <?php if (!empty($rehearsal['roles'])): ?>
                            <?php foreach ($rehearsal['roles'] as $role): ?>
                                <span class="role-tag ie-role-tag" data-role-id="<?= (int)$role['id'] ?>"
                                      style="--role-color: <?= htmlspecialchars($role['tag_color'] ?? '#478cf4') ?>; cursor: default;">
                                    <?= htmlspecialchars($role['name']) ?>
                                    <button type="button" class="ie-role-remove"
                                        onclick="if(!window.IEM?._guard(event))return; window.IEM.removeRole(this, <?= (int)$role['id'] ?>)"
                                        title="Entfernen">×</button>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button type="button" class="role-tag ie-role-add"
                            style="--role-color: #9ca3af; cursor: pointer;"
                            onclick="if(!window.IEM?._guard(event))return; window.IEM.addRolePopover(this)"
                            title="Rolle hinzufügen">+</button>
                    <?php elseif (!empty($rehearsal['roles'])): ?>
                        <?php foreach ($rehearsal['roles'] as $role): ?>
                            <?= \App\Core\Utilities::renderRoleTag($role) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($rehearsal['infos'])): ?>
                        <?php foreach ($rehearsal['infos'] as $info): ?>
                            <span class="ie-info-tag" style="font-size: 11px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; width: fit-content; margin-right: var(--space-1); background-color: transparent; border: 1px solid var(--color-border); color: var(--color-text-primary); transition: all 0.25s ease;">
                                <?= htmlspecialchars($info['emoji']) ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($showLocation || $isInlineEdit): ?>
                        <span class="<?= $isInlineEdit ? 'ie-editable' : '' ?>"
                              data-ie-location
                              style="font-size: 10px; font-weight: var(--font-weight-semibold); text-transform: uppercase; letter-spacing: 0.3px; padding: 2px 6px; border-radius: var(--radius-sm); display: inline-block; width: fit-content; margin-right: var(--space-1); color: var(--color-text-secondary); transition: all 0.25s ease; <?= (!$showLocation && $isInlineEdit) ? 'opacity: 0.4; border: 1px dashed var(--color-border);' : '' ?>"
                              <?php if ($isInlineEdit): ?>
                                  data-ie-field="location"
                                  onclick="if(!window.IEM?._guard(event))return; window.IEM.editLocation(this)"
                              <?php endif; ?>>
                            <?= htmlspecialchars($rehearsal['location'] ?: '📍 Ort…') ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($isInlineEdit): ?>
                        <span class="ie-editable" data-ie-field="color" data-ie-color
                              onclick="if(!window.IEM?._guard(event))return; window.IEM.editColor(this)"
                              style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: var(--radius-sm); cursor: pointer; transition: all 0.25s ease; position: relative; color: var(--color-text-muted); font-size: 10px;">
                            <span data-ie-color-dot style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: <?= htmlspecialchars($rehearsal['color'] ?? '#e5e7eb') ?>; border: 1px solid var(--color-border); transition: all 0.25s ease;"></span>
                            Farbe
                        </span>
                    <?php endif; ?>
            </div>
        </div>

        <!-- === ACTION BUTTONS (promises / legacy only) === -->
        <?php if ($showButtons && $context === 'promises'): ?>
            <div class="flex gap-2 flex-shrink-0">
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
            </div>
        <?php elseif ($showButtons && $context === 'rehearsals'): ?>
            <div class="flex gap-2 flex-shrink-0">
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
            </div>
        <?php elseif ($showButtons && !empty($buttons)): ?>
            <div class="flex gap-2 flex-shrink-0">
                <?php foreach ($buttons as $button): ?>
                    <button type="button" id="<?= $rehearsalId ?>"
                        class="<?= $button['class'] ?? 'btn-primary' ?>"
                        <?= !empty($button['data_attrs']) ? implode(' ', array_map(fn($k, $v) => 'data-' . $k . '="' . htmlspecialchars($v) . '"', array_keys($button['data_attrs']), $button['data_attrs'])) : '' ?>>
                        <?= $button['content'] ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Promise note -->
    <?php if ($context === 'promises' && !empty($note)): ?>
        <div class="rehearsal-note-tag">
            <i class="fa-solid fa-quote-left rehearsal-note-icon"></i>
            <span class="rehearsal-note-text"><?= htmlspecialchars($note) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($context === 'promises'): ?>
        <input type="hidden" id="note<?= $rehearsalId ?>" value="<?= htmlspecialchars($note) ?>">
    <?php endif; ?>

    <!-- Inline edit sections -->
    <?php if ($isInlineEdit): ?>
        <div class="ie-section" onclick="event.stopPropagation()" style="display: none;">
            <?php
            $formData = ['schedule_items' => $rehearsal['schedule_items'] ?? []];
            $autoSave = true;
            $apiUrl = '/' . $orchestraBase . '/api/settings/rehearsal/' . $rehearsalId;
            include __DIR__ . '/schedule-editor.php';
            ?>
        </div>

        <div class="ie-section" onclick="event.stopPropagation()" style="display: none;">
            <?php
            $formData = ['infos' => $rehearsal['infos'] ?? []];
            $autoSave = true;
            $apiUrl = '/' . $orchestraBase . '/api/settings/rehearsal/' . $rehearsalId;
            include __DIR__ . '/infobox-editor.php';
            ?>
        </div>

        <div class="ie-footer">
            <span></span>
            <button type="button" class="ie-footer-btn ie-footer-delete"
                onclick="event.stopPropagation(); window.IEM && window.IEM.deleteRehearsal(<?= $rehearsalId ?>)"
                title="Probe löschen">
                <i class="fas fa-trash-alt"></i> Löschen
            </button>
        </div>
    <?php endif; ?>

    <!-- Schedule Timeline (read-only expandable, non-edit contexts) -->
    <?php if (!$isInlineEdit): ?>
        <?php include __DIR__ . '/schedule-timeline.php'; ?>
    <?php endif; ?>
</div>