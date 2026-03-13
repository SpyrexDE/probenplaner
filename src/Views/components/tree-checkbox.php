<?php
/**
 * Tree Checkbox Component
 * Used for orchestra/group selection with nested levels
 * 
 * Usage:
 * <?php 
 * $items = [
 *     ['name' => 'strings', 'label' => 'Strings', 'level' => 0, 'checked' => false],
 *     ['name' => 'violin', 'label' => 'Violin', 'level' => 1, 'checked' => true]
 * ];
 * include __DIR__ . '/tree-checkbox.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/tree-checkbox.php'; 
 * ?>
 */
?>

<style>
/* TREE CHECKBOX COMPONENT */
.checkbox-group {
    margin: 0; /* Remove margins to prevent gaps in tree lines */
    position: relative;
}

.checkbox-item {
    position: relative;
    display: flex;
    align-items: center;
    margin: 0; /* Remove margins to prevent gaps */
    padding: 6px 8px;
    border-radius: var(--radius-base);
    transition: all var(--transition-base);
    cursor: pointer;
    user-select: none;
    line-height: 1.4;
    background-color: transparent;
}

@media (hover: hover) {
    .checkbox-item:hover {
        background-color: var(--color-bg-tertiary);
        transform: translateX(2px);
        box-shadow: var(--shadow-sm);
    }

    .checkbox-item:active {
        transform: translateX(1px);
    }
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: var(--space-3);
    accent-color: var(--color-primary);
    cursor: pointer;
    pointer-events: none; /* Prevent direct checkbox clicks */
    transition: all var(--transition-base);
}

.checkbox-item label {
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    cursor: pointer;
    user-select: none;
    flex: 1;
    padding: var(--space-1) 0;
    pointer-events: none; /* Prevent direct label clicks */
    transition: color var(--transition-base);
}

/* Handle disabled state */
.checkbox-item input[type="checkbox"]:disabled + label {
    color: var(--color-text-muted);
    cursor: not-allowed;
}

.checkbox-item input[type="checkbox"]:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.checkbox-item:has(input[type="checkbox"]:disabled) {
    cursor: not-allowed;
    opacity: 0.8;
}

@media (hover: hover) {
    .checkbox-item:has(input[type="checkbox"]:disabled):hover {
        background-color: transparent;
        transform: none;
        box-shadow: none;
    }
}

/* Tree indentation with proper vertical lines */
.checkbox-item.level-1 {
    padding-left: 48px;
}

.checkbox-item.level-2 {
    padding-left: 87px;
}

.checkbox-item.level-3 {
    padding-left: 127px;
}

.checkbox-item.level-4 {
    padding-left: 160px;
}

.checkbox-item.level-5 {
    padding-left: 200px;
}

/* Continuous vertical lines on each indentation level */
.checkbox-item.level-1::before {
    content: '';
    position: absolute;
    left: 16px;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: var(--color-border);
    z-index: 0;
    pointer-events: none;
    transition: transform var(--transition-base);
}

.checkbox-item.level-2::before {
    content: '';
    position: absolute;
    left: 16px;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: var(--color-border);
    z-index: 0;
    pointer-events: none;
    box-shadow: 40px 0 0 0 var(--color-border); /* Second line at level 2 position */
    transition: transform var(--transition-base);
}

.checkbox-item.level-3::before {
    content: '';
    position: absolute;
    left: 16px;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: var(--color-border);
    z-index: 0;
    pointer-events: none;
    box-shadow: 40px 0 0 0 var(--color-border), 80px 0 0 0 var(--color-border); /* All parent level lines */
    transition: transform var(--transition-base);
}

.checkbox-item.level-4::before {
    content: '';
    position: absolute;
    left: 16px;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: var(--color-border);
    z-index: 0;
    pointer-events: none;
    box-shadow: 40px 0 0 0 var(--color-border), 80px 0 0 0 var(--color-border), 120px 0 0 0 var(--color-border);
    transition: transform var(--transition-base);
}

.checkbox-item.level-5::before {
    content: '';
    position: absolute;
    left: 16px;
    top: 0;
    bottom: 0;
    width: 1px;
    background-color: var(--color-border);
    z-index: 0;
    pointer-events: none;
    box-shadow: 40px 0 0 0 var(--color-border), 80px 0 0 0 var(--color-border), 120px 0 0 0 var(--color-border), 160px 0 0 0 var(--color-border);
    transition: transform var(--transition-base);
}

@media (hover: hover) {
    /* Keep lines stationary during hover animation */
    .checkbox-item:hover::before {
        transform: translateX(-2px);
    }

    .checkbox-item:active::before {
        transform: translateX(-1px);
    }
}

/* Indeterminate state styling */
.checkbox-item input[type="checkbox"]:indeterminate {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
}

/* Enhanced checked state */
.checkbox-item input[type="checkbox"]:checked + label {
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
}

@media (hover: hover) {
    .checkbox-item:hover label {
        color: var(--color-text-primary);
    }
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
$items = $items ?? [];
$groupClass = $groupClass ?? '';

$groupClasses = ['checkbox-group'];
if ($groupClass) $groupClasses[] = $groupClass;
$groupClassString = implode(' ', $groupClasses);
?>

<div class="<?= $groupClassString ?>">
    <?php foreach ($items as $item): ?>
        <?php
        $itemClasses = ['checkbox-item'];
        if (isset($item['level']) && $item['level'] > 0) {
            $itemClasses[] = 'level-' . $item['level'];
        }
        
        $itemClassString = implode(' ', $itemClasses);
        
        $checked = $item['checked'] ?? false;
        $disabled = $item['disabled'] ?? false;
        $indeterminate = $item['indeterminate'] ?? false;
        
        $checkboxAttrs = '';
        if ($checked) $checkboxAttrs .= ' checked';
        if ($disabled) $checkboxAttrs .= ' disabled';
        if ($indeterminate) $checkboxAttrs .= ' data-indeterminate="true"';
        ?>
        
        <div class="<?= $itemClassString ?>" onclick="toggleCheckbox('<?= htmlspecialchars($item['name']) ?>')">
            <input type="checkbox" 
                   id="<?= htmlspecialchars($item['name']) ?>" 
                   name="<?= htmlspecialchars($item['name']) ?>" 
                   value="<?= htmlspecialchars($item['value'] ?? $item['name']) ?>"
                   <?= $checkboxAttrs ?>>
            <label for="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['label']) ?></label>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleCheckbox(name) {
    const checkbox = document.getElementById(name);
    if (checkbox && !checkbox.disabled) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change'));
    }
}
</script>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Orchestra group selection tree -->
 * <?php 
 * $items = [
 *     ['name' => 'strings', 'label' => 'Strings', 'level' => 0, 'checked' => false],
 *     ['name' => 'violin1', 'label' => 'Violin I', 'level' => 1, 'checked' => true],
 *     ['name' => 'violin2', 'label' => 'Violin II', 'level' => 1, 'checked' => false],
 *     ['name' => 'woodwinds', 'label' => 'Woodwinds', 'level' => 0, 'checked' => false],
 *     ['name' => 'flute', 'label' => 'Flute', 'level' => 1, 'checked' => true]
 * ];
 * include __DIR__ . '/tree-checkbox.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/tree-checkbox.php'; 
 * ?>
 */
?>
