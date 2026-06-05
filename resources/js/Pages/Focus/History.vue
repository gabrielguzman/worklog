<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    sessions: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')

const apply = () => {
    router.get('/focus/history', {
        search: search.value,
        status: status.value,
        from: from.value,
        to: to.value,
    }, { preserveState: true, replace: true })
}

const clearFilters = () => {
    search.value = ''
    status.value = ''
    from.value = ''
    to.value = ''
    router.get('/focus/history', {})
}

const hasFilters = computed(() =>
    search.value || status.value || from.value || to.value
)

const getStatusLabel = (status) => {
    const labels = {
        'running': 'En progreso',
        'completed': 'Completada',
        'cancelled': 'Cancelada',
    }
    return labels[status] || status
}

const getStatusColor = (status) => {
    const colors = {
        'running': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'cancelled': 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400',
    }
    return colors[status] || 'bg-gray-100 text-gray-700'
}
</script>

<template>
    <Head title="Historial de Sesiones — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Historial de sesiones</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Todas tus sesiones de enfoque</p>
                </div>
                <Link href="/focus"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva sesión
                </Link>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Total sesiones</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_sessions }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Completadas</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.completed_sessions }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Total minutos</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ stats.total_minutes }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Promedio min</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ stats.avg_duration }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Sesiones semana</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ stats.week_sessions }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:shadow-md dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Min semana</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ stats.week_minutes }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-4 space-y-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="search" placeholder="Buscar en notas o tarea..." @input="apply"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 pl-9 pr-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:bg-gray-700 dark:text-white" />
                </div>

                <div class="flex gap-2 flex-wrap">
                    <select v-model="status" @change="apply" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none dark:bg-gray-700 dark:text-white">
                        <option value="">Todos los estados</option>
                        <option value="completed">Completadas</option>
                        <option value="running">En progreso</option>
                        <option value="cancelled">Canceladas</option>
                    </select>

                    <input v-model="from" type="date" @change="apply" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none dark:bg-gray-700 dark:text-white" />

                    <input v-model="to" type="date" @change="apply" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none dark:bg-gray-700 dark:text-white" />

                    <button v-if="hasFilters" @click="clearFilters"
                        class="flex items-center gap-1 rounded-lg border border-red-200 dark:border-red-700 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de sesiones -->
            <div v-if="sessions.data.length === 0" class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
                <p class="text-4xl mb-3">🍅</p>
                <p class="text-gray-700 dark:text-gray-400 font-medium">
                    {{ hasFilters ? 'Sin sesiones con esos filtros' : 'No hay sesiones de enfoque registradas' }}
                </p>
            </div>

            <div v-else class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Fecha y hora</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Tarea</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Duración</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Estado</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Notas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="session in sessions.data" :key="session.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ session.started_at_label }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="session.task" class="flex items-center gap-2">
                                        <div v-if="session.task.project" class="h-3 w-3 rounded-full" :style="{ backgroundColor: session.task.project.color }" />
                                        <a :href="`/tasks/${session.task.id}`" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ session.task.title }}
                                        </a>
                                    </div>
                                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">Sin tarea</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ session.duration_minutes }}m</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusColor(session.status)]">
                                        {{ getStatusLabel(session.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p v-if="session.notes" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ session.notes }}
                                    </p>
                                    <span v-else class="text-sm text-gray-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="sessions.last_page > 1" class="flex items-center justify-between pt-4">
                <p class="text-sm text-gray-700 dark:text-gray-400">Mostrando {{ sessions.from }}–{{ sessions.to }} de {{ sessions.total }}</p>
                <div class="flex gap-1">
                    <Link v-if="sessions.prev_page_url" :href="sessions.prev_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">← Anterior</Link>
                    <Link v-if="sessions.next_page_url" :href="sessions.next_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">Siguiente →</Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
