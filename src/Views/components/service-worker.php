<?php

/**
 * Service Worker Component
 * PWA service worker registration and management
 */
?>

<script>
    // PWA Service Worker Registration with Version Control
    if ('serviceWorker' in navigator) {
        // Store current app version from server (use tag for PWA stability)
        window.APP_VERSION = '<?php echo \App\Core\Version::getTag(); ?>';
        window.APP_ENV = '<?php echo APP_ENV; ?>';

        window.addEventListener('load', function() {
            // Register dynamic service worker (no timestamp - let version handle updates)
            const swUrl = '/dynamic-sw.php';

            navigator.serviceWorker.register(swUrl, {
                scope: '/',
                updateViaCache: 'none' // Always check for SW updates
            }).then(function(registration) {
                console.log('Dynamic Service Worker registered:', registration.scope, 'Version:', window.APP_VERSION);

                // Check for updates immediately and periodically
                function checkForUpdates() {
                    registration.update().then(() => {
                        console.log('Service Worker update check completed');
                    });
                }

                // Initial update check
                checkForUpdates();

                // Periodic update checks every 30 seconds in production/test
                if (window.APP_ENV !== 'development') {
                    setInterval(checkForUpdates, 30000);
                }

                // Listen for service worker updates (only show notifications in production)
                registration.addEventListener('updatefound', function() {
                    const newWorker = registration.installing;
                    console.log('New Service Worker found:', newWorker);

                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // VERIFY VERSION before showing popup to avoid "ghost updates"
                            // This happens if dynamic-sw.php changed but VERSION is actually the same
                            const messageChannel = new MessageChannel();
                            messageChannel.port1.onmessage = (event) => {
                                if (event.data && event.data.type === 'VERSION_INFO') {
                                    const newVersion = event.data.version;
                                    console.log('New SW Version:', newVersion, 'Current App Version:', window.APP_VERSION);

                                    if (newVersion !== window.APP_VERSION) {
                                        // New version available, show update notification
                                        showUpdatePopup();
                                    } else {
                                        console.log('Service Worker updated but version is identical. Skipping notification.');
                                    }
                                }
                            };

                            newWorker.postMessage({
                                type: 'CHECK_VERSION'
                            }, [messageChannel.port2]);
                        }
                    });
                });

                function showUpdatePopup() {
                    if (window.Swal) {
                        Swal.fire({
                            title: 'Update verfügbar',
                            text: 'Eine neue Version der App ist verfügbar. Die Seite wird neu geladen um die neueste Version zu laden.',
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Jetzt aktualisieren',
                            cancelButtonText: 'Später',
                            confirmButtonColor: '#478cf4',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Show loading state
                                Swal.fire({
                                    title: 'Update wird durchgeführt...',
                                    text: 'Bitte warten Sie, während die neue Version geladen wird.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    willOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                // Request service worker to clear old caches
                                if (navigator.serviceWorker.controller) {
                                    navigator.serviceWorker.controller.postMessage({
                                        type: 'CLEAR_OLD_CACHES'
                                    });
                                } else {
                                    // Fallback if no service worker controller
                                    window.location.reload(true);
                                }
                            }
                        });
                    } else {
                        // Fallback if confirm is not available
                        if (confirm('Eine neue Version ist verfügbar. Jetzt aktualisieren?')) {
                            if (navigator.serviceWorker.controller) {
                                navigator.serviceWorker.controller.postMessage({
                                    type: 'CLEAR_OLD_CACHES'
                                });
                            } else {
                                window.location.reload(true);
                            }
                        }
                    }
                }

                // Listen for messages from service worker
                navigator.serviceWorker.addEventListener('message', event => {
                    if (event.data && event.data.type === 'VERSION_AVAILABLE') {
                        console.log('New service worker version available:', event.data.version);
                        // Version is available but caches are not cleared yet - user needs to confirm
                    } else if (event.data && event.data.type === 'CACHE_CLEARED') {
                        console.log('Service Worker caches cleared:', event.data.success);

                        if (event.data.success) {
                            // Cache clearing successful, now reload to get fresh content
                            console.log('Cache clearing successful, reloading page...');
                            window.location.reload(true);
                        } else {
                            // Cache clearing failed, show error and reload anyway
                            console.error('Cache clearing failed:', event.data.error);
                            Swal.fire({
                                title: 'Update-Fehler',
                                text: 'Cache konnte nicht vollständig gelöscht werden, aber das Update wird trotzdem fortgesetzt.',
                                icon: 'warning',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#478cf4'
                            }).then(() => {
                                window.location.reload(true);
                            });
                        }
                    }
                });

            }).catch(function(error) {
                console.error('Service Worker registration failed:', error);
                // In development, this is expected and okay
                if (window.APP_ENV === 'development') {
                    console.log('Service Worker registration failed in development - this is normal');
                }
            });

            // Listen for service worker controller changes (when SW takes control)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('Service Worker controller changed - new version is now active');
                // Optional: Reload the page to use the new service worker
                // window.location.reload();
            });
        });

        // Manual version check function (can be called from anywhere)
        window.checkAppVersion = function() {
            if (!navigator.serviceWorker.controller) {
                console.log('No service worker controller available for version check');
                return;
            }

            const messageChannel = new MessageChannel();
            messageChannel.port1.onmessage = function(event) {
                if (event.data && event.data.type === 'VERSION_INFO') {
                    console.log('Current SW version:', event.data.version);
                    console.log('Client app version:', window.APP_VERSION);

                    if (event.data.version !== window.APP_VERSION) {
                        console.log('Version mismatch detected - triggering update');
                        location.reload(true);
                    }
                }
            };

            navigator.serviceWorker.controller.postMessage({
                    type: 'CHECK_VERSION'
                },
                [messageChannel.port2]
            );
        };
    }

    // PWA Installation Prompt
    let deferredPrompt;
    const installCard = document.getElementById('pwa-install-card');

    window.addEventListener('beforeinstallprompt', function(e) {
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        // Show the install card
        if (installCard) {
            installCard.style.display = 'block';
        }
    });

    function installPWA() {
        if (deferredPrompt) {
            // Show the install prompt
            deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            deferredPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                deferredPrompt = null;
                if (installCard) {
                    installCard.style.display = 'none';
                }
            });
        }
    }

    // Hide install card if app is already installed
    window.addEventListener('appinstalled', function() {
        console.log('PWA was installed');
        if (installCard) {
            installCard.style.display = 'none';
        }
        deferredPrompt = null;

        // Show success message
        if (window.notifySuccess) {
            window.notifySuccess('App erfolgreich installiert!');
        }
    });

    // Hide install card on mobile if already in standalone mode
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
        if (installCard) {
            installCard.style.display = 'none';
        }
    }
</script>