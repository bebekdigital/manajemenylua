const CACHE_NAME = 'ylua-pwa-v27';
const CURRENT_CACHES = [CACHE_NAME];

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

// Activate Event: Bersihkan SELURUH cache versi lama untuk mencegah stale HTML/JS
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

// Fetch Event
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Jangan sentuh request non-GET
  if (request.method !== 'GET') {
    return;
  }

  // 1. Permintaan Navigasi HTML Halaman Penuh: ALWAYS Network-Only (Cegah stale skeleton/HTML lama)
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => {
        return new Response('Koneksi terputus. Silakan periksa jaringan internet Anda.', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: new Headers({ 'Content-Type': 'text/plain; charset=utf-8' })
        });
      })
    );
    return;
  }

  // 2. Permintaan Inertia SPA (X-Inertia: true): ALWAYS Network-Only
  const isInertia = request.headers.get('X-Inertia') === 'true';
  if (isInertia) {
    event.respondWith(
      fetch(request).catch(() => {
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

  // 3. Jangan cache endpoint autentikasi, logout, download template, atau polling realtime
  const isExcluded = (
    url.pathname === '/login' ||
    url.pathname === '/logout' ||
    url.pathname.startsWith('/api/') ||
    url.pathname.startsWith('/students/template') ||
    url.pathname.startsWith('/staff/template')
  );

  if (isExcluded) {
    event.respondWith(fetch(request));
    return;
  }

  // 4. Aset Statis Berkelanjutan (ikon, gambar, font): Cache-first dengan network fallback
  const isStaticMedia = (
    url.pathname.startsWith('/images/') ||
    url.pathname.startsWith('/storage/logos/') ||
    url.pathname.includes('/fonts/') ||
    url.hostname.includes('fonts.googleapis.com') ||
    url.hostname.includes('fonts.gstatic.com')
  );

  if (isStaticMedia) {
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

  // 5. Default GET requests lainnya -> Network-first dengan fallback
  event.respondWith(
    fetch(request).catch(async () => {
      const cached = await caches.match(request);
      return cached || new Response('Offline', { status: 503 });
    })
  );
});

// Listener untuk pesan dari client (reload / skip waiting / force clear)
self.addEventListener('message', (event) => {
  if (event.data && (event.data.type === 'SKIP_WAITING' || event.data.type === 'CLEAR_CACHE')) {
    caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k))));
    self.skipWaiting();
  }
});
