<?php

/**
 * Minimal Date Separator Component
 * Simple line separator with "HEUTE" text in the middle
 */

?>

<div class="date-separator-wrapper" id="dateSeparator">
    <!-- Past rehearsals load trigger -->
    <!-- Past rehearsals load trigger -->
    <?php if ($hasPastRehearsals ?? false): ?>
        <div class="load-past-button-wrapper">
            <button class="load-past-button" id="loadPastButton">
                <i class="fas fa-history"></i>
                <span>Vergangene Proben laden</span>
            </button>
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
    document.addEventListener('DOMContentLoaded', function() {
        var separator = document.getElementById('dateSeparator');
        if (separator) {
            setTimeout(function() {
                var separatorRect = separator.getBoundingClientRect();
                var navbar = document.querySelector('.top-nav, nav');
                var navbarHeight = navbar ? navbar.offsetHeight : 64;

                if (separatorRect.top < window.innerHeight) {
                    window.scrollTo({ top: window.scrollY + separatorRect.bottom - navbarHeight, behavior: 'auto' });
                }
                document.dispatchEvent(new CustomEvent('scrollPositioningComplete'));
            }, 100);
        }

        var loadPastButton = document.getElementById('loadPastButton');
        if (loadPastButton) {
            loadPastButton.addEventListener('click', function() {
                loadPastViaLazySection();
            });
        }
    });

    document.documentElement.style.scrollBehavior = 'auto';
    document.body.style.scrollBehavior = 'auto';

    function loadPastViaLazySection() {
        var separator = document.getElementById('dateSeparator');
        var btn = document.getElementById('loadPastButton');
        if (!separator) return;

        // Already loaded
        if (document.getElementById('pastRehearsalsLazy')) return;

        // Hide button
        if (btn && btn.parentElement) btn.parentElement.style.display = 'none';

        // Build lazy-section URL
        var base = '/' + <?= json_encode(($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')) ?>;
        var url = <?= json_encode($pastLazyUrl ?? null) ?>;
        if (!url) {
            url = base + '/rehearsals/past?offset=0';
        }

        // Create lazy-section container
        var section = document.createElement('div');
        section.id = 'pastRehearsalsLazy';
        section.setAttribute('data-lazy-section', '');
        section.setAttribute('data-lazy-url', url);
        section.setAttribute('data-lazy-id', 'past-rehearsals');
        section.setAttribute('data-lazy-prepend', 'true');
        section.setAttribute('data-lazy-skeleton-type', 'cards');
        section.setAttribute('data-lazy-skeleton-count', '3');

        // Skeleton placeholder
        section.innerHTML =
            '<div class="lazy-skeleton">' +
                '<div class="lazy-skeleton-card"><div class="lazy-skeleton-bar bar-title"></div><div class="lazy-skeleton-bar bar-text"></div><div class="lazy-skeleton-bar bar-subtitle"></div><div class="lazy-skeleton-bar bar-short"></div></div>'.repeat(3) +
            '</div>';

        // Insert before separator — scroll preservation
        var scrollTop = window.scrollY;
        separator.parentNode.insertBefore(section, separator);
        var heightAdded = section.getBoundingClientRect().height;
        window.scrollTo(0, scrollTop + heightAdded);

        // Re-apply filters after each batch loads
        section.addEventListener('lazy:loaded', function() {
            var search = document.getElementById('bulkSearch');
            if (window.BulkMgr && (search?.value || Object.keys(window.BulkMgr._activeFilters || {}).length)) {
                window.BulkMgr.search(search?.value || '');
            }
        });

        // Kick off lazy loading
        if (window.LazySection) {
            LazySection.observe(section);
        }
    }
</script>