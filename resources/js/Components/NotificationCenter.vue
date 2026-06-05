<script setup>
import { useNotification } from '@/composables/useNotification'

const { notifications, remove } = useNotification()

const iconMap = {
    success: '✓',
    error: '✕',
    info: 'ℹ',
    warning: '⚠',
}

const colorMap = {
    success: 'bg-green-50 dark:bg-green-900/30 border-green-200 text-green-800',
    error: 'bg-red-50 dark:bg-red-900/30 border-red-200 text-red-800',
    info: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 text-blue-800',
    warning: 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 text-yellow-800',
}

const dotMap = {
    success: 'bg-green-500',
    error: 'bg-red-500',
    info: 'bg-blue-500',
    warning: 'bg-yellow-500',
}
</script>

<template>
    <div role="status" aria-live="polite" aria-label="Notificaciones del sistema" class="fixed top-6 right-6 z-50 space-y-3 max-w-md">
        <transition-group name="notification" tag="div">
            <div v-for="notification in notifications" :key="notification.id"
                :class="['rounded-lg border px-4 py-3 shadow-lg flex items-center gap-3 animate-in fade-in slide-in-from-top-2', colorMap[notification.type]]">

                <!-- Icono de estado -->
                <div :class="['w-5 h-5 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0', dotMap[notification.type]]">
                    {{ iconMap[notification.type] }}
                </div>

                <!-- Mensaje -->
                <p class="flex-1 text-sm font-medium">{{ notification.message }}</p>

                <!-- Botón cerrar -->
                <button @click="remove(notification.id)"
                    class="text-sm opacity-60 hover:opacity-100 transition-opacity shrink-0">
                    ✕
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.notification-enter-active,
.notification-leave-active {
    transition: all 0.3s ease;
}

.notification-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.notification-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>
