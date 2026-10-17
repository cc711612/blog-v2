// v4：改版快取策略後升版號，activate 時會清掉 v3 以前被「永久快取」的舊內容（含 404/5xx、feed、跨網域資源）
const CACHE_NAME = 'roy-blog-v2-cache-v4';
const PRECACHE_URLS = ['/', '/article', '/manifest.json', '/favicon.ico'];
const MAX_DOCUMENT_ENTRIES = 50;
const STATIC_PREFIXES = ['/images/'];
const STATIC_PATHS = ['/favicon.ico'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    )
  );
  self.clients.claim();
});

// 只有成功（response.ok）的回應才會被快取，避免把 404/5xx 永久存起來
const putIfOk = (request, response) => {
  if (response && response.ok) {
    const cloned = response.clone();
    caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned).then(() => trimDocuments(cache)));
  }
  return response;
};

// 文章 HTML 每看一篇就快取一份，設上限（刪最舊的），避免無限成長；預載的 URL 不刪
const trimDocuments = (cache) =>
  cache.keys().then((requests) => {
    const removable = requests.filter((request) => {
      const path = new URL(request.url).pathname;
      return !PRECACHE_URLS.includes(path) && !isStaticAsset(path);
    });
    const overflow = removable.length - MAX_DOCUMENT_ENTRIES;
    return Promise.all(removable.slice(0, Math.max(0, overflow)).map((request) => cache.delete(request)));
  });

const isStaticAsset = (path) => STATIC_PATHS.includes(path) || STATIC_PREFIXES.some((prefix) => path.startsWith(prefix));

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  // 跨網域（GA/GTM、cdnjs 等）一律交給瀏覽器，不經過 Service Worker
  if (url.origin !== self.location.origin) {
    return;
  }

  const isDocument = request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html');

  // HTML：network-first，離線時才用快取
  if (isDocument) {
    event.respondWith(
      fetch(request)
        .then((response) => putIfOk(request, response))
        .catch(() => caches.match(request).then((cached) => cached || caches.match('/')))
    );
    return;
  }

  // manifest：network-first（品牌色、圖示改了要能更新）
  if (url.pathname === '/manifest.json') {
    event.respondWith(
      fetch(request)
        .then((response) => putIfOk(request, response))
        .catch(() => caches.match(request))
    );
    return;
  }

  // 靜態資源（圖片、favicon）：cache-first
  if (isStaticAsset(url.pathname)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        if (cached) {
          return cached;
        }

        // 離線抓不到圖片就回網路錯誤，不要回首頁 HTML 假裝是圖片
        return fetch(request).then((response) => putIfOk(request, response));
      })
    );
    return;
  }

  // 其餘（/feed.xml、/sitemap.xml、/api、/livewire 等）不攔截，永遠走網路
});
