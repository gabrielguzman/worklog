<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
    weekStart: String,
    weekEnd: String,
    weekFormatted: String,
    weekDays: Array,
    unassignedTasks: Array,
    projects: Array,
    tags: Array,
})

const selectedDate = ref(props.weekStart)
const draggingTask = ref(null)

const goToWeek = () => {
    router.get('/planning/week', { week: selectedDate.value })
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

const totalTasksThisWeek = computed(() => {
    return props.weekDays.reduce((sum, day) => sum + day.taskCount, 0) + props.unassignedTasks.length
})

const PRIORITY = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700 dark:text-red-400 border-red-300', dot: 'bg-red-500' },
    high: { label: 'Alta', class: 'bg-orange-100 text-orange-700 dark:text-orange-400 border-orange-300', dot: 'bg-orange-500' },
    medium: { label: 'Media', class: 'bg-yellow-100 text-yellow-700 dark:text-yellow-400 border-yellow-300', dot: 'bg-yellow-500' },
    low: { label: 'Baja', class: 'bg-gray-100 text-gray-700 border-gray-300 dark:border-gray-600', dot: 'bg-gray-400' },
}

const STATUS = {
    pending: { label: 'Pendiente', class: 'text-gray-500 dark:text-gray-400' },
    in_progress: { label: 'En progreso', class: 'text-blue-600 dark:text-blue-400' },
}

const onDragStart = (task) => {
    draggingTask.value = task
}

const onDragOver = (e) => {
    e.preventDefault()
}

const onDrop = async (e, dueDate) => {
    e.preventDefault()
    if (!draggingTask.value) return

    try {
        await axios.patch('/planning/task-due-date', {
            task_id: draggingTask.value.id,
            due_date: dueDate,
        })
        router.reload({ preserveScroll: true })
    } catch (err) {
        console.error('Error updating due date:', err)
    } finally {
        draggingTask.value = null
    }
}

const onTaskClick = (taskId) => {
    router.visit(`/tasks/${taskId}`)
}
</script>

<template>
    <Head title="Planificación Semanal — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Planificación semanal</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">{{ weekFormatted }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link :href="`/planning/week?week=${prevWeek}`"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <input v-model="selectedDate" type="date" @change="goToWeek"
                        class="px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <Link :href="`/planning/week?week=${nextWeek}`"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <Link href="/dashboard"
                        class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Métrica -->
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:shadow-md">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Tareas esta semana</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ totalTasksThisWeek }}</p>
            </div>

            <!-- Grid de días -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div v-for="day in weekDays" :key="day.date"
                    class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md overflow-hidden">

                    <!-- Header del día -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3 border-b border-gray-200">
                        <p class="text-xs font-bold text-gray-600 uppercase">{{ day.label }}</p>
                        <p class="text-sm text-gray-700 font-medium mt-0.5">{{ day.dateFormat }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ day.taskCount }} tareas</p>
                    </div>

                    <!-- Área drop para tareas -->
                    <div @dragover="onDragOver" @drop="(e) => onDrop(e, day.date)"
                        class="min-h-96 p-3 space-y-2 bg-white">

                        <div v-if="day.tasks.length === 0"
                            class="h-full flex items-center justify-center text-gray-300 text-sm">
                            ↓ Arrastra tareas aquí
                        </div>

                        <div v-for="task in day.tasks" :key="task.id"
                            draggable="true"
                            @dragstart="onDragStart(task)"
                            @click="onTaskClick(task.id)"
                            class="p-3 rounded-lg bg-white border border-gray-200 hover:border-blue-400 cursor-move transition-all group">

                            <!-- Punto de prioridad + Título -->
                            <div class="flex items-start gap-2 mb-2">
                                <div :class="['w-2 h-2 rounded-full shrink-0 mt-1', PRIORITY[task.priority].dot]" />
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate flex-1">{{ task.title }}</p>
                            </div>

                            <!-- Metadata -->
                            <div class="flex items-center gap-1 flex-wrap">
                                <span :class="['text-[10px] font-semibold px-1.5 py-0.5 rounded-full', PRIORITY[task.priority].class]">
                                    {{ PRIORITY[task.priority].label.charAt(0) }}
                                </span>
                                <span v-if="task.project"
                                    class="text-[10px] px-1.5 py-0.5 rounded-full text-white"
                                    :style="{ backgroundColor: task.project.color }">
                                    {{ task.project.name.substring(0, 8) }}
                                </span>
                                <span :class="['text-[10px] font-semibold px-1.5 py-0.5 rounded-full', STATUS[task.status].class]">
                                    {{ STATUS[task.status].label === 'Pendiente' ? 'P' : 'IP' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tareas sin asignar -->
            <div v-if="unassignedTasks.length > 0" class="rounded-xl border border-gray-200 bg-white shadow-sm dark:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">📋 Tareas sin asignar fecha</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                    <div v-for="task in unassignedTasks" :key="task.id"
                        draggable="true"
                        @dragstart="onDragStart(task)"
                        @click="onTaskClick(task.id)"
                        class="p-4 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-blue-400 cursor-move transition-all hover:bg-gray-50">

                        <div class="flex items-start gap-2 mb-2">
                            <div :class="['w-2 h-2 rounded-full shrink-0 mt-1', PRIORITY[task.priority].dot]" />
                            <p class="text-sm font-medium text-gray-900 dark:text-white flex-1">{{ task.title }}</p>
                        </div>

                        <div class="flex items-center gap-1 flex-wrap">
                            <span :class="['text-[10px] font-semibold px-2 py-1 rounded-full', PRIORITY[task.priority].class]">
                                {{ PRIORITY[task.priority].label }}
                            </span>
                            <span v-if="task.project"
                                class="text-[10px] px-2 py-1 rounded-full text-white"
                                :style="{ backgroundColor: task.project.color }">
                                {{ task.project.name }}
                            </span>
                        </div>
                    </div>

                    <div v-if="unassignedTasks.length === 0" class="col-span-full text-center py-8 text-gray-400">
                        Todas las tareas tienen fecha asignada 🎉
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
