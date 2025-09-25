<?php
/**
 * Card Component - Tailwind utility classes + minimal custom styles
 * 
 * Usage examples:
 * <?php 
 * $content = 'Simple card content';
 * include __DIR__ . '/card.php'; 
 * ?>
 * 
 * <?php 
 * $header = 'Card Header'; $content = 'Card with header and footer'; $footer = 'Card Footer'; $hover = true;
 * include __DIR__ . '/card.php'; 
 * ?>
 */
?>

<style>
/* Only sophisticated hover effects that Tailwind can't handle */
.card-sophisticated-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
}

.card-sophisticated-focus:focus-within {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 2px 4px rgba(0, 0, 0, 0.06), 0 0 0 2px rgba(71, 140, 244, 0.2);
}

.card-interactive:hover {
    cursor: pointer;
}

/* Subtle shadow animations for nested cards */
.card-nested:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
    transform: translateY(-1px);
}
</style>

<?php
// Set defaults
$hover = $hover ?? false;
$padding = $padding ?? true;
$header = $header ?? null;
$footer = $footer ?? null;
$content = $content ?? '';
$size = $size ?? 'md';
$variant = $variant ?? 'default';
$interactive = $interactive ?? false;
$nested = $nested ?? false;

// Size classes for padding
$sizeClasses = [
    'xs' => 'p-3',
    'sm' => 'p-4', 
    'md' => 'p-6',
    'lg' => 'p-8',
    'xl' => 'p-10'
];

$headerFooterSizeClasses = [
    'xs' => 'px-3 py-2',
    'sm' => 'px-4 py-3',
    'md' => 'px-6 py-4', 
    'lg' => 'px-8 py-5',
    'xl' => 'px-10 py-6'
];

// Base Tailwind classes
$baseClasses = "rounded-lg border overflow-hidden shadow-sm transition-all duration-200";

// Add hover effect classes
if ($hover) {
    $baseClasses .= " card-sophisticated-hover";
}

if ($interactive) {
    $baseClasses .= " card-interactive";
}

if ($nested) {
    $baseClasses .= " card-nested";
} else {
    $baseClasses .= " card-sophisticated-focus";
}

// Colors using CSS variables for theme support
$baseStyle = "background-color: var(--color-bg-primary); border-color: var(--color-border);";

// Content padding class
$contentPaddingClass = $padding ? $sizeClasses[$size] : '';

// For cards with headers/footers, adjust content padding
if ($header || $footer) {
    $contentPaddingClass = $headerFooterSizeClasses[$size];
}

// Build additional attributes
$attributes = '';
if (isset($onclick)) {
    $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
    $baseClasses .= ' cursor-pointer';
}
if (isset($id)) $attributes .= ' id="' . htmlspecialchars($id) . '"';
if (isset($class)) $baseClasses .= ' ' . htmlspecialchars($class);
?>

<div class="<?= $baseClasses ?>" style="<?= $baseStyle ?>"<?= $attributes ?>>
    <?php if ($header): ?>
        <div class="<?= $headerFooterSizeClasses[$size] ?> border-b font-semibold" 
             style="border-color: var(--color-border); background-color: var(--color-bg-secondary); color: var(--color-text-primary);">
            <?= $header ?>
        </div>
    <?php endif; ?>
    
    <div class="<?= $contentPaddingClass ?>" style="color: var(--color-text-primary);">
        <?= $content ?>
        
        <?php if (isset($children)): ?>
            <?= $children ?>
        <?php endif; ?>
    </div>
    
    <?php if ($footer): ?>
        <div class="<?= $headerFooterSizeClasses[$size] ?> border-t" 
             style="border-color: var(--color-border); background-color: var(--color-bg-secondary); color: var(--color-text-secondary);">
            <?= $footer ?>
        </div>
    <?php endif; ?>
</div>

<?php
/**
 * Alternative usage with content blocks:
 * 
 * <?php ob_start(); ?>
 * <h3 class="text-lg font-semibold mb-4">Custom Content</h3>
 * <p>This is custom content inside the card.</p>
 * <div class="flex justify-end mt-4">
 *     <?php $type = 'primary'; $text = 'Action'; include __DIR__ . '/button.php'; ?>
 * </div>
 * <?php 
 * $content = ob_get_clean();
 * $header = 'Card Title';
 * $hover = true;
 * include __DIR__ . '/card.php'; 
 * ?>
 */
?>
