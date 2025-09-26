<?php
/**
 * Button Component - Tailwind utility classes + minimal custom styles for sophisticated effects
 * 
 * Usage examples:
 * <?php 
 * $type = 'primary'; $text = 'Save Changes'; 
 * include __DIR__ . '/button.php'; 
 * ?>
 * 
 * <?php 
 * $type = 'outline'; $size = 'sm'; $text = 'Cancel'; $onclick = 'closeModal()'; 
 * include __DIR__ . '/button.php'; 
 * ?>
 */
?>

<!-- 
  🎨 CONSOLIDATED BUTTON SYSTEM
  All button styles now use utilities.css for maximum maintainability
  
  Available classes:
  - Base: btn-base 
  - Sizes: btn-xs, btn-sm, btn-md, btn-lg
  - Variants: btn-primary, btn-secondary, btn-success, btn-danger, btn-outline, btn-ghost
  - States: disabled
-->

<?php
// Set defaults
$type = $type ?? 'secondary';
$size = $size ?? 'md';
$text = $text ?? 'Button';
$disabled = $disabled ?? false;
$htmlType = $htmlType ?? 'button';
$icon = $icon ?? false;
$iconOnly = $iconOnly ?? false;

// 🎨 CONSOLIDATED SYSTEM: Use utilities.css classes
$baseClasses = "btn-base";

// Size classes from utilities.css
$sizeClasses = [
    'xs' => 'btn-xs',
    'sm' => 'btn-sm', 
    'md' => 'btn-md',
    'lg' => 'btn-lg'
];

// Icon-only adjustments - use flex-center utility for perfect centering
if ($iconOnly) {
    $baseClasses .= " flex-center";
}

// Type classes from utilities.css - all sophisticated gradients included
$typeClasses = [
    'primary' => 'btn-primary',
    'secondary' => 'btn-secondary', 
    'success' => 'btn-success',
    'danger' => 'btn-danger',
    'outline' => 'btn-outline',
    'ghost' => 'btn-ghost'
];

// Disabled state from utilities.css
$disabledClasses = $disabled ? 'disabled' : '';

$allClasses = trim("$baseClasses {$sizeClasses[$size]} {$typeClasses[$type]} $disabledClasses");

// No inline styles needed - everything is in utilities.css
$style = '';

// Build attributes
$attributes = '';
if (isset($onclick)) $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
if (isset($id)) $attributes .= ' id="' . htmlspecialchars($id) . '"';
if (isset($class)) $attributes .= ' ' . htmlspecialchars($class);
if ($htmlType === 'submit') $attributes .= ' type="submit"';
if ($htmlType === 'button') $attributes .= ' type="button"';
if ($disabled) $attributes .= ' disabled';
?>

<button class="<?= $allClasses ?>"<?= $attributes ?>>
    <?php if ($icon && !$iconOnly): ?>
        <i class="<?= htmlspecialchars($icon) ?> mr-2"></i>
    <?php endif; ?>
    
    <?php if ($iconOnly && $icon): ?>
        <i class="<?= htmlspecialchars($icon) ?>"></i>
    <?php else: ?>
        <?= htmlspecialchars($text) ?>
    <?php endif; ?>
</button>
