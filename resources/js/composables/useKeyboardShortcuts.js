import { onMounted, onUnmounted } from 'vue'

const shortcuts = [
    {
        keys: ['ctrl', '/'],
        label: 'Búsqueda global',
        description: 'Abre búsqueda global',
        handler: null,
    },
    {
        keys: ['ctrl', 'alt', 'n'],
        label: 'Nueva tarea',
        description: 'Crea una nueva tarea',
        handler: null,
    },
    {
        keys: ['ctrl', 'alt', 'e'],
        label: 'Captura rápida',
        description: 'Abre captura rápida (entrada o tarea)',
        handler: null,
    },
    {
        keys: ['ctrl', 'alt', 'd'],
        label: 'Toggle Dark Mode',
        description: 'Cambia entre modo claro y oscuro',
        handler: null,
    },
    {
        keys: ['?'],
        label: 'Mostrar ayuda',
        description: 'Muestra este panel de atajos',
        handler: null,
    },
]

export function useKeyboardShortcuts() {
    const isMac = () => navigator.platform.toUpperCase().indexOf('MAC') >= 0
    const cmdKey = isMac() ? '⌘' : 'Ctrl'

    const registerShortcut = (keyCombo, handler) => {
        const idx = shortcuts.findIndex(s =>
            JSON.stringify(s.keys) === JSON.stringify(Array.isArray(keyCombo) ? keyCombo : [keyCombo])
        )
        if (idx !== -1) {
            shortcuts[idx].handler = handler
        }
    }

    const handleKeyDown = (e) => {
        // No ejecutar si está escribiendo en un input (excepto algunos atajos)
        if (
            e.target.tagName === 'INPUT' ||
            e.target.tagName === 'TEXTAREA' ||
            e.target.contentEditable === 'true'
        ) {
            // Permitir algunos atajos incluso en inputs
            if (!(e.ctrlKey && (e.key === '/' || e.key === 'alt')) && e.key !== '?') {
                return
            }
        }

        const isCtrl = e.ctrlKey
        const isAlt = e.altKey
        const isShift = e.shiftKey

        // Ctrl+/ para búsqueda global
        if (isCtrl && e.key === '/') {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['ctrl', '/']))?.handler
            handler?.()
        }

        // Ctrl+Alt+N para nueva tarea
        if (isCtrl && isAlt && e.key === 'n') {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['ctrl', 'alt', 'n']))?.handler
            handler?.()
        }

        // Ctrl+Alt+E para captura rápida
        if (isCtrl && isAlt && e.key === 'e') {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['ctrl', 'alt', 'e']))?.handler
            handler?.()
        }

        // Ctrl+Alt+D para dark mode
        if (isCtrl && isAlt && e.key === 'd') {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['ctrl', 'alt', 'd']))?.handler
            handler?.()
        }

        // ? para ayuda
        if (e.key === '?' && !isCtrl && !isAlt) {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['?']))?.handler
            handler?.()
        }
    }

    const setup = (handlers = {}) => {
        // Registrar handlers
        Object.entries(handlers).forEach(([key, handler]) => {
            registerShortcut(key, handler)
        })

        // Agregar event listener
        document.addEventListener('keydown', handleKeyDown)
    }

    const cleanup = () => {
        document.removeEventListener('keydown', handleKeyDown)
    }

    const getShortcuts = () => shortcuts.map(s => ({
        ...s,
        keysDisplay: s.keys.map(k => {
            if (k === 'cmd') return isMac() ? '⌘' : 'Ctrl'
            if (k === 'ctrl') return 'Ctrl'
            if (k === 'alt') return isMac() ? '⌥' : 'Alt'
            if (k === 'shift') return '⇧'
            return k.toUpperCase()
        }).join(' + ')
    }))

    return {
        registerShortcut,
        setup,
        cleanup,
        getShortcuts,
        cmdKey,
        isMac: isMac(),
    }
}
