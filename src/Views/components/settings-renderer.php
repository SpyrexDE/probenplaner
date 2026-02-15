<?php

/**
 * Settings Renderer Component
 *
 * Renders auto-save form fields from FieldRegistry definitions.
 *
 * Usage:
 *   $settingsEntity   = 'orchestra';
 *   $settingsEntityId = $orchestra['id'];
 *   $settingsData     = $orchestra;
 *   $settingsGroups   = ['basic', 'security'];  // optional filter
 *   include __DIR__ . '/settings-renderer.php';
 */

use App\Core\FieldRegistry;

$entity   = $settingsEntity ?? '';
$entityId = $settingsEntityId ?? 0;
$data     = $settingsData ?? [];
$groups   = $settingsGroups ?? null; // null = all groups
$orchestraId = $_SESSION['current_orchestra_id'] ?? '';

$allFields  = FieldRegistry::getFields($entity);
$groupDefs  = FieldRegistry::getGroups($entity);

// Filter groups if requested
if ($groups !== null) {
    $groupDefs = array_intersect_key($groupDefs, array_flip($groups));
}

// Index fields by group
$fieldsByGroup = [];
foreach ($allFields as $field) {
    $g = $field['group'] ?? 'default';
    if ($groups !== null && !in_array($g, $groups)) continue;
    $fieldsByGroup[$g][] = $field;
}
?>

<style>
    /* SETTINGS RENDERER */
    .settings-save-indicator {
        position: fixed;
        bottom: var(--space-4);
        right: var(--space-4);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        display: none;
        align-items: center;
        gap: var(--space-2);
        z-index: 1000;
        box-shadow: var(--shadow-lg);
        transition: all var(--transition-base);
    }

    .settings-save-indicator.saving {
        background: var(--color-primary-100);
        color: var(--color-primary-700);
    }

    .settings-save-indicator.success {
        background: var(--color-success-100, #d1fae5);
        color: var(--color-success-700, #047857);
    }

    .settings-save-indicator.error {
        background: var(--color-error-100, #fee2e2);
        color: var(--color-error-700, #b91c1c);
    }

    .settings-field-error {
        color: var(--color-error-500, #ef4444);
        font-size: var(--font-size-xs);
        margin-top: var(--space-1);
    }

    .form-input-modern.error {
        border-color: var(--color-error-500, #ef4444) !important;
    }
</style>

<?php foreach ($groupDefs as $groupKey => $groupMeta): ?>
    <?php if (empty($fieldsByGroup[$groupKey])) continue; ?>

    <div class="modern-card mb-6">
        <div class="modern-card-header">
            <div class="flex items-center">
                <?php
                $bgColors = [
                    'blue'   => 'bg-blue-100',
                    'yellow' => 'bg-yellow-100',
                    'green'  => 'bg-green-100',
                    'purple' => 'bg-purple-100',
                    'red'    => 'bg-red-100',
                ];
                $textColors = [
                    'blue'   => 'text-blue-600',
                    'yellow' => 'text-yellow-600',
                    'green'  => 'text-green-600',
                    'purple' => 'text-purple-600',
                    'red'    => 'text-red-600',
                ];
                $bg = $bgColors[$groupMeta['iconBg'] ?? 'blue'] ?? 'bg-blue-100';
                $tc = $textColors[$groupMeta['iconBg'] ?? 'blue'] ?? 'text-blue-600';
                ?>
                <div class="w-8 h-8 <?= $bg ?> rounded-lg flex items-center justify-center mr-3">
                    <?= icon($groupMeta['icon'] ?? 'cog', $tc . ' text-sm') ?>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($groupMeta['label']) ?></h2>
                </div>
            </div>
        </div>

        <div class="modern-card-body">
            <div class="space-y-4">
                <?php foreach ($fieldsByGroup[$groupKey] as $field): ?>
                    <?php
                    $name  = $field['name'];
                    $type  = $field['type'];
                    $label = $field['label'] ?? $name;
                    $desc  = $field['description'] ?? '';
                    $icon  = $field['icon'] ?? '';
                    $val   = $data[$name] ?? ($field['default'] ?? '');

                    $dataAttrs = sprintf(
                        'data-field="%s" data-entity="%s" data-entity-id="%s" data-orchestra-id="%s" data-save-mode="auto" data-field-type="%s"',
                        htmlspecialchars($name),
                        htmlspecialchars($entity),
                        htmlspecialchars((string) $entityId),
                        htmlspecialchars((string) $orchestraId),
                        htmlspecialchars($type)
                    );

                    $isRequired = in_array('required', $field['validation'] ?? []);
                    ?>

                    <?php if ($type === 'toggle'): ?>
                        <?php
                        $checkboxName = $name;
                        $checkboxId = $name;
                        $checkboxLabel = $label;
                        $checkboxDescription = $desc;
                        $checked = (bool) $val;
                        $disabled = false;
                        $inline = false;
                        ?>
                        <div class="modern-checkbox-group">
                            <div class="flex items-start">
                                <input type="checkbox"
                                    id="<?= htmlspecialchars($checkboxId) ?>"
                                    name="<?= htmlspecialchars($checkboxName) ?>"
                                    class="modern-checkbox"
                                    value="1"
                                    <?= $checked ? 'checked' : '' ?>
                                    <?= $dataAttrs ?>>
                                <div class="ml-3 flex-1">
                                    <label for="<?= htmlspecialchars($checkboxId) ?>" class="modern-checkbox-label">
                                        <?= htmlspecialchars($checkboxLabel) ?>
                                    </label>
                                    <?php if ($checkboxDescription): ?>
                                        <p class="modern-checkbox-description"><?= htmlspecialchars($checkboxDescription) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($type === 'secret'): ?>
                        <div class="form-group-modern">
                            <label for="<?= htmlspecialchars($name) ?>" class="form-label-modern" style="display: flex; align-items: center;">
                                <?php if ($icon): ?>
                                    <?= icon($icon, 'form-label-icon') ?>
                                <?php endif; ?>
                                <?= htmlspecialchars($label) ?>
                                <i class="fas fa-eye settings-toggle-visibility"
                                    onclick="this.closest('.form-group-modern').querySelector('input').type = this.closest('.form-group-modern').querySelector('input').type === 'password' ? 'text' : 'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');"
                                    title="Anzeigen/Verbergen"
                                    style="margin-left: 8px; font-size: 14px; color: var(--color-text-secondary); cursor: pointer; transition: color var(--transition-base); padding: 2px;"></i>
                            </label>
                            <input type="password"
                                class="form-input-modern"
                                id="<?= htmlspecialchars($name) ?>"
                                name="<?= htmlspecialchars($name) ?>"
                                value="<?= htmlspecialchars((string) $val) ?>"
                                <?= $isRequired ? 'required' : '' ?>
                                <?= $dataAttrs ?>>
                            <?php if ($desc): ?>
                                <div class="form-help-text"><?= htmlspecialchars($desc) ?></div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($type === 'text'): ?>
                        <div class="form-group-modern">
                            <label for="<?= htmlspecialchars($name) ?>" class="form-label-modern">
                                <?php if ($icon): ?>
                                    <?= icon($icon, 'form-label-icon') ?>
                                <?php endif; ?>
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text"
                                class="form-input-modern"
                                id="<?= htmlspecialchars($name) ?>"
                                name="<?= htmlspecialchars($name) ?>"
                                value="<?= htmlspecialchars((string) $val) ?>"
                                <?= $isRequired ? 'required' : '' ?>
                                <?= $dataAttrs ?>>
                            <?php if ($desc): ?>
                                <div class="form-help-text"><?= htmlspecialchars($desc) ?></div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <!-- Custom / unsupported type: <?= htmlspecialchars($type) ?> — render as text -->
                        <div class="form-group-modern">
                            <label for="<?= htmlspecialchars($name) ?>" class="form-label-modern">
                                <?php if ($icon): ?>
                                    <?= icon($icon, 'form-label-icon') ?>
                                <?php endif; ?>
                                <?= htmlspecialchars($label) ?>
                            </label>
                            <input type="text"
                                class="form-input-modern"
                                id="<?= htmlspecialchars($name) ?>"
                                name="<?= htmlspecialchars($name) ?>"
                                value="<?= htmlspecialchars((string) $val) ?>"
                                <?= $isRequired ? 'required' : '' ?>
                                <?= $dataAttrs ?>>
                            <?php if ($desc): ?>
                                <div class="form-help-text"><?= htmlspecialchars($desc) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php endforeach; ?>

<!-- Save indicator -->
<div id="settingsSaveIndicator" class="settings-save-indicator">
    <span class="indicator-text"></span>
</div>

<script src="/assets/js/settings-engine.js"></script>