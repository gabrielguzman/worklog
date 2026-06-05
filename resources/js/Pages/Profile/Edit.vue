<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    stats: Object,
    productivityChart: Array,
    topProjects: Array,
    badges: Array,
    recentActivity: Array,
})

const page = usePage()
const activeTab = ref('overview')

const hoursInFocus = computed(() => {
    if (!props.stats) return 0
    return (props.stats.total_focus_minutes / 60).toFixed(1)
})

const completionRate = computed(() => {
    if (!props.stats) return 0
    const total = props.stats.total_tasks
    if (total === 0) return 0
    return Math.round((props.stats.completed_tasks / total) * 100)
})
</script>

<template>
    <Head title="Perfil" />

    <AppLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">

            <!-- Header Perfil -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-900 dark:to-blue-800 shadow-lg overflow-hidden">
                <div class="p-8 text-white">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-6">
                            <div class="w-24 h-24 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-4xl border-4 border-white/30">
                                👤
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold">{{ $page.props.auth.user.name }}</h1>
                                <p class="text-blue-100">{{ $page.props.auth.user.email }}</p>
                                <p v-if="props.stats?.streak_days > 0" class="text-sm text-blue-100 mt-2">
                                    🔥 Racha de {{ props.stats.streak_days }} días
                                </p>
                            </div>
                        </div>
                        <button
                            @click="activeTab = 'settings'"
                            class="px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 backdrop-blur transition-colors">
                            ⚙️ Editar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <button
                    @click="activeTab = 'overview'"
                    :class="['px-4 py-3 font-medium transition-colors', activeTab === 'overview' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300']">
                    📊 Resumen
                </button>
                <button
                    @click="activeTab = 'stats'"
                    :class="['px-4 py-3 font-medium transition-colors', activeTab === 'stats' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300']">
                    📈 Estadísticas
                </button>
                <button
                    @click="activeTab = 'settings'"
                    :class="['px-4 py-3 font-medium transition-colors', activeTab === 'settings' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300']">
                    ⚙️ Configuración
                </button>
            </div>

            <!-- RESUMEN -->
            <div v-if="activeTab === 'overview'" class="space-y-6">
                <!-- KPIs -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-medium">Tareas completadas</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ props.stats.completed_tasks }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ completionRate }}% completadas</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-medium">Minutos de enfoque</p>
                        <p class="text-3xl font-bold text-orange-600 mt-2">{{ hoursInFocus }}h</p>
                        <p class="text-xs text-gray-500 mt-1">{{ props.stats.focus_sessions }} sesiones</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-medium">Entradas registradas</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ props.stats.total_entries }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-medium">Proyectos activos</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ props.stats.total_projects }}</p>
                    </div>
                </div>

                <!-- Badges -->
                <div v-if="props.badges && props.badges.length > 0" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🏆 Logros Desbloqueados</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div v-for="badge in props.badges" :key="badge.id" class="text-center p-4 rounded-lg bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-700">
                            <div class="text-4xl mb-2">{{ badge.icon }}</div>
                            <p class="font-semibold text-sm text-gray-900 dark:text-white">{{ badge.name }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ badge.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📋 Actividad Reciente</h2>
                    <div class="space-y-3">
                        <div v-for="(activity, idx) in props.recentActivity" :key="idx" class="flex items-start gap-4 pb-3 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <div :class="['text-xl', {
                                'text-green-600': activity.color === 'green',
                                'text-blue-600': activity.color === 'blue',
                                'text-orange-600': activity.color === 'orange',
                            }]">{{ activity.icon }}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ activity.action }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ activity.title }}</p>
                                <p v-if="activity.subtitle" class="text-xs text-gray-500">{{ activity.subtitle }}</p>
                            </div>
                            <p class="text-xs text-gray-500 whitespace-nowrap">{{ activity.timestamp }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ESTADÍSTICAS -->
            <div v-if="activeTab === 'stats'" class="space-y-6">
                <!-- Gráfico de productividad -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">📈 Productividad (Últimos 7 días)</h2>
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="(day, idx) in props.productivityChart" :key="idx" class="text-center">
                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ day.date }}</p>
                            <div class="space-y-1">
                                <div class="h-16 bg-gradient-to-t from-green-400 to-green-600 rounded-t opacity-70" :style="{ height: day.tasks * 5 + 'px' }"></div>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">{{ day.tasks }}t</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Proyectos principales -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎯 Proyectos Principales</h2>
                    <div class="space-y-3">
                        <div v-for="project in props.topProjects" :key="project.name" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: project.color }"></div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ project.name }}</p>
                            </div>
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ project.count }} tareas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONFIGURACIÓN -->
            <div v-if="activeTab === 'settings'" class="space-y-6">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Información Personal</h2>
                    </div>
                    <div class="p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cambiar Contraseña</h2>
                    </div>
                    <div class="p-6">
                        <UpdatePasswordForm />
                    </div>
                </div>

                <div class="rounded-2xl border border-red-200 dark:border-red-700/50 bg-red-50 dark:bg-red-900/20 shadow-sm dark:shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-100 dark:border-red-700/50">
                        <h2 class="text-lg font-semibold text-red-900 dark:text-red-300">Zona de Peligro</h2>
                    </div>
                    <div class="p-6">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
