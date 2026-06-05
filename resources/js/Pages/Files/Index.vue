<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    files:   Object,
    counts:  Object,
    filters: Object,
    context: Object,
})

// ── Filtros ──────────────────────────────────────────────────────
const search   = ref(props.filters.search ?? '')
const typeTab  = ref(props.filters.type   ?? '')

let searchTimer = null
const apply = () => {
    const params = {
        search:  search.value  || undefined,
        type:    typeTab.value || undefined,
        entry:   props.filters.entry || undefined,
        task:    props.filters.task  || undefined,
    }
    router.get('/files', params, { preserveState: true, replace: true })
}
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(apply, 350) })
watch(typeTab, apply)

// ── Upload drag & drop ───────────────────────────────────────────
const isDragging    = ref(false)
const uploadError   = ref('')
const uploadSuccess = ref('')
const uploading     = ref(false)

const form = useForm({
    files:           [],
    attachable_type: props.context?.type  ?? '',
    attachable_id:   props.context?.id    ?? '',
})

const ACCEPTED = 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.mp4,.mov'

const onFilePick = (e) => {
    const picked = Array.from(e.target.files ?? [])
    handleFiles(picked)
    e.target.value = ''
}

const onDrop = (e) => {
    isDragging.value = false
    handleFiles(Array.from(e.dataTransfer.files))
}

const handleFiles = (newFiles) => {
    uploadError.value = ''
    const oversized = newFiles.filter(f => f.size > 20 * 1024 * 1024)
    if (oversized.length) {
        uploadError.value = `${oversized.length} archivo(s) superan los 20 MB máximos.`
        return
    }
    if (newFiles.length > 10) {
        uploadError.value = 'Máximo 10 archivos por vez.'
        return
    }
    form.files = newFiles
    submitUpload()
}

const submitUpload = () => {
    if (!form.files.length) return
    uploading.value = true
    uploadError.value = ''

    form.post('/files', {
        forceFormData: true,
        onSuccess: () => {
            uploadSuccess.value = '✓ Archivos subidos correctamente'
            setTimeout(() => uploadSuccess.value = '', 3000)
        },
        onError: (errors) => {
            uploadError.value = Object.values(errors).flat().join(' ')
        },
        onFinish: () => { uploading.value = false },
    })
}

// ── Galería / Lightbox ───────────────────────────────────────────
const lightbox = ref(null)

const mimeGroup = (mime) => {
    if (mime?.startsWith('image/')) return 'image'
    if (mime === 'application/pdf') return 'pdf'
    if (mime?.includes('spreadsheet') || mime?.includes('excel') || mime === 'text/csv') return 'spreadsheet'
    if (mime?.includes('word') || mime?.includes('document')) return 'word'
    if (mime?.startsWith('video/')) return 'video'
    return 'file'
}

const groupIcon = (mime) => ({
    image:       null,   // se muestra thumbnail
    pdf:         '📄',
    spreadsheet: '📊',
    word:        '📝',
    video:       '🎬',
    file:        '📎',
})[mimeGroup(mime)] ?? '📎'

const deleteFile = (id) => {
    if (!confirm('¿Eliminar este archivo? No se puede deshacer.')) return
    router.delete(`/files/${id}`, { preserveScroll: true })
}

const TABS = [
    { key: '',        label: 'Todos',      count: props.counts.all },
    { key: 'images',  label: 'Imágenes',   count: props.counts.images },
    { key: 'pdfs',    label: 'PDFs',       count: props.counts.pdfs },
    { key: 'documents', label: 'Documentos', count: props.counts.documents },
]

const formatBytes = (b) => {
    if (b < 1024)        return b + ' B'
    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB'
    return (b / 1024 / 1024).toFixed(1) + ' MB'
}
</script>

<template>
    <Head title="Archivos — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-6xl mx-auto space-y-5">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">Archivos</h1>
                    <p v-if="context" class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5">
                        Filtrando por
                        <Link
                            :href="`/${context.type === 'entry' ? 'entries' : 'tasks'}/${context.id}`"
                            class="text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline">
                            {{ context.title }}
                        </Link>
                        <button @click="router.get('/files')" class="ml-2 text-gray-400 hover:text-red-500 text-xs">✕ Quitar filtro</button>
                    </p>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-0.5">
                        {{ counts.all }} archivo{{ counts.all !== 1 ? 's' : '' }}
                    </p>
                </div>
            </div>

            <!-- Zona de upload (drag & drop) -->
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
                :class="[
                    'relative rounded-2xl border-2 border-dashed transition-all',
                    isDragging
                        ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 scale-[1.01]'
                        : uploading
                        ? 'border-blue-300 bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30/50'
                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-blue-300 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900'
                ]"
            >
                <label class="flex flex-col items-center gap-3 py-8 cursor-pointer">
                    <input type="file" multiple :accept="ACCEPTED" class="hidden" @change="onFilePick" />

                    <div :class="['rounded-full p-4 transition-colors', isDragging ? 'bg-blue-100' : 'bg-gray-100']">
                        <svg :class="['h-8 w-8 transition-colors', isDragging ? 'text-blue-500' : 'text-gray-400']"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>

                    <div class="text-center">
                        <p v-if="uploading" class="text-sm font-medium text-blue-600 dark:text-blue-400 dark:text-blue-400">
                            Subiendo archivos...
                        </p>
                        <p v-else-if="isDragging" class="text-sm font-medium text-blue-600 dark:text-blue-400 dark:text-blue-400">
                            Soltá los archivos aquí
                        </p>
                        <p v-else class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Arrastrá archivos acá o
                            <span class="text-blue-600 dark:text-blue-400 dark:text-blue-400">hacé clic para seleccionar</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Imágenes, PDFs, documentos, videos · Máx 20 MB · Hasta 10 archivos por vez
                        </p>
                    </div>
                </label>

                <!-- Barra de progreso -->
                <div v-if="uploading" class="absolute bottom-0 left-0 right-0 h-1 rounded-b-2xl bg-gray-100 overflow-hidden">
                    <div class="h-full bg-blue-500 animate-pulse w-3/4" />
                </div>
            </div>

            <!-- Mensajes de feedback -->
            <Transition name="fade">
                <div v-if="uploadError"
                    class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/30 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-400 dark:text-red-400">
                    <span>⚠</span> {{ uploadError }}
                </div>
            </Transition>
            <Transition name="fade">
                <div v-if="uploadSuccess"
                    class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 dark:bg-green-900/30 dark:bg-green-900/30 px-4 py-3 text-sm text-green-700 dark:text-green-400 dark:text-green-400">
                    <span>✓</span> {{ uploadSuccess }}
                </div>
            </Transition>

            <!-- Buscador + tabs -->
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <!-- Búsqueda -->
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input v-model="search" placeholder="Buscar por nombre o texto OCR..."
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 pl-9 pr-4 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100" />
                </div>

                <!-- Tabs tipo -->
                <div class="flex gap-1 rounded-xl p-1 bg-gray-100 shrink-0">
                    <button v-for="tab in TABS" :key="tab.key"
                        @click="typeTab = tab.key"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all whitespace-nowrap',
                            typeTab === tab.key ? 'bg-white dark:bg-gray-800 shadow text-gray-900 dark:text-white dark:text-white' : 'text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300'
                        ]">
                        {{ tab.label }}
                        <span class="ml-1 text-xs text-gray-400">({{ tab.count }})</span>
                    </button>
                </div>
            </div>

            <!-- Galería -->
            <div v-if="files.data.length === 0"
                class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-20 text-center">
                <p class="text-5xl mb-3">📭</p>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium">Sin archivos</p>
                <p class="text-gray-400 text-sm mt-1">
                    {{ search ? 'Sin resultados para "' + search + '"' : 'Arrastrá o hacé clic arriba para subir' }}
                </p>
            </div>

            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <div
                    v-for="file in files.data" :key="file.id"
                    class="group relative rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm dark:shadow-md hover:shadow-md hover:border-gray-300 dark:border-gray-600 dark:border-gray-600 transition-all overflow-hidden"
                >
                    <!-- Thumbnail o ícono -->
                    <div class="aspect-square relative overflow-hidden bg-gray-50 dark:bg-gray-900">
                        <!-- Imagen → clickeable al lightbox -->
                        <button v-if="file.is_image"
                            @click="lightbox = file"
                            class="w-full h-full focus:outline-none">
                            <img :src="file.url" :alt="file.original_name"
                                class="w-full h-full object-cover transition-transform group-hover:scale-105" />
                        </button>
                        <!-- PDF → ícono con link externo -->
                        <a v-else :href="file.url" target="_blank" rel="noopener"
                            class="flex flex-col items-center justify-center w-full h-full gap-2 hover:bg-gray-100 transition-colors">
                            <span class="text-4xl">{{ groupIcon(file.mime_type) }}</span>
                        </a>

                        <!-- Acciones (hover overlay) -->
                        <div class="absolute inset-x-0 bottom-0 flex items-center justify-between px-2 py-1.5
                                    bg-gradient-to-t from-black/60 to-transparent
                                    opacity-0 group-hover:opacity-100 transition-opacity">
                            <a :href="file.url" target="_blank" download
                                class="p-1 text-white hover:text-blue-300 transition-colors" title="Descargar">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </a>
                            <button @click="deleteFile(file.id)"
                                class="p-1 text-white hover:text-red-400 transition-colors" title="Eliminar">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Info del archivo -->
                    <div class="px-2 py-2">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" :title="file.original_name">
                            {{ file.original_name }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-0.5 flex items-center justify-between">
                            <span>{{ file.size_humans }}</span>
                            <span v-if="file.attachable_type" class="text-blue-400">
                                {{ file.attachable_type === 'Entry' ? '📝' : '✅' }}
                            </span>
                        </p>
                        <!-- OCR snippet -->
                        <p v-if="file.ocr_text" class="text-[10px] text-gray-400 mt-0.5 truncate italic">
                            "{{ file.ocr_text }}"
                        </p>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="files.last_page > 1" class="flex items-center justify-between pt-2">
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    Mostrando {{ files.from }}–{{ files.to }} de {{ files.total }} archivos
                </p>
                <div class="flex gap-1">
                    <Link v-if="files.prev_page_url" :href="files.prev_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        ← Anterior
                    </Link>
                    <Link v-if="files.next_page_url" :href="files.next_page_url"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                        Siguiente →
                    </Link>
                </div>
            </div>

        </div>

        <!-- ── Lightbox ── -->
        <Transition name="fade">
            <div v-if="lightbox" @click.self="lightbox = null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4">
                <!-- Botón cerrar -->
                <button @click="lightbox = null"
                    class="absolute top-4 right-4 p-2 text-white/80 hover:text-white rounded-full hover:bg-white dark:bg-gray-800/10 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Imagen -->
                <img :src="lightbox.url" :alt="lightbox.original_name"
                    class="max-h-[85vh] max-w-[90vw] rounded-lg shadow-2xl object-contain" />

                <!-- Info -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-4
                            bg-black/70 backdrop-blur-sm rounded-full px-5 py-2.5 text-white text-sm">
                    <span class="font-medium truncate max-w-xs">{{ lightbox.original_name }}</span>
                    <span class="text-white/50">·</span>
                    <span class="text-white/70">{{ lightbox.size_humans }}</span>
                    <a :href="lightbox.url" download
                        class="flex items-center gap-1.5 text-blue-300 hover:text-blue-200 transition-colors font-medium ml-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Descargar
                    </a>
                </div>

                <!-- OCR text si existe -->
                <div v-if="lightbox.ocr_text" class="absolute top-4 left-4 max-w-xs max-h-96 overflow-y-auto
                            bg-black/70 backdrop-blur-sm rounded-lg px-4 py-3 text-white text-xs font-mono">
                    <p class="text-white/50 mb-2 font-sans text-[11px] uppercase tracking-wide">📄 OCR Detectado</p>
                    <p class="text-white/80 leading-relaxed whitespace-pre-wrap break-words">{{ lightbox.ocr_text }}</p>
                </div>
            </div>
        </Transition>

    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
