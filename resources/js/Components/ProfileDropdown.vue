<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    user: Object,
})

const open = ref(false)

const logout = () => {
    router.post('/logout')
}
</script>

<template>
    <div class="relative">
        <button
            @click="open = !open"
            class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors w-full"
        >
            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold shrink-0">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ user.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
            </div>
            <svg
                class="h-4 w-4 text-gray-500 dark:text-gray-400 shrink-0 transition-transform"
                :class="{ 'rotate-180': open }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="open"
                @click.away="open = false"
                class="absolute bottom-full left-0 right-0 mb-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
            >
                <!-- Perfil -->
                <Link
                    href="/profile"
                    @click="open = false"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    👤 Mi Perfil
                </Link>

                <!-- Separador -->
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                <!-- Preferencias -->
                <button
                    disabled
                    class="block w-full text-left px-4 py-2 text-sm text-gray-500 dark:text-gray-500 opacity-50 cursor-not-allowed"
                >
                    ⚙️ Preferencias
                </button>

                <!-- Separador -->
                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                <!-- Cerrar sesión -->
                <button
                    @click="logout"
                    class="block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                >
                    🚪 Cerrar sesión
                </button>
            </div>
        </transition>
    </div>
</template>
