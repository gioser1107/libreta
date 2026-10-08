const CACHE = 'libreta-v3';

const PRECACHE = [
    '/offline.html',
    '/favicon.ico',
    '/icons/favicon-32.png',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    if (request.cache === 'only-if-cached' && request.mode !== 'same-origin') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstNavigate(request));
        return;
    }

    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
    }
});

function isStaticAsset(pathname) {
    return pathname.startsWith('/build/')
        || pathname.startsWith('/icons/')
        || pathname.startsWith('/splash/')
        || pathname.startsWith('/img/')
        || pathname === '/offline.html'
        || pathname === '/favicon.ico';
}

async function cacheFirst(request) {
    const cached = await caches.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response.ok && response.type === 'basic') {
        const copy = response.clone();
        caches.open(CACHE).then((cache) => cache.put(request, copy));
    }

    return response;
}

async function networkFirstNavigate(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const cached = await caches.match('/offline.html');

        if (cached) {
            return cached;
        }

        return new Response('Sin conexión', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
    }
}
