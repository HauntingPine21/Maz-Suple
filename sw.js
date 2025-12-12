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
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

// *************************************************************
// CORRECCIÓN APLICADA AQUÍ: Manejo del fallo de red (fetch)
// *************************************************************

self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request)
            .then(resp => {
                // 1. Si el recurso está en el caché, lo devuelve inmediatamente (Cache First).
                if (resp) return resp;

                // 2. Si NO está en caché, intenta ir a la red (fetch).
                return fetch(event.request)
                    // 3. ¡IMPORTANTE! Captura el error de red (TypeError: Failed to fetch)
                    .catch(error => {
                        // La red falló. Si no pudimos obtener el recurso de la red ni del caché, 
                        // esto evita que el Service Worker se rompa.
                        console.error('Falló la red para:', event.request.url, error);
                        // Opcional: Podrías devolver un recurso de fallback aquí, como una imagen predeterminada 
                        // o una página HTML de "Sin Conexión".
                        // Por ahora, solo se maneja el error para evitar el Uncaught.
                    });
            })
    );
});