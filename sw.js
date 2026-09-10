const CACHE_NAME = 'perpustakaan-pwa-v2-20260824';
const BASE = new URL('./', self.location).pathname;
const APP_SHELL = [
  BASE,
  BASE + 'index.php',
  BASE + 'assets/img/no-cover.svg',
  BASE + 'assets/img/no-avatar.svg'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(APP_SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Halaman PHP: utamakan jaringan agar data database tetap terbaru.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(request).then(r => r || caches.match(BASE + 'index.php')))
    );
    return;
  }

  // Aset: network-first, lalu cache jika sedang offline.
  if (url.origin === self.location.origin || url.hostname.includes('cdn.')) {
    event.respondWith(
      fetch(request).then(response => {
        if (response && response.ok && url.origin === self.location.origin) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
        }
        return response;
      }).catch(() => caches.match(request))
    );
  }
});
