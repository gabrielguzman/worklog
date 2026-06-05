<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    results:   Object,
    totals:    Object,
    totalAll:  Number,
    query:     String,
    filters:   Object,
    projects:  Array,
    tags:      Array,
    hasQuery:  Boolean,
})

// ── Búsqueda ─────────────────────────────────────────────────────
const searchInput = ref(props.query ?? '')
const typeTab     = ref(props.filters.type       ?? 'all')
const project_id  = ref(props.filters.project_id ?? '')
const tag         = ref(props.filters.tag        ?? '')
const from        = ref(props.filters.from       ?? '')
const to          = ref(props.filters.to         ?? '')
const inputRef    = ref(null)

onMounted(() => inputRef.value?.focus())

let searchTimer = null
const apply = (immediate = false) => {
    clearTimeout(searchTimer)
    const run = () => router.get('/search', {
        q:          searchInput.value || undefined,
        type:       typeTab.value     || undefined,
        project_id: project_id.value  || undefined,
        tag:        tag.value         || undefined,
        from:       from.value        || undefined,
        to:         to.value          || undefined,
    }, { preserveState: true, replace: true })

    if (immediate) run()
    else searchTimer = setTimeout(run, 300)
}

watch(searchInput, () => apply(false))
watch([typeTab, project_id, tag, from, to], () => apply(true))

const clearAll = () => {
    searchInput.value = ''; typeTab.value = 'all'
    project_id.value = ''; tag.value = ''; from.value = ''; to.value = ''
    apply(true)
    inputRef.value?.focus()
}

// ── Highlight ─────────────────────────────────────────────────────
const highlight = (text, q) => {
    if (!text || !q) return text ?? ''
    const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    return text.replace(new RegExp(`(${escaped})`, 'gi'),
        '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>')
}

// ── Configuraciones visuales ──────────────────────────────────────
const TYPE_ENTRY = {
    general:       { emoji: '📝', label: 'General'       },
    reunion:       { emoji: '🤝', label: 'Reunión'       },
    deploy:        { emoji: '🚀', label: 'Deploy'        },
    code_review:   { emoji: '🔍', label: 'Code Review'   },
    investigacion: { emoji: '🔬', label: 'Investigación' },
    planificacion: { emoji: '📋', label: 'Planificación' },
}

const PRIORITY = {
    urgent: { label: 'Urgente', class: 'bg-red-100 text-red-700 dark:text-red-400 dark:text-red-400'      },
    high:   { label: 'Alta',    class: 'bg-orange-100 text-orange-700 dark:text-orange-400' },
    medium: { label: 'Media',   class: 'bg-yellow-100 text-yellow-700 dark:text-yellow-400' },
    low:    { label: 'Baja',    class: 'bg-gray-100 text-gray-500 dark:text-gray-400 dark:text-gray-400'    },
}

const STATUS_ICON = {
    pending:     '○',
    in_progress: '◑',
    done:        '✓',
}

const mimeIcon = (mime) => {
    if (mime?.startsWith('image/')) return '🖼️'
    if (mime === 'application/pdf') return '📄'
    if (mime?.includes('word'))     return '📝'
    if (mime?.includes('excel') || mime?.includes('spreadsheet')) return '📊'
    return '📎'
}

// ── Tabs ──────────────────────────────────────────────────────────
const tabs = computed(() => [
    { key: 'all',     label: 'Todo',     count: props.totalAll         },
    { key: 'entries', label: 'Entradas', count: props.totals.entries   },
    { key: 'tasks',   label: 'Tareas',   count: props.totals.tasks     },
    { key: 'files',   label: 'Archivos', count: props.totals.files     },
])

// Resultados visibles según tab
const visibleEntries = computed(() => typeTab.value === 'all' || typeTab.value === 'entries' ? props.results.entries : [])
const visibleTasks   = computed(() => typeTab.value === 'all' || typeTab.value === 'tasks'   ? props.results.tasks   : [])
const visibleFiles   = computed(() => typeTab.value === 'all' || typeTab.value === 'files'   ? props.results.files   : [])

const hasFilters = computed(() => project_id.value || tag.value || from.value || to.value)
</script>

<template>
    <Head title="Búsqueda — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-5xl mx-auto space-y-6">

            <!-- Buscador principal -->
            <div class="space-y-3">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        ref="inputRef"
                        v-model="searchInput"
                        type="text"
                        placeholder="Buscar en entradas, tareas y archivos..."
                        class="w-full rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pl-12 pr-12 py-4 text-gray-900 dark:text-white dark:text-white text-lg shadow-sm dark:shadow-md focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 transition-all"
                        :class="{ 'border-blue-400': searchInput }"
                    />
                    <button v-if="searchInput || hasFilters" @click="clearAll"
                        class="absolute right-4 top-1/2 -translate-y-1/2 p-1 rounded-full text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Filtros adicionales -->
                <div class="flex gap-2 flex-wrap">
                    <select v-model="project_id"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800">
                        <option value="">Todos los proyectos</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <select v-model="tag"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800">
                        <option value="">Todos los tags</option>
                        <option v-for="t in tags" :key="t.id" :value="t.name">{{ t.name }}</option>
                    </select>
                    <input v-model="from" type="date" placeholder="Desde"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800" />
                    <input v-model="to" type="date" placeholder="Hasta"
                        class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm focus:border-blue-400 focus:outline-none bg-white dark:bg-gray-800" />
                    <button v-if="hasFilters" @click="project_id = ''; tag = ''; from = ''; to = ''"
                        class="flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:bg-red-900/30 dark:bg-red-900/30 transition-colors">
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Estado inicial sin búsqueda -->
            <div v-if="!hasQuery" class="text-center py-20">
                <p class="text-6xl mb-4">🔍</p>
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 dark:text-gray-100">Búsqueda global</h2>
                <p class="text-gray-400 mt-2 max-w-sm mx-auto">
                    Buscá en todas tus entradas, tareas y archivos al mismo tiempo.
                </p>
                <div class="flex flex-wrap justify-center gap-2 mt-6">
                    <button v-for="suggestion in ['deploy', 'code review', 'reunión', 'bug', 'backend']"
                        :key="suggestion"
                        @click="searchInput = suggestion"
                        class="px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:border-blue-300 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors bg-white dark:bg-gray-800">
                        {{ suggestion }}
                    </button>
                </div>
            </div>

            <!-- Sin resultados -->
            <div v-else-if="totalAll === 0"
                class="text-center py-20">
                <p class="text-5xl mb-3">📭</p>
                <p class="text-gray-600 dark:text-gray-400 font-medium">Sin resultados para "{{ query }}"</p>
                <p class="text-gray-400 text-sm mt-1">Probá con otras palabras o ajustá los filtros</p>
            </div>

            <!-- Resultados -->
            <template v-else>
                <!-- Resumen + Tabs -->
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                        <span class="font-semibold text-gray-900 dark:text-white dark:text-white">{{ totalAll }}</span>
                        resultado{{ totalAll !== 1 ? 's' : '' }} para
                        <span class="font-semibold text-blue-600 dark:text-blue-400 dark:text-blue-400">"{{ query }}"</span>
                    </p>
                </div>

                <div class="flex gap-1 rounded-xl p-1 bg-gray-100 w-fit">
                    <button v-for="tab in tabs" :key="tab.key"
                        @click="typeTab = tab.key"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-sm font-medium transition-all whitespace-nowrap',
                            typeTab === tab.key ? 'bg-white dark:bg-gray-800 shadow text-gray-900 dark:text-white dark:text-white' : 'text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300'
                        ]">
                        {{ tab.label }}
                        <span :class="['ml-1 text-xs font-semibold', typeTab === tab.key ? 'text-blue-600 dark:text-blue-400 dark:text-blue-400' : 'text-gray-400']">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- ── Entradas ── -->
                <section v-if="visibleEntries.length">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">📝</span>
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-100">Entradas</h2>
                        <span class="text-xs text-gray-400">{{ totals.entries }} resultado{{ totals.entries !== 1 ? 's' : '' }}</span>
                        <Link v-if="typeTab === 'all' && totals.entries > 10"
                            href="#" @click.prevent="typeTab = 'entries'"
                            class="ml-auto text-xs text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline">
                            Ver los {{ totals.entries }} →
                        </Link>
                    </div>

                    <div class="space-y-2">
                        <Link v-for="r in visibleEntries" :key="r.id" :href="r.url"
                            class="flex gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-md hover:shadow-md hover:border-blue-200 transition-all group">
                            <span class="text-2xl shrink-0 mt-0.5">
                                {{ TYPE_ENTRY[r.entry_type]?.emoji ?? '📝' }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors"
                                    v-html="highlight(r.title, query)" />
                                <p v-if="r.snippet" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1 line-clamp-2"
                                    v-html="highlight(r.snippet, query)" />
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    <span class="text-xs text-gray-400">{{ r.meta }}</span>
                                    <span v-if="r.project"
                                        class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                                        :style="{ backgroundColor: r.project.color }">
                                        {{ r.project.name }}
                                    </span>
                                    <span v-for="t in r.tags.slice(0,3)" :key="t.name"
                                        class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ t.name }}
                                    </span>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-300 group-hover:text-blue-400 shrink-0 self-center transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </section>

                <!-- ── Tareas ── -->
                <section v-if="visibleTasks.length">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">✅</span>
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-100">Tareas</h2>
                        <span class="text-xs text-gray-400">{{ totals.tasks }} resultado{{ totals.tasks !== 1 ? 's' : '' }}</span>
                        <Link v-if="typeTab === 'all' && totals.tasks > 10"
                            href="#" @click.prevent="typeTab = 'tasks'"
                            class="ml-auto text-xs text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline">
                            Ver los {{ totals.tasks }} →
                        </Link>
                    </div>

                    <div class="space-y-2">
                        <Link v-for="r in visibleTasks" :key="r.id" :href="r.url"
                            class="flex gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-md hover:shadow-md hover:border-blue-200 transition-all group">
                            <!-- Checkbox visual -->
                            <div :class="[
                                'mt-0.5 h-5 w-5 rounded-full border-2 shrink-0 flex items-center justify-center',
                                r.status === 'done'        ? 'border-green-500 bg-green-500' :
                                r.status === 'in_progress' ? 'border-blue-500 bg-blue-100'  :
                                                             'border-gray-300 dark:border-gray-600 dark:border-gray-600'
                            ]">
                                <svg v-if="r.status === 'done'" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <div v-else-if="r.status === 'in_progress'" class="h-2 w-2 rounded-full bg-blue-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p :class="['font-semibold group-hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors', r.status === 'done' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white dark:text-white']"
                                    v-html="highlight(r.title, query)" />
                                <p v-if="r.snippet" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1 line-clamp-2"
                                    v-html="highlight(r.snippet, query)" />
                                <div class="flex items-center gap-2 mt-2 flex-wrap">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', PRIORITY[r.priority]?.class]">
                                        {{ PRIORITY[r.priority]?.label }}
                                    </span>
                                    <span v-if="r.project"
                                        class="text-xs font-medium px-2 py-0.5 rounded-full text-white"
                                        :style="{ backgroundColor: r.project.color }">
                                        {{ r.project.name }}
                                    </span>
                                    <span v-for="t in r.tags.slice(0,2)" :key="t.name"
                                        class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                        {{ t.name }}
                                    </span>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-300 group-hover:text-blue-400 shrink-0 self-center transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </section>

                <!-- ── Archivos ── -->
                <section v-if="visibleFiles.length">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg">📎</span>
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-100">Archivos</h2>
                        <span class="text-xs text-gray-400">{{ totals.files }} resultado{{ totals.files !== 1 ? 's' : '' }}</span>
                        <Link v-if="typeTab === 'all' && totals.files > 10"
                            href="#" @click.prevent="typeTab = 'files'"
                            class="ml-auto text-xs text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline">
                            Ver los {{ totals.files }} →
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <a v-for="r in visibleFiles" :key="r.id" :href="r.url"
                            target="_blank" rel="noopener"
                            class="flex gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-md hover:shadow-md hover:border-blue-200 transition-all group">
                            <!-- Thumbnail o ícono -->
                            <div class="h-12 w-12 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shrink-0 flex items-center justify-center">
                                <img v-if="r.is_image" :src="r.url" :alt="r.title"
                                    class="h-full w-full object-cover" />
                                <span v-else class="text-2xl">{{ mimeIcon(r.mime_type) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white dark:text-white truncate group-hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors text-sm"
                                    v-html="highlight(r.title, query)" />
                                <p v-if="r.snippet" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5 line-clamp-2 italic"
                                    v-html="'OCR: ' + highlight(r.snippet, query)" />
                                <p class="text-xs text-gray-400 mt-1">{{ r.meta }}</p>
                            </div>
                        </a>
                    </div>
                </section>

            </template>

        </div>
    </AppLayout>
</template>
