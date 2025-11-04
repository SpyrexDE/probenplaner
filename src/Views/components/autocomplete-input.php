<?php
/**
 * Autocomplete Input Component - Searchable dropdown with custom input support
 * Uses Tailwind utility classes + minimal custom styles
 * 
 * Usage:
 * <?php 
 * $name = 'rehearsal_type';
 * $id = 'rehearsal_type';
 * $label = 'Sondertermin';
 * $value = $formData['rehearsal_type'] ?? '';
 * $suggestions = ['Konzertreise', 'Konzert', 'Generalprobe', 'Registerprobe', 'Probenwochenende'];
 * include __DIR__ . '/autocomplete-input.php'; 
 * ?>
 */
?>

<style>
.autocomplete-wrapper {
    position: relative;
}

.autocomplete-dropdown {
    position: absolute;
    top: calc(100% - 2px);
    left: 0;
    right: 0;
    z-index: 50;
    max-height: 200px;
    overflow-y: auto;
    background: var(--color-bg-primary);
    border: 2px solid var(--color-border);
    border-top: none;
    border-radius: 0 0 var(--radius-base) var(--radius-base);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    display: none;
}

.autocomplete-wrapper:focus-within .autocomplete-dropdown.show {
    border-color: var(--color-primary);
}

.autocomplete-input:focus {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}

.autocomplete-dropdown.show {
    display: block;
}

.autocomplete-item {
    padding: var(--space-3) var(--space-4);
    cursor: pointer;
    transition: background-color var(--transition-fast);
    color: var(--color-text-primary);
}

.autocomplete-item:hover,
.autocomplete-item.highlighted {
    background-color: var(--color-primary-50);
}

.autocomplete-item:first-child {
    border-top: 1px solid var(--color-border-light);
}

.autocomplete-no-results {
    padding: var(--space-3) var(--space-4);
    color: var(--color-text-muted);
    font-style: italic;
}
</style>

<div class="form-group autocomplete-wrapper">
    <?php if (isset($label) && $label): ?>
        <label for="<?= htmlspecialchars($id ?? $name) ?>" class="form-label">
            <?= htmlspecialchars($label) ?>
        </label>
    <?php endif; ?>
    
    <input 
        type="text"
        id="<?= htmlspecialchars($id ?? $name) ?>"
        name="<?= htmlspecialchars($name) ?>"
        value="<?= htmlspecialchars($value ?? '') ?>"
        class="form-input autocomplete-input"
        autocomplete="off"
        data-suggestions='<?= json_encode($suggestions ?? []) ?>'
        <?= isset($required) && $required ? 'required' : '' ?>
        <?= isset($placeholder) && $placeholder ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '' ?>
    />
    
    <div class="autocomplete-dropdown" id="<?= htmlspecialchars($id ?? $name) ?>-dropdown"></div>
</div>

<script>
(function() {
    'use strict';
    
    const input = document.getElementById('<?= htmlspecialchars($id ?? $name) ?>');
    const dropdown = document.getElementById('<?= htmlspecialchars($id ?? $name) ?>-dropdown');
    const suggestions = JSON.parse(input.getAttribute('data-suggestions') || '[]');
    let highlightedIndex = -1;
    
    function filterSuggestions(query) {
        if (!query.trim()) {
            return suggestions;
        }
        const lowerQuery = query.toLowerCase();
        return suggestions.filter(suggestion => 
            suggestion.toLowerCase().includes(lowerQuery)
        );
    }
    
    function renderDropdown(filteredSuggestions) {
        dropdown.innerHTML = '';
        
        if (filteredSuggestions.length === 0) {
            dropdown.classList.add('show');
            dropdown.innerHTML = '<div class="autocomplete-no-results">Keine Vorschläge gefunden</div>';
            return;
        }
        
        filteredSuggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.textContent = suggestion;
            item.dataset.index = index;
            
            item.addEventListener('click', () => {
                input.value = suggestion;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                hideDropdown();
            });
            
            item.addEventListener('mouseenter', () => {
                highlightedIndex = index;
                updateHighlight();
            });
            
            dropdown.appendChild(item);
        });
        
        dropdown.classList.add('show');
        highlightedIndex = -1;
    }
    
    function updateHighlight() {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            if (index === highlightedIndex) {
                item.classList.add('highlighted');
            } else {
                item.classList.remove('highlighted');
            }
        });
    }
    
    function hideDropdown() {
        dropdown.classList.remove('show');
        highlightedIndex = -1;
    }
    
    function selectHighlighted() {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        if (highlightedIndex >= 0 && highlightedIndex < items.length) {
            items[highlightedIndex].click();
        }
    }
    
    input.addEventListener('input', function() {
        const query = this.value;
        const filtered = filterSuggestions(query);
        renderDropdown(filtered);
    });
    
    input.addEventListener('focus', function() {
        const query = this.value;
        const filtered = filterSuggestions(query);
        if (filtered.length > 0 || query.trim() === '') {
            renderDropdown(filtered);
        }
    });
    
    input.addEventListener('blur', function(e) {
        setTimeout(() => {
            if (!dropdown.contains(document.activeElement)) {
                hideDropdown();
            }
        }, 200);
    });
    
    input.addEventListener('keydown', function(e) {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (highlightedIndex < items.length - 1) {
                highlightedIndex++;
                updateHighlight();
                items[highlightedIndex].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (highlightedIndex > 0) {
                highlightedIndex--;
                updateHighlight();
                items[highlightedIndex].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlightedIndex >= 0 && highlightedIndex < items.length) {
                selectHighlighted();
            } else if (items.length === 1) {
                items[0].click();
            }
        } else if (e.key === 'Escape') {
            hideDropdown();
            input.blur();
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            hideDropdown();
        }
    });
})();
</script>

