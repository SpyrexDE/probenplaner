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
<?php include __DIR__ . '/rehearsal-card-styles.php'; ?>
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
        data-groups="<?= htmlspecialchars(json_encode(array_values($groupArray))) ?>"
        data-tags="<?= htmlspecialchars(implode(',', $rehearsal['tags'] ?? [])) ?>"
        data-roles="<?= htmlspecialchars(implode(',', array_map(fn($r) => $r['name'] ?? '', $rehearsal['roles'] ?? []))) ?>"
        data-note="<?= htmlspecialchars(implode(' ', array_map(fn($i) => ($i['emoji'] ?? '') . ' ' . ($i['text'] ?? ''), $rehearsal['infos'] ?? []))) ?>"
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

        <!-- === ACTION BUTTONS === -->
        <?php if ($showButtons && $context === 'promises'): ?>
            <div class="flex gap-2 flex-shrink-0 self-center">
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
            <div class="flex gap-2 flex-shrink-0 self-center">
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
            <div class="flex gap-2 flex-shrink-0 self-center">
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
            $editorId = null;
            include __DIR__ . '/schedule-editor.php';
            ?>
        </div>

        <div class="ie-section" onclick="event.stopPropagation()" style="display: none;">
            <?php
            $formData = ['infos' => $rehearsal['infos'] ?? []];
            $autoSave = true;
            $apiUrl = '/' . $orchestraBase . '/api/settings/rehearsal/' . $rehearsalId;
            $editorId = null;
            include __DIR__ . '/infobox-editor.php';
            ?>
        </div>

        <div class="ie-footer">
            <div class="ie-tags" data-ie-tags>
                <?php foreach (($rehearsal['tags'] ?? []) as $tag): ?>
                    <span class="ie-tag" data-tag="<?= htmlspecialchars($tag) ?>">
                        <?= htmlspecialchars($tag) ?>
                        <button type="button" class="ie-tag-remove"
                            onclick="if(!window.IEM?._guard(event))return; window.IEM.removeTag(this)"
                            title="Entfernen">×</button>
                    </span>
                <?php endforeach; ?>
                <button type="button" class="ie-tag-add"
                    onclick="if(!window.IEM?._guard(event))return; window.IEM.addTagInput(this)"
                    title="Tag hinzufügen">+ Tag</button>
            </div>
            <button type="button" class="ie-footer-btn ie-footer-edit"
                onclick="event.stopPropagation(); window.IEM && window.IEM.duplicateRehearsal(<?= $rehearsalId ?>, this)"
                title="Probe duplizieren">
                <i class="fas fa-copy"></i> Duplizieren
            </button>
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