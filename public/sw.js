const CACHE_NAME = 'ylua-pwa-v10';
const PRECACHE_ASSETS = [
  '/favicon.png',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png',
  '/images/icons/apple-touch-icon.png'
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.warn('[SW] Precache partial error:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Activate Event: Clean up outdated caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Jangan cache request non-GET atau request ke API/login/logout
  if (request.method !== 'GET') {
    return;
  }

  // Permintaan statis (build assets, images, icons, fonts) -> Cache-first dengan network fallback
  const isStaticAsset = (
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/images/') ||
    url.pathname.startsWith('/storage/logos/') ||
    url.pathname.includes('/fonts/') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com')
  );

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        });
      })
    );
    return;
  }

  // Untuk Halaman & Inertia Requests: Network-First agar data selalu mutakhir dan tidak stale
  event.respondWith(
    fetch(request)
      .then((networkResponse) => {
        return networkResponse;
      })
      .catch(async () => {
        // Fallback jika offline
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
          return cachedResponse;
        }
        return new Response('Anda sedang offline. Silakan periksa koneksi internet Anda.', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: new Headers({ 'Content-Type': 'text/plain; charset=utf-8' })
        });
      })
  );
});

// Listener untuk pesan dari client (misal untuk reload / skip waiting)
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

