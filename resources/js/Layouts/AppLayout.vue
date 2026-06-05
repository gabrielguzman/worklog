<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage, router, useForm } from '@inertiajs/vue3'
import NotificationCenter from '@/Components/NotificationCenter.vue'
import DarkModeToggle from '@/Components/DarkModeToggle.vue'
import PWAInstallButton from '@/Components/PWAInstallButton.vue'
import ProfileDropdown from '@/Components/ProfileDropdown.vue'
import KeyboardShortcutsHelp from '@/Components/KeyboardShortcutsHelp.vue'
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'
import { useDarkMode } from '@/composables/useDarkMode'

const page         = usePage()
const user         = computed(() => page.props.auth.user)
const shared       = computed(() => page.props.shared ?? {})
const projects     = computed(() => shared.value.projects ?? [])
const tags         = computed(() => shared.value.tags     ?? [])
const sidebarOpen  = ref(false)
const isMobile     = ref(false)

// Keyboard shortcuts
const { setup: setupShortcuts, cleanup: cleanupShortcuts } = useKeyboardShortcuts()
const { toggleDarkMode, initTheme } = useDarkMode()

// ── Navegación ────────────────────────────────────────────────────
const navItems = [
    { name: 'Dashboard',  href: '/dashboard',   icon: 'grid'    },
    { name: 'Registro',   href: '/entries',     icon: 'book'    },
    { name: 'Tareas',     href: '/tasks',       icon: 'check'   },
    { name: 'Calendario', href: '/calendar',    icon: 'calendar'},
    { name: 'Focus',      href: '/focus',       icon: 'timer'   },
    { name: 'Archivos',   href: '/files',       icon: 'folder'  },
    { name: 'Proyectos',  href: '/projects',    icon: 'layers'  },
    { name: 'Tags',       href: '/tags',        icon: 'tag'     },
    { name: 'Plantillas', href: '/templates',   icon: 'template'},
    { name: 'Búsqueda',   href: '/search',      icon: 'search'  },
]

const isActive = (item) => {
    if (item.href === '/dashboard') return page.url === '/dashboard'
    return page.url.startsWith(item.href)
}

const logout = () => router.post('/logout')

// ── Captura rápida ────────────────────────────────────────────────
const quickOpen   = ref(false)
const quickTab    = ref('entry')  // 'entry' | 'task'
const quickTitle  = ref(null)     // ref al input de título

const ENTRY_TYPES = [
    { value: 'general',       label: 'General',    emoji: '📝' },
    { value: 'reunion',       label: 'Reunión',    emoji: '🤝' },
    { value: 'deploy',        label: 'Deploy',     emoji: '🚀' },
    { value: 'code_review',   label: 'Code Review',emoji: '🔍' },
    { value: 'investigacion', label: 'Investigación',emoji:'🔬'},
]

const PRIORITIES = [
    { value: 'urgent', label: 'Urgente', color: 'text-red-600'    },
    { value: 'high',   label: 'Alta',    color: 'text-orange-500' },
    { value: 'medium', label: 'Media',   color: 'text-yellow-500' },
    { value: 'low',    label: 'Baja',    color: 'text-gray-400'   },
]

const entryForm = useForm({
    title:      '',
    type:       'general',
    project_id: '',
    content:    '',
    entry_date: new Date().toISOString().split('T')[0],
    entry_time: new Date().toTimeString().slice(0, 5),
    is_pinned:  false,
    tags:       [],
})

const taskForm = useForm({
    title:      '',
    priority:   'medium',
    project_id: '',
    due_date:   '',
    description:'',
    status:     'pending',
    tags:       [],
})

const openQuick = (tab = 'entry') => {
    quickTab.value = tab
    entryForm.reset()
    taskForm.reset()
    entryForm.type = 'general'
    entryForm.entry_date = new Date().toISOString().split('T')[0]
    entryForm.entry_time = new Date().toTimeString().slice(0, 5)
    taskForm.priority = 'medium'
    taskForm.status   = 'pending'
    quickOpen.value = true
    setTimeout(() => quickTitle.value?.focus(), 100)
}

const closeQuick = () => { quickOpen.value = false }

const submitEntry = () => {
    entryForm.post('/entries', {
        onSuccess: () => {
            closeQuick()
        },
    })
}

const submitTask = () => {
    taskForm.post('/tasks', {
        onSuccess: () => {
            closeQuick()
        },
    })
}

// Keyboard shortcut: Ctrl+Shift+N
const onKeydown = (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === 'N') {
        e.preventDefault()
        quickOpen.value ? closeQuick() : openQuick('entry')
    }
    if (e.key === 'Escape' && quickOpen.value) closeQuick()
}

onMounted(() => {
    // Initialize dark mode theme
    initTheme()

    // Mobile detection
    const checkMobile = () => {
        isMobile.value = window.innerWidth < 768
        if (isMobile.value) sidebarOpen.value = false
    }
    checkMobile()
    window.addEventListener('resize', checkMobile)

    // Keyboard events
    window.addEventListener('keydown', onKeydown)

    // Setup keyboard shortcuts
    setupShortcuts({
        'ctrl,/': () => router.visit('/search'),
        'ctrl,alt,n': () => openQuick('task'),
        'ctrl,alt,e': () => quickOpen.value ? closeQuick() : openQuick('entry'),
        'ctrl,alt,d': () => toggleDarkMode(),
    })

    onUnmounted(() => {
        window.removeEventListener('resize', checkMobile)
        window.removeEventListener('keydown', onKeydown)
        cleanupShortcuts()
    })
})

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown)
    cleanupShortcuts()
})
</script>

<template>
    <!-- Skip to main content link (for keyboard users) -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-4 focus:left-4 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-lg focus:outline-none">
        Saltar al contenido principal
    </a>

    <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">

        <!-- Overlay móvil -->
        <Transition name="fade">
            <div v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-black/40 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 flex w-60 flex-col bg-gray-900 transition-transform duration-300 lg:relative lg:translate-x-0',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]">
            <!-- Logo + atajo -->
            <div class="flex h-16 items-center justify-between px-5 border-b border-gray-700/50">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white tracking-tight">WorkLog</span>
                </div>
                <!-- Botón captura rápida -->
                <button @click="openQuick()"
                    aria-label="Captura rápida (nueva entrada o tarea)"
                    title="Captura rápida (Ctrl+Shift+N)"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>

            <!-- Nav items -->
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                <Link v-for="item in navItems" :key="item.name" :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                        isActive(item) ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                    ]"
                    @click="sidebarOpen = false">
                    <!-- grid -->
                    <svg v-if="item.icon==='grid'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    <!-- book -->
                    <svg v-if="item.icon==='book'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    <!-- check -->
                    <svg v-if="item.icon==='check'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <!-- timer -->
                    <svg v-if="item.icon==='timer'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <!-- folder -->
                    <svg v-if="item.icon==='folder'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" /></svg>
                    <!-- layers -->
                    <svg v-if="item.icon==='layers'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" /></svg>
                    <!-- tag -->
                    <svg v-if="item.icon==='tag'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" /></svg>
                    <!-- template -->
                    <svg v-if="item.icon==='template'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>
                    <!-- search -->
                    <svg v-if="item.icon==='search'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <!-- calendar -->
                    <svg v-if="item.icon==='calendar'" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>

                    {{ item.name }}
                </Link>
            </nav>

            <!-- Atajo visible + user footer -->
            <div class="border-t border-gray-700/50 px-3 py-2">
                <button @click="openQuick()" class="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-gray-500 dark:text-gray-400 hover:text-white hover:bg-gray-800 transition-colors mb-1">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Captura rápida
                    <span class="ml-auto font-mono text-[10px] bg-gray-800 px-1.5 py-0.5 rounded">Ctrl+⇧+N</span>
                </button>
            </div>
            <div class="border-t border-gray-700/50 px-3 py-3">
                <ProfileDropdown :user="user" />

                <div class="flex items-center gap-2 px-2 py-2">
                    <PWAInstallButton />
                    <DarkModeToggle />
                </div>
            </div>
        </aside>

        <!-- Contenido principal -->
        <div class="flex flex-1 flex-col min-w-0 overflow-hidden">
            <!-- Header móvil -->
            <header class="flex h-14 items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 md:hidden">
                <button @click="sidebarOpen = true" aria-label="Abrir menú" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <span class="font-semibold text-gray-900 dark:text-white dark:text-white">WorkLog</span>
                <div class="flex items-center gap-1 ml-auto">
                    <button @click="openQuick()" aria-label="Captura rápida" class="p-2 rounded-lg text-gray-500 dark:text-gray-400 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </button>
                    <DarkModeToggle />
                </div>
            </header>

            <!-- Backdrop para sidebar en móvil -->
            <div v-if="sidebarOpen && isMobile" @click="sidebarOpen = false"
                class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"></div>

            <!-- Main content -->
            <main id="main-content" class="flex-1 overflow-y-auto"><slot /></main>
        </div>
    </div>

    <!-- ── Modal captura rápida ── -->
    <Transition name="slide-down">
        <div v-if="quickOpen" @click.self="closeQuick"
            class="fixed inset-0 z-50 flex items-start justify-center pt-16 bg-black/40 px-4">
            <div role="dialog" aria-modal="true" aria-labelledby="quick-modal-title" class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-800 shadow-2xl overflow-hidden" @click.stop>

                <!-- Header con tabs -->
                <h2 id="quick-modal-title" class="sr-only">Captura rápida de entrada o tarea</h2>
                <div class="flex items-center justify-between px-5 pt-4 pb-0">
                    <div class="flex gap-1 rounded-xl bg-gray-100 dark:bg-gray-700 p-1">
                        <button @click="quickTab = 'entry'"
                            :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-all', quickTab === 'entry' ? 'bg-white shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']">
                            📝 Entrada
                        </button>
                        <button @click="quickTab = 'task'"
                            :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-all', quickTab === 'task' ? 'bg-white shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400']">
                            ✅ Tarea
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 font-mono hidden sm:block">Esc para cerrar</span>
                        <button @click="closeQuick" aria-label="Cerrar captura rápida" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- ── Formulario Entrada ── -->
                <form v-if="quickTab === 'entry'" @submit.prevent="submitEntry" class="px-5 py-4 space-y-4">
                    <input ref="quickTitle" v-model="entryForm.title" type="text"
                        placeholder="¿Qué hiciste? Título de la entrada..."
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-gray-900 dark:text-white text-base focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :class="{ 'border-red-400': entryForm.errors.title }" />

                    <!-- Tipo rápido -->
                    <div class="flex gap-1.5 flex-wrap">
                        <button v-for="t in ENTRY_TYPES" :key="t.value" type="button"
                            @click="entryForm.type = t.value"
                            :class="['flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all',
                                entryForm.type === t.value ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-700' : 'border-gray-200 text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:border-gray-600']">
                            {{ t.emoji }} {{ t.label }}
                        </button>
                    </div>

                    <!-- Notas rápidas -->
                    <textarea v-model="entryForm.content" rows="3"
                        placeholder="Notas, detalles, decisiones... (opcional)"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 dark:text-white resize-none focus:border-blue-400 focus:outline-none" />

                    <div class="flex gap-3">
                        <select v-model="entryForm.project_id" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                            <option value="">Sin proyecto</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <button type="submit" :disabled="entryForm.processing || !entryForm.title"
                            class="flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-sm dark:shadow-md">
                            <svg v-if="entryForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Guardar entrada
                        </button>
                    </div>
                </form>

                <!-- ── Formulario Tarea ── -->
                <form v-else @submit.prevent="submitTask" class="px-5 py-4 space-y-4">
                    <input ref="quickTitle" v-model="taskForm.title" type="text"
                        placeholder="¿Qué hay que hacer? Título de la tarea..."
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-gray-900 dark:text-white text-base focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :class="{ 'border-red-400': taskForm.errors.title }" />

                    <!-- Prioridad rápida -->
                    <div class="flex gap-2">
                        <button v-for="p in PRIORITIES" :key="p.value" type="button"
                            @click="taskForm.priority = p.value"
                            :class="['flex-1 py-2 rounded-lg border text-xs font-semibold transition-all',
                                taskForm.priority === p.value ? `border-current ${p.color} bg-gray-50` : 'border-gray-200 text-gray-400 hover:border-gray-300 dark:border-gray-600']">
                            {{ p.label }}
                        </button>
                    </div>

                    <textarea v-model="taskForm.description" rows="2"
                        placeholder="Descripción, contexto... (opcional)"
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-900 dark:text-white resize-none focus:border-blue-400 focus:outline-none" />

                    <div class="flex gap-3">
                        <select v-model="taskForm.project_id" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                            <option value="">Sin proyecto</option>
                            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <input v-model="taskForm.due_date" type="date" class="rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" />
                        <button type="submit" :disabled="taskForm.processing || !taskForm.title"
                            class="flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-sm dark:shadow-md">
                            <svg v-if="taskForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            Crear tarea
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </Transition>

    <!-- Notificaciones -->
    <NotificationCenter />

    <!-- Ayuda de Keyboard Shortcuts -->
    <KeyboardShortcutsHelp />
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-down-enter-active { transition: all 0.2s ease-out; }
.slide-down-leave-active { transition: all 0.15s ease-in; }
.slide-down-enter-from { opacity: 0; transform: translateY(-16px); }
.slide-down-leave-to   { opacity: 0; transform: translateY(-8px); }
</style>
