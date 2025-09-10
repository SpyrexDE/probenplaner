<?php
/**
 * Dynamic Service Worker Generator
 * Generates service worker with proper versioning and environment handling
 */

// Include bootstrap to get access to configuration and version info
require_once '../bootstrap.php';

use App\Core\Version;

// Set proper content type for JavaScript
header('Content-Type: application/javascript');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Get current version information (use tag for PWA stability)
$appVersion = Version::getTag();
$isDevelopment = (APP_ENV === 'development');

// Generate cache name with version
$cacheVersion = $isDevelopment ? 'dev-no-cache' : 'probenplaner-' . str_replace(['v', '.', '-'], ['', '_', '_'], $appVersion);

?>
// Dynamic Service Worker for Probenplaner PWA  
// Environment: <?= APP_ENV ?>
// Version: <?= $appVersion ?> 

const APP_VERSION = '<?= $appVersion ?>';
const IS_DEVELOPMENT = <?= $isDevelopment ? 'true' : 'false' ?>;
const CACHE_NAME = '<?= $cacheVersion ?>';
const OFFLINE_URL = '/offline.html';

console.log('Service Worker loading with version:', APP_VERSION, 'Environment:', '<?= APP_ENV ?>');

<?php if ($isDevelopment): ?>
// DEVELOPMENT MODE: Disable all caching
console.log('Development mode: Caching disabled');

// Install event - no caching in development
self.addEventListener('install', event => {
    console.log('Service Worker [DEV]: Installing - no caching');
    // Skip waiting to activate immediately
    self.skipWaiting();
});

// Activate event - clear all caches in development
self.addEventListener('activate', event => {
    console.log('Service Worker [DEV]: Activating - clearing all caches');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            // Delete all caches in development
            return Promise.all(
                cacheNames.map(cache => {
                    console.log('Service Worker [DEV]: Deleting cache:', cache);
                    return caches.delete(cache);
                })
            );
        }).then(() => {
            console.log('Service Worker [DEV]: All caches cleared');
            return self.clients.claim();
        })
    );
});

// Fetch event - always fetch from network in development
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    console.log('Service Worker [DEV]: Network-only fetch:', event.request.url);
    
    // Always fetch fresh from network, no caching
    event.respondWith(
        fetch(event.request.clone()).catch(error => {
            console.log('Service Worker [DEV]: Network request failed:', event.request.url, error);
            
            // Return offline page for navigation requests if available
            if (event.request.mode === 'navigate') {
                return fetch(OFFLINE_URL).catch(() => {
                    return new Response('Offline - Development Mode', {
                        status: 200,
                        statusText: 'OK',
                        headers: { 'Content-Type': 'text/html' }
                    });
                });
            }
            
            throw error;
        })
    );
});

<?php else: ?>
// PRODUCTION MODE: Full caching with version control

// Only cache true static assets - NO dynamic content or HTML pages
const STATIC_CACHE_URLS = [
    '/offline.html',
    '/assets/css/theme.css',
    '/assets/css/components.css', 
    '/assets/css/focus-removal.css',
    '/assets/css/promises-dashboard.css',
    '/assets/js/jquery.min.js',
    '/assets/js/notifications.js',
    '/assets/js/script.min.js',
    '/assets/js/collapse.js',
    '/assets/js/dropdown.js',
    '/assets/js/tooltip.js',
    '/assets/img/Logo.png',
    '/assets/fonts/FontAwesome.otf',
    '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    console.log('Service Worker [PROD]: Installing version', APP_VERSION);
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('Service Worker [PROD]: Caching static assets for version', APP_VERSION);
            return cache.addAll(STATIC_CACHE_URLS).catch(error => {
                console.error('Service Worker [PROD]: Failed to cache some assets:', error);
                // Cache individual assets that succeed, continue even if some fail
                return Promise.allSettled(
                    STATIC_CACHE_URLS.map(url => {
                        return cache.add(url).catch(err => {
                            console.warn('Failed to cache:', url, err);
                        });
                    })
                );
            });
        }).then(() => {
            console.log('Service Worker [PROD]: Installation complete for version', APP_VERSION);
            // Force activation of new service worker
            return self.skipWaiting();
        })
    );
});

// Activate event - clear old caches and take control
self.addEventListener('activate', event => {
    console.log('Service Worker [PROD]: Activating version', APP_VERSION);
    event.waitUntil(
        // Clear all old caches when activating new service worker
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        console.log('Service Worker [PROD]: Clearing old cache during activation:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => {
            console.log('Service Worker [PROD]: Taking control for version', APP_VERSION);
            // Take control of all pages immediately
            return self.clients.claim();
        }).then(() => {
            // Notify all clients about the new version
            return self.clients.matchAll().then(clients => {
                clients.forEach(client => {
                    client.postMessage({
                        type: 'VERSION_UPDATED',
                        version: APP_VERSION,
                        cacheCleared: true
                    });
                });
            });
        })
    );
});

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Never cache dynamic content - always fetch fresh from network
    const url = event.request.url;
    const isStaticAsset = (
        url.includes('/assets/css/') ||
        url.includes('/assets/js/') ||
        url.includes('/assets/img/') ||
        url.includes('/assets/fonts/') ||
        url.includes('/assets/icons/') ||
        url.endsWith('.css') ||
        url.endsWith('.js') ||
        url.endsWith('.png') ||
        url.endsWith('.jpg') ||
        url.endsWith('.jpeg') ||
        url.endsWith('.gif') ||
        url.endsWith('.svg') ||
        url.endsWith('.ico') ||
        url.endsWith('.woff') ||
        url.endsWith('.woff2') ||
        url.endsWith('.ttf') ||
        url.endsWith('.otf') ||
        url.endsWith('/manifest.json') ||
        url.endsWith('/offline.html')
    ) && !url.includes('.php');

    // Always fetch dynamic content fresh (PHP pages, API endpoints, etc.)
    if (!isStaticAsset) {
        event.respondWith(
            fetch(event.request).then(fetchResponse => {
                console.log('Service Worker [PROD]: Serving fresh dynamic content from network:', event.request.url);
                return fetchResponse;
            }).catch(error => {
                console.log('Service Worker [PROD]: Dynamic request failed:', event.request.url, error);
                // Return offline page only for navigation requests
                if (event.request.mode === 'navigate' || event.request.destination === 'document') {
                    return caches.match(OFFLINE_URL);
                }
                throw error;
            })
        );
        return;
    }

    // For static assets only, use cache-first strategy
    event.respondWith(
        caches.match(event.request).then(response => {
            // Return cached version if available
            if (response) {
                console.log('Service Worker [PROD]: Serving static asset from cache:', event.request.url);
                return response;
            }

            // Otherwise fetch from network
            return fetch(event.request).then(fetchResponse => {
                // Check if we received a valid response
                if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                    return fetchResponse;
                }

                // Clone the response (can only be consumed once)
                const responseToCache = fetchResponse.clone();

                // Cache the static asset for future requests
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseToCache);
                    console.log('Service Worker [PROD]: Cached new static asset:', event.request.url);
                });

                return fetchResponse;
            }).catch(error => {
                console.log('Service Worker [PROD]: Static asset request failed:', event.request.url, error);
                throw error;
            });
        })
    );
});
<?php endif; ?>

// Handle background sync (for future enhancement)
self.addEventListener('sync', event => {
    console.log('Service Worker: Background sync triggered:', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(
            // Add background sync logic here if needed
            Promise.resolve()
        );
    }
});

// Handle push notifications (for future enhancement)
self.addEventListener('push', event => {
    console.log('Service Worker: Push message received:', event);
    
    const options = {
        body: event.data ? event.data.text() : 'Neue Nachricht vom Probenplaner',
        icon: '/assets/img/Logo.png',
        badge: '/assets/img/Logo.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'open',
                title: 'Öffnen',
                icon: '/assets/img/Logo.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Probenplaner', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    console.log('Service Worker: Notification click received:', event);
    
    event.notification.close();

    if (event.action === 'open') {
        event.waitUntil(
            clients.openWindow('/')
        );
    } else {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Handle version checking and cache clearing messages from main thread
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'CHECK_VERSION') {
        // Respond with current version
        event.ports[0].postMessage({
            type: 'VERSION_INFO',
            version: APP_VERSION,
            isDevelopment: IS_DEVELOPMENT
        });
    } else if (event.data && event.data.type === 'CLEAR_OLD_CACHES') {
        // Clear old caches when user confirms update
        console.log('Service Worker [PROD]: User requested cache clearing');
        event.waitUntil(
            caches.keys().then(cacheNames => {
                // Delete all caches that don't match current version
                return Promise.all(
                    cacheNames.map(cache => {
                        if (cache !== CACHE_NAME) {
                            console.log('Service Worker [PROD]: Clearing old cache:', cache);
                            return caches.delete(cache);
                        }
                    })
                );
            }).then(() => {
                console.log('Service Worker [PROD]: Old caches cleared successfully');
                // Notify the client that cache clearing is complete
                if (event.source) {
                    event.source.postMessage({
                        type: 'CACHE_CLEARED',
                        version: APP_VERSION,
                        success: true
                    });
                }
            }).catch(error => {
                console.error('Service Worker [PROD]: Failed to clear old caches:', error);
                if (event.source) {
                    event.source.postMessage({
                        type: 'CACHE_CLEARED',
                        version: APP_VERSION,
                        success: false,
                        error: error.message
                    });
                }
            })
        );
    }
});

// Log service worker registration
console.log('Service Worker registered successfully for version:', APP_VERSION);
