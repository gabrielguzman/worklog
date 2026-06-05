import { ref, onMounted } from 'vue'

const isOnline = ref(navigator.onLine)
const isInstallable = ref(false)
const deferredPrompt = ref(null)

export function usePWA() {
  onMounted(() => {
    // Registrar Service Worker
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker
        .register('/service-worker.js')
        .then((registration) => {
          console.log('[PWA] Service Worker registrado:', registration)
        })
        .catch((error) => {
          console.error('[PWA] Error al registrar SW:', error)
        })
    }

    // Escuchar cambios de conexión
    window.addEventListener('online', () => {
      isOnline.value = true
      console.log('[PWA] En línea')
      // Sincronizar datos offline
      if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then((registration) => {
          registration.sync.register('sync-tasks')
        })
      }
    })

    window.addEventListener('offline', () => {
      isOnline.value = false
      console.log('[PWA] Sin línea')
    })

    // beforeinstallprompt para botón de instalar
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault()
      deferredPrompt.value = e
      isInstallable.value = true
      console.log('[PWA] App es instalable')
    })

    // Cuando la app se instala
    window.addEventListener('appinstalled', () => {
      console.log('[PWA] App instalada')
      deferredPrompt.value = null
      isInstallable.value = false
    })
  })

  // Instalar PWA
  const installPWA = async () => {
    if (!deferredPrompt.value) return

    deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    console.log('[PWA] Resultado:', outcome)

    deferredPrompt.value = null
    isInstallable.value = false
  }

  // Guardar datos offline
  const saveOfflineTask = async (endpoint, method, data) => {
    if (isOnline.value) {
      // Si estamos online, enviar normalmente
      return fetch(endpoint, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      })
    }

    // Si estamos offline, guardar en IndexDB
    return saveToIndexDB({
      endpoint,
      method,
      data,
      timestamp: Date.now(),
    })
  }

  // IndexDB helpers
  const openIndexDB = () => {
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

  const saveToIndexDB = async (task) => {
    const db = await openIndexDB()
    return new Promise((resolve, reject) => {
      const transaction = db.transaction(['offlineTasks'], 'readwrite')
      const store = transaction.objectStore('offlineTasks')
      const request = store.add(task)
      request.onerror = () => reject(request.error)
      request.onsuccess = () => {
        console.log('[PWA] Tarea guardada offline:', request.result)
        resolve(request.result)
      }
    })
  }

  return {
    isOnline,
    isInstallable,
    installPWA,
    saveOfflineTask,
  }
}
