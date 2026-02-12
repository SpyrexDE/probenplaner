<?php
/**
 * Dynamic Group Selector Component
 * 
 * Generates a hierarchical group selection form based on the GroupManager configuration
 * This replaces the hardcoded group checkboxes in rehearsal create/edit forms
 * 
 * @param array $formData - Form data for checked state
 * @param bool $isEdit - Whether this is edit mode (affects checkbox IDs and values)
 */

use App\Core\GroupManager;

$groupManager = new GroupManager();
$config = $groupManager->getConfig();

/**
 * Recursively generate form checkboxes from config
 */
function generateGroupCheckboxes($groups, $level = 0, $formData = [], $parentClass = '') {
    $html = '';
    $groupsArray = array_values($groups); // Convert to indexed array for easier last-item detection
    $totalGroups = count($groupsArray);
    
    foreach ($groupsArray as $index => $group) {
        if (!is_array($group) || !isset($group['id'])) {
            continue;
        }
        
        $groupId = $group['id'];
        $displayName = $group['display_name'] ?? $groupId;
        $isLastInGroup = ($index === $totalGroups - 1);
        
        // Determine CSS classes based on level
        $levelClass = match($level) {
            0 => 'checkbox-group',
            1 => 'checkbox-group sub-group',
            default => 'checkbox-group sub-sub-group'
        };
        
        // Generate checkbox for this group with proper CSS level classes
        $classes = ['checkbox-item'];
        if ($level > 0) {
            $classes[] = 'level-' . $level;
        }
        if ($isLastInGroup) {
            $classes[] = 'last-in-group';
        }
        
        $html .= '<div class="' . implode(' ', $classes) . '">' . "\n";
        
        // Determine checked state
        $isChecked = in_array($groupId, $formData['groups'] ?? []);
        
        // Render checkbox
        $html .= '  <input type="checkbox" id="' . htmlspecialchars($groupId) . '" name="groups[]" value="' . htmlspecialchars($groupId) . '" ' . ($isChecked ? 'checked' : '') . '>' . "\n";
        
        // Legacy exclusion logic removed
        
        $html .= '  <label for="' . htmlspecialchars($groupId) . '">' . htmlspecialchars($displayName) . '</label>' . "\n";
        $html .= '</div>' . "\n";
        
        // Recursive children rendering
        if (isset($group['children']) && is_array($group['children']) && !empty($group['children'])) {
            $html .= '<div class="' . $levelClass . '">' . "\n";
            $html .= generateGroupCheckboxes($group['children'], $level + 1, $formData, $levelClass);
            $html .= '</div>' . "\n";
        }
    }
    
    return $html;
}

// Generate the complete form with cleaned form data, but keep original for exclusion logic
echo generateGroupCheckboxes($config, 0, $formData ?? []);

// Add some JavaScript for hierarchical functionality
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize hierarchical checkbox logic
    initializeHierarchicalCheckboxes();
});

// 3-Level Hierarchy Logic for dynamic groups
function initializeHierarchicalCheckboxes() {
    const groupManager = {
        init: function() {
            this.bindEventListeners();
            // Calculate initial states for all parent checkboxes
            this.calculateInitialStates();
        },
        
        calculateInitialStates: function() {
            // Find all checkboxes that have children and update their states
            const allCheckboxes = document.querySelectorAll('input[type="checkbox"][name="groups[]"]');
            
            // Bottom-up processing
            const checkboxesByDepth = [];
            allCheckboxes.forEach(checkbox => {
                const item = checkbox.closest('.checkbox-item');
                const depth = this.getDepthLevel(item);
                if (!checkboxesByDepth[depth]) checkboxesByDepth[depth] = [];
                checkboxesByDepth[depth].push(checkbox);
            });
            
            for (let depth = checkboxesByDepth.length - 1; depth >= 0; depth--) {
                if (checkboxesByDepth[depth]) {
                    checkboxesByDepth[depth].forEach(checkbox => {
                        // Only update state if this checkbox has children and is not already checked
                        // This preserves the initial checked state from the server
                        if (this.findChildCheckboxes(checkbox).length > 0 && !checkbox.checked) {
                            this.updateCheckboxState(checkbox);
                        }
                    });
                }
            }
            
            // Now ensure parent-child consistency: if a parent is checked, check all children
            allCheckboxes.forEach(checkbox => {
                if (checkbox.checked && this.findChildCheckboxes(checkbox).length > 0) {
                    this.checkChildren(checkbox);
                }
            });
        },
        
        getDepthLevel: function(checkboxItem) {
            // Count how many checkbox-group ancestors this item has
            let depth = 0;
            let current = checkboxItem;
            
            while (current && current !== document.body) {
                if (current.classList && current.classList.contains('checkbox-group')) {
                    depth++;
                }
                current = current.parentElement;
            }
            
            return depth;
        },
        
            bindEventListeners: function() {
      const checkboxes = document.querySelectorAll('input[type="checkbox"][name="groups[]"]');
      
      checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
          this.handleCheckboxChange(e.target);
        });
      });
      
      // Add click handlers for entire checkbox items
      const checkboxItems = document.querySelectorAll('.checkbox-item');
      checkboxItems.forEach(item => {
        item.addEventListener('click', (e) => {
          // Don't trigger if clicking directly on checkbox or label
          if (e.target.tagName === 'INPUT' || e.target.tagName === 'LABEL') {
            return;
          }
          
          const checkbox = item.querySelector('input[type="checkbox"]');
          if (checkbox) {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      });
    },
        
        handleCheckboxChange: function(checkbox) {
            if (checkbox.checked) {
                this.checkChildren(checkbox);
                this.updateParentStates(checkbox);
            } else {
                this.uncheckChildren(checkbox);
                this.updateParentStates(checkbox);
            }
        },
        
        updateParentStates: function(changedCheckbox) {
            // Walk up the DOM tree to update all parent states
            this.updateParentsRecursively(changedCheckbox);
        },
        
        updateParentsRecursively: function(checkbox) {
            const parentCheckbox = this.findParentCheckbox(checkbox);
            if (parentCheckbox) {
                this.updateCheckboxState(parentCheckbox);
                // Recursively update grandparents
                this.updateParentsRecursively(parentCheckbox);
            }
        },
        
        findParentCheckbox: function(childCheckbox) {
            // Walk up DOM to find the parent checkbox
            let current = childCheckbox.closest('.checkbox-item');
            if (!current) return null;
            
            // Go up to find the parent .checkbox-group
            let parent = current.parentElement;
            while (parent && !parent.classList.contains('checkbox-group')) {
                parent = parent.parentElement;
            }
            
            if (!parent) return null;
            
            // The parent checkbox is the previous sibling of this group
            const parentItem = parent.previousElementSibling;
            if (parentItem && parentItem.classList.contains('checkbox-item')) {
                return parentItem.querySelector('input[type="checkbox"]');
            }
            
            return null;
        },
        
        findChildCheckboxes: function(parentCheckbox) {
            // Find all direct child checkboxes
            const parentItem = parentCheckbox.closest('.checkbox-item');
            if (!parentItem) return [];
            
            // Look for the next .checkbox-group sibling
            let nextSibling = parentItem.nextElementSibling;
            while (nextSibling && !nextSibling.classList.contains('checkbox-group')) {
                nextSibling = nextSibling.nextElementSibling;
            }
            
            if (!nextSibling) return [];
            
            // Find all direct child checkboxes (not nested deeper)
            const directChildren = [];
            const childItems = nextSibling.children;
            
            for (let child of childItems) {
                if (child.classList.contains('checkbox-item')) {
                    const checkbox = child.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        directChildren.push(checkbox);
                    }
                }
            }
            
            return directChildren;
        },
        
        updateCheckboxState: function(parentCheckbox) {
            const childCheckboxes = this.findChildCheckboxes(parentCheckbox);
            
            // If no children, don't change the parent state
            if (childCheckboxes.length === 0) return;
            
            let checkedCount = 0;
            let indeterminateCount = 0;
            
            childCheckboxes.forEach(child => {
                if (child.checked) {
                    checkedCount++;
                } else if (child.indeterminate) {
                    indeterminateCount++;
                }
            });
            
            // Determine parent state based on children
            if (checkedCount === 0 && indeterminateCount === 0) {
                // No children checked or indeterminate
                parentCheckbox.checked = false;
                parentCheckbox.indeterminate = false;
            } else if (checkedCount === childCheckboxes.length && indeterminateCount === 0) {
                // All children fully checked (no indeterminate)
                parentCheckbox.checked = true;
                parentCheckbox.indeterminate = false;
            } else {
                // Mixed state: some checked, some unchecked, or some indeterminate
                parentCheckbox.checked = false;
                parentCheckbox.indeterminate = true;
            }
        },
        
        checkChildren: function(checkbox) {
            const childCheckboxes = this.findChildCheckboxes(checkbox);
            
            childCheckboxes.forEach(childCheckbox => {
                if (!childCheckbox.checked) {
                    childCheckbox.checked = true;
                    childCheckbox.indeterminate = false;
                    // Recursively check their children
                    this.checkChildren(childCheckbox);
                }
            });
        },
        
        uncheckChildren: function(checkbox) {
            const childCheckboxes = this.findChildCheckboxes(checkbox);
            
            childCheckboxes.forEach(childCheckbox => {
                if (childCheckbox.checked || childCheckbox.indeterminate) {
                    childCheckbox.checked = false;
                    childCheckbox.indeterminate = false;
                    // Recursively uncheck their children
                    this.uncheckChildren(childCheckbox);
                }
            });
        }
    };
    
    groupManager.init();
}
</script>
