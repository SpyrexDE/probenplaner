<?php
/**
 * Datetime Range Picker Component
 * 
 * Renders two datetime-local inputs for start and end time.
 * Includes validation to ensure end time is after start time.
 * 
 * Variables:
 * - $start_name: Name attribute for start input (default: 'start')
 * - $end_name: Name attribute for end input (default: 'end')
 * - $start_value: Value for start input (Y-m-d\TH:i)
 * - $end_value: Value for end input (Y-m-d\TH:i)
 * - $label_start: Label for start input (default: 'Startzeit')
 * - $label_end: Label for end input (default: 'Endzeit')
 * - $required: Whether inputs are required (default: true)
 */

$startName = $start_name ?? 'start';
$endName = $end_name ?? 'end';
$startValue = $start_value ?? '';
$endValue = $end_value ?? '';
$labelStart = $label_start ?? 'Anfang';
$labelEnd = $label_end ?? 'Ende';
$isRequired = $required ?? true;
$requiredAttr = $isRequired ? 'required' : '';

// Ensure values are in correct format for datetime-local (Y-m-d\TH:i)
// If they come from MySQL (Y-m-d H:i:s), replace space with T and strip seconds if needed
function formatForInput($val) {
    if (empty($val)) return '';
    $val = str_replace(' ', 'T', $val);
    // Remove seconds if present
    if (strlen($val) > 16) {
        $val = substr($val, 0, 16);
    }
    return $val;
}

$startValue = formatForInput($startValue);
$endValue = formatForInput($endValue);
?>

<style>
/* Datetime Range Picker Styles */
.datetime-range-container {
    display: flex;
    align-items: flex-end;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.datetime-input-group {
    flex: 1;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.datetime-input-group label {
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    font-size: var(--font-size-base);
}

.datetime-range-separator {
    display: flex;
    align-items: center;
    padding-bottom: 0.8rem; /* Align with input text roughly */
    color: var(--color-text-muted);
    font-weight: 500;
}

/* Modern input styling matching form-input-modern */
.datetime-input {
    width: 100%;
    padding: var(--space-3) var(--space-4);
    font-size: var(--font-size-base);
    line-height: var(--line-height-normal);
    color: var(--color-text-primary);
    background: var(--color-bg-primary);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-base);
    transition: all var(--transition-base);
}

.datetime-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px var(--color-primary-100);
}

.datetime-error-message {
    color: var(--color-error);
    font-size: var(--font-size-sm);
    margin-top: var(--space-2);
    display: none;
}

@media (max-width: 600px) {
    .datetime-range-container {
        flex-direction: column;
        align-items: stretch;
        gap: var(--space-2);
    }
    
    .datetime-range-separator {
        padding-bottom: 0;
        justify-content: center;
        margin: -0.5rem 0;
        font-size: 0.9rem;
    }
}
</style>

<div class="datetime-range-picker">
    <div class="datetime-range-container">
        <div class="datetime-input-group">
            <label for="<?= $startName ?>"><?= $labelStart ?><?= $isRequired ? ' <span style="color: var(--color-error);">*</span>' : '' ?></label>
            <input 
                type="datetime-local" 
                id="<?= $startName ?>" 
                name="<?= $startName ?>" 
                class="datetime-input" 
                value="<?= htmlspecialchars($startValue) ?>" 
                <?= $requiredAttr ?>
            >
        </div>
        
        <div class="datetime-range-separator">
            <span>bis</span>
        </div>
        
        <div class="datetime-input-group">
            <label for="<?= $endName ?>"><?= $labelEnd ?><?= $isRequired ? ' <span style="color: var(--color-error);">*</span>' : '' ?></label>
            <input 
                type="datetime-local" 
                id="<?= $endName ?>" 
                name="<?= $endName ?>" 
                class="datetime-input" 
                value="<?= htmlspecialchars($endValue) ?>" 
                <?= $requiredAttr ?>
            >
        </div>
    </div>
    <div class="datetime-error-message" id="<?= $startName ?>-error">
        <i class="fas fa-exclamation-circle" style="margin-right: 0.25rem;"></i> Die Endzeit muss nach der Startzeit liegen.
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const startInput = document.getElementById('<?= $startName ?>');
        const endInput = document.getElementById('<?= $endName ?>');
        const errorMsg = document.getElementById('<?= $startName ?>-error');
        
        function validateRange() {
            if (startInput.value && endInput.value) {
                if (startInput.value >= endInput.value) {
                    endInput.setCustomValidity('Die Endzeit muss nach der Startzeit liegen.');
                    errorMsg.style.display = 'block';
                    endInput.style.borderColor = 'var(--color-error)';
                } else {
                    endInput.setCustomValidity('');
                    errorMsg.style.display = 'none';
                    endInput.style.borderColor = '';
                }
            } else {
                endInput.setCustomValidity('');
                errorMsg.style.display = 'none';
                endInput.style.borderColor = '';
            }
        }
        
        startInput.addEventListener('input', function() {
            if (!endInput.value && startInput.value) {
                endInput.value = startInput.value;
            }
            validateRange();
        });
        startInput.addEventListener('change', function() {
            if (!endInput.value && startInput.value) {
                endInput.value = startInput.value;
            }
            validateRange();
        });
        endInput.addEventListener('change', validateRange);

        validateRange();
    });
    </script>
</div>
