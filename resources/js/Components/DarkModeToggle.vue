<script setup>
import { useDarkMode } from '@/composables/useDarkMode'

const { isDark, toggleDarkMode, isLoaded } = useDarkMode()

const handleClick = () => {
    console.log('🌙 DarkModeToggle button clicked')
    toggleDarkMode()
}
</script>

<template>
    <button v-if="isLoaded"
        @click="handleClick"
        :title="isDark ? 'Light Mode' : 'Dark Mode'"
        :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
        <svg v-if="!isDark" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
        </svg>
        <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.536a1 1 0 11 1.414-1.414l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 10-1.414 1.414l.707.707zM5 10a1 1 0 01-1-1V6a1 1 0 112 0v3a1 1 0 01-1 1zm10 0a1 1 0 011-1h3a1 1 0 110 2h-3a1 1 0 01-1-1z" clip-rule="evenodd" />
        </svg>
    </button>
</template>
