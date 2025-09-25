<?php
/**
 * Page Header Component - Component-colocated styling
 * Simple page header with title and subtitle
 * 
 * Usage:
 * <?php 
 * $title = 'Page Title';
 * $subtitle = 'Optional subtitle';
 * include __DIR__ . '/page-header.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/page-header.php'; 
 * ?>
 */
?>

<style>
/* PAGE HEADER COMPONENT - All styles colocated */
.page-header {
  text-align: center;
  margin-bottom: var(--space-8);
  padding: var(--space-6) 0;
}

.page-subtitle {
  font-size: var(--font-size-lg);
  color: var(--color-text-secondary);
  font-weight: var(--font-weight-medium);
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
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$class = $class ?? '';

// Build classes
$classes = ['page-header'];
if ($class) $classes[] = $class;

$classString = implode(' ', $classes);
?>

<div class="<?= $classString ?>">
    <?php if ($title): ?>
        <h1><?= htmlspecialchars($title) ?></h1>
    <?php endif; ?>
    
    <?php if ($subtitle): ?>
        <p class="page-subtitle"><?= htmlspecialchars($subtitle) ?></p>
    <?php endif; ?>
</div>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic header -->
 * <?php 
 * $title = 'Welcome';
 * $subtitle = 'Get started with your rehearsals';
 * include __DIR__ . '/page-header.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/page-header.php'; 
 * ?>
 */
?>
