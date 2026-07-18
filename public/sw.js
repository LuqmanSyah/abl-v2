const CACHE = 'sdm-pegawai-v1';
const CACHE_PREFIX = '/pegawai';
const ASSETS = [
  '/pegawai',
  '/pegawai/login',
  '/pegawai/attendance/capture',
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE).then(cache => cache.addAll(ASSETS)).catch(() => {})
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE).map(k => caches.delete(k))
    ))
  );
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  if (url.pathname.startsWith(CACHE_PREFIX)) {
    event.respondWith(
      caches.match(event.request).then(cached => {
        const fetched = fetch(event.request).then(response => {
          const clone = response.clone();
          caches.open(CACHE).then(cache => cache.put(event.request, clone));
          return response;
        }).catch(() => cached);

        return cached || fetched;
      })
    );
    return;
  }

  if (url.pathname.startsWith('/icons/') || url.pathname === '/manifest.json') {
    event.respondWith(
      caches.match(event.request).then(cached => cached || fetch(event.request))
    );
  }
});

self.addEventListener('sync', event => {
  if (event.tag === 'sync-attendance') {
    event.waitUntil(processQueue());
  }
});

self.addEventListener('push', event => {
  let data = { title: 'SDM Pegawai', body: '', url: '/pegawai' };
  if (event.data) {
    try {
      data = Object.assign(data, event.data.json());
    } catch (e) { /* ignore malformed payload */ }
  }
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon || '/icons/icon-192.png',
      badge: '/icons/icon-192.png',
      data: { url: data.url },
      requireInteraction: true,
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = event.notification.data?.url || '/pegawai';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if (client.url === url && 'focus' in client) return client.focus();
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});

async function processQueue() {
  try {
    const db = await openDb();
    const records = await getAll(db);
    for (const record of records) {
      try {
        await sendRecord(record);
        await removeRecord(db, record.client_uuid);
      } catch (e) {
        if (!e.retryable) await removeRecord(db, record.client_uuid);
      }
    }
  } catch (e) {
    return;
  }
}

function openDb() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('sdm-attendance', 2);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

function getAll(db) {
  return new Promise((resolve, reject) => {
    const request = db.transaction('queue').objectStore('queue').getAll();
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

function removeRecord(db, clientUuid) {
  return new Promise((resolve, reject) => {
    const request = db.transaction('queue', 'readwrite').objectStore('queue').delete(clientUuid);
    request.onsuccess = resolve;
    request.onerror = () => reject(request.error);
  });
}

function sendRecord(data) {
  const body = new FormData();
  Object.entries(data).forEach(([key, value]) => {
    if (!['photo', 'endpoint', 'duty_trip_id'].includes(key)) {
      body.append(key, value);
    }
  });
  body.append('photo', data.photo, 'attendance.jpg');

  return fetch(data.endpoint, {
    method: 'POST',
    headers: { 'Accept': 'application/json' },
    body,
  }).then(res => {
    if (!res.ok) throw new Error('Sync failed');
    return res;
  });
}
