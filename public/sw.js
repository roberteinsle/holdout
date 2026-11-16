/*
 * Holdout offline copy (#14). Keeps the printout (/printout: supply, eat first,
 * stock by location; a self-contained page with inline styles) on the device.
 * The app asks for a fresh copy on every page load while online (at most every
 * few minutes). When the server cannot be reached, every page opens this copy
 * with an "offline copy" banner. Nothing else is cached; the app itself
 * always needs the server.
 */
const CACHE = 'holdout-offline-v1';
const COPY = '/printout';
const ASSETS = ['/logo.svg', '/favicon.svg'];
const REFRESH_AFTER_MS = 5 * 60 * 1000;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('holdout-') && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

/** Fetch the printout and keep it, with the time it was saved. Only a real printout counts (not the "Who are you?" page). */
async function saveCopy(force = false) {
    const cache = await caches.open(CACHE);
    const current = await cache.match(COPY);
    const savedAt = Number(current?.headers.get('X-Holdout-Saved-At') ?? 0);

    if (!force && Date.now() - savedAt < REFRESH_AFTER_MS) {
        return savedAt;
    }

    const response = await fetch(COPY, { credentials: 'same-origin', cache: 'no-store' });

    if (!response.ok || response.redirected || response.headers.get('X-Holdout-Printout') !== '1') {
        return savedAt || null;
    }

    const headers = new Headers(response.headers);
    const now = Date.now();
    headers.set('X-Holdout-Saved-At', String(now));
    await cache.put(COPY, new Response(await response.blob(), { status: 200, headers }));

    return now;
}

async function savedAt() {
    const copy = await (await caches.open(CACHE)).match(COPY);

    return copy ? Number(copy.headers.get('X-Holdout-Saved-At')) : null;
}

self.addEventListener('message', (event) => {
    const reply = (savedAt) => event.ports[0]?.postMessage({ savedAt });

    if (event.data === 'refresh' || event.data === 'refresh-now') {
        event.waitUntil(saveCopy(event.data === 'refresh-now').catch(() => savedAt()).then(reply));
    } else if (event.data === 'status') {
        event.waitUntil(savedAt().then(reply));
    }
});

/** The saved copy, with the offline banner switched on. */
async function offlineCopy() {
    const copy = await (await caches.open(CACHE)).match(COPY);

    if (!copy) {
        return new Response(
            '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Holdout</title>'
            + '<body style="font:16px system-ui,sans-serif;margin:2rem"><h1>Holdout</h1>'
            + '<p>The server cannot be reached, and there is no offline copy on this device yet. Open the app once while it is reachable (over HTTPS).</p></body>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } },
        );
    }

    const html = (await copy.text()).replace('data-offline-banner hidden', 'data-offline-banner');

    return new Response(html, { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Pages: always the network first; the saved copy only when the server is unreachable.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => offlineCopy()));

        return;
    }

    // The logo and favicon of the copy.
    if (ASSETS.includes(new URL(request.url).pathname)) {
        event.respondWith(fetch(request).catch(() => caches.match(request)));
    }
});
