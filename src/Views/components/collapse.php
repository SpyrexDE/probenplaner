<?php
/**
 * Collapse Component - Tailwind utility classes + minimal custom styles  
 * Classes required for collapse.js: .collapse
 * 
 * Usage examples:
 * <?php 
 * $triggerText = 'Show Details';
 * $content = '<p>This content can be collapsed.</p>';
 * include __DIR__ . '/collapse.php'; 
 * ?>
 * 
 * <?php 
 * $triggerText = 'Advanced Options';
 * $triggerClass = 'btn-outline';
 * $content = '<div class="space-y-4">Advanced form fields...</div>';
 * $defaultOpen = true;
 * include __DIR__ . '/collapse.php'; 
 * ?>
 */
?>

<style>
/* Required for collapse.js */
.collapse {
    display: none;
}

.collapse.show {
    display: block;
}

.collapsing {
    position: relative;
    height: 0;
    overflow: hidden;
    transition: height var(--transition-base);
}

/* Smooth icon rotation */
.collapse-trigger-icon {
    transition: transform var(--transition-base);
}

.collapse-trigger[aria-expanded="true"] .collapse-trigger-icon {
    transform: rotate(180deg);
}

/* Border and styling */
.collapse-container {
    border-color: var(--color-border);
}

.collapse-trigger:hover {
    background-color: var(--color-bg-secondary);
}

.collapse-content {
    border-color: var(--color-border);
}
</style>

<?php
// Set defaults
$triggerText = $triggerText ?? 'Ein-/Ausblenden';
$triggerIcon = $triggerIcon ?? 'fas fa-chevron-down';
$triggerClass = $triggerClass ?? '';
$content = $content ?? '';
$defaultOpen = $defaultOpen ?? false;
$id = $id ?? 'collapse-' . uniqid();
$variant = $variant ?? 'default'; // default, card, minimal
$size = $size ?? 'md';

// Size classes
$sizeClasses = [
    'sm' => 'p-3',
    'md' => 'p-4',
    'lg' => 'p-6'
];

// Trigger classes based on variant
$triggerBaseClasses = "w-full flex items-center justify-between text-left transition-colors duration-200 focus:outline-none";

if ($variant === 'card') {
    $triggerClasses = $triggerBaseClasses . " " . $sizeClasses[$size] . " border rounded-t-md collapse-container card-bg";
} elseif ($variant === 'minimal') {
    $triggerClasses = $triggerBaseClasses . " py-2 border-b";
} else {
    $triggerClasses = $triggerBaseClasses . " " . $sizeClasses[$size] . " border rounded-md collapse-container card-bg";
}

$triggerClasses .= " " . $triggerClass;

// Content classes based on variant  
if ($variant === 'card') {
    $contentClasses = "collapse-content border border-t-0 rounded-b-md card-bg " . $sizeClasses[$size];
} elseif ($variant === 'minimal') {
    $contentClasses = "pt-2";
} else {
    $contentClasses = "collapse-content border border-t-0 rounded-b-md card-bg " . $sizeClasses[$size];
}
?>

<div class="collapse-wrapper">
    <!-- Collapse Trigger -->
    <button type="button" 
            class="<?= $triggerClasses ?> collapse-trigger"
            style="color: var(--color-text-primary);"
            onclick="toggleCollapse('<?= $id ?>')"
            aria-expanded="<?= $defaultOpen ? 'true' : 'false' ?>"
            aria-controls="<?= $id ?>"
            id="<?= $id ?>-trigger">
        
        <span class="font-semibold"><?= htmlspecialchars($triggerText) ?></span>
        
        <?php if ($triggerIcon): ?>
            <i class="<?= htmlspecialchars($triggerIcon) ?> collapse-trigger-icon text-sm ml-2" 
               style="color: var(--color-text-secondary);"></i>
        <?php endif; ?>
    </button>

    <!-- Used by JavaScript -->
    <div class="collapse <?= $defaultOpen ? 'show' : '' ?> <?= $contentClasses ?>" 
         id="<?= $id ?>"
         style="color: var(--color-text-primary);">
        <?= $content ?>
        
        <?php if (isset($children)): ?>
            <?= $children ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple collapse toggle function (can be enhanced by existing collapse.js)
function toggleCollapse(id) {
    const content = document.getElementById(id);
    const trigger = document.getElementById(id + '-trigger');
    
    if (content.classList.contains('show')) {
        // Hide
        content.classList.remove('show');
        content.classList.add('collapsing');
        trigger.setAttribute('aria-expanded', 'false');
        
        // Animate height to 0
        const height = content.scrollHeight;
        content.style.height = height + 'px';
        
        requestAnimationFrame(() => {
            content.style.height = '0px';
        });
        
        setTimeout(() => {
            content.classList.remove('collapsing');
            content.style.height = '';
        }, 200);
    } else {
        // Show
        content.classList.remove('collapse');
        content.classList.add('collapsing');
        trigger.setAttribute('aria-expanded', 'true');
        
        // Animate height from 0 to full
        const height = content.scrollHeight;
        content.style.height = '0px';
        
        requestAnimationFrame(() => {
            content.style.height = height + 'px';
        });
        
        setTimeout(() => {
            content.classList.remove('collapsing');
            content.classList.add('show');
            content.style.height = '';
        }, 200);
    }
}
</script>

<?php
/**
 * Alternative usage with content blocks:
 * 
 * <?php ob_start(); ?>
 * <div class="space-y-4">
 *     <?php $name = 'advanced_option'; $label = 'Advanced Setting'; include __DIR__ . '/form-input.php'; ?>
 *     <?php $name = 'expert_mode'; $type = 'checkbox'; $label = 'Enable Expert Mode'; include __DIR__ . '/form-input.php'; ?>
 * </div>
 * <?php 
 * $content = ob_get_clean();
 * $triggerText = 'Advanced Options';
 * $variant = 'card';
 * include __DIR__ . '/collapse.php'; 
 * ?>
 */
?>
