<?php
/**
 * User Badge Component
 * Small badges to display user role indicators
 * 
 * Usage:
 * <?php 
 * $icon = 'music';
 * $title = 'Conductor';
 * include __DIR__ . '/user-badge.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/user-badge.php'; 
 * ?>
 */
?>

<style>
/* USER BADGE */
.user-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 18px;
    border-radius: var(--radius-base);
    background: linear-gradient(30deg, var(--color-bg-secondary), var(--color-primary-200));
    margin-left: var(--space-1);
    font-size: var(--font-size-xs);
    color: var(--color-primary);
    box-shadow: var(--shadow-sm);
    filter: saturate(0.8);
    vertical-align: text-bottom;
    flex-shrink: 0;
    transition: all var(--transition-base);
    position: relative;
    overflow: hidden;
}

.user-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer-badge 2s infinite;
}

@keyframes shimmer-badge {
    0%, 100% { left: -100%; }
    50% { left: 100%; }
}

.user-badge:hover {
    filter: saturate(1.2);
    transform: scale(1.1);
    box-shadow: var(--shadow-md);
}

.user-badge i {
    line-height: 1;
    background: linear-gradient(30deg, var(--color-primary-200), var(--color-primary));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
    z-index: 1;
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
$icon = $icon ?? 'user';
$title = $title ?? '';
$class = $class ?? '';

// Build attributes
$attributes = '';
if ($title) {
    $attributes .= ' title="' . htmlspecialchars($title) . '"';
}
if ($class) {
    $attributes .= ' class="user-badge ' . htmlspecialchars($class) . '"';
} else {
    $attributes .= ' class="user-badge"';
}
?>

<span<?= $attributes ?>>
    <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
</span>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic badge -->
 * <?php 
 * $icon = 'music';
 * $title = 'Conductor';
 * include __DIR__ . '/user-badge.php'; 
 * ?>
 * 
 * <!-- Custom class badge -->
 * <?php 
 * $icon = 'star';
 * $title = 'Leader';
 * $class = 'leader-badge';
 * include __DIR__ . '/user-badge.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/user-badge.php'; 
 * ?>
 */
?>
