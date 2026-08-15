/**
 * Service worker dasar (SRS §13, §18 PRD) — cache shell untuk pemakaian
 * offline dasar. Sinkronisasi data (draft Presensi/Jurnal) dipicu dari
 * resources/js/offline/sync.js saat event 'online' di halaman aktif, bukan
 * lewat Background Sync API — penuh/robust background sync API browser
 * adalah kelanjutan yang disengaja untuk sesi pengembangan berikutnya.
 */
const CACHE_NAME = 'si-ppg-shell-v2';
const SHELL_URLS = ['/manifest.json', '/icons/icon-192.png', '/icons/icon-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    // Hanya cache-first untuk asset statis di /build/ — halaman & API selalu network,
    // supaya data Presensi/Jurnal tidak pernah menampilkan versi basi.
    if (event.request.method !== 'GET' || !event.request.url.includes('/build/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => cached ?? fetch(event.request).then((response) => {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));

            return response;
        }))
    );
});
