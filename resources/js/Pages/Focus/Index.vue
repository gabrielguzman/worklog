<script setup>
import { ref, computed, onUnmounted, onMounted, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
    pendingTasks:   Array,
    recentSessions: Array,
    todayMinutes:   Number,
    weekSessions:   Number,
})

// ── Estado del timer ──────────────────────────────────────────────
const DURATIONS = [15, 25, 50]

const selectedTask  = ref(null)
const duration      = ref(25)          // minutos configurados
const remaining     = ref(25 * 60)     // segundos restantes
const running       = ref(false)
const sessionId     = ref(null)
const intervalRef   = ref(null)

// Modal post-sesión
const showComplete  = ref(false)
const completeNotes = ref('')
const completeTitle = ref('')
const createEntry   = ref(true)
const completing    = ref(false)

// Estado visual del timer
const progress = computed(() => {
    const total = duration.value * 60
    return Math.round(((total - remaining.value) / total) * 100)
})

const display = computed(() => {
    const m = Math.floor(remaining.value / 60).toString().padStart(2, '0')
    const s = (remaining.value % 60).toString().padStart(2, '0')
    return `${m}:${s}`
})

const circumference = 2 * Math.PI * 90  // r=90

// Actualizar título de la pestaña con el timer
watch([running, remaining], () => {
    if (running.value) {
        document.title = `🍅 ${display.value} — WorkLog`
    } else {
        document.title = 'Focus — WorkLog'
    }
})

const startTimer = async () => {
    try {
        const res = await axios.post('/focus/start', {
            task_id:           selectedTask.value?.id ?? null,
            duration_minutes:  duration.value,
        })
        sessionId.value = res.data.id
        remaining.value = duration.value * 60
        running.value   = true

        intervalRef.value = setInterval(() => {
            if (remaining.value <= 0) {
                clearInterval(intervalRef.value)
                running.value = false
                onTimerEnd()
                return
            }
            remaining.value--
        }, 1000)
    } catch {
        alert('Error al iniciar la sesión')
    }
}

const pauseTimer = () => {
    running.value = false
    clearInterval(intervalRef.value)
}

const resumeTimer = () => {
    running.value = true
    intervalRef.value = setInterval(() => {
        if (remaining.value <= 0) {
            clearInterval(intervalRef.value)
            running.value = false
            onTimerEnd()
            return
        }
        remaining.value--
    }, 1000)
}

const cancelSession = async () => {
    if (!sessionId.value) return
    clearInterval(intervalRef.value)
    running.value = false
    await axios.patch(`/focus/${sessionId.value}/cancel`)
    sessionId.value = null
    remaining.value = duration.value * 60
    router.reload()
}

const onTimerEnd = () => {
    // Vibrar en móvil si disponible
    if ('vibrate' in navigator) navigator.vibrate([500, 200, 500])
    // Notificación browser si hay permiso
    if (Notification.permission === 'granted') {
        new Notification('🍅 ¡Sesión completada!', {
            body: `${duration.value} minutos de foco terminados. ¿Qué lograste?`,
            icon: '/favicon.ico',
        })
    }
    completeTitle.value = selectedTask.value
        ? `Sesión de trabajo: ${selectedTask.value.title}`
        : 'Sesión de foco'
    completeNotes.value = ''
    createEntry.value   = true
    showComplete.value  = true
}

const submitComplete = async () => {
    if (!sessionId.value) return
    completing.value = true
    try {
        await axios.patch(`/focus/${sessionId.value}/complete`, {
            notes:        completeNotes.value,
            create_entry: createEntry.value,
            entry_title:  createEntry.value ? completeTitle.value : null,
        })
        showComplete.value = false
        sessionId.value    = null
        remaining.value    = duration.value * 60
        router.reload({ preserveScroll: true })
    } catch {
        alert('Error al guardar la sesión')
    } finally {
        completing.value = false
    }
}

const notificationStatus = ref('checking')

const requestNotificationPermission = async () => {
    if (!('Notification' in window)) {
        alert('Este navegador no soporta notificaciones')
        notificationStatus.value = 'not-supported'
        return
    }

    if (Notification.permission === 'granted') {
        alert('✓ Ya tienes notificaciones habilitadas')
        notificationStatus.value = 'granted'
        return
    }

    if (Notification.permission === 'denied') {
        alert('❌ Las notificaciones están deshabilitadas. Debes cambiar los permisos del navegador.')
        notificationStatus.value = 'denied'
        return
    }

    try {
        const permission = await Notification.requestPermission()
        if (permission === 'granted') {
            alert('✓ ¡Notificaciones habilitadas! Recibirás alertas cuando termine la sesión.')
            notificationStatus.value = 'granted'
            // Enviar notificación de prueba
            new Notification('WorkLog - Focus', {
                body: 'Las notificaciones están activadas. Te avisaremos cuando termine tu sesión.',
                icon: '🍅'
            })
        } else {
            notificationStatus.value = 'denied'
        }
    } catch (error) {
        console.error('Error al solicitar permiso:', error)
        notificationStatus.value = 'error'
    }
}


const selectDuration = (d) => {
    if (running.value) return
    duration.value  = d
    remaining.value = d * 60
}

const formatMinutes = (m) => {
    if (m < 60) return `${m} min`
    return `${Math.floor(m / 60)}h ${m % 60 > 0 ? m % 60 + 'min' : ''}`
}

const PRIORITY_DOT = {
    urgent: 'bg-red-500',
    high:   'bg-orange-400',
    medium: 'bg-yellow-400',
    low:    'bg-gray-300',
}

onMounted(() => {
    // Detectar estado actual de notificaciones
    if ('Notification' in window) {
        if (Notification.permission === 'granted') {
            notificationStatus.value = 'granted'
        } else if (Notification.permission === 'denied') {
            notificationStatus.value = 'denied'
        }
    } else {
        notificationStatus.value = 'not-supported'
    }
})

onUnmounted(() => {
    clearInterval(intervalRef.value)
    document.title = 'WorkLog'
})
</script>

<template>
    <Head title="Focus — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Modo Focus 🍅</h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5">
                        Hoy: {{ formatMinutes(todayMinutes) }} · Esta semana: {{ weekSessions }} sesiones
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/focus/history"
                        class="text-xs text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 transition-colors font-medium">
                        📊 Historial
                    </Link>
                    <button @click="requestNotificationPermission"
                        :class="[
                            'text-xs border rounded-lg px-3 py-1.5 transition-colors font-medium',
                            notificationStatus === 'granted'
                                ? 'text-green-600 dark:text-green-400 border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
                                : 'text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 border-gray-200 dark:border-gray-700'
                        ]">
                        {{ notificationStatus === 'granted' ? '✓ Notificaciones activas' : '🔔 Activar notificaciones' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Panel del timer -->
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-6 flex flex-col items-center gap-6">

                    <!-- Selector de duración -->
                    <div class="flex gap-2">
                        <button v-for="d in DURATIONS" :key="d"
                            @click="selectDuration(d)"
                            :disabled="running"
                            :class="[
                                'px-4 py-2 rounded-xl text-sm font-semibold transition-all',
                                duration === d
                                    ? 'bg-blue-600 text-white shadow-md'
                                    : 'bg-gray-100 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:bg-gray-600 disabled:opacity-50'
                            ]">
                            {{ d }} min
                        </button>
                    </div>

                    <!-- Timer circular -->
                    <div class="relative">
                        <svg class="w-52 h-52 -rotate-90" viewBox="0 0 200 200">
                            <!-- Track -->
                            <circle cx="100" cy="100" r="90" fill="none"
                                stroke="#f3f4f6" stroke-width="10" />
                            <!-- Progress -->
                            <circle cx="100" cy="100" r="90" fill="none"
                                :stroke="running ? '#3B82F6' : '#9CA3AF'"
                                stroke-width="10"
                                stroke-linecap="round"
                                :stroke-dasharray="circumference"
                                :stroke-dashoffset="circumference - (circumference * progress / 100)"
                                class="transition-all duration-1000" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-bold font-mono tabular-nums"
                                :class="running ? 'text-blue-600 dark:text-blue-400' : 'text-gray-800 dark:text-gray-100'">
                                {{ display }}
                            </span>
                            <span class="text-sm text-gray-400 mt-1">
                                {{ running ? '🍅 enfocado' : sessionId ? '⏸ pausado' : 'listo' }}
                            </span>
                        </div>
                    </div>

                    <!-- Controles -->
                    <div class="flex gap-3">
                        <button v-if="!sessionId"
                            @click="startTimer"
                            class="flex items-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-white font-semibold hover:bg-blue-700 shadow-lg transition-all active:scale-95">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Iniciar
                        </button>
                        <template v-else>
                            <button v-if="running" @click="pauseTimer"
                                class="flex items-center gap-2 rounded-xl bg-yellow-500 px-6 py-3 text-white font-semibold hover:bg-yellow-600 transition-all active:scale-95">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                Pausa
                            </button>
                            <button v-else @click="resumeTimer"
                                class="flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-white font-semibold hover:bg-blue-700 transition-all active:scale-95">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                Reanudar
                            </button>
                            <button @click="cancelSession"
                                class="flex items-center gap-2 rounded-xl border border-red-200 px-4 py-3 text-red-600 font-semibold hover:bg-red-50 dark:bg-red-900/30 transition-all">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                Cancelar
                            </button>
                        </template>
                    </div>

                    <!-- Tarea seleccionada -->
                    <div v-if="selectedTask" class="w-full rounded-xl border border-blue-100 bg-blue-50 dark:bg-blue-900/30 px-4 py-3">
                        <p class="text-xs text-blue-500 font-medium mb-0.5">Trabajando en:</p>
                        <p class="text-sm font-semibold text-blue-800 truncate">{{ selectedTask.title }}</p>
                    </div>
                    <p v-else class="text-xs text-gray-400 text-center">Seleccioná una tarea para vincular la sesión →</p>
                </div>

                <!-- Lista de tareas -->
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">Tareas pendientes</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Seleccioná en cuál vas a trabajar</p>
                    </div>
                    <div class="overflow-y-auto max-h-80 divide-y divide-gray-50">
                        <!-- Sin tarea -->
                        <button @click="selectedTask = null"
                            :class="['w-full flex items-center gap-3 px-5 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors',
                                     !selectedTask ? 'bg-blue-50 dark:bg-blue-900/30' : '']">
                            <div :class="['h-4 w-4 rounded-full border-2 shrink-0', !selectedTask ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600']" />
                            <span class="text-sm text-gray-500 dark:text-gray-400 italic">Sin tarea (sesión libre)</span>
                        </button>
                        <button v-for="task in pendingTasks" :key="task.id"
                            @click="selectedTask = task"
                            :class="['w-full flex items-center gap-3 px-5 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors',
                                     selectedTask?.id === task.id ? 'bg-blue-50 dark:bg-blue-900/30' : '']">
                            <div :class="['h-4 w-4 rounded-full border-2 shrink-0 flex items-center justify-center',
                                          selectedTask?.id === task.id ? 'border-blue-500 bg-blue-500' : 'border-gray-300 dark:border-gray-600']">
                                <div v-if="selectedTask?.id === task.id" class="h-2 w-2 rounded-full bg-white dark:bg-gray-800" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ task.title }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span :class="['h-2 w-2 rounded-full shrink-0', PRIORITY_DOT[task.priority]]" />
                                    <span v-if="task.project" class="text-xs text-gray-400 truncate">{{ task.project.name }}</span>
                                </div>
                            </div>
                        </button>
                        <div v-if="pendingTasks.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">
                            Sin tareas pendientes 🎉
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de sesiones -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Sesiones recientes</h2>
                    <Link href="/focus/history" class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                        Ver todas →
                    </Link>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-if="recentSessions.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">
                        Sin sesiones todavía. ¡Iniciá tu primer Pomodoro!
                    </div>
                    <div v-for="s in recentSessions" :key="s.id"
                        class="flex items-center gap-4 px-5 py-3">
                        <span :class="['text-xl', s.status === 'completed' ? '' : 'opacity-40']">🍅</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ s.duration_minutes }} min</p>
                                <span :class="[
                                    'text-xs px-2 py-0.5 rounded-full font-medium',
                                    s.status === 'completed' ? 'bg-green-100 text-green-700 dark:text-green-400' :
                                    s.status === 'cancelled' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600 dark:text-blue-400'
                                ]">{{ s.status }}</span>
                            </div>
                            <p v-if="s.task" class="text-xs text-gray-400 truncate mt-0.5">{{ s.task.title }}</p>
                            <p v-if="s.notes" class="text-xs text-gray-400 italic mt-0.5 truncate">"{{ s.notes }}"</p>
                        </div>
                        <span class="text-xs text-gray-400 shrink-0">{{ s.started_at }}</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Modal post-sesión ── -->
        <Transition name="fade">
            <div v-if="showComplete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl p-6 space-y-5" @click.stop>

                    <div class="text-center">
                        <p class="text-5xl mb-2">🎉</p>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">¡Sesión completada!</h2>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ duration }} minutos de foco. ¿Qué lograste?</p>
                    </div>

                    <textarea v-model="completeNotes" rows="3" autofocus
                        placeholder="Anotá lo que hiciste, problemas encontrados, próximos pasos..."
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none resize-none" />

                    <label class="flex items-center gap-3 cursor-pointer select-none p-3 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-100">
                        <input v-model="createEntry" type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-400" />
                        <span class="text-sm font-medium text-blue-800">Crear entrada en el registro</span>
                    </label>

                    <input v-if="createEntry" v-model="completeTitle" type="text"
                        placeholder="Título de la entrada..."
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none" />

                    <div class="flex gap-3">
                        <button @click="submitComplete" :disabled="completing"
                            class="flex-1 rounded-xl bg-blue-600 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                            {{ completing ? 'Guardando...' : 'Guardar sesión' }}
                        </button>
                        <button @click="showComplete = false; sessionId = null; remaining = duration * 60; router.reload()"
                            class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                            Saltar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
