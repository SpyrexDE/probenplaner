<?php
/**
 * Theme Selector Component - Component-colocated styling
 * Comprehensive theme selection interface with full and compact variants
 * 
 * Usage:
 * <?php 
 * $themes = [
 *     ['id' => 'default', 'name' => 'Default', 'description' => 'Clean and modern', 'colors' => ['#3b82f6', '#10b981']],
 *     ['id' => 'dark', 'name' => 'Dark', 'description' => 'Easy on the eyes', 'colors' => ['#374151', '#6366f1']]
 * ];
 * $selectedTheme = 'default';
 * $compact = false;
 * include __DIR__ . '/theme-selector.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/theme-selector.php'; 
 * ?>
 */
?>

<style>
/* THEME SELECTOR COMPONENT - All styles colocated */

/* === FULL THEME SELECTION COMPONENTS === */

/* Theme selection grid layout */
.theme-selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--space-4);
    margin-top: var(--space-4);
}

/* Individual theme option container */
.theme-option {
    position: relative;
}

/* Theme radio input (hidden) */
.theme-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Theme card styling */
.theme-card {
    display: block;
    position: relative;
    /* border: 2px solid var(--color-border); → form-border-2 utility */
    border-radius: var(--radius-lg);
    /* background: var(--color-bg-primary); → card-bg utility */
    padding: var(--space-5);
    cursor: pointer;
    /* transition: all var(--transition-base); → form-transition utility */
    /* overflow: hidden; → card-overflow utility */
    box-shadow: var(--shadow-sm);
    animation: theme-card-enter 0.3s ease-out;
}

@keyframes theme-card-enter {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.theme-card:hover {
    border-color: var(--color-primary-200);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

/* Keyboard focus ring for accessibility */
.theme-radio:focus-visible + .theme-card {
    outline: 3px solid var(--color-primary-300);
    outline-offset: 3px;
}

/* When theme is selected */
.theme-radio:checked + .theme-card {
    border-color: var(--color-primary);
    box-shadow: 0 4px 20px rgba(71, 140, 244, 0.25);
    background: linear-gradient(135deg, var(--color-bg-primary) 0%, var(--color-primary-50) 100%);
}

/* Theme preview container */
.theme-preview {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

/* Color preview row */
.theme-colors {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
}

.theme-color {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-base);
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all var(--transition-base);
}

.theme-color:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Theme information */
.theme-info {
    flex: 1;
}

.theme-name {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
    margin: 0 0 var(--space-2) 0;
    line-height: var(--line-height-tight);
}

.theme-description {
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
    margin: 0;
    line-height: var(--line-height-normal);
}

/* Selected indicator */
.theme-selected-indicator {
    position: absolute;
    top: var(--space-3);
    right: var(--space-3);
    width: 28px;
    height: 28px;
    background: var(--color-primary);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.8);
    transition: all var(--transition-base);
    box-shadow: var(--shadow-lg);
    color: var(--color-white);
}

.theme-radio:checked + .theme-card .theme-selected-indicator {
    opacity: 1;
    transform: scale(1);
    animation: theme-check-bounce 0.4s ease-out;
}

@keyframes theme-check-bounce {
    0% {
        transform: scale(0.8);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* === COMPACT THEME SELECTION COMPONENTS === */

/* Compact theme selection layout */
.theme-selection-compact {
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;
    align-items: center;
}

/* Compact theme option container */
.theme-option-compact {
    position: relative;
}

/* Compact theme radio input (hidden) */
.theme-radio-compact {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

/* Compact theme selector */
.theme-selector-compact {
    display: block;
    position: relative;
    padding: var(--space-3) var(--space-4);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-lg);
    background: var(--color-bg-primary);
    cursor: pointer;
    transition: all var(--transition-base);
    min-width: 120px;
    text-align: center;
    box-shadow: var(--shadow-sm);
}

.theme-selector-compact:hover {
    border-color: var(--color-primary-200);
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
}

/* Improve focus visibility and smoothness */
.theme-selector-compact:focus { outline: none; }
.theme-radio-compact:focus-visible + .theme-selector-compact,
.theme-selector-compact:focus-visible {
    outline: 3px solid var(--color-primary-300);
    outline-offset: 2px;
}

/* When theme is selected */
.theme-radio-compact:checked + .theme-selector-compact {
    border-color: var(--color-primary);
    background: linear-gradient(135deg, var(--color-bg-primary) 0%, var(--color-primary-50) 100%);
    box-shadow: 0 4px 16px rgba(71, 140, 244, 0.2);
}

/* Compact theme preview */
.theme-preview-compact {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
}

/* Compact color dots */
.theme-colors-compact {
    display: flex;
    gap: var(--space-1);
    justify-content: center;
}

.theme-dot {
    width: 12px;
    height: 12px;
    border-radius: var(--radius-full);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all var(--transition-base);
}

/* Compact theme name */
.theme-name-compact {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-text-primary);
    line-height: var(--line-height-tight);
}

/* Compact theme check indicator */
.theme-check-compact {
    position: absolute;
    top: var(--space-1);
    right: var(--space-1);
    width: 18px;
    height: 18px;
    background: var(--color-primary);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.8);
    transition: all var(--transition-base);
    box-shadow: var(--shadow-md);
    color: var(--color-white);
}

.theme-radio-compact:checked + .theme-selector-compact .theme-check-compact {
    opacity: 1;
    transform: scale(1);
}

/* === RESPONSIVE DESIGN === */
@media (max-width: 640px) {
    .theme-selection-grid {
        grid-template-columns: 1fr;
        gap: var(--space-3);
    }
    
    .theme-card {
        padding: var(--space-4);
    }
    
    .theme-colors {
        justify-content: center;
    }
    
    .theme-color {
        width: 28px;
        height: 28px;
    }
}

/* Theme preview in mobile view */
@media (max-width: 480px) {
    .theme-preview {
        text-align: center;
    }
    
    .theme-name {
        font-size: var(--font-size-base);
    }
    
    .theme-description {
        font-size: var(--font-size-xs);
    }
    
    .theme-selection-compact {
        justify-content: center;
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
$themes = $themes ?? [
    ['id' => 'default', 'name' => 'Standard', 'description' => 'Klassisches Design mit modernen Elementen', 'colors' => ['#3b82f6', '#10b981', '#f59e0b']],
    ['id' => 'jeunesse', 'name' => 'Jeunesse', 'description' => 'Jugendliches Design mit lebendigen Farben', 'colors' => ['#ec4899', '#8b5cf6', '#f97316']]
];
$selectedTheme = $selectedTheme ?? 'default';
$compact = $compact ?? false;
$name = $name ?? 'theme_selection';
?>

<?php if ($compact): ?>
    <!-- Compact Theme Selection -->
    <div class="theme-selection-compact">
        <?php foreach ($themes as $theme): ?>
            <div class="theme-option-compact">
                <input type="radio" 
                       id="theme-<?= htmlspecialchars($theme['id']) ?>" 
                       name="<?= htmlspecialchars($name) ?>" 
                       value="<?= htmlspecialchars($theme['id']) ?>" 
                       class="theme-radio-compact"
                       <?= ($selectedTheme === $theme['id']) ? 'checked' : '' ?>>
                
                <label for="theme-<?= htmlspecialchars($theme['id']) ?>" class="theme-selector-compact form-border-2 card-bg form-transition">
                    <div class="theme-preview-compact">
                        <div class="theme-colors-compact">
                            <?php foreach (array_slice($theme['colors'], 0, 3) as $color): ?>
                                <div class="theme-dot" style="background-color: <?= htmlspecialchars($color) ?>;"></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="theme-name-compact"><?= htmlspecialchars($theme['name']) ?></div>
                    </div>
                    <div class="theme-check-compact">
                        <i class="fas fa-check" style="font-size: 10px;"></i>
                    </div>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Full Theme Selection -->
    <div class="theme-selection-grid">
        <?php foreach ($themes as $theme): ?>
            <div class="theme-option">
                <input type="radio" 
                       id="theme-<?= htmlspecialchars($theme['id']) ?>" 
                       name="<?= htmlspecialchars($name) ?>" 
                       value="<?= htmlspecialchars($theme['id']) ?>" 
                       class="theme-radio"
                       <?= ($selectedTheme === $theme['id']) ? 'checked' : '' ?>>
                
                <label for="theme-<?= htmlspecialchars($theme['id']) ?>" class="theme-card form-border-2 card-bg form-transition card-overflow">
                    <div class="theme-preview">
                        <div class="theme-colors">
                            <?php foreach ($theme['colors'] as $color): ?>
                                <div class="theme-color" style="background-color: <?= htmlspecialchars($color) ?>;"></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="theme-info">
                            <h3 class="theme-name"><?= htmlspecialchars($theme['name']) ?></h3>
                            <p class="theme-description"><?= htmlspecialchars($theme['description']) ?></p>
                        </div>
                    </div>
                    <div class="theme-selected-indicator">
                        <i class="fas fa-check" style="font-size: 14px;"></i>
                    </div>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Full theme selector -->
 * <?php 
 * $themes = [
 *     ['id' => 'light', 'name' => 'Light Theme', 'description' => 'Bright and clean', 'colors' => ['#ffffff', '#3b82f6']],
 *     ['id' => 'dark', 'name' => 'Dark Theme', 'description' => 'Easy on the eyes', 'colors' => ['#1f2937', '#6366f1']]
 * ];
 * $selectedTheme = 'light';
 * include __DIR__ . '/theme-selector.php'; 
 * ?>
 * 
 * <!-- Compact theme selector -->
 * <?php 
 * $themes = [
 *     ['id' => 'blue', 'name' => 'Blue', 'colors' => ['#3b82f6', '#1d4ed8']],
 *     ['id' => 'green', 'name' => 'Green', 'colors' => ['#10b981', '#047857']]
 * ];
 * $compact = true;
 * $selectedTheme = 'blue';
 * include __DIR__ . '/theme-selector.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/theme-selector.php'; 
 * ?>
 */
?>
