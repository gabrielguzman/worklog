<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    date: String,
    dateFormatted: String,
    metrics: Object,
    entries: Array,
    tasksCompleted: Array,
    focusSessions: Array,
    projectStats: Array,
})

const selectedDate = ref(props.date)

const getExportUrl = (format) => `/export/report/daily/${format}?date=${props.date}`

const prevDate = computed(() => {
    const d = new Date(selectedDate.value)
    d.setDate(d.getDate() - 1)
    return d.toISOString().split('T')[0]
})

const nextDate = computed(() => {
    const d = new Date(selectedDate.value)
    d.setDate(d.getDate() + 1)
    return d.toISOString().split('T')[0]
})

const goToDate = () => {
    router.get('/reports/daily', { date: selectedDate.value })
}

const focusHours = computed(() => (props.metrics.focus_minutes / 60).toFixed(1))

const TYPE_EMOJI = {
    general: '📝', reunion: '🤝', deploy: '🚀', code_review: '🔍',
    investigacion: '🔬', planificacion: '📋',
}

const PRIORITY = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700 dark:text-red-400' },
    high: { label: 'Alta', class: 'bg-orange-100 text-orange-700 dark:text-orange-400' },
    medium: { label: 'Media', class: 'bg-yellow-100 text-yellow-700 dark:text-yellow-400' },
    low: { label: 'Baja', class: 'bg-gray-100 text-gray-500 dark:text-gray-400' },
}
</script>

<template>
    <Head :title="`Reporte ${dateFormatted} — WorkLog`" />

    <AppLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <!-- Header con navegación -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Reporte del día</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">{{ dateFormatted }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Selector de fecha -->
                    <div class="flex items-center gap-2">
                        <Link :href="`/reports/daily?date=${prevDate}`"
                            class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </Link>
                        <input v-model="selectedDate" type="date" @change="goToDate"
                            class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <Link :href="`/reports/daily?date=${nextDate}`"
                            class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Export button -->
                    <a :href="getExportUrl('csv')" class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        📥 Descargar
                    </a>

                    <Link href="/dashboard"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Métricas principales -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Entradas registradas</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ metrics.entries_count }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Tareas completadas</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ metrics.tasks_completed }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Tareas pendientes</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">{{ metrics.tasks_pending }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Tiempo de enfoque</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ focusHours }}h</p>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Entradas -->
                <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Entradas del día</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="entries.length === 0" class="px-5 py-8 text-center text-gray-400 text-sm">
                            Sin entradas registradas
                        </div>
                        <div v-for="entry in entries" :key="entry.id"
                            class="px-5 py-3.5 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start gap-3">
                                <span class="text-lg shrink-0 mt-0.5">{{ TYPE_EMOJI[entry.type] ?? '📝' }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-medium text-gray-900 dark:text-white truncate">{{ entry.title }}</p>
                                        <span class="text-xs text-gray-400 shrink-0">{{ entry.time.substring(0, 5) }}</span>
                                    </div>
                                    <div v-if="entry.project || entry.tags.length" class="flex items-center gap-2 flex-wrap">
                                        <span v-if="entry.project"
                                            class="text-[10px] font-medium px-2 py-1 rounded-full text-white"
                                            :style="{ backgroundColor: entry.project.color }">
                                            {{ entry.project.name }}
                                        </span>
                                        <span v-for="tag in entry.tags.slice(0, 2)" :key="tag.name"
                                            class="text-[10px] px-2 py-1 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                            {{ tag.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas por proyecto -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Por proyecto</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="projectStats.length === 0" class="px-5 py-6 text-center text-gray-400 text-xs">
                            Sin tareas completadas
                        </div>
                        <div v-for="(stat, i) in projectStats" :key="i"
                            class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <p class="text-sm text-gray-700 font-medium">{{ stat.project_name }}</p>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ stat.count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tareas completadas y sesiones de enfoque -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Tareas completadas -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">✅ Tareas completadas</h2>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                        <div v-if="tasksCompleted.length === 0" class="px-5 py-8 text-center text-gray-400 text-sm">
                            Sin tareas completadas
                        </div>
                        <div v-for="task in tasksCompleted" :key="task.id"
                            class="px-5 py-3 hover:bg-gray-50 transition-colors group">
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1.5">{{ task.title }}</p>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span :class="['text-[10px] font-semibold px-2 py-1 rounded-full', PRIORITY[task.priority]?.class]">
                                    {{ PRIORITY[task.priority]?.label }}
                                </span>
                                <span v-if="task.project"
                                    class="text-[10px] px-2 py-1 rounded-full text-white font-medium"
                                    :style="{ backgroundColor: task.project.color }">
                                    {{ task.project.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sesiones de enfoque -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">⏱️ Sesiones de enfoque</h2>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-80 overflow-y-auto">
                        <div v-if="focusSessions.length === 0" class="px-5 py-8 text-center text-gray-400 text-sm">
                            Sin sesiones de enfoque
                        </div>
                        <div v-for="session in focusSessions" :key="session.id"
                            class="px-5 py-3 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ session.duration_minutes }}min</p>
                                <span :class="[
                                    'text-[10px] font-medium px-2 py-1 rounded-full',
                                    session.status === 'completed' ? 'bg-green-100 text-green-700 dark:text-green-400' : 'bg-gray-100 text-gray-600'
                                ]">
                                    {{ session.status === 'completed' ? '✓' : '✕' }}
                                </span>
                            </div>
                            <p v-if="session.task" class="text-xs text-gray-600 truncate">📌 {{ session.task.title }}</p>
                            <p v-if="session.notes" class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">{{ session.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
