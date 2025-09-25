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

<style>
/* Only sophisticated effects that Tailwind can't handle */
.btn-primary-gradient {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    box-shadow: 0 4px 14px rgba(71, 140, 244, 0.35);
}
.btn-primary-gradient:hover {
    background: linear-gradient(135deg, var(--color-primary-dark) 0%, #2563eb 100%);
    box-shadow: 0 6px 20px rgba(71, 140, 244, 0.45);
}

.btn-secondary-gradient {
    background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-secondary-dark) 100%);
    box-shadow: 0 4px 14px rgba(244, 71, 107, 0.35);
}
.btn-secondary-gradient:hover {
    background: linear-gradient(135deg, var(--color-secondary-dark) 0%, #dc2626 100%);
    box-shadow: 0 6px 20px rgba(244, 71, 107, 0.45);
}

.btn-success-gradient {
    background: linear-gradient(135deg, var(--color-success) 0%, var(--color-success-dark) 100%);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}
.btn-success-gradient:hover {
    background: linear-gradient(135deg, var(--color-success-dark) 0%, #047857 100%);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
}

.btn-danger-gradient {
    background: linear-gradient(135deg, var(--color-error) 0%, var(--color-error-dark) 100%);
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
}
.btn-danger-gradient:hover {
    background: linear-gradient(135deg, var(--color-error-dark) 0%, #b91c1c 100%);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.45);
}

.btn-base-sophisticated:hover {
    background: linear-gradient(135deg, var(--color-gray-50) 0%, var(--color-white) 100%);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.btn-outline-sophisticated:hover {
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    box-shadow: 0 4px 14px rgba(71, 140, 244, 0.35);
}
</style>

<?php
// Set defaults
$type = $type ?? 'secondary';
$size = $size ?? 'md';
$text = $text ?? 'Button';
$disabled = $disabled ?? false;
$htmlType = $htmlType ?? 'button';
$icon = $icon ?? false;
$iconOnly = $iconOnly ?? false;

// Tailwind base classes for all buttons
$baseClasses = "inline-flex items-center justify-content font-semibold transition-all duration-200 select-none whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-offset-2";

// Size classes using Tailwind
$sizeClasses = [
    'xs' => 'px-2.5 py-1.5 text-xs h-7 rounded',
    'sm' => 'px-3 py-2 text-sm h-9 rounded',
    'md' => 'px-6 py-3 text-base h-12 rounded-md', 
    'lg' => 'px-8 py-4 text-lg h-14 rounded-lg',
    'xl' => 'px-10 py-5 text-xl h-16 rounded-xl'
];

// Icon-only button sizes
if ($iconOnly) {
    $sizeClasses = [
        'xs' => 'p-1.5 text-xs w-7 h-7 rounded',
        'sm' => 'p-2 text-sm w-9 h-9 rounded',
        'md' => 'p-3 text-base w-12 h-12 rounded-md',
        'lg' => 'p-4 text-lg w-14 h-14 rounded-lg',
        'xl' => 'p-5 text-xl w-16 h-16 rounded-xl'
    ];
}

// Type classes - mostly Tailwind with custom gradients for sophisticated effects
$typeClasses = [
    'primary' => 'btn-primary-gradient text-white border-none hover:-translate-y-0.5 focus:ring-blue-500',
    'secondary' => 'btn-secondary-gradient text-white border-none hover:-translate-y-0.5 focus:ring-pink-500',
    'success' => 'btn-success-gradient text-white border-none hover:-translate-y-0.5 focus:ring-green-500', 
    'danger' => 'btn-danger-gradient text-white border-none hover:-translate-y-0.5 focus:ring-red-500',
    'outline' => 'btn-outline-sophisticated border-2 hover:text-white hover:-translate-y-0.5 focus:ring-blue-500',
    'ghost' => 'hover:bg-gray-100 focus:ring-gray-500',
    'link' => 'text-blue-600 hover:text-blue-800 underline-offset-4 hover:underline focus:ring-blue-500'
];

// Set specific colors for outline and ghost based on theme variables
$outlineStyle = "color: var(--color-primary); border-color: var(--color-primary);";
$ghostStyle = "color: var(--color-text-primary);";

// Base button style using CSS variables for theme support
$baseStyle = "background: linear-gradient(135deg, var(--color-bg-primary) 0%, var(--color-gray-50) 100%); color: var(--color-text-primary); border: 1px solid var(--color-border); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);";

$disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';

$allClasses = trim("$baseClasses {$sizeClasses[$size]} {$typeClasses[$type]} $disabledClasses");

// Build style attribute
$style = '';
if ($type === 'outline') {
    $style = $outlineStyle;
} elseif ($type === 'ghost') {
    $style = $ghostStyle;
} elseif ($type === 'link') {
    $style = '';
} elseif (!in_array($type, ['primary', 'secondary', 'success', 'danger'])) {
    $style = $baseStyle . ' btn-base-sophisticated';
    $allClasses .= ' btn-base-sophisticated';
}

// Build attributes
$attributes = '';
if (isset($onclick)) $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
if (isset($id)) $attributes .= ' id="' . htmlspecialchars($id) . '"';
if (isset($class)) $attributes .= ' ' . htmlspecialchars($class);
if ($htmlType === 'submit') $attributes .= ' type="submit"';
if ($htmlType === 'button') $attributes .= ' type="button"';
if ($disabled) $attributes .= ' disabled';
?>

<button class="<?= $allClasses ?>" <?= $style ? 'style="' . $style . '"' : '' ?><?= $attributes ?>>
    <?php if ($icon && !$iconOnly): ?>
        <i class="<?= htmlspecialchars($icon) ?> mr-2"></i>
    <?php endif; ?>
    
    <?php if ($iconOnly && $icon): ?>
        <i class="<?= htmlspecialchars($icon) ?>"></i>
    <?php else: ?>
        <?= htmlspecialchars($text) ?>
    <?php endif; ?>
</button>
