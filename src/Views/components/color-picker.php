<?php
/**
 * Color Picker Component
 * 
 * Usage:
 * <?php 
 * $label = 'Choose Color';
 * $name = 'color';
 * $selectedColor = '#3b82f6';
 * $colors = ['#ffffff', '#3b82f6', '#10b981', '#f59e0b'];
 * include __DIR__ . '/color-picker.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/color-picker.php'; 
 * ?>
 */
?>

<style>
/* COLOR PICKER COMPONENT */
.compact-color-picker {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    align-items: flex-start;
    margin-bottom: var(--space-4);
}

.compact-color-picker-label {
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    font-size: var(--font-size-sm);
    margin-bottom: var(--space-2);
}

.compact-color-picker-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: var(--space-2);
    width: 264px;
    padding: var(--space-2);
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

@media (max-width: 640px) {
    .compact-color-picker-grid {
        width: 240px;
        gap: var(--space-1);
        grid-template-columns: repeat(5, 1fr);
    }
    
    .compact-color-option {
        width: 32px !important;
        height: 32px !important;
    }
}

.compact-color-option {
    width: 36px;
    height: 36px;
    border: 2px solid transparent;
    border-radius: var(--radius-base);
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
    outline: none;
    background-clip: content-box;
    background: none !important;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Predefined color classes */
.compact-color-option.color-ffffff { background-color: #ffffff !important; border: 2px solid var(--color-border); }
.compact-color-option.color-3b82f6 { background-color: #3b82f6 !important; }
.compact-color-option.color-10b981 { background-color: #10b981 !important; }
.compact-color-option.color-f59e0b { background-color: #f59e0b !important; }
.compact-color-option.color-ef4444 { background-color: #ef4444 !important; }
.compact-color-option.color-8b5cf6 { background-color: #8b5cf6 !important; }
.compact-color-option.color-f97316 { background-color: #f97316 !important; }
.compact-color-option.color-ec4899 { background-color: #ec4899 !important; }
.compact-color-option.color-14b8a6 { background-color: #14b8a6 !important; }
.compact-color-option.color-6366f1 { background-color: #6366f1 !important; }
.compact-color-option.color-6b7280 { background-color: #6b7280 !important; }
.compact-color-option.color-475569 { background-color: #475569 !important; }

.compact-color-option:hover {
    border-color: var(--color-border-dark);
    transform: translateY(-1px) scale(1.05);
    box-shadow: var(--shadow-md);
    z-index: 10;
    position: relative;
}

.compact-color-option:focus {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

.compact-color-option.selected {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
    transform: scale(1.1);
    z-index: 5;
}

.compact-color-option.selected::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: var(--font-weight-bold);
    font-size: 14px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.5));
    animation: checkmark-appear 0.2s ease-out;
}

@keyframes checkmark-appear {
    0% { 
        transform: translate(-50%, -50%) scale(0);
        opacity: 0;
    }
    50% { 
        transform: translate(-50%, -50%) scale(1.3);
        opacity: 1;
    }
    100% { 
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

/* White color special handling for visibility */
.compact-color-option.color-ffffff.selected::after {
    color: var(--color-text-primary);
    text-shadow: none;
    filter: none;
}

/* === DROPDOWN COLOR PICKER === */
.color-picker {
    position: relative;
}

.color-picker-btn {
    width: 100%;
    text-align: left;
    position: relative;
    padding: var(--space-3) var(--space-4);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-base);
    background-color: var(--color-bg-primary);
    color: var(--color-text-primary);
    cursor: pointer;
    transition: all var(--transition-base);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.color-picker-btn:hover {
    border-color: var(--color-primary-200);
    box-shadow: var(--shadow-sm);
}

.color-options {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: var(--color-bg-primary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-base);
    box-shadow: var(--shadow-lg);
    z-index: 10;
    display: none;
    max-height: 200px;
    overflow-y: auto;
    animation: dropdown-appear 0.2s ease-out;
}

@keyframes dropdown-appear {
    0% { 
        opacity: 0;
        transform: translateY(-10px);
    }
    100% { 
        opacity: 1;
        transform: translateY(0);
    }
}

.color-picker:hover .color-options,
.color-picker.open .color-options {
    display: block;
}

.color-option {
    display: block;
    padding: var(--space-3);
    border-bottom: 1px solid var(--color-border-light);
    transition: background-color var(--transition-base);
    cursor: pointer;
    color: var(--color-text-primary);
    text-decoration: none;
}

.color-option:last-child {
    border-bottom: none;
}

.color-option:hover {
    background-color: var(--color-bg-secondary);
    color: var(--color-primary);
}

.color-option.selected {
    background-color: var(--color-primary-100);
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
}
</style>

<?php
// Styles-only mode check
$renderComponent = $renderComponent ?? true;

if (!$renderComponent) {
    // Styles-only mode: just load the styles and exit
    return;
}

// Component defaults
$label = $label ?? 'Farbe wählen';
$name = $name ?? 'color';
$selectedColor = $selectedColor ?? '';
$colors = $colors ?? [
    '#ffffff', '#3b82f6', '#10b981', '#f59e0b', 
    '#ef4444', '#8b5cf6', '#f97316', '#ec4899', 
    '#14b8a6', '#6366f1', '#6b7280', '#475569'
];

// Convert hex to CSS class
function getColorClass($hex) {
    return 'color-' . ltrim($hex, '#');
}
?>

<div class="compact-color-picker">
    <?php if ($label): ?>
        <label class="compact-color-picker-label"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    
    <div class="compact-color-picker-grid">
        <?php foreach ($colors as $color): ?>
            <?php 
            $colorClass = getColorClass($color);
            $isSelected = ($selectedColor === $color);
            
            $classes = ['compact-color-option', $colorClass];
            if ($isSelected) $classes[] = 'selected';
            $classString = implode(' ', $classes);
            ?>
            
            <button type="button" 
                    class="<?= $classString ?>"
                    data-color="<?= htmlspecialchars($color) ?>"
                    title="<?= htmlspecialchars($color) ?>"
                    onclick="selectColor('<?= htmlspecialchars($name) ?>', '<?= htmlspecialchars($color) ?>')">
            </button>
        <?php endforeach; ?>
    </div>
    
    <input type="hidden" name="<?= htmlspecialchars($name) ?>" id="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($selectedColor) ?>">
</div>

<script>
function selectColor(fieldName, color) {
    // Update hidden input
    const hiddenInput = document.getElementById(fieldName);
    if (hiddenInput) {
        hiddenInput.value = color;
    }
    
    // Update visual selection
    const colorPicker = hiddenInput.closest('.compact-color-picker');
    const allOptions = colorPicker.querySelectorAll('.compact-color-option');
    
    allOptions.forEach(option => {
        option.classList.remove('selected');
    });
    
    const selectedOption = colorPicker.querySelector(`[data-color="${color}"]`);
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }
    
    // Trigger change event
    hiddenInput.dispatchEvent(new Event('change'));
}
</script>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic color picker -->
 * <?php 
 * $label = 'Rehearsal Color';
 * $name = 'rehearsal_color';
 * $selectedColor = '#3b82f6';
 * include __DIR__ . '/color-picker.php'; 
 * ?>
 * 
 * <!-- Custom colors -->
 * <?php 
 * $label = 'Theme Color';
 * $name = 'theme_color';
 * $colors = ['#ff0000', '#00ff00', '#0000ff'];
 * $selectedColor = '#ff0000';
 * include __DIR__ . '/color-picker.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/color-picker.php'; 
 * ?>
 */
?>
