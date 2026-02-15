<?php

/**
 * Modern Checkbox Component
 * Used for settings and configuration options
 * 
 * Usage:
 * <?php 
 * $name = 'feature_enabled';
 * $label = 'Enable Feature';
 * $description = 'This enables the new feature for all users';
 * $checked = true;
 * include __DIR__ . '/modern-checkbox.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/modern-checkbox.php'; 
 * ?>
 */
?>

<style>
    /* MODERN CHECKBOX COMPONENT */
    .modern-checkbox-group,
    .modern-checkbox-container {
        background: var(--color-gray-50);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-4);
        transition: all var(--transition-base);
        margin-bottom: var(--space-4);
        position: relative;
        overflow: hidden;
    }

    .modern-checkbox-group::before,
    .modern-checkbox-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(71, 140, 244, 0.05), transparent);
        transition: left 0.6s;
    }

    .modern-checkbox-group:hover,
    .modern-checkbox-container:hover {
        background: var(--color-gray-100);
        border-color: var(--color-primary-200);
        box-shadow: var(--shadow-sm);
    }

    .modern-checkbox-group:hover::before,
    .modern-checkbox-container:hover::before {
        left: 100%;
    }

    .modern-checkbox {
        width: 20px;
        height: 20px;
        accent-color: var(--color-primary);
        border-radius: var(--radius-sm);
        border: 2px solid var(--color-border);
        margin-top: 2px;
        cursor: pointer;
        transition: all var(--transition-base);
        flex-shrink: 0;
    }

    .modern-checkbox:hover {
        border-color: var(--color-primary-200);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1);
        transform: scale(1.05);
    }

    .modern-checkbox:checked {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1);
        animation: modern-checkbox-check 0.3s ease-out;
    }

    @keyframes modern-checkbox-check {
        0% {
            transform: scale(0.8);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .modern-checkbox-label {
        font-size: var(--font-size-base);
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
        cursor: pointer;
        line-height: var(--line-height-tight);
        margin-bottom: var(--space-1);
        display: block;
        transition: color var(--transition-base);
    }

    .modern-checkbox:checked+.ml-3 .modern-checkbox-label {
        color: var(--color-primary);
    }

    .modern-checkbox-description {
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
        line-height: var(--line-height-relaxed);
        margin: 0;
        transition: color var(--transition-base);
    }

    .modern-checkbox-group:hover .modern-checkbox-description,
    .modern-checkbox-container:hover .modern-checkbox-description {
        color: var(--color-text-primary);
    }

    /* Alternative layout for inline checkboxes */
    .modern-checkbox-inline {
        display: flex;
        align-items: flex-start;
        gap: var(--space-3);
        background: transparent;
        border: none;
        padding: var(--space-2);
    }

    .modern-checkbox-inline .modern-checkbox-label {
        margin-bottom: 0;
    }

    /* Disabled state */
    .modern-checkbox:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .modern-checkbox:disabled+.ml-3 .modern-checkbox-label {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .modern-checkbox:disabled+.ml-3 .modern-checkbox-description {
        opacity: 0.5;
    }

    .modern-checkbox-group:has(.modern-checkbox:disabled),
    .modern-checkbox-container:has(.modern-checkbox:disabled) {
        opacity: 0.7;
        background: var(--color-gray-25);
    }

    .modern-checkbox-group:has(.modern-checkbox:disabled):hover,
    .modern-checkbox-container:has(.modern-checkbox:disabled):hover {
        background: var(--color-gray-25);
        border-color: var(--color-border);
        box-shadow: none;
    }
</style>

<?php
// Check if this is styles-only mode
$renderComponent = $renderComponent ?? true;

if (!$renderComponent) {
    // Styles-only mode: just load the styles and exit
    return;
}

// Set defaults for component rendering
$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$description = $description ?? '';
$checked = $checked ?? false;
$disabled = $disabled ?? false;
$value = $value ?? '1';
$inline = $inline ?? false;

// Build classes
$groupClasses = ['modern-checkbox-group'];
if ($inline) $groupClasses[] = 'modern-checkbox-inline';

$groupClassString = implode(' ', $groupClasses);

// Build attributes
$attributes = '';
if ($disabled) $attributes .= ' disabled';
if ($checked) $attributes .= ' checked';
?>

<div class="<?= $groupClassString ?>">
    <div class="flex items-start">
        <input type="checkbox"
            id="<?= htmlspecialchars($id) ?>"
            name="<?= htmlspecialchars($name) ?>"
            class="modern-checkbox"
            value="<?= htmlspecialchars($value) ?>"
            <?= $attributes ?>>

        <div class="ml-3 flex-1">
            <?php if ($label): ?>
                <label for="<?= htmlspecialchars($id) ?>" class="modern-checkbox-label">
                    <?= htmlspecialchars($label) ?>
                </label>
            <?php endif; ?>

            <?php if ($description): ?>
                <p class="modern-checkbox-description">
                    <?= htmlspecialchars($description) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic modern checkbox -->
 * <?php 
 * $name = 'notifications';
 * $label = 'Enable Notifications';
 * $description = 'Receive email notifications for new rehearsals';
 * $checked = true;
 * include __DIR__ . '/modern-checkbox.php'; 
 * ?>
 * 
 * <!-- Disabled checkbox -->
 * <?php 
 * $name = 'premium_feature';
 * $label = 'Premium Feature';
 * $description = 'This feature requires a premium subscription';
 * $disabled = true;
 * include __DIR__ . '/modern-checkbox.php'; 
 * ?>
 * 
 * <!-- Inline variant -->
 * <?php 
 * $name = 'terms';
 * $label = 'I agree to the terms';
 * $inline = true;
 * include __DIR__ . '/modern-checkbox.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/modern-checkbox.php'; 
 * ?>
 */
?>