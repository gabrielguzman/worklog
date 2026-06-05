<script setup>
import { computed, ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import LineChart from '@/Components/Charts/LineChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'
import axios from 'axios'

const props = defineProps({
    metrics:        Object,
    pendingTasks:   Array,
    todayEntries:   Array,
    recentEntries:  Array,
    recentFiles:    Array,
    weekActivity:   Array,
    recentActivity: Array,
    todayFormatted: String,
    chartData:      Object,
})

const activeTab = ref('today') // today, week, stats, tasks

const priorityConfig = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700' },
    high:   { label: 'Alta',    class: 'bg-orange-100 text-orange-700' },
    medium: { label: 'Media',   class: 'bg-yellow-100 text-yellow-700' },
    low:    { label: 'Baja',    class: 'bg-gray-100 text-gray-500' },
}

const typeConfig = {
    general:       { label: 'General',       emoji: '📝' },
    reunion:       { label: 'Reunión',        emoji: '🤝' },
    deploy:        { label: 'Deploy',         emoji: '🚀' },
    code_review:   { label: 'Code Review',    emoji: '🔍' },
    investigacion: { label: 'Investigación',  emoji: '🔬' },
    planificacion: { label: 'Planificación',  emoji: '📋' },
}

const maxActivity = computed(() => Math.max(...props.weekActivity.map(d => d.count), 1))

// Chart data
const last30DaysChart = computed(() => ({
    labels: props.chartData?.last30Days?.map(d => d.date) || [],
    datasets: [{
        label: 'Tareas completadas',
        data: props.chartData?.last30Days?.map(d => d.count) || [],
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#3B82F6',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
    }]
}))

const projectChart = computed(() => ({
    labels: props.chartData?.projectDistribution?.map(p => p.name) || [],
    datasets: [{
        label: 'Tareas pendientes',
        data: props.chartData?.projectDistribution?.map(p => p.count) || [],
        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
    }]
}))

const priorityChart = computed(() => ({
    labels: props.chartData?.priorityDistribution?.map(p => p.priority) || [],
    datasets: [{
        data: props.chartData?.priorityDistribution?.map(p => p.count) || [],
        backgroundColor: ['#EF4444', '#F97316', '#FBBF24', '#D1D5DB'],
    }]
}))

const focusChart = computed(() => ({
    labels: props.chartData?.focusLast7Days?.map(d => d.date) || [],
    datasets: [{
        label: 'Minutos de enfoque',
        data: props.chartData?.focusLast7Days?.map(d => d.minutes) || [],
        backgroundColor: '#8B5CF6',
        borderColor: '#7C3AED',
        borderWidth: 2,
    }]
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false } }
}

const mimeIcon = (mime) => {
    if (mime?.startsWith('image/')) return '🖼️'
    if (mime === 'application/pdf') return '📄'
    if (mime?.includes('word'))     return '📝'
    if (mime?.includes('excel') || mime?.includes('spreadsheet')) return '📊'
    return '📎'
}

const summary = ref(null)
const summaryError = ref(false)
const summaryLoad = ref(false)

onMounted(() => {
    loadSummary()
})

const loadSummary = async () => {
    summaryLoad.value = true
    summaryError.value = false
    try {
        const res = await axios.get('/api/ai/summary')
        summary.value = res.data.summary
    } catch {
        summaryError.value = true
    } finally {
        summaryLoad.value = false
    }
}

// Computed para tareas urgentes
const urgentTasks = computed(() => {
    if (!props.pendingTasks) return []
    return props.pendingTasks.filter(t => t.priority === 'urgent').slice(0, 5)
})
const highPriorityTasks = computed(() => {
    if (!props.pendingTasks) return []
    return props.pendingTasks.filter(t => t.priority === 'high').slice(0, 5)
})
const overdueTasks = computed(() => {
    if (!props.pendingTasks) return []
    return props.pendingTasks.filter(t => t.is_overdue)
})
</script>

<template>
    <Head title="Dashboard — WorkLog" />

    <AppLayout>
        <div class="p-6 space-y-6 max-w-7xl mx-auto">

            <!-- Header -->
            <div class="flex items-start justify-between gap-4 flex-wrap sm:flex-nowrap">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm sm:text-base text-gray-700 dark:text-gray-300 mt-1 capitalize">{{ todayFormatted }}</p>
                </div>
                <div class="flex gap-2 flex-wrap sm:flex-nowrap">
                    <Link href="/entries/create"
                        aria-label="Crear nueva entrada"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-2 sm:px-4 py-2 text-xs sm:text-sm font-medium text-white hover:bg-blue-700 transition-colors shadow-sm whitespace-nowrap">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Nueva entrada</span>
                        <span class="sm:hidden">Entrada</span>
                    </Link>
                    <Link href="/tasks/create"
                        aria-label="Crear nueva tarea"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors shadow-sm whitespace-nowrap">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="hidden sm:inline">Nueva tarea</span>
                        <span class="sm:hidden">Tarea</span>
                    </Link>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <button @click="activeTab = 'today'"
                    :class="['px-4 py-3 font-medium border-b-2 transition-colors',
                        activeTab === 'today' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    📅 Hoy
                </button>
                <button @click="activeTab = 'week'"
                    :class="['px-4 py-3 font-medium border-b-2 transition-colors',
                        activeTab === 'week' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    📊 Esta semana
                </button>
                <button @click="activeTab = 'stats'"
                    :class="['px-4 py-3 font-medium border-b-2 transition-colors',
                        activeTab === 'stats' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    📈 Estadísticas
                </button>
                <button @click="activeTab = 'tasks'"
                    :class="['px-4 py-3 font-medium border-b-2 transition-colors',
                        activeTab === 'tasks' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900']">
                    ⏳ Tareas
                </button>
            </div>

            <!-- TAB 1: HOY -->
            <div v-if="activeTab === 'today'" class="space-y-6">
                <!-- Métricas del día -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Entradas hoy</span>
                            <span class="text-2xl">📝</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900">{{ metrics.entries_today }}</p>
                        <p class="text-xs text-gray-700 mt-1">registros</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Completadas hoy</span>
                            <span class="text-2xl">✅</span>
                        </div>
                        <p class="text-3xl font-bold text-green-600">{{ metrics.tasks_completed_today }}</p>
                        <p class="text-xs text-gray-700 mt-1">tareas</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Pendientes hoy</span>
                            <span class="text-2xl">⏳</span>
                        </div>
                        <p class="text-3xl font-bold text-orange-600">{{ metrics.tasks_pending }}</p>
                        <p class="text-xs text-gray-700 mt-1">sin completar</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Archivos esta semana</span>
                            <span class="text-2xl">📂</span>
                        </div>
                        <p class="text-3xl font-bold text-blue-600">{{ metrics.files_this_week }}</p>
                        <p class="text-xs text-gray-700 mt-1">subidos</p>
                    </div>
                </div>

                <!-- Resumen IA + Actividad semanal -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Resumen IA -->
                    <div class="lg:col-span-2 rounded-xl border border-gradient bg-gradient-to-r from-purple-50 to-blue-50 shadow-sm p-6 border-transparent bg-clip-border"
                        style="border-image: linear-gradient(135deg, #a78bfa, #60a5fa) 1">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">✨</span>
                                <h3 class="font-semibold text-gray-900">Resumen del día</h3>
                            </div>
                            <button @click="loadSummary" :disabled="summaryLoad"
                                aria-label="Recargar resumen"
                                :class="['text-xs px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-700 dark:text-gray-300 hover:border-gray-300 transition-colors',
                                    summaryLoad ? 'opacity-50 cursor-not-allowed' : '']">
                                <span v-if="summaryLoad" class="inline-block animate-spin">⟳</span>
                                <span v-else>🔄</span>
                            </button>
                        </div>
                        <div v-if="summaryLoad" class="space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded pulse-soft"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded pulse-soft w-5/6"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded pulse-soft w-4/6"></div>
                        </div>
                        <p v-else-if="summaryError" class="text-sm text-red-600">Error al generar el resumen</p>
                        <p v-else class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ summary }}</p>
                    </div>

                    <!-- Actividad semanal -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Actividad semanal</h3>
                        <div class="flex items-end justify-between gap-1.5 h-24 mb-4">
                            <div v-for="day in weekActivity" :key="day.date"
                                class="flex flex-col items-center gap-1 flex-1">
                                <div class="w-full rounded-t-sm transition-all"
                                    :class="day.is_today ? 'bg-blue-500' : 'bg-gray-200 dark:bg-gray-600'"
                                    :style="{ height: day.count > 0 ? `${Math.round((day.count / maxActivity) * 72)}px` : '2px' }"
                                />
                                <span class="text-[8px] text-gray-400 capitalize font-medium">{{ day.label.substring(0, 3) }}</span>
                            </div>
                        </div>
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Entradas recientes</p>
                        <div class="space-y-1.5">
                            <div v-for="entry in recentEntries.slice(0, 4)" :key="entry.id"
                                class="flex items-center gap-1.5 text-xs">
                                <span>{{ typeConfig[entry.type]?.emoji ?? '📝' }}</span>
                                <span class="text-gray-700 dark:text-gray-300 truncate flex-1">{{ entry.title }}</span>
                            </div>
                            <p v-if="recentEntries.length === 0" class="text-xs text-gray-400">Sin entradas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ESTA SEMANA -->
            <div v-if="activeTab === 'week'" class="space-y-6">
                <!-- Gráficos principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Tareas completadas (30 días)</h3>
                        <div class="h-72">
                            <LineChart :chartData="last30DaysChart" :chartOptions="chartOptions" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Tiempo de enfoque (7 días)</h3>
                        <div class="h-72">
                            <BarChart :chartData="focusChart" :chartOptions="chartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Distribución -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Tareas por proyecto</h3>
                        <div class="h-72">
                            <BarChart :chartData="projectChart" :chartOptions="chartOptions" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Distribución por prioridad</h3>
                        <div class="h-72">
                            <DoughnutChart :chartData="priorityChart" :chartOptions="chartOptions" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: ESTADÍSTICAS (Prep for future feature) -->
            <div v-if="activeTab === 'stats'" class="space-y-6">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-8 text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">📊 Estadísticas avanzadas próximamente</p>
                    <p class="text-sm text-gray-700">Análisis profundo de productividad, metas personalizadas y comparativas por período.</p>
                    <Link href="/reports/daily" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Ver reportes actuales
                    </Link>
                </div>
            </div>

            <!-- TAB 4: TAREAS -->
            <div v-if="activeTab === 'tasks'" class="space-y-6">
                <!-- Tareas vencidas -->
                <div v-if="overdueTasks.length > 0" class="rounded-xl border border-red-200 bg-red-50 shadow-sm p-6">
                    <h3 class="font-semibold text-red-900 mb-4">⚠️ Tareas vencidas ({{ overdueTasks.length }})</h3>
                    <div class="space-y-2">
                        <div v-for="task in overdueTasks.slice(0, 5)" :key="task.id"
                            class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-red-100 hover:shadow-md transition-shadow">
                            <span class="text-lg">🔴</span>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">{{ task.title }}</p>
                                <p class="text-xs text-red-600">Vence: {{ task.due_date }}</p>
                            </div>
                            <Link :href="`/tasks/${task.id}`" class="text-blue-600 hover:underline text-xs font-medium shrink-0">Ver →</Link>
                        </div>
                    </div>
                </div>

                <!-- Grid de tareas por prioridad -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Urgentes -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="text-lg">🔴</span> Urgentes
                            <span class="text-xs font-normal text-gray-500">({{ urgentTasks.length }})</span>
                        </h3>
                        <div v-if="urgentTasks.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                            <Link v-for="task in urgentTasks" :key="task.id"
                                :href="`/tasks/${task.id}`"
                                class="block p-3 rounded-lg border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ task.title }}</p>
                                <div class="flex items-center justify-between mt-1">
                                    <span v-if="task.project" class="text-xs text-gray-600 dark:text-gray-400">{{ task.project.name }}</span>
                                    <span class="text-xs text-red-600">{{ task.due_date }}</span>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Sin tareas urgentes 🎉</p>
                            <Link href="/tasks/create" class="text-xs text-blue-600 hover:underline font-medium">
                                Crear tarea
                            </Link>
                        </div>
                    </div>

                    <!-- Altas -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="text-lg">🟠</span> Altas
                            <span class="text-xs font-normal text-gray-500">({{ highPriorityTasks.length }})</span>
                        </h3>
                        <div v-if="highPriorityTasks.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                            <Link v-for="task in highPriorityTasks" :key="task.id"
                                :href="`/tasks/${task.id}`"
                                class="block p-3 rounded-lg border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ task.title }}</p>
                                <div class="flex items-center justify-between mt-1">
                                    <span v-if="task.project" class="text-xs text-gray-600 dark:text-gray-400">{{ task.project.name }}</span>
                                    <span class="text-xs text-orange-600">{{ task.due_date }}</span>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Sin tareas de alta prioridad 🎉</p>
                            <Link href="/tasks/create" class="text-xs text-blue-600 hover:underline font-medium">
                                Crear tarea
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Todas las tareas -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">📋 Todas las tareas ({{ pendingTasks.length }})</h3>
                        <Link href="/tasks/kanban" aria-label="Ver todas las tareas en Kanban" class="text-sm text-blue-600 hover:underline font-medium">Ver Kanban →</Link>
                    </div>

                    <div v-if="pendingTasks.length === 0" class="text-center py-8">
                        <p class="text-gray-700">Sin tareas pendientes</p>
                        <p class="text-sm text-gray-400 mt-1">¡Excelente trabajo! 🎉</p>
                    </div>

                    <div v-else class="space-y-1 max-h-96 overflow-y-auto">
                        <Link v-for="task in pendingTasks" :key="task.id"
                            :href="`/tasks/${task.id}`"
                            class="flex items-center gap-2.5 px-4 py-3 rounded-lg border border-transparent hover:border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-all">
                            <div :class="['h-2 w-2 rounded-full shrink-0',
                                task.status === 'done' ? 'bg-green-500' :
                                task.priority === 'urgent' ? 'bg-red-500' :
                                task.priority === 'high' ? 'bg-orange-500' : 'bg-gray-300'
                            ]" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ task.title }}</p>
                                <div class="flex gap-1.5 mt-0.5 flex-wrap">
                                    <span v-if="task.project" class="text-[10px] px-1.5 py-0.5 rounded-full text-white"
                                        :style="{ backgroundColor: task.project.color }">
                                        {{ task.project.name.substring(0, 8) }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-[10px] text-gray-700 shrink-0 whitespace-nowrap">{{ task.due_date }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Tab transitions */
.v-enter-active, .v-leave-active {
    transition: opacity 0.2s ease;
}

.v-enter-from, .v-leave-to {
    opacity: 0;
}
</style>
