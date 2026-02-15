<?php

/**
 * Minimal Date Separator Component
 * Simple line separator with "HEUTE" text in the middle
 */

$showOld = isset($_GET['showOld']) && ($_GET['showOld'] === '1' || $_GET['showOld'] === 'true');
?>

<div class="date-separator-wrapper" id="dateSeparator">
    <!-- Past rehearsals load trigger -->
    <!-- Past rehearsals load trigger -->
    <?php if (!$showOld && ($hasPastRehearsals ?? false)): ?>
        <div class="load-past-button-wrapper">
            <a href="?showOld=1" class="load-past-button" id="loadPastButton" style="text-decoration: none;">
                <i class="fas fa-history"></i>
                <span>Vergangene Proben laden</span>
            </a>
        </div>
    <?php endif; ?>

    <div class="minimal-separator">
        <div class="separator-line"></div>
        <span class="separator-text">HEUTE</span>
        <div class="separator-line"></div>
    </div>
</div>

<style>
    .date-separator-wrapper {
        margin: var(--space-6) 0;
    }

    /* Position past rehearsals section */
    .past-rehearsals-section {
        margin-top: var(--space-6);
    }

    /* Ensure container can accommodate off-screen content */
    .container-app {
        position: relative;
    }

    /* Make sure button in section has proper styling */
    .past-rehearsals-section .load-past-button-wrapper {
        margin-bottom: var(--space-4);
    }

    .load-past-button-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: var(--space-4);
    }

    .load-past-button {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-3) var(--space-4);
        font-size: var(--font-size-sm);
        background: var(--color-bg-primary);
        border: 1px solid var(--color-border);
        color: var(--color-text-secondary);
        border-radius: var(--radius-base);
        cursor: pointer;
        transition: all var(--transition-base);
    }

    .load-past-button:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .load-past-button.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    .load-past-button.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 12px;
        height: 12px;
        margin: -6px 0 0 -6px;
        border: 2px solid currentColor;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .minimal-separator {
        display: flex;
        align-items: center;
        gap: var(--space-4);
        margin: var(--space-4) var(--space-5);
    }

    .separator-line {
        flex: 1;
        height: 1px;
        background: var(--color-border);
    }

    .separator-text {
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
        font-weight: var(--font-weight-medium);
        padding: 0 var(--space-3);
        background: var(--color-bg-secondary);
    }

    @media print {
        .date-separator-wrapper {
            display: none;
        }
    }
</style>

<script>
    /**
     * Add element to the beginning of scroll container without affecting scroll position
     * @param {HTMLElement} scrollContainer - The scrollable container
     * @param {HTMLElement} newElement - The element to add
     */
    function prependWithScrollPreservation(scrollContainer, newElement) {
        const scrollTop = window.scrollY;
        const isAtTop = scrollTop === 0;

        if (isAtTop) {
            // Special handling when at the very top to prevent any visible movement
            const originalScrollBehavior = document.documentElement.style.scrollBehavior;
            const originalTransition = document.documentElement.style.transition;

            // Disable animations temporarily
            document.documentElement.style.scrollBehavior = 'auto';
            document.documentElement.style.transition = 'none';

            // Insert element and immediately adjust scroll in same frame
            scrollContainer.insertBefore(newElement, scrollContainer.firstChild);

            // Force layout calculation and get full height including margins
            const elementRect = newElement.getBoundingClientRect();

            // Set scroll position immediately using full rendered height including margins
            window.scrollTo(0, elementRect.height);

            // Restore styles after paint
            requestAnimationFrame(() => {
                document.documentElement.style.scrollBehavior = originalScrollBehavior;
                document.documentElement.style.transition = originalTransition;
            });
        } else {
            // Normal case - not at top
            const scrollHeight = document.documentElement.scrollHeight;

            scrollContainer.insertBefore(newElement, scrollContainer.firstChild);

            // Adjust scroll position to maintain view
            const newScrollHeight = document.documentElement.scrollHeight;
            const heightDifference = newScrollHeight - scrollHeight;
            window.scrollTo(0, scrollTop + heightDifference);
        }
    }

    /**
     * Add element to the end of scroll container (no scroll adjustment needed)
     * @param {HTMLElement} scrollContainer - The scrollable container
     * @param {HTMLElement} newElement - The element to add
     */
    function appendWithScrollPreservation(scrollContainer, newElement) {
        // Adding to bottom naturally preserves scroll position
        scrollContainer.appendChild(newElement);
    }

    /**
     * Generic function that handles both prepend and append
     * @param {HTMLElement} scrollContainer - The scrollable container
     * @param {HTMLElement} newElement - The element to add
     * @param {'prepend'|'append'} position - Where to add the element
     */
    function addElementWithScrollPreservation(scrollContainer, newElement, position = 'append') {
        if (position === 'prepend') {
            prependWithScrollPreservation(scrollContainer, newElement);
        } else {
            appendWithScrollPreservation(scrollContainer, newElement);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Position content to hide separator initially
        const separator = document.getElementById('dateSeparator');
        if (separator && !window.location.search.includes('showOld')) {
            // Scroll to position the separator just above the viewport, accounting for navbar
            setTimeout(function() {
                const separatorRect = separator.getBoundingClientRect();
                const navbar = document.querySelector('.top-nav, nav');
                const navbarHeight = navbar ? navbar.offsetHeight : 64; // Default 64px if not found

                if (separatorRect.top < window.innerHeight) {
                    // Position so first rehearsal is visible but not covered by navbar
                    window.scrollTo({
                        top: window.scrollY + separatorRect.bottom - navbarHeight,
                        behavior: 'auto'
                    });
                }

                // Dispatch event to signal scroll positioning is complete
                document.dispatchEvent(new CustomEvent('scrollPositioningComplete'));
            }, 100);
        }

        const loadPastButton = document.getElementById('loadPastButton');

        if (loadPastButton) {
            loadPastButton.addEventListener('click', function() {
                loadPastRehearsals(0); // Start with offset 0
            });
        }
    });

    // Remove any smooth scroll behavior globally
    document.documentElement.style.scrollBehavior = 'auto';
    document.body.style.scrollBehavior = 'auto';

    let currentOffset = 0;
    const rehearsalsPerPage = 10;

    function loadPastRehearsals(offset = 0) {
        const loadButton = document.getElementById('loadPastButton');
        const sectionButton = document.getElementById('loadPastButtonInSection');
        let pastSection = document.getElementById('pastRehearsalsSection');

        // Loading state
        const activeButton = sectionButton || loadButton;
        if (activeButton) {
            activeButton.classList.add('loading');
            activeButton.disabled = true;
        }

        // Scroll container
        const scrollContainer = document.documentElement;
        const containerApp = document.querySelector('.container-app');

        // AJAX request setup
        const currentPath = window.location.pathname;
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('ajax', '1');
        urlParams.set('pastOnly', '1');
        urlParams.set('offset', offset);
        urlParams.set('limit', rehearsalsPerPage);

        const requestUrl = currentPath + '?' + urlParams.toString();

        fetch(requestUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(async (response) => {
                const contentType = response.headers.get('content-type') || '';
                const text = await response.text().catch(() => '');
                const isJson = contentType.includes('application/json');
                const parseJson = () => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        return null;
                    }
                };
                if (!response.ok) {
                    const data = isJson ? parseJson() : null;
                    const message = (data && (data.message || data.error)) || text || `HTTP ${response.status}`;
                    throw new Error(message);
                }
                return isJson ? (parseJson() || {
                    success: false
                }) : {
                    success: false
                };
            })
            .then(data => {
                if (data.success) {
                    if (offset === 0) {
                        // Initial past rehearsals load
                        if (!pastSection) {
                            const separator = document.getElementById('dateSeparator');
                            pastSection = document.createElement('div');
                            pastSection.id = 'pastRehearsalsSection';
                            pastSection.className = 'past-rehearsals-section';
                            separator.parentNode.insertBefore(pastSection, separator);
                        }

                        pastSection.innerHTML = '';

                        // Sticky button wrapper
                        const newButtonWrapper = document.createElement('div');
                        newButtonWrapper.className = 'load-past-button-wrapper';
                        newButtonWrapper.innerHTML = '<button class="load-past-button" id="loadPastButtonInSection"><i class="fas fa-history"></i><span>Vergangene Proben laden</span></button>';

                        // Rehearsals container
                        const rehearsalsContainer = document.createElement('div');
                        rehearsalsContainer.className = 'past-rehearsals-content';
                        rehearsalsContainer.innerHTML = data.html;

                        // Measure button for scroll adjustment
                        const originalButton = document.getElementById('loadPastButton');
                        let originalButtonHeight = 0;
                        if (originalButton && originalButton.parentElement) {
                            originalButtonHeight = originalButton.parentElement.getBoundingClientRect().height;
                        }

                        pastSection.appendChild(newButtonWrapper);
                        pastSection.appendChild(rehearsalsContainer);

                        // Adjust scroll for content insertion
                        if (containerApp) {
                            const scrollTop = scrollContainer.scrollTop;
                            // Force layout and get full height including margins
                            const heightAdded = pastSection.getBoundingClientRect().height;
                            // Subtract the height of the original button that will be hidden
                            const netHeightChange = heightAdded - originalButtonHeight;
                            // Adjust scroll
                            scrollContainer.scrollTop = scrollTop + netHeightChange;
                        }

                        if (originalButton) {
                            originalButton.parentElement.style.display = 'none';
                        }
                    } else {
                        // Load more rehearsals
                        const contentWrapper = document.createElement('div');
                        contentWrapper.innerHTML = data.html;

                        // Prepend new content
                        const rehearsalsContainer = pastSection ? pastSection.querySelector('.past-rehearsals-content') : null;

                        if (rehearsalsContainer) {
                            prependWithScrollPreservation(rehearsalsContainer, contentWrapper);
                        } else {
                            console.warn('Could not find rehearsals container for load more');
                        }
                    }

                    currentOffset = offset + rehearsalsPerPage;

                    // Update button state
                    const sectionButton = document.getElementById('loadPastButtonInSection');
                    const originalButton = document.getElementById('loadPastButton');

                    const activeButton = sectionButton || originalButton;

                    if (activeButton) {
                        activeButton.classList.remove('loading');
                        activeButton.disabled = false;

                        if (data.hasMore) {
                            activeButton.innerHTML = '<i class="fas fa-history"></i><span>Weitere vergangene Proben laden</span>';
                            activeButton.onclick = null;
                            activeButton.addEventListener('click', function() {
                                loadPastRehearsals(currentOffset);
                            });
                        } else {
                            // Hide button given no further data
                            activeButton.style.display = 'none';
                            // Also hide the wrapper if it exists
                            const buttonWrapper = activeButton.closest('.load-past-button-wrapper');
                            if (buttonWrapper) {
                                buttonWrapper.style.display = 'none';
                            }
                        }
                    }

                    // Clean up any remaining buttons
                    if (originalButton && sectionButton && originalButton !== sectionButton) {
                        originalButton.classList.remove('loading');
                        originalButton.disabled = false;
                    }

                    // Position maintained by preservation functions

                } else {
                    console.error('Failed to load past rehearsals:', data.message);
                    const activeButton = document.getElementById('loadPastButtonInSection') || loadButton;
                    if (activeButton) {
                        activeButton.classList.remove('loading');
                        activeButton.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading past rehearsals:', error);
                const activeButton = document.getElementById('loadPastButtonInSection') || loadButton;
                if (activeButton) {
                    activeButton.classList.remove('loading');
                    activeButton.disabled = false;
                }
            });
    }
</script>