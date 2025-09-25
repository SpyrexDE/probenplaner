<?php
/**
 * Save Indicator Component - Component-colocated styling
 * Fixed position indicator to show save status to users
 * 
 * Usage:
 * <?php 
 * $message = 'Saving...';
 * $show = true;
 * include __DIR__ . '/save-indicator.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/save-indicator.php'; 
 * ?>
 */
?>

<style>
/* SAVE INDICATOR COMPONENT - All styles colocated */
.save-indicator {
    position: fixed;
    bottom: var(--space-5);
    right: var(--space-5);
    background-color: var(--color-gray-800);
    color: var(--color-white);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-full);
    z-index: var(--z-toast);
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--font-size-sm);
    box-shadow: var(--shadow-lg);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.6s ease-out, visibility 0.6s ease-out;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.save-indicator.show {
    opacity: 1;
    visibility: visible;
    animation: bounce-in 0.5s ease-out;
}

@keyframes bounce-in {
    0% { 
        transform: translateY(100px) scale(0.8);
        opacity: 0;
    }
    60% { 
        transform: translateY(-10px) scale(1.05);
        opacity: 1;
    }
    100% { 
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.save-indicator i {
    animation: spin 1s linear infinite;
}

.save-indicator.success {
    background-color: var(--color-success);
}

.save-indicator.error {
    background-color: var(--color-error);
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .save-indicator {
        bottom: var(--space-4);
        right: var(--space-4);
        font-size: var(--font-size-xs);
        padding: var(--space-2) var(--space-3);
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
$message = $message ?? 'Saving...';
$show = $show ?? false;
$type = $type ?? 'default'; // default, success, error
$icon = $icon ?? 'spinner';

// Build classes
$classes = ['save-indicator'];
if ($show) $classes[] = 'show';
if ($type !== 'default') $classes[] = $type;

$classString = implode(' ', $classes);
?>

<div id="save-indicator" class="<?= $classString ?>">
    <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
    <span><?= htmlspecialchars($message) ?></span>
</div>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic saving indicator -->
 * <?php 
 * $message = 'Saving...';
 * $show = true;
 * include __DIR__ . '/save-indicator.php'; 
 * ?>
 * 
 * <!-- Success indicator -->
 * <?php 
 * $message = 'Saved successfully!';
 * $show = true;
 * $type = 'success';
 * $icon = 'check';
 * include __DIR__ . '/save-indicator.php'; 
 * ?>
 * 
 * <!-- Error indicator -->
 * <?php 
 * $message = 'Save failed!';
 * $show = true;
 * $type = 'error';
 * $icon = 'exclamation-triangle';
 * include __DIR__ . '/save-indicator.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/save-indicator.php'; 
 * ?>
 */
?>
