<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({ entry: Object })

const TYPE_CONFIG = {
    general:       { label: 'General',       emoji: '📝', color: 'bg-gray-100 text-gray-600 dark:text-gray-400' },
    reunion:       { label: 'Reunión',        emoji: '🤝', color: 'bg-blue-100 text-blue-700' },
    deploy:        { label: 'Deploy',         emoji: '🚀', color: 'bg-green-100 text-green-700 dark:text-green-400' },
    code_review:   { label: 'Code Review',    emoji: '🔍', color: 'bg-purple-100 text-purple-700' },
    investigacion: { label: 'Investigación',  emoji: '🔬', color: 'bg-amber-100 text-amber-700' },
    planificacion: { label: 'Planificación',  emoji: '📋', color: 'bg-teal-100 text-teal-700' },
}

const PRIORITY_CONFIG = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700 dark:text-red-400' },
    high:   { label: 'Alta',    class: 'bg-orange-100 text-orange-700 dark:text-orange-400' },
    medium: { label: 'Media',   class: 'bg-yellow-100 text-yellow-700 dark:text-yellow-400' },
    low:    { label: 'Baja',    class: 'bg-gray-100 text-gray-500 dark:text-gray-400' },
}

const STATUS_CONFIG = {
    pending:     { label: 'Pendiente',    class: 'text-gray-500 dark:text-gray-400' },
    in_progress: { label: 'En progreso',  class: 'text-blue-600 dark:text-blue-400' },
    done:        { label: 'Hecha',        class: 'text-green-600' },
}

const lightboxSrc = ref(null)

const mimeIcon = (mime) => {
    if (mime?.startsWith('image/')) return '🖼️'
    if (mime === 'application/pdf') return '📄'
    if (mime?.includes('word'))     return '📝'
    return '📎'
}

// Renderizado básico de Markdown a HTML (sin dependencias)
const renderMarkdown = (text) => {
    if (!text) return ''
    return text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/^### (.+)$/gm,  '<h3 class="text-base font-semibold mt-4 mb-1 text-gray-800 dark:text-gray-100">$1</h3>')
        .replace(/^## (.+)$/gm,   '<h2 class="text-lg font-bold mt-5 mb-2 text-gray-900 dark:text-white">$1</h2>')
        .replace(/^# (.+)$/gm,    '<h1 class="text-xl font-bold mt-5 mb-2 text-gray-900 dark:text-white">$1</h1>')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/_(.+?)_/g,       '<em>$1</em>')
        .replace(/`([^`]+)`/g,     '<code class="bg-gray-100 text-red-600 px-1 py-0.5 rounded text-sm font-mono">$1</code>')
        .replace(/```([\s\S]*?)```/g, '<pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-sm font-mono overflow-x-auto my-3"><code>$1</code></pre>')
        .replace(/^- (.+)$/gm,    '<li class="ml-4 list-disc text-gray-700 dark:text-gray-300">$1</li>')
        .replace(/\n{2,}/g, '</p><p class="mt-3">')
        .replace(/\n/g, '<br />')
}

const deleteEntry = () => {
    if (confirm('¿Eliminar esta entrada? No se puede deshacer.')) {
        router.delete(`/entries/${props.entry.id}`)
    }
}

// ── Extracción de tareas con IA ──────────────────────────────────
const extracting     = ref(false)
const extractedTasks = ref([])
const extractError   = ref(null)
const showExtractModal = ref(false)

const extractTasksAI = async () => {
    extracting.value = true
    extractError.value = null
    try {
        const res = await axios.post('/api/ai/extract-tasks', {
            entry_id:   props.entry.id,
            entry_text: props.entry.content,
        })
        extractedTasks.value = res.data.tasks || []
        showExtractModal.value = true
    } catch (err) {
        extractError.value = 'Error al extraer tareas. Intenta de nuevo.'
    } finally {
        extracting.value = false
    }
}

const createTaskFromExtracted = async (taskData) => {
    try {
        await router.post('/tasks', {
            title:       taskData.title,
            description: '',
            priority:    taskData.priority || 'medium',
            status:      'pending',
            entry_id:    props.entry.id,
            project_id:  props.entry.project_id || '',
            due_date:    '',
            tags:        [],
        })
        extractedTasks.value = extractedTasks.value.filter(t => t !== taskData)
        router.reload({ preserveScroll: true })
    } catch {
        // Error manejado por Inertia
    }
}
</script>

<template>
    <Head :title="`${entry.title} — WorkLog`" />

    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <!-- Breadcrumb + acciones -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <Link href="/entries" class="hover:text-blue-600 dark:text-blue-400 transition-colors">Registro</Link>
                    <span>/</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium truncate max-w-xs">{{ entry.title }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="`/entries/${entry.id}/edit`"
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </Link>
                    <button @click="deleteEntry"
                        class="flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:bg-red-900/30 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar
                    </button>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">

                <!-- Header de la entrada -->
                <div class="px-6 pt-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start gap-3">
                        <span class="text-3xl mt-1">{{ TYPE_CONFIG[entry.type]?.emoji ?? '📝' }}</span>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span v-if="entry.is_pinned" class="text-amber-500">📌</span>
                                <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', TYPE_CONFIG[entry.type]?.color]">
                                    {{ TYPE_CONFIG[entry.type]?.label }}
                                </span>
                                <span v-if="entry.project"
                                    class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                                    :style="{ backgroundColor: entry.project.color }">
                                    {{ entry.project.name }}
                                </span>
                                <span v-for="tag in entry.tags" :key="tag.id"
                                    class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                    {{ tag.name }}
                                </span>
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white leading-snug">{{ entry.title }}</h1>
                            <p class="text-sm text-gray-400 mt-1 capitalize">
                                {{ entry.entry_date_label }} · {{ entry.entry_time }} hs
                                <span class="ml-2">· {{ entry.created_at }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cuerpo de contenido -->
                <div class="px-6 py-5">
                    <div v-if="entry.content"
                        class="prose-sm text-gray-700 dark:text-gray-300 leading-relaxed"
                        v-html="renderMarkdown(entry.content)" />
                    <p v-else class="text-gray-400 italic text-sm">Sin contenido.</p>
                </div>
            </div>

            <!-- Grid inferior: adjuntos + tareas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Adjuntos -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">
                            Archivos adjuntos
                            <span class="ml-1.5 text-xs font-normal text-gray-400">({{ entry.attachments.length }})</span>
                        </h2>
                        <Link :href="`/files?entry=${entry.id}`"
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline">+ Subir</Link>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="entry.attachments.length === 0"
                            class="py-6 text-center text-gray-400 text-sm">
                            Sin archivos adjuntos
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="att in entry.attachments" :key="att.id"
                                class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                <!-- Preview imagen o icono -->
                                <button v-if="att.is_image" @click="lightboxSrc = att.url"
                                    class="shrink-0 h-10 w-10 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                    <img :src="att.url" :alt="att.original_name" class="h-full w-full object-cover" />
                                </button>
                                <span v-else class="text-2xl shrink-0">{{ mimeIcon(att.mime_type) }}</span>

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

                <!-- Tareas vinculadas -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">
                            Tareas vinculadas
                            <span class="ml-1.5 text-xs font-normal text-gray-400">({{ entry.tasks.length }})</span>
                        </h2>
                        <div class="flex items-center gap-2">
                            <button @click="extractTasksAI" :disabled="extracting"
                                class="text-xs px-2.5 py-1 rounded-lg border border-purple-200 text-purple-600 dark:text-purple-400 hover:bg-purple-50 disabled:opacity-50 transition-colors">
                                {{ extracting ? '⟳' : '✨' }} IA
                            </button>
                            <Link :href="`/tasks/create?entry=${entry.id}`"
                                class="text-xs text-blue-600 dark:text-blue-400 hover:underline">+ Agregar</Link>
                        </div>
                    </div>
                    <div class="px-5 py-4">
                        <div v-if="entry.tasks.length === 0"
                            class="py-6 text-center text-gray-400 text-sm">
                            Sin tareas vinculadas
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="task in entry.tasks" :key="task.id"
                                class="flex items-start gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                                <!-- Checkbox de estado -->
                                <div :class="[
                                    'mt-0.5 h-4 w-4 rounded border-2 shrink-0',
                                    task.status === 'done'        ? 'border-green-500 bg-green-500' :
                                    task.status === 'in_progress' ? 'border-blue-500 bg-blue-100'  :
                                                                    'border-gray-300 dark:border-gray-600'
                                ]">
                                    <svg v-if="task.status === 'done'" class="h-3 w-3 text-white m-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <Link :href="`/tasks/${task.id}`"
                                        :class="['text-sm font-medium hover:text-blue-600 dark:text-blue-400 transition-colors', task.status === 'done' ? 'line-through text-gray-400' : 'text-gray-800 dark:text-gray-100']">
                                        {{ task.title }}
                                    </Link>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span :class="['text-xs font-medium px-1.5 py-0.5 rounded-full', PRIORITY_CONFIG[task.priority]?.class]">
                                            {{ PRIORITY_CONFIG[task.priority]?.label }}
                                        </span>
                                        <span :class="['text-xs', STATUS_CONFIG[task.status]?.class]">
                                            {{ STATUS_CONFIG[task.status]?.label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal extracción de tareas -->
        <Transition name="fade">
            <div v-if="showExtractModal" @click.self="showExtractModal = false"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-2xl p-6" @click.stop>
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tareas detectadas</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Se encontraron {{ extractedTasks.length }} posibles tareas. Seleccioná cuáles crear.</p>
                    </div>

                    <div v-if="extractedTasks.length === 0" class="py-6 text-center text-gray-400">
                        No se detectaron tareas en el contenido.
                    </div>

                    <div v-else class="space-y-2 max-h-96 overflow-y-auto mb-4">
                        <div v-for="(task, i) in extractedTasks" :key="i"
                            class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-purple-50 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ task.title }}</p>
                                <p v-if="task.priority" class="text-xs text-gray-400 mt-0.5 capitalize">
                                    Prioridad: {{ task.priority }}
                                </p>
                            </div>
                            <button @click="createTaskFromExtracted(task)"
                                class="shrink-0 px-2.5 py-1.5 rounded-lg bg-purple-600 text-white text-xs font-medium hover:bg-purple-700 transition-colors">
                                Crear
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="showExtractModal = false"
                            class="flex-1 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Lightbox imagen -->
        <Transition name="fade">
            <div v-if="lightboxSrc" @click="lightboxSrc = null"
                class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 cursor-zoom-out">
                <img :src="lightboxSrc" class="max-h-full max-w-full rounded-lg shadow-2xl" @click.stop />
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
