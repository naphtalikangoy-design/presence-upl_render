// Service worker UPL PRESENCE : met en cache uniquement les ressources statiques.
// Les pages, les requêtes POST et l'API ne sont JAMAIS servies depuis le cache (pointage = toujours en ligne).
const V = 'upl-presence-v1';
const STATIC = ['/css/app.css', '/js/pointage.js', '/offline.html', '/icons/icon-192.png'];
self.addEventListener('install', e => { e.waitUntil(caches.open(V).then(c => c.addAll(STATIC))); self.skipWaiting(); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(k => Promise.all(k.filter(x => x !== V).map(x => caches.delete(x))))); self.clients.claim(); });
self.addEventListener('fetch', e => {
  const r = e.request, u = new URL(r.url);
  if (r.method !== 'GET' || u.origin !== location.origin) return;
  if (STATIC.includes(u.pathname)) { e.respondWith(caches.match(r).then(m => m || fetch(r))); return; }
  if (r.mode === 'navigate') e.respondWith(fetch(r).catch(() => caches.match('/offline.html')));
});
