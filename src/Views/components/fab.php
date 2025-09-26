<?php
/**
 * Floating Action Button (FAB) Component
 * Extracted from probenplan/index.php where it was hardcoded
 * 
 * Usage:
 * <?php 
 * $icon = 'print';
 * $onclick = 'window.print()';
 * $title = 'Print';
 * include __DIR__ . '/fab.php'; 
 * ?>
 */
?>

<style>
/* FAB Component - Uses existing CSS from components.css plus additional styles */
.fab {
    /* Position and basic styling */
    position: fixed;
    bottom: var(--space-5);
    right: var(--space-5);
    width: 56px;
    height: 56px;
    background-color: var(--color-primary);
    color: var(--color-text-inverse);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-base);
    z-index: 1000;
    border: none;
    cursor: pointer;
}

.fab:hover {
    background-color: var(--color-primary-dark);
    transform: scale(1.1);
    box-shadow: var(--shadow-xl);
}

.fab i {
    font-size: var(--font-size-xl);
}

/* Print hiding */
@media print {
    .fab {
        display: none !important;
    }
}

/* Mobile responsive */
@media (max-width: 768px) {
    .fab {
        bottom: var(--space-4);
        right: var(--space-4);
        width: 52px;
        height: 52px;
    }
    
    .fab i {
        font-size: var(--font-size-lg);
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
$icon = $icon ?? 'plus';
$onclick = $onclick ?? '';
$href = $href ?? '#';
$title = $title ?? '';
$id = $id ?? '';
$variant = $variant ?? 'primary';

// Build attributes
$attributes = '';
if ($title) {
    $attributes .= ' title="' . htmlspecialchars($title) . '"';
}
if ($id) {
    $attributes .= ' id="' . htmlspecialchars($id) . '"';
}

// Determine if we should render as button or link
$isButton = !empty($onclick);
?>

<?php if ($isButton): ?>
    <button class="fab" onclick="<?= htmlspecialchars($onclick) ?>"<?= $attributes ?>>
        <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
    </button>
<?php else: ?>
    <a href="<?= htmlspecialchars($href) ?>" class="fab"<?= $attributes ?>>
        <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
    </a>
<?php endif; ?>
