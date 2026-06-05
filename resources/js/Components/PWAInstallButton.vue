<script setup>
import { usePWA } from '@/composables/usePWA'
import { computed } from 'vue'

const { isInstallable, isOnline, installPWA } = usePWA()

const showButton = computed(() => isInstallable.value)
const statusIcon = computed(() => (isOnline.value ? '🟢' : '🔴'))
const statusText = computed(() => (isOnline.value ? 'En línea' : 'Sin conexión'))
</script>

<template>
  <div class="flex items-center gap-3">
    <!-- Status online/offline -->
    <div class="flex items-center gap-1 text-xs px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
      <span>{{ statusIcon }}</span>
      <span class="text-gray-600 dark:text-gray-400">{{ statusText }}</span>
    </div>

    <!-- Botón instalar PWA -->
    <button
      v-if="showButton"
      @click="installPWA"
      class="flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
      title="Instalar WorkLog como aplicación">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
      </svg>
      Instalar
    </button>
  </div>
</template>
