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

// Assets to cache for offline functionality
const STATIC_CACHE_URLS = [
    '/',
    '/offline.html',
    '/assets/css/theme.css',
    '/assets/css/components.css', 
    '/assets/css/focus-removal.css',
    '/assets/js/jquery.min.js',
    '/assets/js/notifications.js',
    '/assets/js/script.min.js',
    '/assets/img/Logo.png',
    '/assets/img/Logo.png',
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

// Activate event - take control but don't auto-clear caches
self.addEventListener('activate', event => {
    console.log('Service Worker [PROD]: Activating version', APP_VERSION);
    event.waitUntil(
        Promise.resolve().then(() => {
            console.log('Service Worker [PROD]: Taking control for version', APP_VERSION);
            // Take control of all pages immediately, but don't clear caches yet
            return self.clients.claim();
        }).then(() => {
            // Notify all clients about the new version availability (not that caches are cleared)
            return self.clients.matchAll().then(clients => {
                clients.forEach(client => {
                    client.postMessage({
                        type: 'VERSION_AVAILABLE',
                        version: APP_VERSION,
                        cacheCleared: false
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

    // Never cache API endpoints - always fetch fresh
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(error => {
                console.log('Service Worker [PROD]: API request failed:', event.request.url, error);
                throw error;
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request).then(response => {
            // Return cached version if available
            if (response) {
                console.log('Service Worker [PROD]: Serving from cache:', event.request.url);
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

                // Add to cache for future requests (but not API endpoints)
                if (!event.request.url.includes('/api/')) {
                    caches.open(CACHE_NAME).then(cache => {
                        // Only cache GET requests for same origin, excluding API
                        if (event.request.method === 'GET' && event.request.url.startsWith(self.location.origin)) {
                            cache.put(event.request, responseToCache);
                        }
                    });
                }

                return fetchResponse;
            }).catch(error => {
                console.log('Service Worker [PROD]: Network request failed:', event.request.url, error);
                
                // Return offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }
                
                // For other requests, just let them fail
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
