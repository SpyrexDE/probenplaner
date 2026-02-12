<?php
/**
 * Checkbox Component - Component-colocated styling
 * Custom styled checkbox with sophisticated hover and focus effects
 * 
 * Usage:
 * <?php 
 * $name = 'terms';
 * $label = 'I agree to the terms';
 * $checked = false;
 * include __DIR__ . '/checkbox.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/checkbox.php'; 
 * ?>
 */
?>

<style>
/* CHECKBOX COMPONENT - All styles colocated */
.custom-checkbox {
    display: flex;
    align-items: center;
    margin-bottom: var(--space-2);
    cursor: pointer;
    user-select: none;
    gap: var(--space-2);
    transition: all var(--transition-base);
}

.custom-checkbox:hover {
    transform: translateX(2px);
}

.custom-checkbox input[type="checkbox"] {
    position: relative;
    appearance: none;
    width: 20px;
    height: 20px;
    border: 2px solid var(--color-border);
    border-radius: var(--radius-base);
    background-color: var(--color-bg-primary);
    cursor: pointer;
    transition: all var(--transition-base);
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.custom-checkbox input[type="checkbox"]:hover {
    border-color: var(--color-primary-200);
    box-shadow: 0 2px 8px rgba(71, 140, 244, 0.15);
    transform: translateY(-1px);
}

.custom-checkbox input[type="checkbox"]:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 2px 8px rgba(71, 140, 244, 0.25);
}

.custom-checkbox input[type="checkbox"]:checked {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-white);
    animation: checkbox-bounce 0.3s ease-out;
}

@keyframes checkbox-bounce {
    0% { transform: scale(0.8); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.custom-checkbox input[type="checkbox"]:checked::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    width: 8px;
    height: 4px;
    border-left: 2.5px solid var(--color-white);
    border-bottom: 2.5px solid var(--color-white);
    margin-top: -2.5px;
    animation: checkmark-draw 0.2s ease-out 0.1s both;
}

@keyframes checkmark-draw {
    0% { 
        width: 0;
        height: 0;
    }
    50% { 
        width: 8px;
        height: 0;
    }
    100% { 
        width: 8px;
        height: 4px;
    }
}

.custom-checkbox input[type="checkbox"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: var(--color-bg-disabled);
    border-color: var(--color-border-disabled);
}

.custom-checkbox input[type="checkbox"]:disabled:hover {
    border-color: var(--color-border-disabled);
    box-shadow: none;
    transform: none;
}

.custom-checkbox label {
    cursor: pointer;
    color: var(--color-text);
    font-weight: var(--font-weight-medium);
    line-height: var(--line-height-base);
    margin: 0;
    transition: color var(--transition-base);
    flex-grow: 1;
}

.custom-checkbox:hover label {
    color: var(--color-text-primary);
}

.custom-checkbox input[type="checkbox"]:disabled + label {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Variants */
.custom-checkbox.inline {
    display: inline-flex;
    margin-right: var(--space-4);
    margin-bottom: 0;
}

.custom-checkbox.large input[type="checkbox"] {
    width: 24px;
    height: 24px;
}

.custom-checkbox.large input[type="checkbox"]:checked::before {
    width: 10px;
    height: 5px;
    border-width: 3px;
    margin-top: -3px;
}

.custom-checkbox.small input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.custom-checkbox.small input[type="checkbox"]:checked::before {
    width: 6px;
    height: 3px;
    border-width: 2px;
    margin-top: -2px;
}
</style>

<?php
// Styles-only mode check
$renderComponent = $renderComponent ?? true;

if (!$renderComponent) {
    // Styles-only mode: just load the styles and exit
    return;
}

// Component defaults
$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$checked = $checked ?? false;
$disabled = $disabled ?? false;
$value = $value ?? '1';
$size = $size ?? 'normal'; // small, normal, large
$inline = $inline ?? false;
$class = $class ?? '';

$classes = ['custom-checkbox'];
if ($size !== 'normal') $classes[] = $size;
if ($inline) $classes[] = 'inline';
if ($class) $classes[] = $class;

$classString = implode(' ', $classes);

$attributes = '';
if ($disabled) $attributes .= ' disabled';
if ($checked) $attributes .= ' checked';
?>

<div class="<?= $classString ?>">
    <input type="checkbox" 
           id="<?= htmlspecialchars($id) ?>" 
           name="<?= htmlspecialchars($name) ?>" 
           value="<?= htmlspecialchars($value) ?>"
           <?= $attributes ?>>
    <?php if ($label): ?>
        <label for="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
</div>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic checkbox -->
 * <?php 
 * $name = 'terms';
 * $label = 'I agree to the terms';
 * $checked = false;
 * include __DIR__ . '/checkbox.php'; 
 * ?>
 * 
 * <!-- Large inline checkbox -->
 * <?php 
 * $name = 'newsletter';
 * $label = 'Subscribe to newsletter';
 * $size = 'large';
 * $inline = true;
 * include __DIR__ . '/checkbox.php'; 
 * ?>
 * 
 * <!-- Disabled checkbox -->
 * <?php 
 * $name = 'disabled';
 * $label = 'This is disabled';
 * $disabled = true;
 * include __DIR__ . '/checkbox.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/checkbox.php'; 
 * ?>
 */
?>
