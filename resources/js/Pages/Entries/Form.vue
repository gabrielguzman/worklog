<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()
const props = defineProps({
    entry:        Object,   // null = crear, objeto = editar
    projects:     Array,
    tags:         Array,
    templates:    Array,
    templateData: Object,
    defaults:     Object,
})

const isEditing = computed(() => !!props.entry)

const TYPES = [
    { value: 'general',       label: 'General',       emoji: '📝' },
    { value: 'reunion',       label: 'Reunión',        emoji: '🤝' },
    { value: 'deploy',        label: 'Deploy',         emoji: '🚀' },
    { value: 'code_review',   label: 'Code Review',    emoji: '🔍' },
    { value: 'investigacion', label: 'Investigación',  emoji: '🔬' },
    { value: 'planificacion', label: 'Planificación',  emoji: '📋' },
]

const form = useForm({
    title:      props.entry?.title      ?? props.templateData?.title    ?? '',
    content:    props.entry?.content    ?? props.templateData?.content  ?? '',
    type:       props.entry?.type       ?? props.templateData?.type     ?? 'general',
    entry_date: props.entry?.entry_date ?? props.defaults?.entry_date   ?? '',
    entry_time: props.entry?.entry_time ?? props.defaults?.entry_time   ?? '',
    project_id: props.entry?.project_id ?? (page.props.ziggy?.query?.project ?? '') ?? '',
    is_pinned:  props.entry?.is_pinned  ?? false,
    tags:       props.entry?.tags       ?? [],
})

const toggleTag = (id) => {
    const idx = form.tags.indexOf(id)
    if (idx === -1) form.tags.push(id)
    else form.tags.splice(idx, 1)
}

const applyTemplate = (tpl) => {
    const f = tpl.fields
    if (f.title)   form.title   = f.title
    if (f.content) form.content = f.content
    if (f.type)    form.type    = f.type
}

// ── Preview Markdown ────────────────────────────────────────────
const previewMode = ref(false)

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

// Toolbar de markdown
const insertMarkdown = (textarea, before, after = '') => {
    if (!textarea) return
    const start = textarea.selectionStart
    const end   = textarea.selectionEnd
    const sel   = form.content.substring(start, end)
    form.content = form.content.substring(0, start) + before + sel + after + form.content.substring(end)
    // Restaurar foco
    setTimeout(() => {
        textarea.focus()
        textarea.setSelectionRange(start + before.length, start + before.length + sel.length)
    }, 0)
}

const submit = () => {
    if (isEditing.value) {
        form.put(`/entries/${props.entry.id}`)
    } else {
        form.post('/entries')
    }
}

// Auto-clear errors cuando se edita un campo
watch([
    () => form.title,
    () => form.content,
    () => form.entry_date,
    () => form.entry_time,
], () => {
    // Limpiar errores al editar
    if (form.errors.title && form.title.length > 0) {
        delete form.errors.title
    }
    if (form.errors.entry_date && form.entry_date) {
        delete form.errors.entry_date
    }
    if (form.errors.entry_time && form.entry_time) {
        delete form.errors.entry_time
    }
})
</script>

<template>
    <Head :title="isEditing ? 'Editar entrada — WorkLog' : 'Nueva entrada — WorkLog'" />

    <AppLayout>
        <div class="p-6 max-w-3xl mx-auto">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link :href="isEditing ? `/entries/${entry.id}` : '/entries'"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ isEditing ? 'Editar entrada' : 'Nueva entrada' }}
                    </h1>
                    <p class="text-sm text-gray-400">Registrá lo que trabajaste hoy</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Plantillas (solo al crear) -->
                <div v-if="!isEditing && templates.length > 0"
                    class="flex gap-2 flex-wrap">
                    <span class="text-xs text-gray-400 self-center">Plantilla:</span>
                    <button
                        v-for="tpl in templates" :key="tpl.id"
                        type="button"
                        @click="applyTemplate(tpl)"
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 hover:border-blue-300 hover:text-blue-600 dark:text-blue-400 transition-colors">
                        {{ tpl.name }}
                    </button>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="t in TYPES" :key="t.value"
                            type="button"
                            @click="form.type = t.value"
                            :class="[
                                'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-all',
                                form.type === t.value
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900'
                            ]">
                            <span>{{ t.emoji }}</span> {{ t.label }}
                        </button>
                    </div>
                </div>

                <!-- Título -->
                <div>
                    <label for="entry-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Título <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="entry-title"
                        v-model="form.title"
                        type="text"
                        placeholder="¿Qué hiciste?"
                        autofocus
                        aria-label="Título de la entrada"
                        :aria-describedby="form.errors.title ? 'title-error' : undefined"
                        :aria-invalid="!!form.errors.title"
                        required
                        aria-required="true"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-gray-900 dark:text-white focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :class="{ 'border-red-400': form.errors.title }"
                    />
                    <p v-if="form.errors.title" id="title-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.title }}</p>
                </div>

                <!-- Contenido con toolbar markdown y preview -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="entry-content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contenido</label>
                        <div class="flex items-center gap-2">
                            <!-- Toolbar -->
                            <div class="flex gap-1 border-r border-gray-200 dark:border-gray-700 pr-2">
                                <button v-for="btn in [
                                    { label: 'B',   title: 'Negrita',  before: '**', after: '**' },
                                    { label: 'I',   title: 'Cursiva',  before: '_',  after: '_'  },
                                    { label: '•',   title: 'Lista',    before: '\n- ', after: '' },
                                    { label: 'H2',  title: 'Título',   before: '\n## ', after: '' },
                                    { label: '```', title: 'Código',   before: '```\n', after: '\n```' },
                                ]"
                                    :key="btn.label"
                                    type="button"
                                    :title="btn.title"
                                    @click="insertMarkdown($refs.contentArea, btn.before, btn.after)"
                                    class="px-2 py-1 rounded text-xs font-mono font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-100 transition-colors">
                                    {{ btn.label }}
                                </button>
                            </div>
                            <!-- Toggle Preview -->
                            <button type="button" @click="previewMode = !previewMode"
                                :class="['px-2.5 py-1 rounded text-xs font-medium transition-colors',
                                    previewMode ? 'bg-blue-100 text-blue-700' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100']">
                                👁 Preview
                            </button>
                        </div>
                    </div>

                    <!-- Editor o Preview -->
                    <div v-if="!previewMode"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <textarea
                            id="entry-content"
                            ref="contentArea"
                            v-model="form.content"
                            rows="12"
                            placeholder="Escribí los detalles, decisiones, observaciones... (soporta Markdown)"
                            aria-label="Contenido de la entrada"
                            aria-describedby="content-hint"
                            class="w-full px-4 py-3 text-sm text-gray-900 dark:text-white font-mono leading-relaxed focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 resize-y"
                        />
                    </div>

                    <!-- Preview Mode -->
                    <div v-else
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 min-h-96 max-h-96 overflow-y-auto prose prose-sm prose-gray"
                        v-html="renderMarkdown(form.content)">
                    </div>
                </div>

                <!-- Fecha y hora -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="entry-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fecha <span class="text-red-500">*</span>
                        </label>
                        <input id="entry-date"
                            v-model="form.entry_date"
                            type="date"
                            aria-label="Fecha de la entrada"
                            :aria-describedby="form.errors.entry_date ? 'date-error' : undefined"
                            :aria-invalid="!!form.errors.entry_date"
                            required
                            aria-required="true"
                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none"
                            :class="{ 'border-red-400': form.errors.entry_date }" />
                        <p v-if="form.errors.entry_date" id="date-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.entry_date }}</p>
                    </div>
                    <div>
                        <label for="entry-time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Hora <span class="text-red-500">*</span>
                        </label>
                        <input id="entry-time"
                            v-model="form.entry_time"
                            type="time"
                            aria-label="Hora de la entrada"
                            :aria-describedby="form.errors.entry_time ? 'time-error' : undefined"
                            :aria-invalid="!!form.errors.entry_time"
                            required
                            aria-required="true"
                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none"
                            :class="{ 'border-red-400': form.errors.entry_time }" />
                        <p v-if="form.errors.entry_time" id="time-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.entry_time }}</p>
                    </div>
                </div>

                <!-- Proyecto -->
                <div>
                    <label for="entry-project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Proyecto</label>
                    <select id="entry-project"
                        v-model="form.project_id"
                        aria-label="Proyecto de la entrada"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">Sin proyecto</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tag in tags" :key="tag.id"
                            type="button"
                            @click="toggleTag(tag.id)"
                            :class="[
                                'px-3 py-1.5 rounded-full text-sm font-medium transition-all border',
                                form.tags.includes(tag.id)
                                    ? 'text-white border-transparent'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600'
                            ]"
                            :style="form.tags.includes(tag.id) ? { backgroundColor: tag.color } : {}">
                            {{ tag.name }}
                        </button>
                        <p v-if="tags.length === 0" class="text-sm text-gray-400">
                            No hay tags. <Link href="/tags/create" class="text-blue-600 dark:text-blue-400 hover:underline">Crear uno</Link>
                        </p>
                    </div>
                </div>

                <!-- Fijar -->
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input v-model="form.is_pinned" type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-400 focus:ring-blue-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">📌 Fijar esta entrada (aparece primero)</span>
                </label>

                <!-- Botones -->
                <div class="flex items-center gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 transition-colors shadow-sm dark:shadow-md">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        {{ isEditing ? 'Guardar cambios' : 'Crear entrada' }}
                    </button>
                    <Link :href="isEditing ? `/entries/${entry.id}` : '/entries'"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                        Cancelar
                    </Link>
                    <span v-if="form.recentlySuccessful" class="text-sm text-green-600 font-medium">
                        ✓ Guardado
                    </span>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
