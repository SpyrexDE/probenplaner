<?php

/**
 * Sidebar Stats JavaScript Component
 * Handles sidebar statistics loading, display, and UI interactions
 */
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to trigger container-app fade-in
        function triggerContainerFadeIn() {
            const containerApps = document.querySelectorAll('.container-app');
            containerApps.forEach(function(container) {
                container.classList.add('fade-in');
            });
        }

        // Check if page has scroll positioning logic (like date separator)
        const separator = document.getElementById('dateSeparator');
        if (separator) {
            // Listen for scroll positioning completion, then fade in
            document.addEventListener('scrollPositioningComplete', function() {
                triggerContainerFadeIn();
            });

            // Fallback timeout in case the event doesn't fire
            setTimeout(function() {
                triggerContainerFadeIn();
            }, 300);
        } else {
            // No scroll positioning needed, fade in immediately
            setTimeout(function() {
                triggerContainerFadeIn();
            }, 50); // Small delay to ensure DOM is fully ready
        }

        // Robust sidebar toggle setup
        const wrapper = document.getElementById('wrapper');
        const sidebar = document.getElementById('sidebar-wrapper');
        const menuToggle = document.getElementById('menu-toggle');


        // Primary toggle function
        function toggleSidebar() {
            if (wrapper) {
                wrapper.classList.toggle('toggled');
            }
        }

        // Setup menu toggle with multiple handlers for reliability
        if (menuToggle && wrapper) {
            // Modern event listener
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar();
            });

            // Backup onclick property
            menuToggle.onclick = function(e) {
                e.preventDefault();
                toggleSidebar();
                return false;
            };
        }

        // Outside click and escape key handling
        if (wrapper && sidebar && menuToggle) {
            document.addEventListener('click', function(e) {
                if (wrapper.classList.contains('toggled') &&
                    !sidebar.contains(e.target) &&
                    !menuToggle.contains(e.target)) {
                    wrapper.classList.remove('toggled');
                }
            });

            sidebar.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && wrapper.classList.contains('toggled')) {
                    wrapper.classList.remove('toggled');
                }
            });
        }

        // Expose globally for components
        window.toggleSidebar = toggleSidebar;
        window.toggleSidebarMenu = toggleSidebar;

        // Handle window resize
        window.addEventListener('resize', function() {
            const w = document.getElementById('wrapper');
            // Only close sidebar on mobile/tablet breakpoint
            if (window.innerWidth < 1200 && w) {
                w.classList.remove('toggled');
            }
        });

        // Function to load user statistics with retry mechanism
        window.loadUserStats = function(retryCount = 0) {
            const MAX_RETRIES = 2;

            // Set loading state first (but only on initial call, not retries)
            if (retryCount === 0) {
                setStatsLoadingState(true);
            }

            // Use statistics passed from the view instead of making API call
            <?php
            $sidebarStats = $sidebarStats ?? null;
            ?>

            <?php if ($sidebarStats !== null): ?>
                // We have statistics passed from the view, use them directly
                const stats = <?= json_encode($sidebarStats) ?>;

                // Update the sidebar with the passed statistics and rehearsal info
                updateStatsDisplay(stats);
            <?php else: ?>
                // No statistics passed, make API call as fallback
                <?php $orchestraBase = ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? ''); ?>
                fetch('/<?= $orchestraBase ?>/api/user-stats', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache'
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
                            const message = (data && (data.error || data.message)) || text || `HTTP ${response.status}`;
                            throw new Error(message);
                        }
                        return isJson ? (parseJson() || {
                            success: false
                        }) : {
                            success: false
                        };
                    })
                    .then(data => {
                        if (data.success && data.stats) {
                            updateStatsDisplay(data.stats);
                            setStatsLoadingState(false);
                        } else {
                            console.error('API returned error:', data.error || 'Unbekannter Fehler');
                            // Try again if we haven't exceeded max retries
                            if (retryCount < MAX_RETRIES) {
                                setTimeout(() => window.loadUserStats(retryCount + 1), 1000 * (retryCount + 1));
                            } else {
                                // Fallback to zero stats but indicate error state
                                updateStatsDisplay({
                                    attending: 0,
                                    not_attending: 0,
                                    no_response: 0,
                                    total: 0
                                }, true);
                                setStatsLoadingState(false);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Failed to load stats via API:', error);
                        // Try again if we haven't exceeded max retries
                        if (retryCount < MAX_RETRIES) {
                            setTimeout(() => window.loadUserStats(retryCount + 1), 1000 * (retryCount + 1));
                        } else {
                            // Fallback to zero stats but indicate error state
                            updateStatsDisplay({
                                attending: 0,
                                not_attending: 0,
                                no_response: 0,
                                total: 0
                            }, true);
                            setStatsLoadingState(false);
                        }
                    });
            <?php endif; ?>
        }

        // Function to set loading state for stats
        function setStatsLoadingState(isLoading) {
            const dateElement = document.getElementById('next-rehearsal-date');
            const attendingText = document.getElementById('stats-attending');
            const notAttendingText = document.getElementById('stats-not-attending');
            const noResponseText = document.getElementById('stats-no-response');

            if (isLoading) {
                // Show loading state
                if (dateElement) dateElement.textContent = 'Lade...';
                if (attendingText) attendingText.textContent = '-';
                if (notAttendingText) notAttendingText.textContent = '-';
                if (noResponseText) noResponseText.textContent = '-';
            }
        }

        // Function to update stats display
        function updateStatsDisplay(stats, isError = false) {
            const total = stats.total || 1; // Avoid division by zero
            const attendingPercent = ((stats.attending || 0) / total) * 100;
            const notAttendingPercent = ((stats.not_attending || 0) / total) * 100;
            const noResponsePercent = ((stats.no_response || 0) / total) * 100;

            // Update progress bar segments
            const attendingSegment = document.querySelector('.sidebar-stats-segment.attending');
            const notAttendingSegment = document.querySelector('.sidebar-stats-segment.not-attending');
            const noResponseSegment = document.querySelector('.sidebar-stats-segment.no-response');

            if (attendingSegment) attendingSegment.style.width = attendingPercent + '%';
            if (notAttendingSegment) notAttendingSegment.style.width = notAttendingPercent + '%';
            if (noResponseSegment) noResponseSegment.style.width = noResponsePercent + '%';

            // Update legend numbers
            const attendingText = document.getElementById('stats-attending');
            const notAttendingText = document.getElementById('stats-not-attending');
            const noResponseText = document.getElementById('stats-no-response');

            if (attendingText) attendingText.textContent = stats.attending || 0;
            if (notAttendingText) notAttendingText.textContent = stats.not_attending || 0;
            if (noResponseText) noResponseText.textContent = stats.no_response || 0;

            // If this is conductor stats, update the next rehearsal display
            const dateElement = document.getElementById('next-rehearsal-date');
            const titleElement = document.querySelector('.sidebar-stats-header .sidebar-stats-title');

            if (stats.next_rehearsal) {
                if (dateElement) {
                    dateElement.textContent = stats.next_rehearsal.date_formatted || stats.next_rehearsal.date;
                }

                if (titleElement) {
                    const rehearsalType = stats.next_rehearsal.type || <?= json_encode(\App\Core\RehearsalTypeManager::TYPE_REHEARSAL) ?>;
                    titleElement.textContent = rehearsalType;
                }
            } else if (isError && dateElement) {
                // Show error state for conductor view
                dateElement.textContent = 'Fehler beim Laden';
            } else if (dateElement && titleElement) {
                // Clear loading text if no rehearsal and no error (conductor view)
                dateElement.textContent = 'Keine Proben';
            }
        }

        // Update UI visibility based on current route
        updateUIForCurrentRoute();

        // Add event listeners to all internal links for route-based UI updates
        document.querySelectorAll('a[href^="/"]').forEach(function(link) {
            link.addEventListener('click', function() {
                // Get the target route from the link's href
                const route = this.getAttribute('href');

                // Update UI visibility after a short delay to allow navigation
                setTimeout(function() {
                    updateUIForCurrentRoute();
                }, 100);
            });
        });

        // Load user statistics for all logged-in users
        <?php if (isset($_SESSION['user_id'])): ?>
            // Load stats immediately when page loads
            loadUserStats();

            // Also update stats when page becomes visible (e.g., after tab switch)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && typeof window.loadUserStats === 'function') {
                    setTimeout(function() {
                        window.loadUserStats();
                    }, 200);
                }
            });
        <?php endif; ?>
    });

    // Function to update UI visibility based on current route
    function updateUIForCurrentRoute() {
        const currentRoute = window.location.pathname;

        // Determine if buttons should be shown based on route
        const showHistoryButton = currentRoute.startsWith('/promises') || currentRoute.startsWith('/rehearsals');

        // Show help button on main feature pages
        const helpRelevantPaths = ['/promises', '/promises/leader', '/promises/admin',
            '/rehearsals', '/probenplan', '/profile', '/conductor/profile'
        ];

        const showHelpButton = helpRelevantPaths.some(path => currentRoute === path) ||
            currentRoute.startsWith('/promises/') ||
            currentRoute.startsWith('/rehearsals/');

        // Update UI elements visibility
        document.querySelectorAll('.top-nav-icon.fa-history').forEach(function(element) {
            element.style.display = showHistoryButton ? 'inline-block' : 'none';
        });

        document.querySelectorAll('.top-nav-icon.fa-question-circle').forEach(function(element) {
            element.style.display = showHelpButton ? 'inline-block' : 'none';
        });
    }

    // Update UI immediately when script loads
    updateUIForCurrentRoute();
</script>