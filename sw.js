const BASE = "/MAZ-SUPLE";
const CACHE_NAME = "mazsuplementos-v1";

const FILES_TO_CACHE = [
    `${BASE}/`,
    `${BASE}/index.php`,
    `${BASE}/dashboard.php`,
    `${BASE}/ventas.php`,
    `${BASE}/css/index.css`,
    `${BASE}/css/ventas.css`,
    `${BASE}/js/main.js`,
    `${BASE}/js/ventas.js`,
    `${BASE}/js/offline_manager.js`
    
];

self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(FILES_TO_CACHE))
    );
    self.skipWaiting();
});

self.addEventListener("activate", event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== CACHE_NAME)
                    .map(k => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request).then(resp => resp || fetch(event.request))
    );
});
