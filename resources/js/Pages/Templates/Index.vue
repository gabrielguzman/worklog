<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ templates: Array })

const ICONS = [
    { value: 'document',    emoji: '📝' },
    { value: 'users',       emoji: '🤝' },
    { value: 'rocket',      emoji: '🚀' },
    { value: 'code',        emoji: '🔍' },
    { value: 'research',    emoji: '🔬' },
    { value: 'plan',        emoji: '📋' },
    { value: 'bug',         emoji: '🐛' },
    { value: 'star',        emoji: '⭐' },
    { value: 'warning',     emoji: '⚠️' },
    { value: 'check',       emoji: '✅' },
    { value: 'clock',       emoji: '⏰' },
    { value: 'idea',        emoji: '💡' },
]

const ENTRY_TYPES = [
    { value: 'general',       label: 'General'       },
    { value: 'reunion',       label: 'Reunión'       },
    { value: 'deploy',        label: 'Deploy'        },
    { value: 'code_review',   label: 'Code Review'   },
    { value: 'investigacion', label: 'Investigación' },
    { value: 'planificacion', label: 'Planificación' },
]

const PRIORITIES = ['urgent', 'high', 'medium', 'low']

// ── Modal ────────────────────────────────────────────────────────
const modalOpen  = ref(false)
const editTarget = ref(null)

const form = useForm({
    name:      '',
    type:      'entry',
    icon:      'document',
    is_active: true,
    fields: {
        // entry fields
        title:       '',
        content:     '',
        type:        'general',
        // task fields
        priority:    'medium',
        description: '',
    },
})

const iconEmoji = computed(() => ICONS.find(i => i.value === form.icon)?.emoji ?? '📝')

const openCreate = () => {
    editTarget.value = null
    form.reset()
    form.icon      = 'document'
    form.type      = 'entry'
    form.is_active = true
    form.fields    = { title: '', content: '', type: 'general', priority: 'medium', description: '' }
    modalOpen.value = true
}

const openEdit = (tpl) => {
    editTarget.value = tpl
    form.name      = tpl.name
    form.type      = tpl.type
    form.icon      = tpl.icon
    form.is_active = tpl.is_active
    form.fields    = { ...{ title: '', content: '', type: 'general', priority: 'medium', description: '' }, ...tpl.fields }
    modalOpen.value = true
}

const closeModal = () => { modalOpen.value = false; editTarget.value = null }

const submit = () => {
    if (editTarget.value) {
        form.put(`/templates/${editTarget.value.id}`, { onSuccess: closeModal })
    } else {
        form.post('/templates', { onSuccess: closeModal })
    }
}

const deleteTemplate = (tpl) => {
    if (confirm(`¿Eliminar la plantilla "${tpl.name}"?`)) {
        router.delete(`/templates/${tpl.id}`, { preserveScroll: true })
    }
}

const entryTemplates = computed(() => props.templates.filter(t => t.type === 'entry'))
const taskTemplates  = computed(() => props.templates.filter(t => t.type === 'task'))

const getIcon = (icon) => ICONS.find(i => i.value === icon)?.emoji ?? '📝'
</script>

<template>
    <Head title="Plantillas — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">Plantillas</h1>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm mt-0.5">
                        Crea atajos para tipos de entradas y tareas que repetís seguido
                    </p>
                </div>
                <button @click="openCreate"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva plantilla
                </button>
            </div>

            <!-- Vacío -->
            <div v-if="templates.length === 0"
                class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-20 text-center">
                <p class="text-5xl mb-3">📋</p>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium">Sin plantillas todavía</p>
                <p class="text-gray-400 text-sm mt-1 max-w-xs mx-auto">
                    Creá plantillas para reuniones, deploys, code reviews y más. Se usarán al crear nuevas entradas y tareas.
                </p>
                <button @click="openCreate" class="mt-4 text-sm text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline font-medium">
                    + Crear primera plantilla
                </button>
            </div>

            <!-- Sección Entradas -->
            <div v-if="entryTemplates.length > 0">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                    📝 Plantillas de entradas ({{ entryTemplates.length }})
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="tpl in entryTemplates" :key="tpl.id"
                        :class="['group rounded-xl border bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-md hover:shadow-md transition-all', tpl.is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-700 opacity-60']">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">{{ getIcon(tpl.icon) }}</span>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white dark:text-white text-sm">{{ tpl.name }}</p>
                                    <p class="text-xs text-gray-400">
                                        Tipo: {{ tpl.fields?.type ?? 'general' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEdit(tpl)" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button @click="deleteTemplate(tpl)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:bg-red-900/30 dark:bg-red-900/30 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="tpl.fields?.title" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-1">
                            <span class="font-medium">Título:</span> {{ tpl.fields.title }}
                        </p>
                        <p v-if="tpl.fields?.content" class="text-xs text-gray-400 line-clamp-2 font-mono bg-gray-50 dark:bg-gray-900 rounded p-1.5">
                            {{ tpl.fields.content.slice(0, 100) }}...
                        </p>
                        <Link :href="`/entries/create?template=${tpl.id}`"
                            class="mt-3 flex items-center justify-center gap-1.5 w-full rounded-lg border border-blue-200 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 transition-colors">
                            Usar plantilla →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Sección Tareas -->
            <div v-if="taskTemplates.length > 0">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                    ✅ Plantillas de tareas ({{ taskTemplates.length }})
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div v-for="tpl in taskTemplates" :key="tpl.id"
                        :class="['group rounded-xl border bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-md hover:shadow-md transition-all', tpl.is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-700 opacity-60']">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-2xl">{{ getIcon(tpl.icon) }}</span>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white dark:text-white text-sm">{{ tpl.name }}</p>
                                    <p class="text-xs text-gray-400 capitalize">
                                        Prioridad: {{ tpl.fields?.priority ?? 'medium' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openEdit(tpl)" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button @click="deleteTemplate(tpl)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:bg-red-900/30 dark:bg-red-900/30 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="tpl.fields?.description" class="text-xs text-gray-400 line-clamp-2">
                            {{ tpl.fields.description }}
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Modal crear/editar ── -->
        <Transition name="fade">
            <div v-if="modalOpen" @click.self="closeModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 overflow-y-auto">
                <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 shadow-2xl my-4" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">
                            {{ editTarget ? 'Editar plantilla' : 'Nueva plantilla' }}
                        </h2>
                        <button @click="closeModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="px-6 py-5 space-y-5">

                        <!-- Preview -->
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900">
                            <span class="text-3xl">{{ iconEmoji }}</span>
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-100">{{ form.name || 'Nombre de la plantilla' }}</p>
                                <p class="text-xs text-gray-400">Plantilla de {{ form.type === 'entry' ? 'entrada' : 'tarea' }}</p>
                            </div>
                        </div>

                        <!-- Tipo de plantilla -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                            <div class="flex gap-2">
                                <button v-for="t in [{v:'entry',l:'📝 Entrada'},{v:'task',l:'✅ Tarea'}]" :key="t.v"
                                    type="button" @click="form.type = t.v"
                                    :class="['flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                        form.type === t.v ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 text-blue-700' : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600 dark:border-gray-600']">
                                    {{ t.l }}
                                </button>
                            </div>
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" autofocus placeholder="Ej: Reunión de equipo, Bug report..."
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 focus:border-blue-400 focus:outline-none" />
                        </div>

                        <!-- Ícono -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ícono</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="ic in ICONS" :key="ic.value" type="button"
                                    @click="form.icon = ic.value"
                                    :class="['h-10 w-10 rounded-xl text-xl flex items-center justify-center transition-all hover:scale-110',
                                        form.icon === ic.value ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-100 hover:bg-gray-200 dark:bg-gray-600']">
                                    {{ ic.emoji }}
                                </button>
                            </div>
                        </div>

                        <!-- Campos según tipo -->
                        <template v-if="form.type === 'entry'">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título por defecto</label>
                                <input v-model="form.fields.title" type="text" placeholder="Ej: Reunión - , Deploy v"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de entrada</label>
                                <select v-model="form.fields.type" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                                    <option v-for="t in ENTRY_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contenido base (Markdown)</label>
                                <textarea v-model="form.fields.content" rows="6" placeholder="## Participantes:&#10;&#10;## Temas:&#10;&#10;## Decisiones:&#10;"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm font-mono resize-y focus:border-blue-400 focus:outline-none" />
                            </div>
                        </template>

                        <template v-else>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prioridad por defecto</label>
                                <div class="flex gap-2">
                                    <button v-for="p in PRIORITIES" :key="p" type="button" @click="form.fields.priority = p"
                                        :class="['flex-1 py-2 rounded-lg border text-sm font-medium capitalize transition-all',
                                            form.fields.priority === p ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 text-blue-700' : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600 dark:border-gray-600']">
                                        {{ p }}
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción base</label>
                                <textarea v-model="form.fields.description" rows="4"
                                    placeholder="Descripción, criterios de aceptación, pasos para reproducir..."
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm resize-y focus:border-blue-400 focus:outline-none" />
                            </div>
                        </template>

                        <!-- Activo -->
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:border-gray-600 text-blue-600 dark:text-blue-400 dark:text-blue-400" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">Plantilla activa (visible al crear entradas/tareas)</span>
                        </label>

                        <div class="flex gap-3 pt-1">
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                                {{ editTarget ? 'Guardar cambios' : 'Crear plantilla' }}
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
