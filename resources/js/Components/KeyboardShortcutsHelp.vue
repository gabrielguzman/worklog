<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'

const isOpen = ref(false)
const { getShortcuts, isMac } = useKeyboardShortcuts()

const shortcuts = getShortcuts()

const openHelp = () => {
    isOpen.value = true
}

const closeHelp = () => {
    isOpen.value = false
}

const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
        closeHelp()
    }
    if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault()
        isOpen.value = !isOpen.value
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
    <!-- Botón flotante para abrir ayuda -->
    <button @click="openHelp"
        title="Presiona ? para atajos (Cmd+? en Mac)"
        class="fixed bottom-6 right-6 h-10 w-10 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-lg flex items-center justify-center font-bold transition-all hover:scale-110 z-40">
        ?
    </button>

    <!-- Modal de ayuda -->
    <Teleport to="body">
        <transition name="modal">
            <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 dark:bg-gray-900 rounded-xl shadow-2xl max-w-2xl w-full max-h-96 overflow-y-auto">

                    <!-- Header -->
                    <div class="sticky top-0 bg-gray-50 dark:bg-gray-900 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white">⌨️ Atajos de Teclado</h2>
                        <button @click="closeHelp"
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
                            ✕
                        </button>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6 space-y-4">
                        <div v-for="(shortcut, i) in shortcuts" :key="i"
                            class="flex items-start justify-between pb-4 border-b border-gray-100 dark:border-gray-700 dark:border-gray-700 last:border-0">

                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white dark:text-white">{{ shortcut.label }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ shortcut.description }}</p>
                            </div>

                            <div class="flex gap-1.5 shrink-0 ml-4">
                                <span v-for="key in shortcut.keysDisplay.split(' + ')" :key="key"
                                    class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 dark:text-gray-300 text-xs font-semibold border border-gray-200 dark:border-gray-700 dark:border-gray-700">
                                    {{ key }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="sticky bottom-0 bg-gray-50 dark:bg-gray-900 dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">
                        Presiona <kbd class="px-2 py-1 rounded bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 font-mono">ESC</kbd> para cerrar
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
