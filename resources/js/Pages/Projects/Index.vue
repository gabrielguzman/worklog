<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()

const props = defineProps({ projects: Array })

const PRESET_COLORS = [
    '#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6',
    '#EC4899','#14B8A6','#F97316','#06B6D4','#84CC16',
]

// ── Modal ────────────────────────────────────────────────────────
const modalOpen  = ref(false)
const editTarget = ref(null)   // null = crear, objeto = editar

const form = useForm({
    name:        '',
    color:       '#3B82F6',
    description: '',
    is_active:   true,
})

const openCreate = () => {
    editTarget.value = null
    form.reset()
    form.color = '#3B82F6'
    form.is_active = true
    modalOpen.value = true
}

const openEdit = (project) => {
    editTarget.value = project
    form.name        = project.name
    form.color       = project.color
    form.description = project.description ?? ''
    form.is_active   = project.is_active
    modalOpen.value  = true
}

const closeModal = () => { modalOpen.value = false; editTarget.value = null }

const submit = () => {
    if (editTarget.value) {
        form.put(`/projects/${editTarget.value.id}`, {
            onSuccess: closeModal,
        })
    } else {
        form.post('/projects', {
            onSuccess: closeModal,
        })
    }
}

const deleteProject = (project) => {
    const msg = project.entries_count + project.tasks_count > 0
        ? `"${project.name}" tiene ${project.entries_count} entradas y ${project.tasks_count} tareas que quedarán sin proyecto. ¿Continuar?`
        : `¿Eliminar el proyecto "${project.name}"?`
    if (confirm(msg)) {
        router.delete(`/projects/${project.id}`, { preserveScroll: true })
    }
}

const totalEntries = computed(() => props.projects.reduce((s, p) => s + p.entries_count, 0))
const totalTasks   = computed(() => props.projects.reduce((s, p) => s + p.tasks_count, 0))
</script>

<template>
    <Head title="Proyectos — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">Proyectos</h1>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm mt-0.5">
                        {{ projects.length }} proyecto{{ projects.length !== 1 ? 's' : '' }} ·
                        {{ totalEntries }} entradas · {{ totalTasks }} tareas
                    </p>
                </div>
                <button @click="openCreate"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo proyecto
                </button>
            </div>

            <!-- Lista de proyectos -->
            <div v-if="projects.length === 0"
                class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-20 text-center">
                <p class="text-5xl mb-3">📁</p>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium">Sin proyectos todavía</p>
                <button @click="openCreate" class="mt-4 text-sm text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline font-medium">
                    + Crear primer proyecto
                </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="project in projects" :key="project.id"
                    class="group rounded-xl border bg-white dark:bg-gray-800 shadow-sm dark:shadow-md hover:shadow-md transition-all"
                    :class="project.is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-700 opacity-60'"
                    :style="{ borderLeft: `4px solid ${project.color}` }">

                    <div class="p-5">
                        <!-- Header de la card -->
                        <div class="flex items-start justify-between mb-3">
                            <Link :href="route('projects.show', project.id)"
                                class="flex items-center gap-2.5 flex-1 hover:opacity-80 transition-opacity">
                                <div class="h-9 w-9 rounded-lg flex items-center justify-center text-white font-bold text-sm shrink-0"
                                    :style="{ backgroundColor: project.color }">
                                    {{ project.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white dark:text-white leading-tight hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors">{{ project.name }}</p>
                                    <span v-if="!project.is_active"
                                        class="text-[10px] bg-gray-100 text-gray-500 dark:text-gray-400 dark:text-gray-400 px-1.5 py-0.5 rounded font-medium">
                                        Archivado
                                    </span>
                                </div>
                            </Link>
                            <!-- Acciones (hover) -->
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEdit(project)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteProject(project)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:bg-red-900/30 dark:bg-red-900/30 transition-colors">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <p v-if="project.description" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-4 line-clamp-2">
                            {{ project.description }}
                        </p>

                        <!-- Stats -->
                        <div class="flex items-center gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <Link :href="`/entries?project_id=${project.id}`"
                                class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors">
                                <span class="text-sm">📝</span>
                                <span><strong class="text-gray-800 dark:text-gray-100 dark:text-gray-100">{{ project.entries_count }}</strong> entradas</span>
                            </Link>
                            <Link :href="`/tasks?project_id=${project.id}`"
                                class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors">
                                <span class="text-sm">✅</span>
                                <span>
                                    <strong class="text-gray-800 dark:text-gray-100 dark:text-gray-100">{{ project.tasks_pending_count }}</strong>
                                    <span class="text-gray-400"> / {{ project.tasks_count }}</span>
                                </span>
                            </Link>
                            <!-- Barra de progreso de tareas -->
                            <div v-if="project.tasks_count > 0" class="flex-1 h-1.5 rounded-full bg-gray-100 ml-1">
                                <div class="h-1.5 rounded-full bg-green-500 transition-all"
                                    :style="{ width: `${Math.round(project.tasks_done_count / project.tasks_count * 100)}%` }" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal crear / editar ── -->
        <Transition name="fade">
            <div v-if="modalOpen" @click.self="closeModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">
                            {{ editTarget ? 'Editar proyecto' : 'Nuevo proyecto' }}
                        </h2>
                        <button @click="closeModal"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="px-6 py-5 space-y-5">

                        <!-- Preview del color + nombre -->
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900">
                            <div class="h-10 w-10 rounded-xl flex items-center justify-center text-white font-bold"
                                :style="{ backgroundColor: form.color }">
                                {{ form.name ? form.name.charAt(0).toUpperCase() : '?' }}
                            </div>
                            <p class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-100">{{ form.name || 'Nombre del proyecto' }}</p>
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.name" type="text" autofocus
                                placeholder="Ej: API REST, App Mobile..."
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                :class="{ 'border-red-400': form.errors.name }" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button v-for="c in PRESET_COLORS" :key="c"
                                    type="button"
                                    @click="form.color = c"
                                    class="h-8 w-8 rounded-full border-2 transition-all hover:scale-110"
                                    :class="form.color === c ? 'border-gray-900 scale-110' : 'border-transparent'"
                                    :style="{ backgroundColor: c }" />
                                <!-- Color personalizado -->
                                <label class="relative h-8 w-8 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 dark:border-gray-600 flex items-center justify-center cursor-pointer hover:border-gray-400 overflow-hidden transition-colors">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <input type="color" v-model="form.color" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" />
                                </label>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                            <textarea v-model="form.description" rows="2"
                                placeholder="¿De qué trata este proyecto?"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm resize-none focus:border-blue-400 focus:outline-none" />
                        </div>

                        <!-- Activo -->
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:border-gray-600 text-blue-600 dark:text-blue-400 dark:text-blue-400 focus:ring-blue-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Proyecto activo</span>
                        </label>

                        <div class="flex gap-3 pt-1">
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                                {{ editTarget ? 'Guardar cambios' : 'Crear proyecto' }}
                            </button>
                            <button type="button" @click="closeModal"
                                class="flex-1 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
