const CACHE_NAME = 'trikefare-v2';
const MAP_CACHE_NAME = 'trikefare-maps-v1';
const ASSETS_TO_CACHE = [
  './',
  './index.php',
  './index_beta.php',
  './styles.css',
  './icon-192x192.png',
  './icon-512x512.png',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(ASSETS_TO_CACHE))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME && key !== MAP_CACHE_NAME)
          .map(key => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  
  const url = new URL(event.request.url);
  
  // MAP TILE CACHING (Stale-While-Revalidate)
  if (url.hostname.includes('tile.openstreetmap.org')) {
    event.respondWith(
      caches.open(MAP_CACHE_NAME).then(cache => {
        return cache.match(event.request).then(cachedResponse => {
          const fetchPromise = fetch(event.request).then(networkResponse => {
            if (networkResponse && networkResponse.status === 200) {
              cache.put(event.request, networkResponse.clone());
            }
            return networkResponse;
          });
          return cachedResponse || fetchPromise;
        });
      })
    );
    return;
  }

  // Skip other external services (routing/search) but allow local API for network attempt
  if (url.hostname.includes('project-osrm.org') || url.hostname.includes('photon.komoot.io')) {
    return;
  }

  // APP ASSETS (Network-First with fallback)
  event.respondWith(
    fetch(event.request)
      .then(response => {
        if (response && response.status === 200 && !url.pathname.includes('/api/')) {
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
        }
        return response;
      })
      .catch(() => {
        return caches.match(event.request).then(cachedResponse => {
          if (cachedResponse) return cachedResponse;
          if (event.request.mode === 'navigate') {
            // Fallback to whichever main page is cached (index or beta)
            return caches.match(event.request.url) || caches.match('./index.php') || caches.match('./index_beta.php');
          }
        });
      })
  );
});
