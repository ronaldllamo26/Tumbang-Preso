const CACHE_NAME = 'tumbang-preso-v1';
const assetsToCache = [
    './',
    './index.php',
    './manifest.json',
    './assets/sprites/p1_body.png',
    './assets/sprites/p2_body.png',
    './assets/sprites/lata.png',
    './assets/sprites/tsinelas.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(assetsToCache);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
