<?php
/**
 * Floating Action Button (FAB) Component - CONSOLIDATED VERSION
 * Now uses utilities.css for common patterns + component-specific animations
 * 
 * Base classes: Uses btn-base btn-primary rounded-full flex-center shadow-lg transition
 * Custom: Unique FAB positioning, animations, and overlay effects
 * 
 * Usage:
 * <?php 
 * $href = '/rehearsals/create';
 * $icon = 'plus';
 * $title = 'Add Rehearsal';
 * include __DIR__ . '/fab.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/fab.php'; 
 * ?>
 */
?>

<style>
/* FLOATING ACTION BUTTON - Uses utilities + unique FAB behavior */
.fab {
    /* 🎨 CONSOLIDATED: Use utilities.css classes instead of repeating common patterns */
    /* Base button structure from utilities: btn-base btn-primary */
    /* Layout from utilities: flex-center */
    /* Styling from utilities: rounded-full shadow-lg transition */
    
    /* ✨ UNIQUE FAB-SPECIFIC: Position, size, z-index, overflow */
    position: fixed;
    bottom: var(--space-5);
    right: var(--space-5);
    width: 60px;
    height: 60px;
    z-index: var(--z-fixed);
    text-decoration: none;
    overflow: hidden;
}

.fab::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.3) 100%);
    opacity: 0;
    transition: opacity var(--transition-base);
}

.fab:hover {
    /* 🎨 CONSOLIDATED: Hover gradient uses utilities (btn-primary:hover) */
    /* ✨ UNIQUE FAB-SPECIFIC: Custom transform animation */
    transform: scale(1.05) rotate(-5deg);
}

.fab:hover::before {
    opacity: 1;
}

.fab:active {
    transform: scale(0.95) rotate(0deg);
    box-shadow: var(--shadow-lg);
}

.fab i {
    font-size: 24px;
    position: relative;
    z-index: 1;
    transition: transform var(--transition-base);
}

.fab:hover i {
    transform: rotate(90deg);
}

/* FAB variants - Use consolidated utilities */
.fab-secondary {
    /* 🎨 CONSOLIDATED: Use btn-secondary from utilities.css */
}

.fab-success {
    /* 🎨 CONSOLIDATED: Use btn-success from utilities.css */
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .fab {
        bottom: var(--space-4);
        right: var(--space-4);
        width: 56px;
        height: 56px;
    }
    
    .fab i {
        font-size: 20px;
    }
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
$href = $href ?? '#';
$icon = $icon ?? 'plus';
$title = $title ?? '';
$variant = $variant ?? 'primary'; // primary, secondary, success
$onclick = $onclick ?? '';

// Build FAB classes - 🎨 CONSOLIDATED: Use utilities + component-specific
$fabClasses = [
    'fab',                    // Component-specific positioning and animations
    'btn-base',              // Base button structure from utilities
    'flex-center',           // Centering layout from utilities  
    'rounded-full',          // Full border radius from utilities
    'shadow-lg',             // Large shadow from utilities
    'transition',            // Smooth transitions from utilities
];

// Add variant styling from utilities
if ($variant === 'secondary') {
    $fabClasses[] = 'btn-secondary';
} elseif ($variant === 'success') {
    $fabClasses[] = 'btn-success';
} else {
    $fabClasses[] = 'btn-primary';  // Default primary variant
}

$fabClassString = implode(' ', $fabClasses);

// Build attributes
$attributes = '';
if ($title) {
    $attributes .= ' title="' . htmlspecialchars($title) . '"';
}
if ($onclick) {
    $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
}
?>

<?php if ($onclick): ?>
    <button class="<?= $fabClassString ?>"<?= $attributes ?>>
        <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
    </button>
<?php else: ?>
    <a href="<?= htmlspecialchars($href) ?>" class="<?= $fabClassString ?>"<?= $attributes ?>>
        <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
    </a>
<?php endif; ?>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic add button -->
 * <?php 
 * $href = '/rehearsals/create';
 * $icon = 'plus';
 * $title = 'Add New Rehearsal';
 * include __DIR__ . '/fab.php'; 
 * ?>
 * 
 * <!-- Success variant with click handler -->
 * <?php 
 * $icon = 'check';
 * $title = 'Save Changes';
 * $variant = 'success';
 * $onclick = 'saveForm()';
 * include __DIR__ . '/fab.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/fab.php'; 
 * ?>
 */
?>
