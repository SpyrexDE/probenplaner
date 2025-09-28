<?php
use App\Core\Constants;
?>

<style>
/* COMPACT COLOR PICKER COMPONENT - All styles colocated */
.compact-color-picker {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  align-items: flex-start;
  margin-bottom: var(--space-4);
}

.compact-color-picker-label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  margin-bottom: var(--space-2);
}

.compact-color-picker-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: var(--space-3);
  width: 100%;
  max-width: 320px;
}

.compact-color-option {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-base);
  border: 2px solid var(--color-border);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  background: none;
  padding: 0;
  outline: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.compact-color-option:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1;
  border-color: var(--color-primary-300);
}

.compact-color-option:hover:not(.selected) {
  border-width: 3px;
}

.compact-color-option:active {
  transform: scale(0.98);
  transition: transform 0.1s ease;
}

.compact-color-option:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.compact-color-option.selected {
  border: 3px solid var(--color-primary);
  box-shadow: 0 0 0 2px var(--color-primary-100), 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: scale(1.05);
  z-index: 2;
}

.compact-color-option.selected::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: var(--color-white);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-bold);
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
  animation: checkmark-bounce 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  transition: opacity 0.2s ease;
}

@keyframes checkmark-bounce {
  0% {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.3);
  }
  50% {
    transform: translate(-50%, -50%) scale(1.3);
  }
  100% {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

/* Color-specific background styles for rehearsal colors */
.compact-color-option.color-e5e7eb { 
  background-color: #e5e7eb;
  border-color: #d1d5db; /* Visible border for light gray */
}
.compact-color-option.color-3b82f6 { background-color: #3b82f6; }
.compact-color-option.color-10b981 { background-color: #10b981; }
.compact-color-option.color-f59e0b { background-color: #f59e0b; }
.compact-color-option.color-ef4444 { background-color: #ef4444; }
.compact-color-option.color-8b5cf6 { background-color: #8b5cf6; }
.compact-color-option.color-f97316 { background-color: #f97316; }
.compact-color-option.color-ec4899 { background-color: #ec4899; }
.compact-color-option.color-14b8a6 { background-color: #14b8a6; }
.compact-color-option.color-6366f1 { background-color: #6366f1; }
.compact-color-option.color-6b7280 { background-color: #6b7280; }
.compact-color-option.color-475569 { background-color: #475569; }

/* Support for data-color attributes (fallback for rehearsal colors) */
.compact-color-option[data-color="#e5e7eb"] { 
  background-color: #e5e7eb;
  border-color: #d1d5db; /* Visible border for light gray */
}
.compact-color-option[data-color="#3b82f6"] { background-color: #3b82f6; }
.compact-color-option[data-color="#10b981"] { background-color: #10b981; }
.compact-color-option[data-color="#f59e0b"] { background-color: #f59e0b; }
.compact-color-option[data-color="#ef4444"] { background-color: #ef4444; }
.compact-color-option[data-color="#8b5cf6"] { background-color: #8b5cf6; }
.compact-color-option[data-color="#f97316"] { background-color: #f97316; }
.compact-color-option[data-color="#ec4899"] { background-color: #ec4899; }
.compact-color-option[data-color="#14b8a6"] { background-color: #14b8a6; }
.compact-color-option[data-color="#6366f1"] { background-color: #6366f1; }
.compact-color-option[data-color="#6b7280"] { background-color: #6b7280; }
.compact-color-option[data-color="#475569"] { background-color: #475569; }

/* Responsive design */
@media (max-width: 640px) {
  .compact-color-picker-grid {
    grid-template-columns: repeat(6, 1fr);
    max-width: 280px;
    gap: var(--space-2);
  }
  
  .compact-color-option {
    width: 36px;
    height: 36px;
  }
}

@media (max-width: 480px) {
  .compact-color-picker-grid {
    grid-template-columns: repeat(4, 1fr);
    max-width: 200px;
    gap: var(--space-2);
  }
  
  .compact-color-option {
    width: 38px;
    height: 38px;
  }
}
</style>

<div class="compact-color-picker" data-selected-color="<?= htmlspecialchars($selectedColor ?? Constants::COLOR_WHITE) ?>">
    <label class="compact-color-picker-label">Farbenauswahl</label>
    <div class="compact-color-picker-grid">
        <?php foreach (Constants::getRehearsalColors() as $colorValue => $colorName): ?>
            <button 
                type="button" 
                class="compact-color-option color-<?= str_replace('#', '', $colorValue) ?> <?= ($selectedColor ?? Constants::COLOR_WHITE) === $colorValue ? 'selected' : '' ?>" 
                data-color="<?= htmlspecialchars($colorValue) ?>"
                data-color-name="<?= htmlspecialchars($colorName) ?>"
                title="<?= htmlspecialchars($colorName) ?>"
                aria-label="<?= htmlspecialchars($colorName) ?> auswählen"
            ></button>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="color" value="<?= htmlspecialchars($selectedColor ?? Constants::COLOR_WHITE) ?>">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers to color options
    const colorOptions = document.querySelectorAll('.compact-color-option');
    
    colorOptions.forEach(option => {
        option.addEventListener('click', function() {
            const colorPicker = this.closest('.compact-color-picker');
            const hiddenInput = colorPicker.querySelector('input[name="color"]');
            const selectedColor = this.getAttribute('data-color');
            
            // Remove selected class from all options in this picker
            const allOptions = colorPicker.querySelectorAll('.compact-color-option');
            allOptions.forEach(opt => opt.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Update hidden input value
            if (hiddenInput) {
                hiddenInput.value = selectedColor;
                
                // Trigger change event for form validation
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            
            // Update the data attribute on the container
            colorPicker.setAttribute('data-selected-color', selectedColor);
        });
    });
    
    // Initialize the correct selected state on page load
    const colorPickers = document.querySelectorAll('.compact-color-picker');
    
    colorPickers.forEach(picker => {
        const hiddenInput = picker.querySelector('input[name="color"]');
        const currentValue = hiddenInput ? hiddenInput.value : '';
        
        if (currentValue) {
            const currentOption = picker.querySelector(`[data-color="${currentValue}"]`);
            if (currentOption) {
                // Remove selected from all options
                const allOptions = picker.querySelectorAll('.compact-color-option');
                allOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selected to the current option
                currentOption.classList.add('selected');
            }
        }
    });
});
</script>
