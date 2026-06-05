<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
    columns:  Object,
    projects: Array,
    tags:     Array,
    filters:  Object,
})

const PRIORITY = {
    urgent: { label: 'Urgente', dot: 'bg-red-500'    },
    high:   { label: 'Alta',    dot: 'bg-orange-400'  },
    medium: { label: 'Media',   dot: 'bg-yellow-400'  },
    low:    { label: 'Baja',    dot: 'bg-gray-300'    },
}

const COLUMNS = [
    { key: 'pending',     label: 'Pendiente',   color: 'border-yellow-400', headerBg: 'bg-yellow-50 dark:bg-yellow-900/30',  count_color: 'text-yellow-700 dark:text-yellow-400' },
    { key: 'in_progress', label: 'En Progreso',  color: 'border-blue-400',   headerBg: 'bg-blue-50 dark:bg-blue-900/30',    count_color: 'text-blue-700'   },
    { key: 'done',        label: 'Hecha',        color: 'border-green-400',  headerBg: 'bg-green-50 dark:bg-green-900/30',   count_color: 'text-green-700 dark:text-green-400'  },
]

// Estado reactivo local de columnas
const localColumns = ref({
    pending:     [...props.columns.pending],
    in_progress: [...props.columns.in_progress],
    done:        [...props.columns.done],
})

// Filtros
const project_id = ref(props.filters.project_id ?? '')
const priority   = ref(props.filters.priority   ?? '')
const tag        = ref(props.filters.tag        ?? '')

const applyFilters = () => {
    router.get('/tasks/kanban', {
        project_id: project_id.value || undefined,
        priority:   priority.value   || undefined,
        tag:        tag.value        || undefined,
    }, { preserveState: true, replace: true })
}

// Drag & Drop
const dragging = ref(null)
const dragOverCol = ref(null)

const onDragStart = (task, colKey) => {
    dragging.value = { task, sourceCol: colKey }
}

const onDragOver = (e, colKey) => {
    e.preventDefault()
    dragOverCol.value = colKey
}

const onDrop = async (e, targetCol) => {
    e.preventDefault()
    if (!dragging.value || dragging.value.sourceCol === targetCol) {
        dragging.value = null
        dragOverCol.value = null
        return
    }

    const { task, sourceCol } = dragging.value

    // Actualización optimista
    localColumns.value[sourceCol] = localColumns.value[sourceCol].filter(t => t.id !== task.id)
    const updatedTask = { ...task, status: targetCol }
    localColumns.value[targetCol] = [...localColumns.value[targetCol], updatedTask]

    dragging.value = null
    dragOverCol.value = null

    try {
        await axios.patch(`/tasks/${task.id}/status`, { status: targetCol })
    } catch {
        router.reload({ preserveScroll: true })
    }
}

const onDragEnd = () => {
    dragging.value = null
    dragOverCol.value = null
}

const deleteTask = (id, colKey) => {
    if (confirm('¿Eliminar esta tarea?')) {
        router.delete(`/tasks/${id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Kanban — WorkLog" />

    <AppLayout>
        <div class="p-6 h-full flex flex-col space-y-4">

            <!-- Header -->
            <div class="flex items-center justify-between shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kanban</h1>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Toggle Lista / Kanban -->
                    <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <Link href="/tasks"
                            class="px-3 py-1.5 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 transition-colors">
                            ☰ Lista
                        </Link>
                        <span class="px-3 py-1.5 text-sm bg-blue-50 dark:bg-blue-900/30 text-blue-700 font-medium">
                            ▦ Kanban
                        </span>
                    </div>
                    <Link href="/tasks/create"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                        + Nueva tarea
                    </Link>
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex gap-2 flex-wrap shrink-0">
                <select v-model="project_id" @change="applyFilters"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 appearance-none pr-8 cursor-pointer"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23666%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 14l-7 7m0 0l-7-7m7 7V3%22></path></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                    <option value="">Todos los proyectos</option>
                    <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
                <select v-model="priority" @change="applyFilters"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 appearance-none pr-8 cursor-pointer"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23666%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 14l-7 7m0 0l-7-7m7 7V3%22></path></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                    <option value="">Toda prioridad</option>
                    <option v-for="(cfg, val) in PRIORITY" :key="val" :value="val">{{ cfg.label }}</option>
                </select>
                <select v-model="tag" @change="applyFilters"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 appearance-none pr-8 cursor-pointer"
                    style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23666%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 14l-7 7m0 0l-7-7m7 7V3%22></path></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                    <option value="">Todos los tags</option>
                    <option v-for="t in tags" :key="t.id" :value="t.name">{{ t.name }}</option>
                </select>
            </div>

            <!-- Columnas Kanban - responsive con scroll horizontal en mobile -->
            <div class="flex gap-3 md:gap-4 flex-1 overflow-x-auto pb-4 -mx-6 md:mx-0 px-6 md:px-0">
                <div v-for="col in COLUMNS" :key="col.key"
                    class="flex flex-col w-80 sm:w-72 md:w-80 shrink-0">

                    <!-- Header columna -->
                    <div :class="['rounded-t-xl border-t-4 px-4 py-3 flex items-center justify-between', col.color, col.headerBg]">
                        <h2 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">{{ col.label }}</h2>
                        <span :class="['text-sm font-bold', col.count_color]">
                            {{ localColumns[col.key].length }}
                        </span>
                    </div>

                    <!-- Drop zone -->
                    <div
                        @dragover="onDragOver($event, col.key)"
                        @drop="onDrop($event, col.key)"
                        :class="[
                            'flex-1 rounded-b-xl border border-t-0 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-2 space-y-2 min-h-32 transition-colors',
                            dragOverCol === col.key && dragging?.sourceCol !== col.key
                                ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-300' : ''
                        ]">

                        <!-- Cards -->
                        <div v-for="task in localColumns[col.key]" :key="task.id"
                            draggable="true"
                            @dragstart="onDragStart(task, col.key)"
                            @dragend="onDragEnd"
                            :class="[
                                'rounded-xl bg-white dark:bg-gray-800 border shadow-sm dark:shadow-md p-3 cursor-move group hover:shadow-md transition-all',
                                task.is_overdue ? 'border-red-200' : 'border-gray-200 dark:border-gray-700',
                                col.key === 'done' ? 'opacity-70' : ''
                            ]">

                            <!-- Cabecera de la card -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2 flex-1 min-w-0">
                                    <!-- Dot de prioridad -->
                                    <div :class="['mt-1.5 h-2 w-2 rounded-full shrink-0', PRIORITY[task.priority]?.dot]" />
                                    <Link :href="`/tasks/${task.id}`"
                                        :class="['text-sm font-medium hover:text-blue-600 dark:text-blue-400 leading-snug', col.key === 'done' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white']">
                                        {{ task.title }}
                                    </Link>
                                </div>
                                <!-- Acción eliminar (hover) -->
                                <button @click="deleteTask(task.id, col.key)"
                                    class="p-1 shrink-0 rounded text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Meta inferior -->
                            <div class="mt-2 space-y-1.5">
                                <!-- Fecha y proyecto -->
                                <div class="flex items-center justify-between text-[11px]">
                                    <span v-if="task.project"
                                        class="px-1.5 py-0.5 rounded-full text-white font-medium"
                                        :style="{ backgroundColor: task.project.color }">
                                        {{ task.project.name }}
                                    </span>
                                    <span v-else />
                                    <span v-if="task.is_overdue" class="text-red-500 font-semibold">⚠ {{ task.due_label }}</span>
                                    <span v-else-if="task.due_date" class="text-gray-400">📅 {{ task.due_label }}</span>
                                </div>

                                <!-- Tags + Subtareas + Recurrencia -->
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span v-for="t in task.tags.slice(0, 2)" :key="t.id"
                                        class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                        {{ t.name }}
                                    </span>
                                    <span v-if="task.subtasks_count > 0"
                                        class="text-[10px] text-gray-400">
                                        ☑ {{ task.subtasks_done }}/{{ task.subtasks_count }}
                                    </span>
                                    <span v-if="task.recurrence_type && task.recurrence_type !== 'none'"
                                        class="text-[10px] text-blue-500" title="Tarea recurrente">
                                        ↺
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Zona vacía -->
                        <div v-if="localColumns[col.key].length === 0"
                            class="py-8 text-center text-gray-300 text-xs">
                            Arrastrá tareas aquí
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
