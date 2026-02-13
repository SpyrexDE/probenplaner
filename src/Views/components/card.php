<?php
/**
 * Card Component
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

<!-- 
  Available classes:
  - Base: card-base, card-base-xl, card-base-sm
  - Content: card-content-xs, card-content-sm, card-content-md, card-content-lg, card-content-xl
  - Sections: card-header, card-footer
  - Effects: hover-lift, hover-lift-sm, hover-lift-lg
  - Focus: focus-shadow-preserve, focus-shadow-preserve-xl
  - States: interactive, interactive-subtle
-->

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

// Use utilities.css classes
$baseClasses = "card-base";


$baseClasses .= " focus-shadow-preserve";


if ($hover) {
    if ($nested) {
        $baseClasses .= " hover-lift-sm"; // Subtle lift for nested cards
    } else {
        $baseClasses .= " hover-lift";    // Standard lift for main cards
    }
}


if ($interactive) {
    $baseClasses .= " interactive";
}


$contentPaddingClass = $padding ? "card-content-{$size}" : '';

// Build additional attributes
$attributes = '';
if (isset($onclick)) {
    $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
    $baseClasses .= ' interactive';
}
if (isset($id)) $attributes .= ' id="' . htmlspecialchars($id) . '"';
if (isset($class)) $baseClasses .= ' ' . htmlspecialchars($class);
?>

<div class="<?= $baseClasses ?>"<?= $attributes ?>>
    <?php if ($header): ?>
        <div class="card-header card-content-<?= $size ?>">
            <?= $header ?>
        </div>
    <?php endif; ?>
    
    <div class="<?= $contentPaddingClass ?>">
        <?= $content ?>
        
        <?php if (isset($children)): ?>
            <?= $children ?>
        <?php endif; ?>
    </div>
    
    <?php if ($footer): ?>
        <div class="card-footer card-content-<?= $size ?>">
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
