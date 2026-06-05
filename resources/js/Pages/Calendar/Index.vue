<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    events: Array,
    projects: Array,
})

const currentDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1))
const selectedDate = ref(new Date().toISOString().split('T')[0])
const showEventForm = ref(false)

// Meses en español
const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                     'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']

// Obtener días del mes
const daysInMonth = computed(() => {
    return new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0).getDate()
})

const firstDayOfMonth = computed(() => {
    return new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1).getDay()
})

const calendarDays = computed(() => {
    const days = []
    for (let i = 0; i < firstDayOfMonth.value; i++) {
        days.push(null)
    }
    for (let i = 1; i <= daysInMonth.value; i++) {
        days.push(i)
    }
    return days
})

// Eventos para el día seleccionado
const selectedDateEvents = computed(() => {
    return props.events.filter(event => {
        if (!event.start) return false
        return event.start.substring(0, 10) === selectedDate.value
    })
})

// Eventos para toda la semana
const weekEvents = computed(() => {
    const selected = new Date(selectedDate.value)
    const weekStart = new Date(selected)
    weekStart.setDate(selected.getDate() - selected.getDay())

    return props.events.filter(event => {
        if (!event.start) return false
        const eventDate = new Date(event.start)
        const eventWeekStart = new Date(eventDate)
        eventWeekStart.setDate(eventDate.getDate() - eventDate.getDay())

        return eventWeekStart.getTime() === weekStart.getTime()
    }).sort((a, b) => new Date(a.start) - new Date(b.start))
})

// Propiedades de prioridad
const priorityConfig = {
    urgent: { label: 'Urgente', color: 'bg-red-500' },
    high: { label: 'Alta', color: 'bg-orange-500' },
    medium: { label: 'Media', color: 'bg-yellow-500' },
    low: { label: 'Baja', color: 'bg-gray-500' },
}

// Métodos
const previousMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

const selectDay = (day) => {
    if (!day) return
    const year = currentDate.value.getFullYear()
    const month = String(currentDate.value.getMonth() + 1).padStart(2, '0')
    const dayStr = String(day).padStart(2, '0')
    selectedDate.value = `${year}-${month}-${dayStr}`
}

const isSelectedDay = (day) => {
    if (!day) return false
    const year = currentDate.value.getFullYear()
    const month = String(currentDate.value.getMonth() + 1).padStart(2, '0')
    const dayStr = String(day).padStart(2, '0')
    const dayDate = `${year}-${month}-${dayStr}`
    return dayDate === selectedDate.value
}

const isToday = (day) => {
    if (!day) return false
    const today = new Date()
    return day === today.getDate() &&
           currentDate.value.getMonth() === today.getMonth() &&
           currentDate.value.getFullYear() === today.getFullYear()
}

const dayHasEvents = (day) => {
    if (!day) return false
    const year = currentDate.value.getFullYear()
    const month = String(currentDate.value.getMonth() + 1).padStart(2, '0')
    const dayStr = String(day).padStart(2, '0')
    const dayDate = `${year}-${month}-${dayStr}`
    return props.events.some(event => event.start?.substring(0, 10) === dayDate)
}

const getEventsForDay = (day) => {
    if (!day) return []
    const year = currentDate.value.getFullYear()
    const month = String(currentDate.value.getMonth() + 1).padStart(2, '0')
    const dayStr = String(day).padStart(2, '0')
    const dayDate = `${year}-${month}-${dayStr}`
    return props.events.filter(event => event.start?.substring(0, 10) === dayDate)
}

const statusBadge = (status) => {
    const badges = {
        'pending': { label: '○ Pendiente', color: 'bg-yellow-100 text-yellow-800' },
        'in_progress': { label: '◑ En progreso', color: 'bg-blue-100 text-blue-800' },
        'done': { label: '● Hecha', color: 'bg-green-100 text-green-800' },
    }
    return badges[status] || badges['pending']
}
</script>

<template>
    <Head title="Calendario — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-7xl mx-auto">

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📅 Calendario</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Visualiza y gestiona tus tareas por fecha</p>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Calendar -->
                <div class="lg:col-span-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-6">
                    <!-- Month Navigation -->
                    <div class="flex items-center justify-between mb-6">
                        <button @click="previousMonth" aria-label="Mes anterior"
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ monthNames[currentDate.getMonth()] }} {{ currentDate.getFullYear() }}
                        </h2>
                        <button @click="nextMonth" aria-label="Mes siguiente"
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    <!-- Day Names -->
                    <div class="grid grid-cols-7 gap-2 mb-2">
                        <div v-for="day in dayNames" :key="day"
                            class="text-center text-xs font-semibold text-gray-600 dark:text-gray-400 py-2">
                            {{ day }}
                        </div>
                    </div>

                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="(day, index) in calendarDays" :key="index"
                            @click="selectDay(day)"
                            :class="[
                                'relative aspect-square p-2 rounded-lg border-2 transition-all cursor-pointer',
                                day === null ? 'invisible' : '',
                                isSelectedDay(day) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:border-gray-600',
                                isToday(day) ? 'ring-2 ring-green-500' : '',
                            ]">

                            <!-- Day number -->
                            <div :class="[
                                'text-sm font-semibold mb-1',
                                isToday(day) ? 'text-green-600' : 'text-gray-900 dark:text-white'
                            ]">
                                {{ day }}
                            </div>

                            <!-- Event dots -->
                            <div v-if="dayHasEvents(day)" class="flex gap-0.5 flex-wrap">
                                <div v-for="event in getEventsForDay(day).slice(0, 2)" :key="event.id"
                                    :class="[
                                        'w-1.5 h-1.5 rounded-full',
                                        event.backgroundColor === '#EF4444' ? 'bg-red-500' :
                                        event.backgroundColor === '#F97316' ? 'bg-orange-500' :
                                        event.backgroundColor === '#FBBF24' ? 'bg-yellow-500' : 'bg-blue-500'
                                    ]" />
                                <span v-if="getEventsForDay(day).length > 2" class="text-[10px] text-gray-500 dark:text-gray-400">
                                    +{{ getEventsForDay(day).length - 2 }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Leyenda:</p>
                        <div class="flex flex-wrap gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-red-500"></div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Urgente</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Alta</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Media</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Baja</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Date Tasks + Week Preview -->
                <div class="space-y-6">

                    <!-- Selected Date Details -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">{{ selectedDate }}</h3>

                        <div v-if="selectedDateEvents.length === 0" class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">Sin tareas para este día</p>
                            <Link href="/tasks/create" class="inline-block mt-2 text-blue-600 dark:text-blue-400 text-sm hover:underline font-medium">
                                + Crear tarea
                            </Link>
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="event in selectedDateEvents" :key="event.id"
                                class="p-3 rounded-lg border-l-4 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 transition-colors"
                                :style="{ borderLeftColor: event.backgroundColor }">

                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">{{ event.title }}</p>
                                        <div class="flex gap-1.5 mt-1 flex-wrap">
                                            <span :class="['text-[10px] px-2 py-0.5 rounded-full text-white',
                                                event.backgroundColor === '#EF4444' ? 'bg-red-500' :
                                                event.backgroundColor === '#F97316' ? 'bg-orange-500' :
                                                event.backgroundColor === '#FBBF24' ? 'bg-yellow-500' : 'bg-blue-500'
                                            ]">
                                                {{ priorityConfig[event.extendedProps.priority]?.label }}
                                            </span>
                                            <span :class="[
                                                'text-[10px] px-2 py-0.5 rounded-full',
                                                statusBadge(event.extendedProps.status).color
                                            ]">
                                                {{ statusBadge(event.extendedProps.status).label }}
                                            </span>
                                        </div>
                                    </div>
                                    <Link :href="`/tasks/${event.id}`"
                                        class="text-blue-600 dark:text-blue-400 hover:underline text-xs font-medium shrink-0">
                                        Ver
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Week Overview -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">📊 Esta semana</h3>

                        <div v-if="weekEvents.length === 0" class="text-center py-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Sin tareas esta semana</p>
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="event in weekEvents" :key="event.id"
                                class="p-2 rounded-lg bg-gray-50 dark:bg-gray-900 text-xs">
                                <div class="font-medium text-gray-900 dark:text-white truncate">{{ event.title }}</div>
                                <p class="text-gray-500 dark:text-gray-400">{{ event.start }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Acciones rápidas</h3>
                        <div class="space-y-2">
                            <Link href="/tasks/create" as="button"
                                class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                + Nueva tarea
                            </Link>
                            <Link href="/tasks/kanban" as="button"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                Ver Kanban
                            </Link>
                            <Link href="/entries/create" as="button"
                                class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                + Nueva entrada
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth transitions */
* {
    @apply transition-colors duration-smooth;
}
</style>
