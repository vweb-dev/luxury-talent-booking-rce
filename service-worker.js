const CACHE_NAME = 'rce-static-v1';
const STATIC_ASSETS = [
  '/',
  '/assets/css/app.css',
  '/assets/js/http.js',
  '/assets/js/ui.js',
  '/assets/img/logo.png',
  '/manifest.webmanifest'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .catch(error => {
        console.error('Failed to cache static assets:', error);
      })
  );
  self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - serve from cache for static assets, bypass cache for API calls
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Always bypass cache for API calls and authenticated routes
  if (url.pathname.startsWith('/api/') || 
      url.pathname.startsWith('/login') ||
      url.pathname.startsWith('/saportal/') ||
      url.pathname.startsWith('/client/') ||
      url.pathname.startsWith('/talent/') ||
      url.pathname.startsWith('/admin/') ||
      event.request.method !== 'GET') {
    return fetch(event.request);
  }
  
  // For static assets, try cache first, then network
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        
        return fetch(event.request)
          .then(response => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Clone the response for caching
            const responseToCache = response.clone();
            
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            
            return response;
          });
      })
      .catch(() => {
        // Fallback for offline scenarios
        if (event.request.destination === 'document') {
          return caches.match('/');
        }
      })
  );
});

// Handle background sync for offline actions
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(
      // Handle any background sync operations here
      console.log('Background sync triggered')
    );
  }
});

// Handle push notifications (future feature)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: '/assets/img/logo.png',
      badge: '/assets/img/logo.png',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey
      }
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});
