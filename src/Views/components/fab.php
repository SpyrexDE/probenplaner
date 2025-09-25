<?php
/**
 * Floating Action Button (FAB) Component - Component-colocated styling
 * Sophisticated FAB with animations and hover effects
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
/* FLOATING ACTION BUTTON COMPONENT - All styles colocated */
.fab {
    position: fixed;
    bottom: var(--space-5);
    right: var(--space-5);
    width: 60px;
    height: 60px;
    border-radius: var(--radius-full);
    background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
    color: var(--color-white);
    border: none;
    box-shadow: var(--shadow-lg);
    cursor: pointer;
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    justify-content: center;
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
    background: linear-gradient(135deg, var(--color-primary-dark) 0%, #1e40af 100%);
    box-shadow: var(--shadow-xl);
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

/* FAB variants */
.fab-secondary {
    background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-secondary-dark) 100%);
}

.fab-secondary:hover {
    background: linear-gradient(135deg, var(--color-secondary-dark) 0%, #dc2626 100%);
}

.fab-success {
    background: linear-gradient(135deg, var(--color-success) 0%, var(--color-success-dark) 100%);
}

.fab-success:hover {
    background: linear-gradient(135deg, var(--color-success-dark) 0%, #047857 100%);
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

// Build FAB classes
$fabClasses = ['fab'];
if ($variant !== 'primary') {
    $fabClasses[] = 'fab-' . $variant;
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
