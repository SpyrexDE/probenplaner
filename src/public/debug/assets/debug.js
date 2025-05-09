/**
 * Debug Dashboard JavaScript
 * 
 * Functionality for the debug dashboard UI
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize collapsible sections
    initCollapsible();
    
    // Initialize dynamic form controls
    initFormControls();
    
    // Add confirm dialogs to dangerous actions
    initConfirmActions();
    
    // Update timestamp every minute
    initTimestamp();
});

/**
 * Initialize collapsible sections
 */
function initCollapsible() {
    const collapsibles = document.querySelectorAll('.collapsible-header');
    
    collapsibles.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('.collapse-icon');
            
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                if (icon) icon.textContent = '▼';
                this.setAttribute('aria-expanded', 'false');
            } else {
                content.style.maxHeight = content.scrollHeight + 'px';
                if (icon) icon.textContent = '▲';
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });
}

/**
 * Initialize dynamic form controls
 */
function initFormControls() {
    // Dynamic dependency between form controls
    const dependentSelects = document.querySelectorAll('[data-depends-on]');
    
    dependentSelects.forEach(select => {
        const sourceId = select.getAttribute('data-depends-on');
        const sourceElement = document.getElementById(sourceId);
        
        if (sourceElement) {
            const updateOptions = function() {
                const dependencyValue = sourceElement.value;
                const options = select.querySelectorAll('option');
                
                options.forEach(option => {
                    const showFor = option.getAttribute('data-show-for');
                    if (showFor) {
                        const values = showFor.split(',');
                        option.hidden = !values.includes(dependencyValue);
                    }
                });
                
                // Reset to first non-hidden option if current is hidden
                if (select.selectedOptions[0] && select.selectedOptions[0].hidden) {
                    select.value = Array.from(options).find(o => !o.hidden)?.value || '';
                }
            };
            
            // Initial update
            updateOptions();
            
            // Update on change
            sourceElement.addEventListener('change', updateOptions);
        }
    });
    
    // Copy to clipboard buttons
    const copyButtons = document.querySelectorAll('.copy-btn');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-copy-target');
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Copy the text
                const text = targetElement.textContent || targetElement.value;
                navigator.clipboard.writeText(text).then(() => {
                    // Show success feedback
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            }
        });
    });
}

/**
 * Add confirmation dialogs to dangerous actions
 */
function initConfirmActions() {
    const dangerButtons = document.querySelectorAll('[data-confirm]');
    
    dangerButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Update timestamp display
 */
function initTimestamp() {
    const timestampElement = document.querySelector('.timestamp');
    
    if (timestampElement) {
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toISOString().replace('T', ' ').substring(0, 19);
            timestampElement.textContent = timeStr;
        }, 60000); // Update every minute
    }
}

/**
 * Show a message toast
 * @param {string} message - The message to display
 * @param {string} type - The message type (success, error, warning, info)
 */
function showMessage(message, type = 'info') {
    const messageContainer = document.createElement('div');
    messageContainer.className = `message ${type}`;
    messageContainer.textContent = message;
    
    // Add to page
    const content = document.querySelector('.debug-content');
    content.insertBefore(messageContainer, content.firstChild);
    
    // Remove after delay
    setTimeout(() => {
        messageContainer.style.opacity = '0';
        setTimeout(() => {
            messageContainer.remove();
        }, 300);
    }, 5000);
}

/**
 * Toggle visibility of an element
 * @param {string} elementId - The ID of the element to toggle
 */
function toggleVisibility(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = element.style.display === 'none' ? '' : 'none';
    }
} 