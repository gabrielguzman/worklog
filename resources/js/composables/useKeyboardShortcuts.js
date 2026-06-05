import { onMounted, onUnmounted } from 'vue'

const shortcuts = [
    {
        keys: ['cmd', 'k'],
        label: 'Búsqueda global',
        description: 'Abre búsqueda global',
        handler: null,
    },
    {
        keys: ['cmd', 'n'],
        label: 'Nueva tarea',
        description: 'Crea una nueva tarea',
        handler: null,
    },
    {
        keys: ['cmd', 'shift', 'n'],
        label: 'Captura rápida',
        description: 'Abre captura rápida (entrada o tarea)',
        handler: null,
    },
    {
        keys: ['cmd', 'shift', 'd'],
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
        // No ejecutar si está escribiendo en un input
        if (
            e.target.tagName === 'INPUT' ||
            e.target.tagName === 'TEXTAREA' ||
            e.target.contentEditable === 'true'
        ) {
            // Permitir algunos atajos incluso en inputs
            if (!(e.metaKey || e.ctrlKey) && e.key !== '?') {
                return
            }
        }

        const isMacOS = e.metaKey
        const isCtrl = e.ctrlKey
        const isShift = e.shiftKey

        // Cmd+K o Ctrl+K
        if ((isMacOS || isCtrl) && e.key === 'k') {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['cmd', 'k']))?.handler
            handler?.()
        }

        // Cmd+N o Ctrl+N
        if ((isMacOS || isCtrl) && e.key === 'n' && !isShift) {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['cmd', 'n']))?.handler
            handler?.()
        }

        // Cmd+Shift+N o Ctrl+Shift+N
        if ((isMacOS || isCtrl) && e.key === 'n' && isShift) {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['cmd', 'shift', 'n']))?.handler
            handler?.()
        }

        // Cmd+Shift+D o Ctrl+Shift+D
        if ((isMacOS || isCtrl) && e.key === 'd' && isShift) {
            e.preventDefault()
            const handler = shortcuts.find(s => JSON.stringify(s.keys) === JSON.stringify(['cmd', 'shift', 'd']))?.handler
            handler?.()
        }

        // ? para ayuda
        if (e.key === '?' && !isCtrl && !isMacOS) {
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
