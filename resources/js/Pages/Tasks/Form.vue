<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()
const props = defineProps({
    task:         Object,
    projects:     Array,
    tags:         Array,
    entries:      Array,
    templates:    Array,
    templateData: Object,
    defaults:     Object,
})

const isEditing = computed(() => !!props.task)

const PRIORITIES = [
    { value: 'low',    label: 'Baja',    emoji: '🟢', class: 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' },
    { value: 'medium', label: 'Media',   emoji: '🟡', class: 'border-yellow-300 text-yellow-700 dark:text-yellow-400' },
    { value: 'high',   label: 'Alta',    emoji: '🟠', class: 'border-orange-300 text-orange-700 dark:text-orange-400' },
    { value: 'urgent', label: 'Urgente', emoji: '🔴', class: 'border-red-300 text-red-700 dark:text-red-400' },
]

const STATUSES = [
    { value: 'pending',     label: 'Pendiente',   icon: '○' },
    { value: 'in_progress', label: 'En progreso', icon: '◑' },
    { value: 'done',        label: 'Hecha',       icon: '●' },
]

const form = useForm({
    title:               props.task?.title               ?? props.templateData?.title       ?? '',
    description:        props.task?.description         ?? props.templateData?.description ?? '',
    priority:           props.task?.priority            ?? props.templateData?.priority    ?? 'medium',
    status:             props.task?.status              ?? 'pending',
    due_date:           props.task?.due_date            ?? props.defaults?.due_date ?? '',
    project_id:         props.task?.project_id          ?? props.defaults?.project_id ?? (page.props.ziggy?.query?.project ?? '') ?? '',
    entry_id:           props.task?.entry_id            ?? props.defaults?.entry_id ?? '',
    recurrence_type:    props.task?.recurrence_type     ?? 'none',
    recurrence_interval: props.task?.recurrence_interval ?? 1,
    recurrence_ends_at: props.task?.recurrence_ends_at  ?? '',
    tags:               props.task?.tags                ?? [],
})

const showRecurrence = ref(
    props.task?.recurrence_type && props.task?.recurrence_type !== 'none'
)

const toggleTag = (id) => {
    const idx = form.tags.indexOf(id)
    if (idx === -1) form.tags.push(id)
    else form.tags.splice(idx, 1)
}

const applyTemplate = (tpl) => {
    const f = tpl.fields
    if (f.title)       form.title       = f.title
    if (f.description) form.description = f.description
    if (f.priority)    form.priority    = f.priority
}

const submit = () => {
    if (isEditing.value) {
        form.put(`/tasks/${props.task.id}`)
    } else {
        form.post('/tasks')
    }
}

const backHref = computed(() =>
    isEditing.value ? `/tasks/${props.task.id}` : '/tasks'
)
</script>

<template>
    <Head :title="isEditing ? 'Editar tarea — WorkLog' : 'Nueva tarea — WorkLog'" />

    <AppLayout>
        <div class="p-6 max-w-2xl mx-auto">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link :href="backHref"
                    class="p-2 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ isEditing ? 'Editar tarea' : 'Nueva tarea' }}
                </h1>
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

                <!-- Título -->
                <div>
                    <label for="task-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Título <span class="text-red-500">*</span>
                    </label>
                    <input id="task-title"
                        v-model="form.title"
                        type="text"
                        autofocus
                        placeholder="¿Qué hay que hacer?"
                        aria-label="Título de la tarea"
                        :aria-describedby="form.errors.title ? 'title-error' : undefined"
                        :aria-invalid="!!form.errors.title"
                        required
                        aria-required="true"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-gray-900 dark:text-white focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :class="{ 'border-red-400': form.errors.title }" />
                    <p v-if="form.errors.title" id="title-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.title }}</p>
                </div>

                <!-- Descripción -->
                <div>
                    <label for="task-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                    <textarea id="task-description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Contexto, criterios de aceptación, notas técnicas..."
                        aria-label="Descripción de la tarea"
                        aria-describedby="description-hint"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-900 dark:text-white focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100 resize-y" />
                    <p id="description-hint" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opcional. Agrega contexto y criterios de aceptación.</p>
                </div>

                <!-- Prioridad -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prioridad</label>
                    <div class="flex gap-2 flex-wrap">
                        <button v-for="p in PRIORITIES" :key="p.value" type="button"
                            @click="form.priority = p.value"
                            :class="[
                                'flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-all',
                                form.priority === p.value
                                    ? `${p.class} bg-opacity-20 border-2`
                                    : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600'
                            ]">
                            {{ p.emoji }} {{ p.label }}
                        </button>
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
                    <div class="flex gap-2">
                        <button v-for="s in STATUSES" :key="s.value" type="button"
                            @click="form.status = s.value"
                            :class="[
                                'flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-all flex-1 justify-center',
                                form.status === s.value
                                    ? s.value === 'done'        ? 'border-green-500 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                                    : s.value === 'in_progress' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700'
                                    :                             'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'
                                    : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600'
                            ]">
                            <span class="text-base">{{ s.icon }}</span> {{ s.label }}
                        </button>
                    </div>
                </div>

                <!-- Fecha límite + Proyecto -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="task-due-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha límite</label>
                        <input id="task-due-date"
                            v-model="form.due_date"
                            type="date"
                            aria-label="Fecha límite de la tarea"
                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" />
                    </div>
                    <div>
                        <label for="task-project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Proyecto</label>
                        <select id="task-project"
                            v-model="form.project_id"
                            aria-label="Proyecto de la tarea"
                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                            <option value="">Sin proyecto</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Repetir -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button type="button"
                        @click="showRecurrence = !showRecurrence"
                        class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Repetir
                            <span v-if="form.recurrence_type !== 'none'"
                                class="text-xs font-normal text-blue-600 dark:text-blue-400">
                                (activo)
                            </span>
                        </span>
                        <svg :class="['h-4 w-4 text-gray-400 transition-transform', showRecurrence ? 'rotate-180' : '']"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div v-if="showRecurrence" class="px-4 pb-4 pt-2 space-y-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Frecuencia</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="opt in [
                                    { value: 'none',    label: 'Sin repetir' },
                                    { value: 'daily',   label: 'Diario'      },
                                    { value: 'weekly',  label: 'Semanal'     },
                                    { value: 'monthly', label: 'Mensual'     },
                                ]" :key="opt.value" type="button"
                                    @click="form.recurrence_type = opt.value"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg border text-xs font-medium transition-all',
                                        form.recurrence_type === opt.value
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700'
                                            : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600'
                                    ]">
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="form.recurrence_type !== 'none'" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    Cada cuántos
                                    {{ form.recurrence_type === 'daily' ? 'días' : form.recurrence_type === 'weekly' ? 'semanas' : 'meses' }}
                                </label>
                                <input v-model.number="form.recurrence_interval" type="number" min="1" max="365"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Termina el (opcional)</label>
                                <input v-model="form.recurrence_ends_at" type="date"
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Entrada vinculada -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Entrada del registro vinculada</label>
                    <select v-model="form.entry_id"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">Sin entrada vinculada</option>
                        <option v-for="e in entries" :key="e.id" :value="e.id">
                            {{ e.title }} — {{ e.entry_date }}
                        </option>
                    </select>
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="tag in tags" :key="tag.id" type="button"
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
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex items-center gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" :disabled="form.processing"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 shadow-sm dark:shadow-md transition-colors">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                        </svg>
                        {{ isEditing ? 'Guardar cambios' : 'Crear tarea' }}
                    </button>
                    <Link :href="backHref"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 transition-colors">
                        Cancelar
                    </Link>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
