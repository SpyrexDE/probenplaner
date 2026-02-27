/* Shared JavaScript functionality for promise views */

// Initialize promise view functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeCollapseControls();
    initializeUserClickHandlers();
    initializeViewToggle();
});

// Initialize collapse controls for tree nodes
function initializeCollapseControls() {
    const treeHeaders = document.querySelectorAll('.tree-node-header[data-toggle="collapse"]');
    treeHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            const newExpanded = !expanded;
            this.setAttribute('aria-expanded', newExpanded);

            // Toggle the parent tree node expanded class
            const treeNode = this.closest('.tree-node');
            if (treeNode) {
                treeNode.classList.toggle('tree-node-expanded', newExpanded);
            }

            // Get target content element
            const targetId = this.getAttribute('href');
            if (targetId) {
                const target = document.querySelector(targetId);
                if (target) {
                    // Toggle content visibility
                    if (newExpanded) {
                        target.classList.remove('collapsed');
                        target.classList.add('expanded', 'expanding');
                        // Remove expanding class after animation
                        setTimeout(() => {
                            target.classList.remove('expanding');
                        }, 200);
                    } else {
                        target.classList.remove('expanded', 'expanding');
                        target.classList.add('collapsed');
                    }
                }
            }
        });
    });
}

// Initialize click handlers for user spans
function initializeUserClickHandlers() {
    const userSpans = document.querySelectorAll('.userSpan');
    userSpans.forEach(span => {
        if (span) {
            span.style.cursor = 'pointer';

            span.addEventListener('click', function (e) {
                // Prevent click from affecting parent elements
                e.stopPropagation();

                const userId = this.getAttribute('data-user-id');
                if (userId && typeof openEditModal === 'function') {
                    openEditModal(userId);
                } else {
                    console.warn('Member Edit Modal cannot be opened: missing user_id or openEditModal function.');
                }
            });
        }
    });
}



// Standardized API call helper
function standardApiCall(url, options = {}) {
    const finalOptions = {
        method: 'GET',
        headers: { 'Accept': 'application/json', ...(options.headers || {}) },
        ...options
    };
    return fetch(url, finalOptions).then(async response => {
        const contentType = response.headers.get('content-type') || '';
        const text = await response.text().catch(() => '');
        const isJson = contentType.includes('application/json');
        const parseJson = () => { try { return JSON.parse(text); } catch (e) { return null; } };
        if (!response.ok) {
            const data = isJson ? parseJson() : null;
            const message = (data && (data.error || data.message)) || text || `HTTP ${response.status}`;
            throw new Error(message);
        }
        return isJson ? (parseJson() || {}) : {};
    }).catch(error => {
        // Automatically check if the error coming from the server has details/message structure
        let details = error.message;
        // Try to see if message is a JSON object with details
        try {
            const parsed = JSON.parse(error.message);
            if (parsed && typeof parsed === 'object') {
                if (parsed.message) error.message = parsed.message;
                if (parsed.details) details = parsed.details;
            }
        } catch (e) { }

        // Re-throw to allow specific handling if needed, but ensure we have a good message
        error.details = details;
        throw error;
    });
}

// Show error toast notification
function showErrorToast(message, timer = 3000) {
    window.notifyError(message, { timer: timer });
}

// Initialize view toggle functionality (leader view only)
function initializeViewToggle() {
    const viewToggle = document.getElementById('viewToggle');
    if (!viewToggle) return;

    const simpleViews = document.querySelectorAll('.simple-view');
    const sectionalViews = document.querySelectorAll('.sectional-view');
    const toggleLabels = document.querySelectorAll('.toggle-label');

    // Update label styling based on toggle state
    function updateLabels(isChecked) {
        // Single label variant: highlight when ON
        if (toggleLabels[0]) {
            toggleLabels[0].classList.toggle('active', isChecked);
        }
    }

    // Toggle views
    function toggleViews(showSectional) {
        simpleViews.forEach(view => {
            if (view) {
                view.style.display = showSectional ? 'none' : 'block';
            }
        });

        sectionalViews.forEach(view => {
            if (view) {
                view.style.display = showSectional ? 'block' : 'none';
            }
        });

        updateLabels(showSectional);
    }

    // Check URL parameter to determine initial state (takes precedence over localStorage)
    const urlParams = new URLSearchParams(window.location.search);
    const viewAllParam = urlParams.get('viewAll');

    if (viewAllParam === '1') {
        // URL says show all sections
        viewToggle.checked = true;
        toggleViews(true);
    } else {
        // URL says show only own section (or no parameter)
        viewToggle.checked = false;
        toggleViews(false);
        updateLabels(false);
    }

    // Handle toggle change
    viewToggle.addEventListener('change', function () {
        // Update URL parameter and reload page
        const url = new URL(window.location);
        if (this.checked) {
            // Show all sections
            url.searchParams.set('viewAll', '1');
        } else {
            // Show only own section
            url.searchParams.delete('viewAll');
        }

        // Reload page with new parameter
        window.location.href = url.toString();
    });
}
