<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()

const props = defineProps({
    project: Object,
    entries: Array,
    tasks: Array,
    files: Array,
})

const TYPE_EMOJI = {
    general: '📝', reunion: '🤝', deploy: '🚀', code_review: '🔍',
    investigacion: '🔬', planificacion: '📋',
}

const PRIORITY_DOT = {
    urgent: 'bg-red-500', high: 'bg-orange-400', medium: 'bg-yellow-400', low: 'bg-gray-300',
}

const STATUS = {
    pending: { label: 'Pendiente', class: 'text-gray-500 dark:text-gray-400 dark:text-gray-400' },
    in_progress: { label: 'En progreso', class: 'text-blue-600 dark:text-blue-400 dark:text-blue-400' },
    done: { label: 'Hecha', class: 'text-green-600' },
}

const stats = computed(() => ({
    entries: props.entries.length,
    tasks: props.tasks.length,
    tasksDone: props.tasks.filter(t => t.status === 'done').length,
    files: props.files.length,
}))

const mimeIcon = (mime) => {
    if (mime?.startsWith('image/')) return '🖼️'
    if (mime === 'application/pdf') return '📄'
    if (mime?.includes('word')) return '📝'
    if (mime?.includes('excel') || mime?.includes('spreadsheet')) return '📊'
    return '📎'
}
</script>

<template>
    <Head :title="`${project.name} — WorkLog`" />

    <AppLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl flex items-center justify-center text-white font-bold"
                        :style="{ backgroundColor: project.color }">
                        {{ project.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ project.name }}</h1>
                        <p v-if="project.description" class="text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">{{ project.description }}</p>
                    </div>
                </div>
                <Link href="/projects"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Entradas</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mt-1">{{ stats.entries }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Tareas</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mt-1">{{ stats.tasks }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Completadas</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.tasksDone }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Archivos</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mt-1">{{ stats.files }}</p>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <Link :href="`${route('entries.create')}?project=${project.id}`"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-blue-300 hover:shadow-md transition-all group">
                    <div class="h-9 w-9 rounded-lg bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 flex items-center justify-center group-hover:bg-blue-100">📝</div>
                    <div class="text-sm">
                        <p class="font-medium text-gray-800 dark:text-gray-100 dark:text-gray-100">Nueva entrada</p>
                        <p class="text-xs text-gray-400">en este proyecto</p>
                    </div>
                </Link>
                <Link :href="`${route('tasks.create')}?project=${project.id}`"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-green-300 hover:shadow-md transition-all group">
                    <div class="h-9 w-9 rounded-lg bg-green-50 dark:bg-green-900/30 dark:bg-green-900/30 flex items-center justify-center group-hover:bg-green-100">✅</div>
                    <div class="text-sm">
                        <p class="font-medium text-gray-800 dark:text-gray-100 dark:text-gray-100">Nueva tarea</p>
                        <p class="text-xs text-gray-400">en este proyecto</p>
                    </div>
                </Link>
                <Link :href="`${route('files.index')}?project=${project.id}`"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-purple-300 hover:shadow-md transition-all group">
                    <div class="h-9 w-9 rounded-lg bg-purple-50 flex items-center justify-center group-hover:bg-purple-100">📎</div>
                    <div class="text-sm">
                        <p class="font-medium text-gray-800 dark:text-gray-100 dark:text-gray-100">Ver archivos</p>
                        <p class="text-xs text-gray-400">del proyecto</p>
                    </div>
                </Link>
                <Link :href="route('projects.index')"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-gray-300 dark:border-gray-600 dark:border-gray-600 hover:shadow-md transition-all group">
                    <div class="h-9 w-9 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center group-hover:bg-gray-100">⚙️</div>
                    <div class="text-sm">
                        <p class="font-medium text-gray-800 dark:text-gray-100 dark:text-gray-100">Editar proyecto</p>
                        <p class="text-xs text-gray-400">configuración</p>
                    </div>
                </Link>
            </div>

            <!-- Grid principal -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Tareas -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white dark:text-white">Tareas</h2>
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:text-gray-400">
                            {{ stats.tasksDone }}/{{ stats.tasks }}
                        </span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="tasks.length === 0" class="px-5 py-8 text-center text-gray-400 text-sm">
                            Sin tareas en este proyecto
                        </div>
                        <div v-else>
                            <div v-for="task in tasks" :key="task.id"
                                class="flex items-start gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                <div :class="['h-2 w-2 rounded-full mt-1.5 shrink-0', PRIORITY_DOT[task.priority]]" />
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('tasks.show', task.id)"
                                        :class="['text-sm font-medium hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors', task.status === 'done' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white dark:text-white']">
                                        {{ task.title }}
                                    </Link>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span :class="['text-xs', STATUS[task.status]?.class]">
                                            {{ STATUS[task.status]?.label }}
                                        </span>
                                        <span v-if="task.due_date" class="text-xs text-gray-400">
                                            📅 {{ task.due_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Entradas -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white dark:text-white">Entradas</h2>
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:text-gray-400">
                            {{ stats.entries }}
                        </span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div v-if="entries.length === 0" class="px-5 py-8 text-center text-gray-400 text-sm">
                            Sin entradas en este proyecto
                        </div>
                        <div v-else>
                            <div v-for="entry in entries.slice(0, 8)" :key="entry.id"
                                class="flex items-start gap-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                <span class="text-lg mt-0.5">{{ TYPE_EMOJI[entry.type] ?? '📝' }}</span>
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('entries.show', entry.id)"
                                        class="text-sm font-medium text-gray-900 dark:text-white dark:text-white hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors truncate block">
                                        {{ entry.title }}
                                    </Link>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-gray-400">{{ entry.time }}</span>
                                        <span class="text-xs text-gray-400">{{ entry.date }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="entries.length > 8" class="px-5 py-3 text-center border-t border-gray-50">
                                <Link href="/entries"
                                    class="text-xs text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline font-medium">
                                    Ver todas las entradas →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archivos -->
            <div v-if="files.length > 0" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white dark:text-white">Archivos</h2>
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:text-gray-400">
                        {{ stats.files }}
                    </span>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="file in files.slice(0, 10)" :key="file.id"
                        class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                        <span class="text-xl shrink-0">{{ mimeIcon(file.mime_type) }}</span>
                        <div class="flex-1 min-w-0">
                            <a :href="file.url" target="_blank"
                                class="text-sm text-gray-900 dark:text-white dark:text-white hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors truncate block">
                                {{ file.original_name }}
                            </a>
                            <p class="text-xs text-gray-400">{{ file.size_humans }}</p>
                        </div>
                        <a :href="file.url" download
                            class="p-1.5 text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 rounded-lg transition-colors shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
