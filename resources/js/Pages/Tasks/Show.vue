<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TaskComments from '@/Components/TaskComments.vue'

const props = defineProps({ task: Object })

const PRIORITY = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700 dark:text-red-400'      },
    high:   { label: 'Alta',    class: 'bg-orange-100 text-orange-700 dark:text-orange-400' },
    medium: { label: 'Media',   class: 'bg-yellow-100 text-yellow-700 dark:text-yellow-400' },
    low:    { label: 'Baja',    class: 'bg-gray-100 text-gray-500 dark:text-gray-400'    },
}
const STATUS = {
    pending:     { label: 'Pendiente',   class: 'bg-gray-100 text-gray-600 dark:text-gray-400',   icon: '○' },
    in_progress: { label: 'En progreso', class: 'bg-blue-100 text-blue-700',   icon: '◑' },
    done:        { label: 'Hecha',       class: 'bg-green-100 text-green-700 dark:text-green-400', icon: '✓' },
}

const toggle = () => router.patch(`/tasks/${props.task.id}/toggle`, {}, { preserveScroll: true })
const deleteTask = () => {
    if (confirm('¿Eliminar esta tarea?')) router.delete(`/tasks/${props.task.id}`)
}

const formatMinutes = (m) => {
    if (m < 60) return `${m} min`
    return `${Math.floor(m / 60)}h ${m % 60}min`
}

const mimeIcon = (mime) => {
    if (mime?.startsWith('image/')) return '🖼️'
    if (mime === 'application/pdf') return '📄'
    return '📎'
}

// ── Entrada rápida ──────────────────────────────────────────────
const showQuickEntry = ref(false)
const entryForm = useForm({
    title:      '',
    content:    '',
    type:       'general',
    project_id: props.task?.project_id || '',
    entry_date: new Date().toISOString().split('T')[0],
    entry_time: new Date().toTimeString().slice(0, 5),
    is_pinned:  false,
    tags:       [],
})

const createQuickEntry = () => {
    entryForm.post('/entries', {
        onSuccess: () => {
            showQuickEntry.value = false
            router.reload({ preserveScroll: true })
        },
    })
}

// ── Subtareas ──────────────────────────────────────────────
const showSubtaskForm = ref(false)
const subtasksCollapsed = ref(false)
const subtaskForm = useForm({
    title:    '',
    priority: 'medium',
    due_date: '',
})

const createSubtask = () => {
    subtaskForm.post(`/tasks/${props.task.id}/subtasks`, {
        onSuccess: () => {
            subtaskForm.reset()
            subtaskForm.priority = 'medium'
            showSubtaskForm.value = false
            router.reload({ preserveScroll: true })
        },
    })
}

const toggleSubtask = (id) => {
    router.patch(`/tasks/${id}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
    <Head :title="`${task.title} — WorkLog`" />

    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <!-- Breadcrumb + acciones -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <Link href="/tasks" class="hover:text-blue-600 dark:text-blue-400">Tareas</Link>
                    <span>/</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium truncate max-w-xs">{{ task.title }}</span>
                </div>
                <div class="flex gap-2">
                    <button @click="toggle"
                        :class="[
                            'flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors',
                            task.status === 'done'
                                ? 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900'
                                : 'border-green-400 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-100'
                        ]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ task.status === 'done' ? 'Marcar pendiente' : 'Marcar hecha' }}
                    </button>
                    <button @click="showQuickEntry = true"
                        class="flex items-center gap-1.5 rounded-lg border border-purple-200 px-3 py-1.5 text-sm text-purple-700 hover:bg-purple-50 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        + Entrada
                    </button>
                    <Link :href="`/tasks/${task.id}/edit`"
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </Link>
                    <button @click="deleteTask"
                        class="flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:bg-red-900/30 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar
                    </button>
                </div>
            </div>

            <!-- Card principal -->
            <div class="rounded-xl border bg-white dark:bg-gray-800 shadow-sm dark:shadow-md"
                :class="task.is_overdue ? 'border-red-200' : 'border-gray-200 dark:border-gray-700'">
                <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start gap-3">
                        <!-- Checkbox de estado -->
                        <button @click="toggle"
                            :class="[
                                'mt-1 h-6 w-6 rounded-full border-2 shrink-0 flex items-center justify-center transition-all',
                                task.status === 'done'        ? 'border-green-500 bg-green-500' :
                                task.status === 'in_progress' ? 'border-blue-500 bg-blue-100'  :
                                                                'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                            ]">
                            <svg v-if="task.status === 'done'" class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <div v-else-if="task.status === 'in_progress'" class="h-2.5 w-2.5 rounded-full bg-blue-500" />
                        </button>

                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', PRIORITY[task.priority]?.class]">
                                    {{ PRIORITY[task.priority]?.label }}
                                </span>
                                <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', STATUS[task.status]?.class]">
                                    {{ STATUS[task.status]?.icon }} {{ STATUS[task.status]?.label }}
                                </span>
                                <span v-if="task.project"
                                    class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                                    :style="{ backgroundColor: task.project.color }">
                                    {{ task.project.name }}
                                </span>
                                <span v-for="tag in task.tags" :key="tag.id"
                                    class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                    {{ tag.name }}
                                </span>
                            </div>

                            <h1 :class="['text-2xl font-bold leading-snug', task.status === 'done' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white']">
                                {{ task.title }}
                            </h1>

                            <!-- Meta -->
                            <div class="flex items-center gap-4 mt-2 text-sm text-gray-400 flex-wrap">
                                <span v-if="task.due_label">
                                    <span v-if="task.is_overdue" class="text-red-600 font-semibold">⚠ Vencida: {{ task.due_label }}</span>
                                    <span v-else>📅 {{ task.due_label }}</span>
                                </span>
                                <span v-if="task.recurrence_type && task.recurrence_type !== 'none'" class="text-blue-600 dark:text-blue-400">
                                    ↺ Repite cada {{ task.recurrence_interval }}
                                    {{ task.recurrence_type === 'daily' ? 'día(s)' : task.recurrence_type === 'weekly' ? 'semana(s)' : 'mes(es)' }}
                                    <span v-if="task.recurrence_ends_at" class="text-gray-400">
                                        hasta {{ task.recurrence_ends_at }}
                                    </span>
                                </span>
                                <span v-if="task.completed_at" class="text-green-600">
                                    ✓ Completada {{ task.completed_at }}
                                </span>
                                <span>Creada {{ task.created_at }}</span>
                                <span v-if="task.total_focus_minutes > 0" class="text-purple-600 dark:text-purple-400">
                                    🍅 {{ formatMinutes(task.total_focus_minutes) }} de foco
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb al padre si es subtarea -->
                <div v-if="task.parent_task" class="flex items-center gap-1.5 text-sm text-gray-400 mb-2">
                    <span>Subtarea de</span>
                    <Link :href="`/tasks/${task.parent_task.id}`" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                        {{ task.parent_task.title }}
                    </Link>
                </div>

                <!-- Descripción -->
                <div class="px-6 py-5">
                    <p v-if="task.description" class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed whitespace-pre-wrap">
                        {{ task.description }}
                    </p>
                    <p v-else class="text-gray-400 italic text-sm">Sin descripción.</p>

                    <!-- Entrada vinculada -->
                    <div v-if="task.entry" class="mt-4 flex items-center gap-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-100">
                        <span class="text-lg">📝</span>
                        <div>
                            <p class="text-xs text-blue-500 font-medium">Entrada del registro</p>
                            <Link :href="`/entries/${task.entry.id}`" class="text-sm text-blue-700 hover:underline font-medium">
                                {{ task.entry.title }}
                                <span class="font-normal text-blue-400 ml-1">— {{ task.entry.date }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subtareas -->
            <div v-if="!task.parent_task" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                <!-- Header colapsable -->
                <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700 cursor-pointer"
                     @click="subtasksCollapsed = !subtasksCollapsed">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">
                            Subtareas
                        </h2>
                        <p v-if="task.subtask_progress.total > 0" class="text-xs text-gray-400 mt-0.5">
                            {{ task.subtask_progress.done }} / {{ task.subtask_progress.total }} completadas
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Barra de progreso -->
                        <div v-if="task.subtask_progress.total > 0"
                             class="w-20 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full transition-all"
                                 :style="{ width: `${(task.subtask_progress.done / task.subtask_progress.total) * 100}%` }"/>
                        </div>
                        <!-- Chevron -->
                        <svg :class="['h-4 w-4 text-gray-400 transition-transform', subtasksCollapsed ? '' : 'rotate-180']"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                <!-- Lista de subtareas -->
                <div v-if="!subtasksCollapsed" class="px-5 py-4 space-y-2">
                    <div v-if="task.subtasks.length === 0 && !showSubtaskForm"
                         class="py-4 text-center text-gray-400 text-sm">
                        Sin subtareas
                    </div>

                    <div v-for="sub in task.subtasks" :key="sub.id"
                         class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 group">
                        <!-- Toggle checkbox -->
                        <button @click="toggleSubtask(sub.id)"
                            :class="[
                                'h-4 w-4 rounded border-2 shrink-0 flex items-center justify-center transition-all',
                                sub.status === 'done' ? 'border-green-500 bg-green-500' : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                            ]">
                            <svg v-if="sub.status === 'done'" class="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>

                        <Link :href="`/tasks/${sub.id}`"
                            :class="['flex-1 text-sm truncate hover:text-blue-600 dark:text-blue-400', sub.status === 'done' ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300']">
                            {{ sub.title }}
                        </Link>

                        <!-- Meta -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span v-if="sub.is_overdue" class="text-xs text-red-500">⚠ {{ sub.due_label }}</span>
                            <span v-else-if="sub.due_date" class="text-xs text-gray-400">{{ sub.due_label }}</span>
                            <span v-for="t in sub.tags" :key="t.id"
                                class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                {{ t.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Formulario inline para nueva subtarea -->
                    <div v-if="showSubtaskForm" class="mt-2 p-3 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/30 space-y-2">
                        <input v-model="subtaskForm.title" type="text" autofocus
                            placeholder="Título de la subtarea..."
                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                            @keydown.enter.prevent="createSubtask"
                            @keydown.escape="showSubtaskForm = false" />
                        <div class="flex items-center gap-2">
                            <select v-model="subtaskForm.priority"
                                class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-xs focus:outline-none">
                                <option value="low">Baja</option>
                                <option value="medium">Media</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                            <input v-model="subtaskForm.due_date" type="date"
                                class="rounded-lg border border-gray-200 dark:border-gray-700 px-2 py-1.5 text-xs focus:outline-none" />
                            <button @click="createSubtask" :disabled="subtaskForm.processing || !subtaskForm.title"
                                class="ml-auto rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60">
                                Agregar
                            </button>
                            <button @click="showSubtaskForm = false"
                                class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                                Cancelar
                            </button>
                        </div>
                    </div>

                    <!-- Botón para abrir el form -->
                    <button v-if="!showSubtaskForm"
                        @click="showSubtaskForm = true"
                        class="flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 hover:underline mt-1">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar subtarea
                    </button>
                </div>
            </div>

            <!-- Fila inferior: focus sessions + adjuntos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Focus Sessions -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Sesiones de foco</h2>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ task.focus_sessions.length }} sesión{{ task.focus_sessions.length !== 1 ? 'es' : '' }}
                                · {{ formatMinutes(task.total_focus_minutes) }} total
                            </p>
                        </div>
                        <Link href="/focus"
                            class="flex items-center gap-1 text-xs text-purple-600 dark:text-purple-400 hover:underline font-medium">
                            🍅 Iniciar Pomodoro
                        </Link>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="task.focus_sessions.length === 0"
                            class="py-6 text-center text-gray-400 text-sm">
                            Sin sesiones de foco registradas
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="s in task.focus_sessions" :key="s.id"
                                class="flex items-start gap-3 p-2.5 rounded-lg bg-gray-50 dark:bg-gray-900">
                                <span class="text-lg shrink-0">🍅</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ s.duration_minutes }} min</span>
                                        <span class="text-xs text-gray-400">{{ s.started_at }}</span>
                                    </div>
                                    <p v-if="s.notes" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ s.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adjuntos -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">
                            Archivos adjuntos
                            <span class="ml-1.5 text-xs font-normal text-gray-400">({{ task.attachments.length }})</span>
                        </h2>
                        <Link :href="`/files?task=${task.id}`" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">+ Subir</Link>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="task.attachments.length === 0"
                            class="py-6 text-center text-gray-400 text-sm">
                            Sin archivos adjuntos
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="att in task.attachments" :key="att.id"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                                <span class="text-2xl shrink-0">{{ mimeIcon(att.mime_type) }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ att.original_name }}</p>
                                    <p class="text-xs text-gray-400">{{ att.size_humans }}</p>
                                </div>
                                <a :href="att.url" target="_blank"
                                    class="p-1.5 text-gray-400 hover:text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 rounded-lg transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal entrada rápida -->
        <Transition name="fade">
            <div v-if="showQuickEntry" @click.self="showQuickEntry = false"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 overflow-y-auto">
                <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 shadow-2xl my-4" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Nueva entrada</h2>
                        <button @click="showQuickEntry = false"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="createQuickEntry" class="px-6 py-5 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título</label>
                            <input v-model="entryForm.title" type="text" autofocus
                                placeholder="¿Qué lograste en esta tarea?"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
                            <textarea v-model="entryForm.content" rows="4"
                                placeholder="Detalles, decisiones, observaciones..."
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm resize-y focus:border-blue-400 focus:outline-none" />
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" :disabled="entryForm.processing"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                                Crear entrada
                            </button>
                            <button type="button" @click="showQuickEntry = false"
                                class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Comentarios -->
            <TaskComments :task-id="task.id" :user="usePage().props.auth.user" />
        </Transition>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
