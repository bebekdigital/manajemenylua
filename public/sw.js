const CACHE_NAME = 'ylua-pwa-v12';
const INERTIA_CACHE = 'ylua-inertia-v12';
const CURRENT_CACHES = [CACHE_NAME, INERTIA_CACHE];

const PRECACHE_ASSETS = [
  '/manifest.json',
  '/favicon.png',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png',
  '/images/icons/apple-touch-icon.png'
];

// Install Event: Simpan aset dasar
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_ASSETS).catch((err) => {
        console.warn('[SW] Precache partial error:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// Activate Event: Bersihkan cache versi lama
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => !CURRENT_CACHES.includes(name))
          .map((name) => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event: Penanganan Navigasi SPA & Aset Statis
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Jangan sentuh request non-GET (POST, PUT, DELETE, dsb.)
  if (request.method !== 'GET') {
    return;
  }

  // 1. Permintaan statis (build assets, images, icons, fonts) -> Cache-first dengan network fallback
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

  // 2. Jangan cache endpoint autentikasi, logout, atau polling realtime
  const isExcluded = (
    url.pathname === '/login' ||
    url.pathname === '/logout' ||
    url.pathname.startsWith('/api/approvals/pending')
  );

  if (isExcluded) {
    event.respondWith(fetch(request));
    return;
  }

  // 3. Request Inertia JSON (X-Inertia: true) -> Simpan di INERTIA_CACHE
  const isInertia = request.headers.get('X-Inertia') === 'true';
  if (isInertia) {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(INERTIA_CACHE).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(async () => {
          const cache = await caches.open(INERTIA_CACHE);
          const cachedResponse = await cache.match(request);
          if (cachedResponse) {
            return cachedResponse;
          }
          return new Response(JSON.stringify({ error: 'offline', message: 'Koneksi terputus.' }), {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
              'Content-Type': 'application/json',
              'X-Inertia': 'true'
            })
          });
        })
    );
    return;
  }

  // 4. Request Navigasi Halaman HTML Penuh (mode: 'navigate') -> Simpan di CACHE_NAME
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return networkResponse;
        })
        .catch(async () => {
          const cache = await caches.open(CACHE_NAME);
          const cachedResponse = await cache.match(request);
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
    return;
  }

  // 5. Default GET requests lainnya -> Network-first biasa
  event.respondWith(
    fetch(request).catch(async () => {
      const cached = await caches.match(request);
      return cached || new Response('Offline', { status: 503 });
    })
  );
});

// Listener untuk pesan dari client (misal untuk reload / skip waiting)
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
