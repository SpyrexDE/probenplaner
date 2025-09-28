/**
 * Compact Color Picker JavaScript
 * Handles the functionality for the new minimalistic color picker
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all compact color pickers on the page
    const colorPickers = document.querySelectorAll('.compact-color-picker');
    
    colorPickers.forEach(function(picker) {
        initializeColorPicker(picker);
    });
});

function initializeColorPicker(picker) {
    const colorOptions = picker.querySelectorAll('.compact-color-option');
    const hiddenInput = picker.querySelector('input[name="color"]');
    
    // Set initial state
    const initialColor = picker.dataset.selectedColor || '#ffffff';
    updateSelectedColor(picker, initialColor);
    
    // Add click event listeners to color options
    colorOptions.forEach(function(option) {
        option.addEventListener('click', function() {
            const color = this.dataset.color;
            
            // Update the picker state
            updateSelectedColor(picker, color);
            
            // Update hidden input
            if (hiddenInput) {
                hiddenInput.value = color;
            }
            
            // Trigger change event for form validation
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
}

function updateSelectedColor(picker, color) {
    // Remove selected class from all options with smooth transition
    const allOptions = picker.querySelectorAll('.compact-color-option');
    allOptions.forEach(function(option) {
        option.classList.remove('selected');
        // Add a small delay to make the transition smoother
        option.style.transition = 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
    });
    
    // Add selected class to the matching option
    const selectedOption = picker.querySelector(`[data-color="${color}"]`);
    if (selectedOption) {
        // Small delay to ensure smooth transition
        setTimeout(() => {
            selectedOption.classList.add('selected');
        }, 50);
    }
    
    // Update the picker's data attribute
    picker.dataset.selectedColor = color;
}

// Export for use in other scripts if needed
window.CompactColorPicker = {
    initialize: initializeColorPicker,
    updateSelectedColor: updateSelectedColor
};
