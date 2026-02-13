<?php
/**
 * Dropdown Component
 * Classes required for dropdown.js: .dropdown-menu, .dropdown-item
 * 
 * Usage examples:
 * <?php 
 * $triggerText = 'Options'; 
 * $items = [
 *     ['text' => 'Edit', 'onclick' => 'editItem()'],
 *     ['text' => 'Delete', 'onclick' => 'deleteItem()', 'class' => 'text-red-600'],
 *     'divider',
 *     ['text' => 'View Details', 'href' => '/details']
 * ];
 * include __DIR__ . '/dropdown.php'; 
 * ?>
 */
?>

<style>
/* Required for dropdown.js */
.dropdown-menu {
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1), 0 10px 10px rgba(0, 0, 0, 0.04);
    border-color: var(--color-border);
}

.dropdown-item:hover,
.dropdown-item:focus {
    background-color: var(--color-primary-50);
    transform: translateX(2px);
}

.dropdown-item:active {
    background-color: var(--color-bg-tertiary);
}

.dropdown-item.disabled,
.dropdown-item:disabled {
    color: var(--color-text-muted);
    pointer-events: none;
    background-color: transparent;
}

/* Smooth show/hide animation */
.dropdown-menu {
    opacity: 0;
    transform: translateY(-4px);
    transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
}

.dropdown-menu.show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
</style>

<?php
// Component defaults
$triggerText = $triggerText ?? 'Optionen';
$triggerIcon = $triggerIcon ?? 'fas fa-chevron-down';
$triggerClass = $triggerClass ?? '';
$items = $items ?? [];
$position = $position ?? 'left'; // left, right, center
$size = $size ?? 'md';
$id = $id ?? 'dropdown-' . uniqid();

// Size classes
$sizeClasses = [
    'sm' => 'min-w-40',
    'md' => 'min-w-48',
    'lg' => 'min-w-56',
    'xl' => 'min-w-64'
];

$positionClasses = [
    'left' => 'left-0',
    'right' => 'right-0', 
    'center' => 'left-1/2 transform -translate-x-1/2'
];

// Trigger classes
$triggerClasses = "inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md border transition-colors duration-200";
$triggerClasses .= " " . $triggerClass;

// Style for trigger button using CSS variables
$triggerStyle = "color: var(--color-text-primary); background-color: var(--color-bg-primary); border-color: var(--color-border);";
?>

<div class="dropdown relative inline-block">
    <!-- Dropdown Trigger -->
    <button type="button" 
            class="<?= $triggerClasses ?>"
            style="<?= $triggerStyle ?>"
            onclick="toggleDropdown('<?= $id ?>')"
            aria-haspopup="true"
            aria-expanded="false"
            id="<?= $id ?>-trigger">
        <?= htmlspecialchars($triggerText) ?>
        <?php if ($triggerIcon): ?>
            <i class="<?= htmlspecialchars($triggerIcon) ?> ml-2 text-xs"></i>
        <?php endif; ?>
    </button>

    <!-- Used by JavaScript -->
    <div class="dropdown-menu absolute top-full <?= $positionClasses[$position] ?> <?= $sizeClasses[$size] ?> py-2 mt-1 text-sm border rounded-md hidden z-50 card-bg"
         style="color: var(--color-text-primary);"
         id="<?= $id ?>-menu"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="<?= $id ?>-trigger">
        
        <?php foreach ($items as $item): ?>
            <?php if ($item === 'divider'): ?>
                <div class="border-t mx-2 my-2" style="border-color: var(--color-border);"></div>
            <?php elseif (is_array($item)): ?>
                <?php
                $itemText = $item['text'] ?? '';
                $itemHref = $item['href'] ?? '';
                $itemOnclick = $item['onclick'] ?? '';
                $itemClass = $item['class'] ?? '';
                $itemDisabled = $item['disabled'] ?? false;
                
                // Used by JavaScript
                $itemClasses = "dropdown-item block w-full px-4 py-2 text-left border-0 cursor-pointer transition-all duration-200 rounded-sm mx-1";
                $itemClasses .= " " . $itemClass;
                if ($itemDisabled) $itemClasses .= " disabled";
                ?>
                
                <?php if ($itemHref): ?>
                    <a href="<?= htmlspecialchars($itemHref) ?>" 
                       class="<?= $itemClasses ?>"
                       role="menuitem"
                       <?= $itemDisabled ? 'tabindex="-1"' : '' ?>>
                        <?= htmlspecialchars($itemText) ?>
                    </a>
                <?php else: ?>
                    <button type="button"
                            class="<?= $itemClasses ?>"
                            <?= $itemOnclick ? 'onclick="' . htmlspecialchars($itemOnclick) . '"' : '' ?>
                            role="menuitem"
                            <?= $itemDisabled ? 'disabled tabindex="-1"' : '' ?>>
                        <?= htmlspecialchars($itemText) ?>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Simple dropdown toggle function (can be enhanced by existing dropdown.js)
function toggleDropdown(id) {
    const menu = document.getElementById(id + '-menu');
    const trigger = document.getElementById(id + '-trigger');
    
    if (menu.classList.contains('show')) {
        menu.classList.remove('show');
        menu.classList.add('hidden');
        trigger.setAttribute('aria-expanded', 'false');
    } else {
        // Close other dropdowns first
        document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
            otherMenu.classList.remove('show');
            otherMenu.classList.add('hidden');
        });
        
        menu.classList.remove('hidden');
        menu.classList.add('show');
        trigger.setAttribute('aria-expanded', 'true');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
            menu.classList.add('hidden');
            const triggerId = menu.id.replace('-menu', '-trigger');
            const trigger = document.getElementById(triggerId);
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    }
});
</script>
