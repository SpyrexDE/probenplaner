<?php

/**
 * REHEARSAL CARD HEAD
 *
 * Weekday, date/time and badge row (type, groups, roles, infos, location).
 * Used by rehearsal-card.php and promises-dashboard-card.php.
 *
 * @param array $rehearsal
 * @param bool $isInlineEdit
 */
include __DIR__ . '/rehearsal-card-styles.php';

$isInlineEdit = $isInlineEdit ?? false;
$smartDisplay = $smartDisplay ?? new \App\Core\SmartGroupDisplay();
$groupsText = $smartDisplay->generateDescription($rehearsal['groups'] ?? [], $rehearsal, false);

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
?>
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
                        <?= htmlspecialchars($groupsText) ?>
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
