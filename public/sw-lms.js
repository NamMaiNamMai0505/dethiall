/* Sprint 9 H4 — Light PWA for LMS shell only (no offline submit queue) */
const CACHE = 'lms-shell-v1';
const PRECACHE = [
  '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  // Only same-origin static-ish GET under /lms or assets — network first
  if (url.origin !== self.location.origin) return;
  if (!(url.pathname.startsWith('/lms') || url.pathname.startsWith('/build') || url.pathname.startsWith('/images'))) {
    return;
  }
  // Never cache POST or exam/checkin endpoints
  if (url.pathname.includes('/checkin') || url.pathname.includes('/submit') || url.pathname.includes('/commit')) {
    return;
  }
  event.respondWith(
    fetch(req)
      .then((res) => {
        const copy = res.clone();
        if (res.ok && (url.pathname.startsWith('/build') || url.pathname.startsWith('/images'))) {
          caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      })
      .catch(() => caches.match(req).then((c) => c || caches.match('/lms/hoc')))
  );
});
