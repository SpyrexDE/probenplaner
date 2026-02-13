

/**
 * Vanilla JavaScript Tooltip Component
 * Replaces Bootstrap's tooltip functionality
 */

class Tooltip {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            title: '',
            placement: 'top',
            trigger: 'hover focus',
            delay: 0,
            html: false,
            container: false,
            ...options
        };

        this.isShown = false;
        this.tooltip = null;
        this.timeout = null;

        this.init();
    }

    init() {
        // Get title from data attribute or element title
        if (!this.options.title) {
            this.options.title = this.element.getAttribute('data-original-title') ||
                this.element.getAttribute('title') ||
                this.element.textContent.trim();
        }

        // Remove original title to prevent browser tooltip
        if (this.element.getAttribute('title')) {
            this.element.setAttribute('data-original-title', this.element.getAttribute('title'));
            this.element.removeAttribute('title');
        }

        // Parse trigger options
        this.triggers = this.options.trigger.split(' ');

        // Add event listeners
        this.triggers.forEach(trigger => {
            switch (trigger) {
                case 'hover':
                    this.element.addEventListener('mouseenter', () => this.show());
                    this.element.addEventListener('mouseleave', () => this.hide());
                    break;
                case 'focus':
                    this.element.addEventListener('focus', () => this.show());
                    this.element.addEventListener('blur', () => this.hide());
                    break;
                case 'click':
                    this.element.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.toggle();
                    });
                    break;
                case 'manual':
                    // Manual trigger only
                    break;
            }
        });
    }

    createTooltip() {
        // Create tooltip element
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'tooltip';
        this.tooltip.setAttribute('role', 'tooltip');
        this.tooltip.setAttribute('aria-hidden', 'true');

        // Create arrow
        const arrow = document.createElement('div');
        arrow.className = 'tooltip-arrow';

        // Create inner content
        const inner = document.createElement('div');
        inner.className = 'tooltip-inner';

        if (this.options.html) {
            inner.innerHTML = this.options.title;
        } else {
            inner.textContent = this.options.title;
        }

        // Assemble tooltip
        this.tooltip.appendChild(arrow);
        this.tooltip.appendChild(inner);

        // Add to DOM
        const container = this.options.container || document.body;
        container.appendChild(this.tooltip);

        // Position tooltip
        this.position();
    }

    position() {
        if (!this.tooltip) return;

        const elementRect = this.element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();

        let top, left;

        switch (this.options.placement) {
            case 'top':
                top = elementRect.top - tooltipRect.height - 8;
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'bottom':
                top = elementRect.bottom + 8;
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                break;
            case 'left':
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                left = elementRect.left - tooltipRect.width - 8;
                break;
            case 'right':
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                left = elementRect.right + 8;
                break;
        }

        // Ensure tooltip stays within viewport
        if (top < 0) {
            top = elementRect.bottom + 8;
            this.options.placement = 'bottom';
        }
        if (left < 0) {
            left = 10;
        }
        if (left + tooltipRect.width > window.innerWidth) {
            left = window.innerWidth - tooltipRect.width - 10;
        }

        // Apply position
        this.tooltip.style.top = (top + window.scrollY) + 'px';
        this.tooltip.style.left = (left + window.scrollX) + 'px';

        // Update arrow position
        const arrow = this.tooltip.querySelector('.tooltip-arrow');
        if (arrow) {
            arrow.className = `tooltip-arrow tooltip-arrow-${this.options.placement}`;
        }
    }

    show() {
        if (this.isShown) return;

        // Clear any existing timeout
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        // Create tooltip if it doesn't exist
        if (!this.tooltip) {
            this.createTooltip();
        }

        // Show tooltip
        this.tooltip.style.display = 'block';
        this.tooltip.setAttribute('aria-hidden', 'false');
        this.isShown = true;

        // Trigger shown event
        this.triggerEvent('shown.bs.tooltip');
    }

    hide() {
        if (!this.isShown) return;

        // Clear any existing timeout
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        if (this.tooltip) {
            this.tooltip.style.display = 'none';
            this.tooltip.setAttribute('aria-hidden', 'true');
        }

        this.isShown = false;

        // Trigger hidden event
        this.triggerEvent('hidden.bs.tooltip');
    }

    toggle() {
        if (this.isShown) {
            this.hide();
        } else {
            this.show();
        }
    }

    destroy() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }

        // Remove event listeners
        this.triggers.forEach(trigger => {
            switch (trigger) {
                case 'hover':
                    this.element.removeEventListener('mouseenter', () => this.show());
                    this.element.removeEventListener('mouseleave', () => this.hide());
                    break;
                case 'focus':
                    this.element.removeEventListener('focus', () => this.show());
                    this.element.removeEventListener('blur', () => this.hide());
                    break;
                case 'click':
                    this.element.removeEventListener('click', (e) => {
                        e.preventDefault();
                        this.toggle();
                    });
                    break;
            }
        });

        this.isShown = false;
    }

    triggerEvent(eventName) {
        const event = new CustomEvent(eventName, {
            bubbles: true,
            detail: { target: this.element }
        });
        this.element.dispatchEvent(event);
    }
}

// Initialize all tooltips on DOM load
document.addEventListener('DOMContentLoaded', function () {
    const tooltipElements = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltipElements.forEach(element => {
        element.tooltipInstance = new Tooltip(element);
    });
});

// Global function for programmatic control
window.Tooltip = Tooltip;

// jQuery-like API for compatibility
if (typeof $ !== 'undefined') {
    $.fn.tooltip = function (action) {
        return this.each(function () {
            const element = this;
            if (!element.tooltipInstance) {
                element.tooltipInstance = new Tooltip(element);
            }

            switch (action) {
                case 'show':
                    element.tooltipInstance.show();
                    break;
                case 'hide':
                    element.tooltipInstance.hide();
                    break;
                case 'toggle':
                    element.tooltipInstance.toggle();
                    break;
                case 'destroy':
                    element.tooltipInstance.destroy();
                    break;
            }
        });
    };
}
