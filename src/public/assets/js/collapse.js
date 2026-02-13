

/**
 * Vanilla JavaScript Collapse Component
 * Replaces Bootstrap's collapse functionality
 */

class Collapse {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            toggle: true,
            parent: null,
            ...options
        };

        this.isTransitioning = false;
        this.isShown = false;

        this.init();
    }

    init() {
        // Set initial state
        this.isShown = this.element.classList.contains('show');

        // Add ARIA attributes
        this.element.setAttribute('aria-hidden', !this.isShown);

        // Find trigger elements
        this.triggers = document.querySelectorAll(
            `[data-toggle="collapse"][href="#${this.element.id}"], [data-toggle="collapse"][data-target="#${this.element.id}"]`
        );

        // Update trigger states
        this.updateTriggers();

        // Add event listeners to triggers
        this.triggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        });
    }

    show() {
        if (this.isTransitioning || this.isShown) return;

        this.isTransitioning = true;

        // Close other collapsible elements in the same parent
        if (this.options.parent) {
            const parent = document.querySelector(this.options.parent);
            if (parent) {
                const siblings = parent.querySelectorAll('.collapse');
                siblings.forEach(sibling => {
                    if (sibling !== this.element && sibling.classList.contains('show')) {
                        const siblingCollapse = new Collapse(sibling);
                        siblingCollapse.hide();
                    }
                });
            }
        }

        // Show element
        this.element.style.display = 'block';
        this.element.style.height = '0';
        this.element.style.overflow = 'hidden';

        // Force reflow
        this.element.offsetHeight;

        this.element.classList.add('collapsing');
        this.element.style.height = this.element.scrollHeight + 'px';

        const onTransitionEnd = () => {
            this.element.classList.remove('collapsing');
            this.element.classList.add('show');
            this.element.style.height = '';
            this.element.style.overflow = '';
            this.isTransitioning = false;
            this.isShown = true;
            this.updateTriggers();
            this.element.removeEventListener('transitionend', onTransitionEnd);
        };

        this.element.addEventListener('transitionend', onTransitionEnd);

        // Fallback for browsers without transition support
        setTimeout(() => {
            if (this.isTransitioning) {
                onTransitionEnd();
            }
        }, 350);
    }

    hide() {
        if (this.isTransitioning || !this.isShown) return;

        this.isTransitioning = true;

        this.element.style.height = this.element.scrollHeight + 'px';
        this.element.style.overflow = 'hidden';

        // Force reflow
        this.element.offsetHeight;

        this.element.classList.add('collapsing');
        this.element.style.height = '0';

        const onTransitionEnd = () => {
            this.element.classList.remove('collapsing');
            this.element.classList.remove('show');
            this.element.style.display = '';
            this.element.style.height = '';
            this.element.style.overflow = '';
            this.isTransitioning = false;
            this.isShown = false;
            this.updateTriggers();
            this.element.removeEventListener('transitionend', onTransitionEnd);
        };

        this.element.addEventListener('transitionend', onTransitionEnd);

        // Fallback for browsers without transition support
        setTimeout(() => {
            if (this.isTransitioning) {
                onTransitionEnd();
            }
        }, 350);
    }

    toggle() {
        if (this.isShown) {
            this.hide();
        } else {
            this.show();
        }
    }

    updateTriggers() {
        this.triggers.forEach(trigger => {
            trigger.setAttribute('aria-expanded', this.isShown);
        });
    }
}

// Initialize all collapse elements on DOM load
document.addEventListener('DOMContentLoaded', function () {
    // Initialize all collapse elements
    const collapseElements = document.querySelectorAll('.collapse');
    collapseElements.forEach(element => {
        new Collapse(element);
    });

    // Handle data-toggle="collapse" links
    const collapseLinks = document.querySelectorAll('[data-toggle="collapse"]');
    collapseLinks.forEach(link => {
        const targetId = link.getAttribute('href') || link.getAttribute('data-target');
        if (targetId) {
            const target = document.querySelector(targetId);
            if (target && !target.collapseInstance) {
                target.collapseInstance = new Collapse(target);
            }
        }
    });
});

// Global function for programmatic control (replaces Bootstrap's jQuery API)
window.Collapse = Collapse;

// jQuery-like API for compatibility
if (typeof $ !== 'undefined') {
    $.fn.collapse = function (action) {
        return this.each(function () {
            const element = this;
            if (!element.collapseInstance) {
                element.collapseInstance = new Collapse(element);
            }

            switch (action) {
                case 'show':
                    element.collapseInstance.show();
                    break;
                case 'hide':
                    element.collapseInstance.hide();
                    break;
                case 'toggle':
                    element.collapseInstance.toggle();
                    break;
            }
        });
    };
}
