<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const page = usePage()
const route = (name) => {
    const routes = {
        'export.entries.csv': `/export/entries/csv`,
        'export.entries.pdf': `/export/entries/pdf`,
    }
    return routes[name] || ''
}

const props = defineProps({
    entries:  Object,
    projects: Array,
    tags:     Array,
    filters:  Object,
})

const TYPE_CONFIG = {
    general:       { label: 'General',       emoji: '📝', color: 'bg-gray-100 text-gray-600 dark:text-gray-400' },
    reunion:       { label: 'Reunión',        emoji: '🤝', color: 'bg-blue-100 text-blue-700' },
    deploy:        { label: 'Deploy',         emoji: '🚀', color: 'bg-green-100 text-green-700 dark:text-green-400' },
    code_review:   { label: 'Code Review',    emoji: '🔍', color: 'bg-purple-100 text-purple-700' },
    investigacion: { label: 'Investigación',  emoji: '🔬', color: 'bg-amber-100 text-amber-700' },
    planificacion: { label: 'Planificación',  emoji: '📋', color: 'bg-teal-100 text-teal-700' },
}

// Filtros locales
const search     = ref(props.filters.search     ?? '')
const project_id = ref(props.filters.project_id ?? '')
const type       = ref(props.filters.type       ?? '')
const tag        = ref(props.filters.tag        ?? '')
const from       = ref(props.filters.from       ?? '')
const to         = ref(props.filters.to         ?? '')

let searchTimer = null
const applyFilters = () => {
    router.get('/entries', {
        search:     search.value     || undefined,
        project_id: project_id.value || undefined,
        type:       type.value       || undefined,
        tag:        tag.value        || undefined,
        from:       from.value       || undefined,
        to:         to.value         || undefined,
    }, { preserveState: true, replace: true })
}

watch(search, (val) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 350)
})

watch([project_id, type, tag, from, to], applyFilters)

const clearFilters = () => {
    search.value = ''; project_id.value = ''; type.value = ''
    tag.value = ''; from.value = ''; to.value = ''
    applyFilters()
}

const hasFilters = computed(() =>
    search.value || project_id.value || type.value || tag.value || from.value || to.value
)

// Agrupar entradas por fecha
const grouped = computed(() => {
    const groups = {}
    for (const e of props.entries.data) {
        const key = e.entry_date
        if (!groups[key]) groups[key] = { label: e.entry_date_label, entries: [] }
        groups[key].entries.push(e)
    }
    return Object.values(groups)
})

const deleteEntry = (id) => {
    if (confirm('¿Eliminar esta entrada? No se puede deshacer.')) {
        router.delete(`/entries/${id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Registro — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Registro diario</h1>
                    <p class="text-gray-700 dark:text-gray-400 text-sm mt-0.5">
                        {{ entries.total }} entrada{{ entries.total !== 1 ? 's' : '' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Export button -->
                    <a :href="route('export.entries.csv')" class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        📥 Descargar
                    </a>
                    <Link href="/entries/create"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva entrada
                    </Link>
                </div>
            </div>

            <!-- Filtros -->
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md p-4 space-y-3">
                <!-- Búsqueda -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="search" type="text" placeholder='Buscar en títulos y contenido...'
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 pl-9 pr-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                    <button v-if="search" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Filtros secundarios -->
                <div class="flex gap-2 flex-wrap">
                    <select v-model="project_id"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 appearance-none pr-8 cursor-pointer"
                        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23666%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 14l-7 7m0 0l-7-7m7 7V3%22></path></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                        <option value="">Todos los proyectos</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>

                    <select v-model="type"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 appearance-none pr-8 cursor-pointer"
                        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23666%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 14l-7 7m0 0l-7-7m7 7V3%22></path></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                        <option value="">Todos los tipos</option>
                        <option v-for="(cfg, val) in TYPE_CONFIG" :key="val" :value="val">
                            {{ cfg.emoji }} {{ cfg.label }}
                        </option>
                    </select>

                    <select v-model="tag"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 appearance-none pr-8 cursor-pointer"
                        style="background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%23666%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 stroke-width=%222%22 d=%22M19 14l-7 7m0 0l-7-7m7 7V3%22></path></svg>'); background-repeat: no-repeat; background-position: right 8px center; background-size: 16px;">
                        <option value="">Todos los tags</option>
                        <option v-for="t in tags" :key="t.id" :value="t.name">{{ t.name }}</option>
                    </select>

                    <input v-model="from" type="date"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900" />
                    <input v-model="to" type="date"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-1.5 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900" />

                    <button v-if="hasFilters" @click="clearFilters"
                        class="flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:bg-red-900/30 transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Lista agrupada por fecha -->
            <div v-if="entries.data.length === 0"
                class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-16 text-center">
                <p class="text-4xl mb-3">📭</p>
                <p class="text-gray-700 dark:text-gray-400 font-medium">Sin entradas</p>
                <p class="text-gray-400 text-sm mt-1">
                    {{ hasFilters ? 'Probá con otros filtros' : 'Creá tu primera entrada del día' }}
                </p>
                <Link v-if="!hasFilters" href="/entries/create"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 font-medium hover:underline">
                    + Nueva entrada
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="group in grouped" :key="group.label">
                    <!-- Separador de fecha -->
                    <div class="flex items-center gap-3 mb-3">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide capitalize">
                            {{ group.label }}
                        </span>
                        <div class="flex-1 h-px bg-gray-100" />
                        <span class="text-xs text-gray-400">{{ group.entries.length }}</span>
                    </div>

                    <!-- Tarjetas -->
                    <div class="space-y-2">
                        <div v-for="entry in group.entries" :key="entry.id"
                            class="group rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md hover:shadow-md hover:border-gray-300 dark:border-gray-600 transition-all">
                            <div class="flex items-start gap-4 p-4">
                                <!-- Tipo emoji -->
                                <div class="text-2xl shrink-0 mt-0.5">
                                    {{ TYPE_CONFIG[entry.type]?.emoji ?? '📝' }}
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <Link :href="`/entries/${entry.id}`"
                                            class="font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:text-blue-400 transition-colors line-clamp-1">
                                            {{ entry.title }}
                                            <span v-if="entry.is_pinned" class="ml-1 text-amber-500 text-sm">📌</span>
                                        </Link>
                                        <span class="text-xs text-gray-400 shrink-0">{{ entry.entry_time }}</span>
                                    </div>

                                    <p v-if="entry.content_preview"
                                        class="text-sm text-gray-700 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ entry.content_preview }}
                                    </p>

                                    <div class="flex items-center gap-2 mt-2 flex-wrap">
                                        <!-- Tipo badge -->
                                        <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', TYPE_CONFIG[entry.type]?.color]">
                                            {{ TYPE_CONFIG[entry.type]?.label }}
                                        </span>
                                        <!-- Proyecto -->
                                        <span v-if="entry.project"
                                            class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                                            :style="{ backgroundColor: entry.project.color }">
                                            {{ entry.project.name }}
                                        </span>
                                        <!-- Tags -->
                                        <span v-for="t in entry.tags.slice(0, 3)" :key="t.id"
                                            class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400">
                                            {{ t.name }}
                                        </span>
                                        <!-- Adjuntos -->
                                        <span v-if="entry.attachments_count > 0"
                                            class="text-xs text-gray-400 flex items-center gap-0.5">
                                            📎 {{ entry.attachments_count }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Acciones (aparecen en hover) -->
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                    <Link :href="`/entries/${entry.id}/edit`"
                                        :aria-label="`Editar entrada: ${entry.title}`"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button @click="deleteEntry(entry.id)"
                                        :aria-label="`Eliminar entrada: ${entry.title}`"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:bg-red-900/30 transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="entries.last_page > 1" class="flex items-center justify-between pt-2">
                <p class="text-sm text-gray-700 dark:text-gray-400">
                    Mostrando {{ entries.from }}–{{ entries.to }} de {{ entries.total }}
                </p>
                <div class="flex gap-1">
                    <Link v-if="entries.prev_page_url" :href="entries.prev_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        ← Anterior
                    </Link>
                    <Link v-if="entries.next_page_url" :href="entries.next_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        Siguiente →
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
