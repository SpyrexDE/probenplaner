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
                    }).catch(error => {
                        // Transient server errors during SW update checks must not surface as modals
                        console.warn('Service Worker update check failed (silent):', error);
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

                            if (window.notifyError) {
                                window.notifyError('Cache konnte nicht vollständig gelöscht werden.' + (event.data.error ? ' ' + event.data.error : ''))
                                    .then(() => {
                                        window.location.reload(true);
                                    });
                            } else {
                                Swal.fire({
                                    title: 'Update-Fehler',
                                    text: 'Cache konnte nicht vollständig gelöscht werden, aber das Update wird trotzdem fortgesetzt.',
                                    icon: 'warning', // Keeping warning icon if fallback
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#478cf4'
                                }).then(() => {
                                    window.location.reload(true);
                                });
                            }
                        }
                    }
                });

            }).catch(function(error) {
                // SW installation errors (e.g. transient server errors) must not surface to users
                console.warn('Service Worker registration failed (silent):', error);
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

    // PWA Installation (sidebar card, see components/pwa-install-card.php)
    // The card is rendered hidden and only revealed when installing is actually possible:
    //  a) Chrome/Edge/Samsung fired beforeinstallprompt, or
    //  b) iOS/iPadOS Safari (no prompt API there -> step-by-step guide).
    // It is not rendered at all inside the JOinJMD app (see sidebar-content.php).
    (function() {
        var installCard = document.getElementById('pwa-install-card');
        var deferredPrompt = null;
        var STANDALONE_MODES = ['standalone', 'fullscreen', 'minimal-ui', 'window-controls-overlay'];

        function matchesMedia(query) {
            try {
                return !!(window.matchMedia && window.matchMedia(query).matches);
            } catch (e) {
                return false;
            }
        }

        function isStandalone() {
            for (var i = 0; i < STANDALONE_MODES.length; i++) {
                if (matchesMedia('(display-mode: ' + STANDALONE_MODES[i] + ')')) return true;
            }
            // iOS home screen web app
            return window.navigator.standalone === true;
        }

        function isIOS() {
            var ua = navigator.userAgent || '';
            return /iPad|iPhone|iPod/.test(ua) ||
                // iPadOS requests the desktop site and reports itself as a Mac
                (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        }

        function isIOSSafari() {
            if (!isIOS()) return false;
            var ua = navigator.userAgent || '';
            // Real Safari sends "Version/x ... Safari/y". WKWebView-based in-app browsers
            // (JOinJMD, Instagram, Facebook, ...) usually lack it; other iOS browsers add their own token.
            if (!/Version\/[\d.]+.*Safari\//.test(ua)) return false;
            return !/CriOS|FxiOS|EdgiOS|OPiOS|OPT\/|YaBrowser|DuckDuckGo|Ddg\/|GSA\/|FBAN|FBAV|FBIOS|Instagram|Line\/|LinkedInApp|Twitter|Snapchat|Pinterest|MicroMessenger|WhatsApp/i.test(ua);
        }

        function showCard() {
            if (!installCard || isStandalone()) return;
            installCard.classList.remove('hidden');
        }

        function hideCard() {
            if (installCard) installCard.classList.add('hidden');
        }

        function guideStep(iconClass, text, hint) {
            return '<li class="pwa-guide-step">' +
                '<span class="pwa-guide-step-icon" aria-hidden="true"><i class="' + iconClass + '"></i></span>' +
                '<span class="pwa-guide-step-text">' + text +
                (hint ? '<span class="pwa-guide-hint">' + hint + '</span>' : '') +
                '</span></li>';
        }

        function showInstallGuide(kind) {
            var intro, steps, shortText;

            if (kind === 'ios') {
                intro = 'Füge den Probenplaner zu deinem Home-Bildschirm hinzu. Er startet dann wie eine App – im Vollbild und ohne Adressleiste.';
                steps = guideStep('fa-solid fa-arrow-up-from-bracket', 'Tippe in Safari auf <strong>Teilen</strong>',
                        'Das Quadrat mit Pfeil nach oben – je nach iOS-Version unten in der Leiste, oben rechts oder im Menü „•••“.') +
                    guideStep('fa-regular fa-square-plus', 'Wähle <strong>Zum Home-Bildschirm</strong>',
                        'Nicht zu sehen? In der Liste etwas nach unten scrollen.') +
                    guideStep('fa-solid fa-check', 'Tippe auf <strong>Hinzufügen</strong>',
                        'Danach findest du den Probenplaner auf deinem Home-Bildschirm.');
                shortText = 'Tippe in Safari auf „Teilen“ und dann auf „Zum Home-Bildschirm“.';
            } else {
                intro = 'Dein Browser bietet die Installation über sein Menü an.';
                steps = guideStep('fa-solid fa-ellipsis-vertical', 'Öffne das <strong>Browser-Menü</strong>',
                        'Meist oben rechts (⋮ oder •••).') +
                    guideStep('fa-solid fa-download', 'Wähle <strong>App installieren</strong>',
                        'Je nach Browser auch „Zum Startbildschirm hinzufügen“.') +
                    guideStep('fa-solid fa-check', 'Bestätige mit <strong>Installieren</strong>', '');
                shortText = 'Öffne das Browser-Menü und wähle „App installieren“.';
            }

            // window.notifyInfo is NOT a fallback: it calls Swal internally as well.
            // If SweetAlert2 is missing (CDN blocked/offline) or throws, use a plain alert.
            var shown = false;
            if (window.Swal && typeof window.Swal.fire === 'function') {
                var options = {
                    title: 'App installieren',
                    html: '<div class="pwa-guide">' +
                        '<div class="pwa-guide-app"><img src="/assets/icons/pwa/icon-192.png" alt="" width="64" height="64"></div>' +
                        '<p class="pwa-guide-intro">' + intro + '</p>' +
                        '<ol class="pwa-guide-steps">' + steps + '</ol>' +
                        '</div>',
                    showCloseButton: true,
                    closeButtonAriaLabel: 'Schließen',
                    confirmButtonText: 'Verstanden',
                    confirmButtonColor: 'var(--color-primary, #478cf4)',
                    customClass: {
                        popup: 'pwa-guide-popup'
                    }
                };
                if (matchesMedia('(prefers-reduced-motion: reduce)')) {
                    options.showClass = {
                        backdrop: 'swal2-noanimation',
                        popup: '',
                        icon: ''
                    };
                    options.hideClass = {
                        popup: ''
                    };
                }
                try {
                    window.Swal.fire(options);
                    shown = true;
                } catch (err) {
                    console.warn('PWA install guide dialog failed:', err);
                }
            }
            if (!shown) {
                window.alert(shortText);
            }
        }

        window.addEventListener('beforeinstallprompt', function(e) {
            // Suppress the browser's mini-infobar; the sidebar card is our entry point
            e.preventDefault();
            if (isStandalone()) return;
            deferredPrompt = e;
            showCard();
        });

        // Called from the card's onclick. Never a silent no-op.
        window.installPWA = function() {
            if (!deferredPrompt) {
                showInstallGuide(isIOSSafari() ? 'ios' : 'browser');
                return;
            }

            // prompt() may only be called once per event
            var promptEvent = deferredPrompt;
            deferredPrompt = null;

            var promptResult;
            try {
                promptResult = promptEvent.prompt();
            } catch (err) {
                console.warn('PWA install prompt failed:', err);
                showInstallGuide('browser');
                return;
            }

            Promise.resolve(promptResult)
                .then(function() {
                    return promptEvent.userChoice;
                })
                .then(function(choiceResult) {
                    console.log('PWA install prompt outcome:', choiceResult ? choiceResult.outcome : 'unknown');
                    // The event is used up; the browser fires a new one once installing is possible again
                    hideCard();
                })
                .catch(function(err) {
                    console.warn('PWA install prompt failed:', err);
                    showInstallGuide('browser');
                });
        };

        // Hide install card once the app has been installed
        window.addEventListener('appinstalled', function() {
            console.log('PWA was installed');
            deferredPrompt = null;
            hideCard();

            // notifySuccess needs SweetAlert2; skip the toast if it is unavailable
            if (window.notifySuccess && window.Swal) {
                try {
                    window.notifySuccess('App erfolgreich installiert!');
                } catch (err) {}
            }
        });

        // Hide when the page switches into the installed app window
        STANDALONE_MODES.forEach(function(mode) {
            try {
                var mq = window.matchMedia('(display-mode: ' + mode + ')');
                var onChange = function(ev) {
                    if (ev.matches) hideCard();
                };
                if (mq.addEventListener) {
                    mq.addEventListener('change', onChange);
                } else if (mq.addListener) {
                    mq.addListener(onChange);
                }
            } catch (e) {}
        });

        if (isStandalone()) {
            hideCard();
        } else if (isIOSSafari()) {
            // No beforeinstallprompt on iOS: offer the manual "Zum Home-Bildschirm" guide
            showCard();
        }
    })();
</script>