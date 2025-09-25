/*
 * CONSISTENT PATTERNS FOR AI:
 * - Use window.notifySuccess/Error/Info() for all notifications
 * - Use existing .btn-base classes for buttons
 * - Use existing form validation patterns
 * - Never change CSS class names that JavaScript depends on
 */

/**
 * Vanilla JavaScript Dropdown Component
 * Replaces Bootstrap's dropdown functionality
 */

class Dropdown {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            boundary: 'clippingParents',
            display: 'dynamic',
            offset: [0, 2],
            popperConfig: null,
            reference: 'toggle',
            ...options
        };
        
        this.isShown = false;
        this.popper = null;
        
        this.init();
    }
    
    init() {
        // Find toggle element
        this.toggle = this.element.querySelector('[data-toggle="dropdown"]') || this.element;
        
        // Find menu element
        this.menu = this.element.querySelector('.dropdown-menu');
        if (!this.menu) {
            console.warn('Dropdown menu not found for element:', this.element);
            return;
        }
        
        // Set initial state
        this.menu.style.display = 'none';
        this.menu.setAttribute('aria-hidden', 'true');
        
        // Add event listeners
        this.toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggle();
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target)) {
                this.hide();
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isShown) {
                this.hide();
            }
        });
        
        // Handle menu item clicks
        this.menu.addEventListener('click', (e) => {
            const item = e.target.closest('.dropdown-item');
            if (item && !item.hasAttribute('disabled')) {
                this.hide();
            }
        });
    }
    
    show() {
        if (this.isShown) return;
        
        // Hide other dropdowns
        Dropdown.hideAll();
        
        this.isShown = true;
        this.menu.style.display = 'block';
        this.menu.setAttribute('aria-hidden', 'false');
        
        // Add show class
        this.element.classList.add('show');
        this.menu.classList.add('show');
        
        // Position the menu
        this.position();
        
        // Trigger shown event
        this.triggerEvent('shown.bs.dropdown');
    }
    
    hide() {
        if (!this.isShown) return;
        
        this.isShown = false;
        this.menu.style.display = 'none';
        this.menu.setAttribute('aria-hidden', 'true');
        
        // Remove show class
        this.element.classList.remove('show');
        this.menu.classList.remove('show');
        
        // Trigger hidden event
        this.triggerEvent('hidden.bs.dropdown');
    }
    
    toggle() {
        if (this.isShown) {
            this.hide();
        } else {
            this.show();
        }
    }
    
    position() {
        const toggleRect = this.toggle.getBoundingClientRect();
        const menuRect = this.menu.getBoundingClientRect();
        
        // Reset position
        this.menu.style.position = 'absolute';
        this.menu.style.top = '';
        this.menu.style.left = '';
        this.menu.style.right = '';
        this.menu.style.bottom = '';
        
        // Calculate position
        let top = toggleRect.bottom + window.scrollY;
        let left = toggleRect.left + window.scrollX;
        
        // Check if menu goes off the right edge
        if (left + menuRect.width > window.innerWidth) {
            left = window.innerWidth - menuRect.width - 10;
        }
        
        // Check if menu goes off the left edge
        if (left < 0) {
            left = 10;
        }
        
        // Check if menu goes off the bottom edge
        if (top + menuRect.height > window.innerHeight + window.scrollY) {
            // Position above the toggle
            top = toggleRect.top + window.scrollY - menuRect.height;
        }
        
        // Apply position
        this.menu.style.top = top + 'px';
        this.menu.style.left = left + 'px';
    }
    
    triggerEvent(eventName) {
        const event = new CustomEvent(eventName, {
            bubbles: true,
            detail: { target: this.element }
        });
        this.element.dispatchEvent(event);
    }
    
    static hideAll() {
        document.querySelectorAll('.dropdown.show').forEach(dropdown => {
            const instance = dropdown.dropdownInstance;
            if (instance) {
                instance.hide();
            }
        });
    }
}

// Initialize all dropdowns on DOM load
document.addEventListener('DOMContentLoaded', function() {
    const dropdownElements = document.querySelectorAll('.dropdown');
    dropdownElements.forEach(element => {
        element.dropdownInstance = new Dropdown(element);
    });
});

// Global function for programmatic control
window.Dropdown = Dropdown;

// jQuery-like API for compatibility
if (typeof $ !== 'undefined') {
    $.fn.dropdown = function(action) {
        return this.each(function() {
            const element = this;
            if (!element.dropdownInstance) {
                element.dropdownInstance = new Dropdown(element);
            }
            
            switch (action) {
                case 'show':
                    element.dropdownInstance.show();
                    break;
                case 'hide':
                    element.dropdownInstance.hide();
                    break;
                case 'toggle':
                    element.dropdownInstance.toggle();
                    break;
            }
        });
    };
}
