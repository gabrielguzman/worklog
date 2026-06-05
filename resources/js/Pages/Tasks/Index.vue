<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const page = usePage()
const route = (name, params) => {
    const routes = {
        'export.tasks.csv': `/export/tasks/csv`,
        'export.tasks.pdf': `/export/tasks/pdf`,
    }
    let url = routes[name] || ''
    if (params) {
        Object.keys(params).forEach(key => {
            url += `?${key}=${params[key]}`
        })
    }
    return url
}

const props = defineProps({
    tasks:    Object,
    summary:  Object,
    projects: Array,
    tags:     Array,
    filters:  Object,
})

const PRIORITY = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700 dark:text-red-400',    dot: 'bg-red-500'    },
    high:   { label: 'Alta',    class: 'bg-orange-100 text-orange-700 dark:text-orange-400', dot: 'bg-orange-400' },
    medium: { label: 'Media',   class: 'bg-yellow-100 text-yellow-700 dark:text-yellow-400', dot: 'bg-yellow-400' },
    low:    { label: 'Baja',    class: 'bg-gray-100 text-gray-500 dark:text-gray-400',   dot: 'bg-gray-300'   },
}

const STATUS = {
    pending:     { label: 'Pendiente',   icon: '○' },
    in_progress: { label: 'En progreso', icon: '◑' },
    done:        { label: 'Hecha',       icon: '●' },
}

// Filtros
const search     = ref(props.filters.search     ?? '')
const status     = ref(props.filters.status     ?? '')
const priority   = ref(props.filters.priority   ?? '')
const project_id = ref(props.filters.project_id ?? '')
const tag        = ref(props.filters.tag        ?? '')
const show_done  = ref(props.filters.show_done  ?? false)
const overdue    = ref(props.filters.overdue    ?? false)

// Bulk actions
const selectedTasks = ref(new Set())
const bulkForm = ref({ status: '', priority: '', project_id: '' })
const bulkLoading = ref(false)
const bulkError = ref(null)
const bulkSuccess = ref(false)
const showBulkModal = ref(false)

const toggleTaskSelection = (taskId) => {
    if (selectedTasks.value.has(taskId)) {
        selectedTasks.value.delete(taskId)
    } else {
        selectedTasks.value.add(taskId)
    }
}

const toggleSelectAll = () => {
    if (selectedTasks.value.size === props.tasks.data.length) {
        selectedTasks.value.clear()
    } else {
        props.tasks.data.forEach(task => selectedTasks.value.add(task.id))
    }
}

const clearSelection = () => {
    selectedTasks.value.clear()
    showBulkModal.value = false
}

const submitBulkUpdate = async () => {
    if (!window.confirm(`¿Actualizar ${selectedTasks.value.size} tarea(s)?`)) return

    bulkLoading.value = true
    bulkError.value = null
    bulkSuccess.value = false

    try {
        const payload = {
            task_ids: Array.from(selectedTasks.value),
            ...(bulkForm.value.status && { status: bulkForm.value.status }),
            ...(bulkForm.value.priority && { priority: bulkForm.value.priority }),
            ...(bulkForm.value.project_id && { project_id: parseInt(bulkForm.value.project_id) }),
        }

        const response = await axios.patch('/tasks/bulk-update', payload)
        bulkSuccess.value = true
        selectedTasks.value.clear()
        bulkForm.value = { status: '', priority: '', project_id: '' }
        showBulkModal.value = false
        router.reload({ preserveState: true })
    } catch (error) {
        bulkError.value = error.response?.data?.message || 'Error al actualizar tareas'
    } finally {
        bulkLoading.value = false
    }
}

const submitBulkDelete = async () => {
    if (!window.confirm(`¿Eliminar ${selectedTasks.value.size} tarea(s) permanentemente?`)) return

    bulkLoading.value = true
    bulkError.value = null

    try {
        const response = await axios.delete('/tasks/bulk-delete', {
            data: { task_ids: Array.from(selectedTasks.value) }
        })
        bulkSuccess.value = true
        selectedTasks.value.clear()
        showBulkModal.value = false
        router.reload({ preserveState: true })
    } catch (error) {
        bulkError.value = error.response?.data?.message || 'Error al eliminar tareas'
    } finally {
        bulkLoading.value = false
    }
}

let timer = null
const apply = () => {
    router.get('/tasks', {
        search:     search.value     || undefined,
        status:     status.value     || undefined,
        priority:   priority.value   || undefined,
        project_id: project_id.value || undefined,
        tag:        tag.value        || undefined,
        show_done:  show_done.value  || undefined,
        overdue:    overdue.value    || undefined,
    }, { preserveState: true, replace: true })
}

watch(search, v => { clearTimeout(timer); timer = setTimeout(apply, 350) })
watch([status, priority, project_id, tag, show_done, overdue], apply)

const hasFilters = computed(() =>
    search.value || status.value || priority.value || project_id.value || tag.value || overdue.value
)

const clearFilters = () => {
    search.value = ''; status.value = ''; priority.value = ''
    project_id.value = ''; tag.value = ''; overdue.value = false
    apply()
}

const toggle = (task) => {
    router.patch(`/tasks/${task.id}/toggle`, {}, { preserveScroll: true })
}

const deleteTask = (id) => {
    if (confirm('¿Eliminar esta tarea?')) {
        router.delete(`/tasks/${id}`, { preserveScroll: true })
    }
}

// ── Drag & Drop ──────────────────────────────────────────────────
const draggedTask  = ref(null)
const dragOverIdx  = ref(null)
const reordering   = ref(false)

const onDragStart = (task, index) => {
    draggedTask.value = { ...task, origIndex: index }
    dragOverIdx.value = index
}

const onDragOver = (e, index) => {
    e.preventDefault()
    dragOverIdx.value = index
}

const onDrop = async (e, index) => {
    e.preventDefault()
    if (!draggedTask.value || draggedTask.value.origIndex === index) {
        draggedTask.value = null
        dragOverIdx.value = null
        return
    }

    reordering.value = true
    const tasksToReorder = [...props.tasks.data]
    const [movedTask] = tasksToReorder.splice(draggedTask.value.origIndex, 1)
    tasksToReorder.splice(index, 0, movedTask)

    try {
        await axios.post('/tasks/reorder', {
            tasks: tasksToReorder.map((t, i) => ({
                id: t.id,
                sort_order: i,
            })),
        })
        router.reload({ preserveScroll: true })
    } catch {
        // Error - recargar para restaurar el orden
        router.reload({ preserveScroll: true })
    } finally {
        draggedTask.value = null
        dragOverIdx.value = null
        reordering.value = false
    }
}

const onDragEnd = () => {
    draggedTask.value = null
    dragOverIdx.value = null
}
</script>

<template>
    <Head title="Tareas — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tareas</h1>
                    <p class="text-gray-700 dark:text-gray-400 text-sm mt-0.5">{{ tasks.total }} tarea{{ tasks.total !== 1 ? 's' : '' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Toggle Lista / Kanban -->
                    <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <span class="px-3 py-1.5 text-sm bg-blue-50 dark:bg-blue-900/30 text-blue-700 font-medium border-r border-gray-200 dark:border-gray-700">
                            ☰ Lista
                        </span>
                        <Link href="/tasks/kanban"
                            class="px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                            ▦ Kanban
                        </Link>
                    </div>
                    <Link href="/tasks/create"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva tarea
                    </Link>
                    <!-- Export button -->
                    <a :href="route('export.tasks.csv')" class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        📥 Descargar
                    </a>
                </div>
            </div>

            <!-- Resumen de contadores -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <button @click="status = 'pending'; show_done = false"
                    :class="['rounded-xl border p-3 text-left transition-all', status === 'pending' ? 'border-yellow-400 bg-yellow-50 dark:bg-yellow-900/30' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:border-gray-600']">
                    <p class="text-xs text-gray-700 dark:text-gray-400">Pendientes</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ summary.pending }}</p>
                </button>
                <button @click="status = 'in_progress'; show_done = false"
                    :class="['rounded-xl border p-3 text-left transition-all', status === 'in_progress' ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:border-gray-600']">
                    <p class="text-xs text-gray-700 dark:text-gray-400">En progreso</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ summary.in_progress }}</p>
                </button>
                <button @click="overdue = !overdue"
                    :class="['rounded-xl border p-3 text-left transition-all', overdue ? 'border-red-400 bg-red-50 dark:bg-red-900/30' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:border-gray-600']">
                    <p class="text-xs text-gray-700 dark:text-gray-400">Vencidas</p>
                    <p class="text-2xl font-bold text-red-600">{{ summary.overdue }}</p>
                </button>
                <button @click="show_done = !show_done; status = show_done ? 'done' : ''"
                    :class="['rounded-xl border p-3 text-left transition-all', show_done ? 'border-green-400 bg-green-50 dark:bg-green-900/30' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:border-gray-600']">
                    <p class="text-xs text-gray-700 dark:text-gray-400">Hechas hoy</p>
                    <p class="text-2xl font-bold text-green-600">{{ summary.done_today }}</p>
                </button>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-4 space-y-3">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="search" placeholder="Buscar tareas..."
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 pl-9 pr-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                </div>

                <div class="flex gap-2 flex-wrap">
                    <select v-model="priority" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">Toda prioridad</option>
                        <option v-for="(cfg, val) in PRIORITY" :key="val" :value="val">{{ cfg.label }}</option>
                    </select>

                    <select v-model="project_id" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">Todos los proyectos</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>

                    <select v-model="tag" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">Todos los tags</option>
                        <option v-for="t in tags" :key="t.id" :value="t.name">{{ t.name }}</option>
                    </select>

                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        <input v-model="show_done" type="checkbox" class="rounded border-gray-300 dark:border-gray-600" />
                        Ver hechas
                    </label>

                    <button v-if="hasFilters" @click="clearFilters"
                        class="flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:bg-red-900/30">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Bulk actions toolbar -->
            <div v-if="selectedTasks.size > 0" class="rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-700 p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-blue-900 dark:text-blue-200">{{ selectedTasks.size }} tarea(s) seleccionada(s)</span>
                    <button @click="toggleSelectAll" class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                        {{ selectedTasks.size === tasks.data.length ? 'Deseleccionar todo' : 'Seleccionar todo' }}
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="showBulkModal = true" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
                        ✎ Actualizar
                    </button>
                    <button @click="submitBulkDelete" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors">
                        🗑 Eliminar
                    </button>
                    <button @click="clearSelection" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>

            <!-- Bulk update modal -->
            <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 pt-16">
                <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 shadow-2xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 pt-4 pb-0">
                        <h2 id="bulk-modal-title" class="sr-only">Actualizar tareas en lote</h2>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Actualizar {{ selectedTasks.size }} tarea(s)</span>
                        <button @click="showBulkModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitBulkUpdate" class="px-5 py-4 space-y-4">
                        <!-- Error message -->
                        <div v-if="bulkError" class="p-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800">
                            <p class="text-sm text-red-700 dark:text-red-400">{{ bulkError }}</p>
                        </div>

                        <!-- Status dropdown -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Estado (opcional)</label>
                            <select v-model="bulkForm.status" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none dark:bg-gray-700 dark:text-white">
                                <option value="">Sin cambios</option>
                                <option value="pending">Pendiente</option>
                                <option value="in_progress">En progreso</option>
                                <option value="done">Hecha</option>
                            </select>
                        </div>

                        <!-- Priority dropdown -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Prioridad (opcional)</label>
                            <select v-model="bulkForm.priority" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none dark:bg-gray-700 dark:text-white">
                                <option value="">Sin cambios</option>
                                <option value="low">Baja</option>
                                <option value="medium">Media</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>

                        <!-- Project dropdown -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Proyecto (opcional)</label>
                            <select v-model="bulkForm.project_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none dark:bg-gray-700 dark:text-white">
                                <option value="">Sin cambios</option>
                                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button type="submit" :disabled="bulkLoading" class="flex-1 rounded-lg bg-blue-600 text-white py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                <span v-if="bulkLoading" class="inline-block animate-spin mr-2">⟳</span>
                                {{ bulkLoading ? 'Actualizando...' : 'Actualizar' }}
                            </button>
                            <button type="button" @click="showBulkModal = false" :disabled="bulkLoading" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de tareas -->
            <div v-if="tasks.data.length === 0"
                class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
                <p class="text-4xl mb-3">✅</p>
                <p class="text-gray-700 dark:text-gray-400 font-medium">
                    {{ hasFilters ? 'Sin tareas con esos filtros' : 'No hay tareas pendientes' }}
                </p>
                <Link v-if="!hasFilters" href="/tasks/create"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline">
                    + Nueva tarea
                </Link>
            </div>

            <div v-else class="space-y-3">
                <!-- Header con checkbox "seleccionar todo" -->
                <div class="flex items-center gap-2 px-4 py-2">
                    <input type="checkbox"
                        :checked="selectedTasks.size > 0 && selectedTasks.size === tasks.data.length"
                        @change="toggleSelectAll"
                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        <span v-if="selectedTasks.size === 0">Seleccionar todo</span>
                        <span v-else>{{ selectedTasks.size }}/{{ tasks.data.length }}</span>
                    </span>
                </div>

                <div v-for="(task, idx) in tasks.data" :key="task.id"
                    draggable="true"
                    @dragstart="onDragStart(task, idx)"
                    @dragover="onDragOver($event, idx)"
                    @drop="onDrop($event, idx)"
                    @dragend="onDragEnd"
                    :class="[
                        'group rounded-xl border bg-white dark:bg-gray-800 shadow-sm dark:shadow-md hover:shadow-md transition-all cursor-move',
                        dragOverIdx === idx && draggedTask ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : '',
                        reordering ? 'opacity-50' : '',
                        task.status === 'done'    ? 'border-gray-100 dark:border-gray-700 opacity-60' :
                        task.is_overdue           ? 'border-red-200 bg-red-50 dark:bg-red-900/30/30' :
                        task.status === 'in_progress' ? 'border-blue-200' :
                                                    'border-gray-200 dark:border-gray-700'
                    ]">
                    <div class="flex items-start gap-3 p-4">

                        <!-- Selection checkbox -->
                        <input type="checkbox"
                            :checked="selectedTasks.has(task.id)"
                            @change="toggleTaskSelection(task.id)"
                            class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 shrink-0 cursor-pointer" />

                        <!-- Toggle checkbox -->
                        <button @click="toggle(task)"
                            :class="[
                                'mt-0.5 h-5 w-5 rounded-full border-2 shrink-0 flex items-center justify-center transition-all',
                                task.status === 'done'        ? 'border-green-500 bg-green-500' :
                                task.status === 'in_progress' ? 'border-blue-500 bg-blue-100'  :
                                                                'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                            ]">
                            <svg v-if="task.status === 'done'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <div v-else-if="task.status === 'in_progress'" class="h-2 w-2 rounded-full bg-blue-500" />
                        </button>

                        <!-- Indicador de prioridad -->
                        <div :class="['mt-2 h-2 w-2 rounded-full shrink-0', PRIORITY[task.priority]?.dot]" />

                        <!-- Contenido -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <Link :href="`/tasks/${task.id}`"
                                    :class="['font-semibold text-sm hover:text-blue-600 dark:text-blue-400 transition-colors', task.status === 'done' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white']">
                                    {{ task.title }}
                                </Link>

                                <!-- Vencimiento -->
                                <div class="shrink-0 text-right">
                                    <span v-if="task.is_overdue"
                                        class="text-xs font-semibold text-red-600">
                                        ⚠ {{ task.due_label }}
                                    </span>
                                    <span v-else-if="task.due_date"
                                        class="text-xs text-gray-400">
                                        📅 {{ task.due_label }}
                                    </span>
                                </div>
                            </div>

                            <p v-if="task.description" class="text-xs text-gray-700 dark:text-gray-400 mt-0.5 line-clamp-1">
                                {{ task.description }}
                            </p>

                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                <!-- Prioridad badge -->
                                <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-full', PRIORITY[task.priority]?.class]">
                                    {{ PRIORITY[task.priority]?.label }}
                                </span>
                                <!-- Proyecto -->
                                <span v-if="task.project"
                                    class="text-[11px] font-medium px-2 py-0.5 rounded-full text-white"
                                    :style="{ backgroundColor: task.project.color }">
                                    {{ task.project.name }}
                                </span>
                                <!-- Tags -->
                                <span v-for="t in task.tags.slice(0, 2)" :key="t.id"
                                    class="text-[11px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                    {{ t.name }}
                                </span>
                                <!-- Entry vinculada -->
                                <span v-if="task.entry" class="text-[11px] text-gray-400">
                                    📝 {{ task.entry.title }}
                                </span>
                                <!-- Focus sessions -->
                                <span v-if="task.focus_sessions_count > 0" class="text-[11px] text-gray-400">
                                    🍅 {{ task.focus_sessions_count }}
                                </span>
                                <!-- Subtareas -->
                                <span v-if="task.subtasks_count > 0" class="text-[11px] text-gray-400">
                                    ☑ {{ task.subtasks_done }}/{{ task.subtasks_count }}
                                </span>
                            </div>

                            <!-- Breadcrumb al padre si es subtarea -->
                            <div v-if="task.parent_task" class="flex items-center gap-1 text-[10px] text-gray-400 mt-0.5">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                <Link :href="`/tasks/${task.parent_task.id}`" class="hover:text-blue-500 truncate max-w-[120px]">
                                    {{ task.parent_task.title }}
                                </Link>
                            </div>
                        </div>

                        <!-- Acciones (hover) -->
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                            <Link :href="`/tasks/${task.id}/edit`"
                                :aria-label="`Editar tarea: ${task.title}`"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </Link>
                            <button @click="deleteTask(task.id)"
                                :aria-label="`Eliminar tarea: ${task.title}`"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:bg-red-900/30 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="tasks.last_page > 1" class="flex items-center justify-between pt-2">
                <p class="text-sm text-gray-700 dark:text-gray-400">Mostrando {{ tasks.from }}–{{ tasks.to }} de {{ tasks.total }}</p>
                <div class="flex gap-1">
                    <Link v-if="tasks.prev_page_url" :href="tasks.prev_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">← Anterior</Link>
                    <Link v-if="tasks.next_page_url" :href="tasks.next_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">Siguiente →</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
