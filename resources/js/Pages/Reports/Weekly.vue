<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    weekStart: String,
    weekEnd: String,
    weekFormatted: String,
    metrics: Object,
    dailyStats: Array,
    projectStats: Array,
    entryTypes: Array,
    topTags: Array,
})

const selectedDate = ref(props.weekStart)

const goToWeek = () => {
    router.get('/reports/weekly', { week: selectedDate.value })
}

const getExportUrl = (format) => {
    const date = new Date(props.weekStart)
    const week = Math.ceil((date.getDate() + new Date(date.getFullYear(), date.getMonth(), 1).getDay()) / 7)
    const year = date.getFullYear()
    return `/export/report/weekly/${format}?week=${week}&year=${year}`
}

const prevWeek = computed(() => {
    const d = new Date(selectedDate.value)
    d.setDate(d.getDate() - 7)
    return d.toISOString().split('T')[0]
})

const nextWeek = computed(() => {
    const d = new Date(selectedDate.value)
    d.setDate(d.getDate() + 7)
    return d.toISOString().split('T')[0]
})

const maxDailyTasks = computed(() => {
    return Math.max(...props.dailyStats.map(d => d.tasks_completed), 1)
})

const maxDailyMinutes = computed(() => {
    return Math.max(...props.dailyStats.map(d => d.focus_minutes), 1)
})

const totalFocusHours = computed(() => (props.metrics.total_focus_minutes / 60).toFixed(1))
</script>

<template>
    <Head :title="`Reporte Semanal ${weekFormatted} — WorkLog`" />

    <AppLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Reporte semanal</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">{{ weekFormatted }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="`/reports/weekly?week=${prevWeek}`"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <input v-model="selectedDate" type="date" @change="goToWeek"
                        class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <Link :href="`/reports/weekly?week=${nextWeek}`"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>

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
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Entradas totales</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ metrics.total_entries }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Tareas completadas</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ metrics.total_tasks_completed }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Promedio tareas/día</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ metrics.avg_tasks_per_day }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Tiempo de enfoque</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">{{ totalFocusHours }}h</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Promedio enfoque/día</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">{{ metrics.avg_focus_minutes_per_day }}m</p>
                </div>
            </div>

            <!-- Actividad diaria -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Tareas completadas por día -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Tareas completadas por día</h2>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3">
                            <div v-for="day in dailyStats" :key="day.date"
                                class="flex items-center gap-3">
                                <p class="text-xs font-medium text-gray-600 w-10">{{ day.label }}</p>
                                <div class="flex-1 h-6 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full transition-all"
                                        :style="{ width: `${(day.tasks_completed / maxDailyTasks) * 100}%` }"
                                    />
                                </div>
                                <p class="text-xs font-bold text-gray-700 w-6 text-right">{{ day.tasks_completed }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minutos de enfoque por día -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Tiempo de enfoque por día</h2>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3">
                            <div v-for="day in dailyStats" :key="day.date"
                                class="flex items-center gap-3">
                                <p class="text-xs font-medium text-gray-600 w-10">{{ day.label }}</p>
                                <div class="flex-1 h-6 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full transition-all"
                                        :style="{ width: `${(day.focus_minutes / maxDailyMinutes) * 100}%` }"
                                    />
                                </div>
                                <p class="text-xs font-bold text-gray-700 w-14 text-right">{{ day.focus_minutes }}m</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Proyectos y etiquetas -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Top proyectos -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Proyectos más activos</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="projectStats.length === 0" class="px-5 py-6 text-center text-gray-400 text-xs">
                            Sin datos
                        </div>
                        <div v-for="(stat, i) in projectStats" :key="i"
                            class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <p class="text-sm text-gray-700 font-medium truncate">{{ stat.project_name }}</p>
                            <span class="text-sm font-bold text-green-600 shrink-0">{{ stat.count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tipos de entrada -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Tipos de entrada</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="entryTypes.length === 0" class="px-5 py-6 text-center text-gray-400 text-xs">
                            Sin datos
                        </div>
                        <div v-for="(type, i) in entryTypes" :key="i"
                            class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <p class="text-sm text-gray-700 capitalize">{{ type.type }}</p>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ type.count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Top tags -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Top etiquetas</h2>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="topTags.length === 0" class="px-5 py-6 text-center text-gray-400 text-xs">
                            Sin datos
                        </div>
                        <div v-for="tag in topTags" :key="tag.name"
                            class="px-5 py-3 hover:bg-gray-50 transition-colors">
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ tag.name }}</p>
                            <div class="flex gap-2 text-xs text-gray-600">
                                <span v-if="tag.tasks_completed > 0">📋 {{ tag.tasks_completed }} tareas</span>
                                <span v-if="tag.entries > 0">📝 {{ tag.entries }} entradas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
