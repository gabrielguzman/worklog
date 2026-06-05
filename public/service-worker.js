const CACHE_VERSION = 'v1'
const CACHE_NAME = `worklog-${CACHE_VERSION}`
const OFFLINE_URL = '/offline.html'

// URLs que siempre deben venir del servidor
const NETWORK_FIRST_ROUTES = [
  '/api/',
  '/focus/start',
  '/tasks/bulk-update',
  '/tasks/bulk-delete',
  '/export/',
]

// Instalar Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando...')
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Cache abierto')
      return cache.addAll([
        '/',
        '/dashboard',
        '/login',
        '/offline.html',
      ])
    }).then(() => self.skipWaiting())
  )
})

// Activar Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Activando...')
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Eliminando cache antiguo:', cacheName)
            return caches.delete(cacheName)
          }
        })
      )
    }).then(() => self.clients.claim())
  )
})

// Fetch: Cache First + Network Fallback para assets estáticos
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)

  // GET requests solo
  if (request.method !== 'GET') {
    return
  }

  // Network first para rutas críticas
  if (NETWORK_FIRST_ROUTES.some(route => url.pathname.startsWith(route))) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, response.clone())
            })
          }
          return response
        })
        .catch(() => caches.match(request))
    )
    return
  }

  // Cache first para assets estáticos
  if (isStaticAsset(url.pathname)) {
    event.respondWith(
      caches.match(request).then((response) => {
        return response || fetch(request).then((response) => {
          if (response && response.status === 200) {
            const cache = caches.open(CACHE_NAME)
            cache.then((c) => c.put(request, response.clone()))
          }
          return response
        }).catch(() => getOfflineAsset(url.pathname))
      })
    )
    return
  }

  // Network first para páginas HTML
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200) {
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, response.clone())
            })
          }
          return response
        })
        .catch(() => {
          return caches.match(request) || caches.match(OFFLINE_URL)
        })
    )
    return
  }

  // Default: network
  event.respondWith(fetch(request))
})

// Determinar si es un asset estático
function isStaticAsset(pathname) {
  return /\.(js|css|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/i.test(pathname)
}

// Obtener asset offline
function getOfflineAsset(pathname) {
  if (pathname.endsWith('.js')) {
    return new Response('', { status: 404 })
  }
  if (pathname.endsWith('.css')) {
    return new Response('', { status: 404 })
  }
  if (/\.(png|jpg|jpeg|gif|svg)$/i.test(pathname)) {
    return new Response(
      'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="%23f3f4f6" width="100" height="100"/></svg>',
      { headers: { 'Content-Type': 'image/svg+xml' } }
    )
  }
  return fetch(pathname)
}

// Background Sync para tareas
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-tasks') {
    event.waitUntil(syncOfflineTasks())
  }
})

async function syncOfflineTasks() {
  try {
    const db = await openIndexDB()
    const tasks = await getAllOfflineTasks(db)

    for (const task of tasks) {
      try {
        await fetch(task.endpoint, {
          method: task.method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(task.data),
        })
        await removeOfflineTask(db, task.id)
      } catch (error) {
        console.error('[SW] Error sinc:', error)
      }
    }

    // Notificar al cliente
    self.clients.matchAll().then((clients) => {
      clients.forEach((client) => {
        client.postMessage({ type: 'SYNC_COMPLETE' })
      })
    })
  } catch (error) {
    console.error('[SW] Error en sync:', error)
  }
}

// IndexDB helpers
function openIndexDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('WorkLog', 1)
    request.onerror = () => reject(request.error)
    request.onsuccess = () => resolve(request.result)
    request.onupgradeneeded = (event) => {
      const db = event.target.result
      if (!db.objectStoreNames.contains('offlineTasks')) {
        db.createObjectStore('offlineTasks', { keyPath: 'id', autoIncrement: true })
      }
    }
  })
}

function getAllOfflineTasks(db) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['offlineTasks'], 'readonly')
    const store = transaction.objectStore('offlineTasks')
    const request = store.getAll()
    request.onerror = () => reject(request.error)
    request.onsuccess = () => resolve(request.result)
  })
}

function removeOfflineTask(db, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['offlineTasks'], 'readwrite')
    const store = transaction.objectStore('offlineTasks')
    const request = store.delete(id)
    request.onerror = () => reject(request.error)
    request.onsuccess = () => resolve()
  })
}
