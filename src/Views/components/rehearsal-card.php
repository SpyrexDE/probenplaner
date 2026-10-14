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

$rehearsalType = \App\Core\RehearsalTypeManager::getRehearsalType($rehearsal);

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
<?php include __DIR__ . '/rehearsal-card-head.php'; ?>

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
            // Card root carries data-start (kept current by the inline time editor)
            $scheduleStartSource = 'closest:[data-start]';
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