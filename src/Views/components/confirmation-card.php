<?php
/**
 * Confirmation Card Component - Component-colocated styling
 * Modal-style confirmation dialogs for destructive actions
 * 
 * Usage:
 * <?php 
 * $title = 'Delete Rehearsal?';
 * $message = 'Are you sure you want to delete this rehearsal?';
 * $details = 'This action cannot be undone.';
 * $rehearsalInfo = ['date' => '2024-01-15', 'time' => '19:00', 'location' => 'Concert Hall'];
 * include __DIR__ . '/confirmation-card.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/confirmation-card.php'; 
 * ?>
 */
?>

<style>
/* CONFIRMATION CARD COMPONENT - All styles colocated */
.confirmation-card {
    background-color: var(--color-bg-primary);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--color-border);
    overflow: hidden;
    max-width: 500px;
    margin: 0 auto;
    animation: confirmation-appear 0.3s ease-out;
}

@keyframes confirmation-appear {
    0% {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.confirmation-header {
    background: linear-gradient(135deg, var(--color-error-50) 0%, #fef2f2 100%);
    padding: var(--space-6);
    text-align: center;
    border-bottom: 1px solid var(--color-border);
    position: relative;
}

.confirmation-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, var(--color-error-200) 50%, transparent 100%);
}

.confirmation-header i {
    font-size: var(--font-size-3xl);
    margin-bottom: var(--space-3);
    display: block;
    color: var(--color-error);
    animation: warning-pulse 2s infinite;
}

@keyframes warning-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.confirmation-header h3 {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-text-primary);
    margin: 0;
}

.confirmation-content {
    padding: var(--space-6);
}

.confirmation-message {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    margin-bottom: var(--space-3);
    text-align: center;
}

.confirmation-details {
    color: var(--color-text-secondary);
    margin-bottom: var(--space-6);
    line-height: 1.6;
    text-align: center;
}

.rehearsal-details {
    background-color: var(--color-bg-secondary);
    padding: var(--space-4);
    border-radius: var(--radius-base);
    border: 1px solid var(--color-border-light);
    margin-bottom: var(--space-4);
}

.rehearsal-details h4 {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    margin-bottom: var(--space-3);
    text-align: center;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-2) 0;
    border-bottom: 1px solid var(--color-border-light);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: var(--font-weight-medium);
    color: var(--color-text-secondary);
}

.detail-value {
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
}

.confirmation-form {
    padding: var(--space-6);
    border-top: 1px solid var(--color-border);
    background: linear-gradient(135deg, var(--color-bg-secondary) 0%, var(--color-bg-tertiary) 100%);
    display: flex;
    gap: var(--space-3);
    justify-content: center;
}

.confirmation-button {
    padding: var(--space-3) var(--space-6);
    border-radius: var(--radius-base);
    font-weight: var(--font-weight-semibold);
    font-size: var(--font-size-base);
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    border: 2px solid transparent;
    min-width: 120px;
    justify-content: center;
}

.confirmation-button.danger {
    background: linear-gradient(135deg, var(--color-error) 0%, var(--color-error-dark) 100%);
    color: var(--color-text-inverse);
    border-color: var(--color-error);
}

.confirmation-button.danger:hover {
    background: linear-gradient(135deg, var(--color-error-dark) 0%, #dc2626 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.confirmation-button.secondary {
    background-color: var(--color-bg-primary);
    color: var(--color-text-primary);
    border-color: var(--color-border);
}

.confirmation-button.secondary:hover {
    background-color: var(--color-bg-tertiary);
    border-color: var(--color-border-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

/* Overlay for modal usage */
.confirmation-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: var(--z-modal);
    padding: var(--space-4);
    animation: overlay-appear 0.3s ease-out;
}

@keyframes overlay-appear {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .confirmation-card {
        max-width: 90vw;
        margin: var(--space-4);
    }
    
    .confirmation-form {
        flex-direction: column;
    }
    
    .confirmation-button {
        width: 100%;
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
$title = $title ?? 'Aktion bestätigen';
$message = $message ?? 'Möchtest du fortfahren?';
$details = $details ?? '';
$icon = $icon ?? 'exclamation-triangle';
$rehearsalInfo = $rehearsalInfo ?? [];
$confirmText = $confirmText ?? 'Bestätigen';
$cancelText = $cancelText ?? 'Abbrechen';
$confirmAction = $confirmAction ?? '';
$cancelAction = $cancelAction ?? 'history.back()';
$showAsModal = $showAsModal ?? false;
?>

<?php if ($showAsModal): ?>
<div class="confirmation-overlay">
<?php endif; ?>

<div class="confirmation-card">
    <div class="confirmation-header">
        <i class="fas fa-<?= htmlspecialchars($icon) ?>"></i>
        <h3><?= htmlspecialchars($title) ?></h3>
    </div>
    
    <div class="confirmation-content">
        <div class="confirmation-message"><?= htmlspecialchars($message) ?></div>
        
        <?php if ($details): ?>
            <div class="confirmation-details"><?= htmlspecialchars($details) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($rehearsalInfo)): ?>
            <div class="rehearsal-details">
                <h4>Rehearsal Details</h4>
                <?php foreach ($rehearsalInfo as $label => $value): ?>
                    <div class="detail-item">
                        <span class="detail-label"><?= htmlspecialchars(ucfirst($label)) ?>:</span>
                        <span class="detail-value"><?= htmlspecialchars($value) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="confirmation-form">
        <?php if ($confirmAction): ?>
            <button onclick="<?= htmlspecialchars($confirmAction) ?>" class="confirmation-button danger">
                <i class="fas fa-trash"></i>
                <?= htmlspecialchars($confirmText) ?>
            </button>
        <?php endif; ?>
        
        <button onclick="<?= htmlspecialchars($cancelAction) ?>" class="confirmation-button secondary">
            <i class="fas fa-times"></i>
            <?= htmlspecialchars($cancelText) ?>
        </button>
    </div>
</div>

<?php if ($showAsModal): ?>
</div>
<?php endif; ?>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic confirmation -->
 * <?php 
 * $title = 'Delete Item?';
 * $message = 'This action cannot be undone';
 * $confirmAction = 'deleteItem(123)';
 * include __DIR__ . '/confirmation-card.php'; 
 * ?>
 * 
 * <!-- Rehearsal deletion with details -->
 * <?php 
 * $title = 'Delete Rehearsal?';
 * $message = 'Are you sure you want to delete this rehearsal?';
 * $details = 'This will remove all attendance data.';
 * $rehearsalInfo = [
 *     'date' => '2024-01-15',
 *     'time' => '19:00 - 21:00',
 *     'location' => 'Concert Hall'
 * ];
 * $confirmAction = 'deleteRehearsal(456)';
 * $showAsModal = true;
 * include __DIR__ . '/confirmation-card.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/confirmation-card.php'; 
 * ?>
 */
?>
