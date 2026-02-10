<?php
/**
 * Promise Button Component - Component-colocated styling
 * Attendance buttons for rehearsal promises (check/cross buttons)
 * 
 * Usage:
 * <?php 
 * $type = 'check'; // check, cross
 * $selected = true;
 * $onclick = 'handleAttendance(true)';
 * include __DIR__ . '/promise-button.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/promise-button.php'; 
 * ?>
 */
?>

<style>
/* PROMISE BUTTON COMPONENT - All styles colocated */

/* Base promise button styles */
.promise-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
    padding: var(--space-2) var(--space-3);
    /* border: 2px solid var(--color-border); → form-border-2 utility */
    border-radius: var(--radius-base);
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-medium);
    cursor: pointer;
    /* transition: all var(--transition-base); → form-transition utility */
    position: relative;
    /* overflow: hidden; → card-overflow utility */
    /* background-color: var(--color-bg-primary); → card-bg utility */
}

.promise-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s;
}

.promise-btn:hover:not(.deselected) {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.promise-btn:hover:not(.deselected)::before {
    left: 100%;
}

.promise-btn:active:not(.deselected) {
    transform: translateY(0);
}

/* Check button (attending) */
.checkBtn {
    color: var(--color-success);
    background-color: var(--color-bg-primary);
}

.checkBtn i {
    color: var(--color-success-icon);
    transition: all var(--transition-base);
}

.checkBtn:not(.deselected) {
    border-color: var(--color-success);
    background: linear-gradient(135deg, var(--color-success-50) 0%, var(--color-success-100) 100%);
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
    opacity: 1;
    animation: pulse-green 1.5s infinite;
}

@keyframes pulse-green {
    0%, 100% { box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25); }
    50% { box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35); }
}

.checkBtn:not(.deselected):hover {
    background: linear-gradient(135deg, var(--color-success-100) 0%, var(--color-success-200) 100%);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
}

.checkBtn.deselected {
    border-color: var(--color-border);
    background-color: var(--color-bg-tertiary);
    box-shadow: none;
    opacity: 0.4;
    color: var(--color-text-muted);
    filter: grayscale(100%);
}

.checkBtn.deselected i {
    color: var(--color-text-muted);
}

.checkBtn.deselected:hover {
    opacity: 0.6;
    filter: grayscale(50%);
}

/* Cross button (not attending) */
.crossBtn {
    color: var(--color-error);
    background-color: var(--color-bg-primary);
}

.crossBtn i {
    color: var(--color-error-icon);
    transition: all var(--transition-base);
}

.crossBtn:not(.deselected) {
    border-color: var(--color-error);
    background: linear-gradient(135deg, var(--color-error-50) 0%, var(--color-error-100) 100%);
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
    opacity: 1;
    animation: pulse-red 1.5s infinite;
}

@keyframes pulse-red {
    0%, 100% { box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25); }
    50% { box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35); }
}

.crossBtn:not(.deselected):hover {
    background: linear-gradient(135deg, var(--color-error-100) 0%, var(--color-error-200) 100%);
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 6px 18px rgba(239, 68, 68, 0.35);
}

.crossBtn.deselected {
    border-color: var(--color-border);
    background-color: var(--color-bg-tertiary);
    box-shadow: none;
    opacity: 0.4;
    color: var(--color-text-muted);
    filter: grayscale(100%);
}

.crossBtn.deselected i {
    color: var(--color-text-muted);
}

.crossBtn.deselected:hover {
    opacity: 0.6;
    filter: grayscale(50%);
}

/* Loading state */
.promise-btn.loading {
    pointer-events: none;
}

.promise-btn.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .promise-btn {
        min-width: 40px;
        height: 40px;
        font-size: var(--font-size-base);
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
$type = $type ?? 'check'; // check, cross
$selected = $selected ?? false;
$loading = $loading ?? false;
$onclick = $onclick ?? '';
$title = $title ?? '';

// Determine classes and icon  
$classes = ['btn-base', 'promise-btn', 'card-bg', 'form-border-2', 'form-transition', 'card-overflow'];
$icon = '';

if ($type === 'check') {
    $classes[] = 'checkBtn';
    $icon = 'check';
    $title = $title ?: 'Als teilnehmend markieren';
} else {
    $classes[] = 'crossBtn';
    $icon = 'times';
    $title = $title ?: 'Als nicht teilnehmend markieren';
}

if (!$selected) {
    $classes[] = 'deselected';
}

if ($loading) {
    $classes[] = 'loading';
    $icon = 'spinner';
}

$classString = implode(' ', $classes);

// Build attributes
$attributes = '';
if ($title) {
    $attributes .= ' title="' . htmlspecialchars($title) . '"';
}
if ($onclick) {
    $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
}
?>

<button class="<?= $classString ?>"<?= $attributes ?>>
    <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
</button>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Attending button (selected) -->
 * <?php 
 * $type = 'check';
 * $selected = true;
 * $onclick = 'markAttending(123)';
 * include __DIR__ . '/promise-button.php'; 
 * ?>
 * 
 * <!-- Not attending button (deselected) -->
 * <?php 
 * $type = 'cross';
 * $selected = false;
 * $onclick = 'markNotAttending(123)';
 * include __DIR__ . '/promise-button.php'; 
 * ?>
 * 
 * <!-- Loading state -->
 * <?php 
 * $type = 'check';
 * $loading = true;
 * include __DIR__ . '/promise-button.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/promise-button.php'; 
 * ?>
 */
?>
