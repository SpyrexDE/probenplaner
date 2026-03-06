<?php

/**
 * Instrument Select Component
 *
 * Renders a styled <select> dropdown of instruments grouped by their immediate parent.
 * Uses GroupManager::getFlattenedSections() for consistent ordering across all consumers.
 *
 * @param string $selectedType  - Currently selected type ID
 * @param string $selectName    - HTML name attribute (default: 'type')
 * @param string $selectId      - HTML id attribute (default: 'instrument-select')
 * @param string $selectClass   - CSS class (default: 'form-input-modern')
 * @param bool   $required      - Whether the field is required (default: true)
 * @param int|null $orchestraId - Override orchestra ID for config lookup (for pre-join flows)
 */

use App\Core\GroupManager;

$selectName   = $selectName   ?? 'type';
$selectId     = $selectId     ?? 'instrument-select';
$selectClass  = $selectClass  ?? 'form-input-modern';
$required     = $required     ?? true;
$selectedType = $selectedType ?? '';
$orchestraId  = $orchestraId  ?? null;

if ($orchestraId) {
    $orch = (new \App\Models\Orchestra())->findById((int) $orchestraId);
    if ($orch && !empty($orch['section_config'])) {
        $custom = is_string($orch['section_config'])
            ? json_decode($orch['section_config'], true)
            : $orch['section_config'];
        if (is_array($custom) && !empty($custom)) {
            $gm = GroupManager::fromConfig($custom);
        }
    }
}
$gm = $gm ?? new GroupManager();

// Single source of truth: ordered [parentId => [leafId, ...]]
$sections = $gm->getFlattenedSections();
?>

<select class="<?= $selectClass ?>" id="<?= $selectId ?>" name="<?= $selectName ?>" <?= $required ? 'required' : '' ?>>
    <option value="" disabled <?= $selectedType === '' ? 'selected' : '' ?>>Instrument auswählen</option>
    <?php foreach ($sections as $groupId => $leafIds): ?>
        <?php if ($groupId !== ''): ?>
            <optgroup label="<?= htmlspecialchars($gm->getDisplayName($groupId)) ?>">
            <?php endif; ?>
            <?php foreach ($leafIds as $leafId): ?>
                <?php $sel = ($leafId === $selectedType) ? ' selected' : ''; ?>
                <option value="<?= htmlspecialchars($leafId) ?>" <?= $sel ?>><?= htmlspecialchars($gm->getDisplayName($leafId)) ?></option>
            <?php endforeach; ?>
            <?php if ($groupId !== ''): ?>
            </optgroup>
        <?php endif; ?>
    <?php endforeach; ?>
</select>