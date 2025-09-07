// Service Worker for Probenplaner PWA
const CACHE_NAME = 'probenplaner-v1';
const OFFLINE_URL = '/offline.html';

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
  '/assets/img/tabIcon.png',
  '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('Service Worker: Caching static assets');
      return cache.addAll(STATIC_CACHE_URLS).catch(error => {
        console.error('Service Worker: Failed to cache some assets:', error);
        // Cache individual assets that succeed, continue even if some fail
        return Promise.allSettled(
          STATIC_CACHE_URLS.map(url => cache.add(url))
        );
      });
    }).then(() => {
      console.log('Service Worker: Installation complete');
      // Force activation of new service worker
      return self.skipWaiting();
    })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Deleting old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => {
      console.log('Service Worker: Activation complete');
      // Take control of all pages immediately
      return self.clients.claim();
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
        console.log('Service Worker: API request failed:', event.request.url, error);
        throw error;
      })
    );
    return;
  }

  event.respondWith(
    caches.match(event.request).then(response => {
      // Return cached version if available
      if (response) {
        console.log('Service Worker: Serving from cache:', event.request.url);
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
        console.log('Service Worker: Network request failed:', event.request.url, error);
        
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
    icon: '/assets/img/tabIcon.png',
    badge: '/assets/img/tabIcon.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'open',
        title: 'Öffnen',
        icon: '/assets/img/tabIcon.png'
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
