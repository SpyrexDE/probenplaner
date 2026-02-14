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
    // PRODUCTION MODE: No caching - always fetch fresh
    // TODO: Re-implement selective caching

    console.log('Service Worker [PROD]: Caching disabled - all requests served fresh from network');

    // Install event - no caching
    self.addEventListener('install', event => {
    console.log('Service Worker [PROD]: Installing version', APP_VERSION, '- no caching');
    // Skip waiting to activate immediately
    self.skipWaiting();
    });

    // Activate event - clear all caches and take control
    self.addEventListener('activate', event => {
    console.log('Service Worker [PROD]: Activating version', APP_VERSION, '- clearing all caches');
    event.waitUntil(
    caches.keys().then(cacheNames => {
    // Delete all caches
    return Promise.all(
    cacheNames.map(cache => {
    console.log('Service Worker [PROD]: Deleting cache:', cache);
    return caches.delete(cache);
    })
    );
    }).then(() => {
    console.log('Service Worker [PROD]: All caches cleared, taking control');
    return self.clients.claim();
    }).then(() => {
    // Notify all clients about the version update
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

    // Fetch event - always fetch from network, no caching
    self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
    return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
    return;
    }

    console.log('Service Worker [PROD]: Network-only fetch:', event.request.url);

    // Always fetch fresh from network, no caching
    event.respondWith(
    fetch(event.request.clone()).catch(error => {
    console.log('Service Worker [PROD]: Network request failed:', event.request.url, error);

    // Return offline page for navigation requests if available
    if (event.request.mode === 'navigate') {
    return fetch(OFFLINE_URL).catch(() => {
    return new Response('Offline - No Cache Mode', {
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
icon: '/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png',
badge: '/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png',
vibrate: [100, 50, 100],
data: {
dateOfArrival: Date.now(),
primaryKey: 1
},
actions: [
{
action: 'open',
title: 'Öffnen',
icon: '/assets/icons/apple/Probenplaner-iOS-Default-1024x1024@1x.png'
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