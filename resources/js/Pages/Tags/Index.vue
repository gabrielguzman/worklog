<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ tags: Array })

const PRESET_COLORS = [
    '#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6',
    '#EC4899','#14B8A6','#F97316','#06B6D4','#84CC16',
    '#6B7280','#1D4ED8','#065F46','#92400E','#7C3AED',
]

// ── Modal ────────────────────────────────────────────────────────
const modalOpen  = ref(false)
const editTarget = ref(null)

const form = useForm({ name: '', color: '#3B82F6' })

const openCreate = () => {
    editTarget.value = null
    form.reset()
    form.color = '#3B82F6'
    modalOpen.value = true
}

const openEdit = (tag) => {
    editTarget.value = tag
    form.name  = tag.name
    form.color = tag.color
    modalOpen.value = true
}

const closeModal = () => { modalOpen.value = false; editTarget.value = null }

const submit = () => {
    if (editTarget.value) {
        form.put(`/tags/${editTarget.value.id}`, { onSuccess: closeModal })
    } else {
        form.post('/tags', { onSuccess: closeModal })
    }
}

const deleteTag = (tag) => {
    const total = tag.entries_count + tag.tasks_count
    const msg = total > 0
        ? `"${tag.name}" está usado en ${tag.entries_count} entradas y ${tag.tasks_count} tareas. Se eliminará el tag (no el contenido). ¿Continuar?`
        : `¿Eliminar el tag "${tag.name}"?`
    if (confirm(msg)) {
        router.delete(`/tags/${tag.id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Tags — WorkLog" />

    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">Tags</h1>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm mt-0.5">
                        {{ tags.length }} tag{{ tags.length !== 1 ? 's' : '' }}
                    </p>
                </div>
                <button @click="openCreate"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 shadow-sm dark:shadow-md transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo tag
                </button>
            </div>

            <!-- Grid de tags -->
            <div v-if="tags.length === 0"
                class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-20 text-center">
                <p class="text-5xl mb-3">🏷️</p>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium">Sin tags todavía</p>
                <button @click="openCreate" class="mt-4 text-sm text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline font-medium">
                    + Crear primer tag
                </button>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div v-for="tag in tags" :key="tag.id"
                    class="group flex items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm dark:shadow-md hover:shadow-md hover:border-gray-300 dark:border-gray-600 dark:border-gray-600 transition-all">

                    <!-- Color pill -->
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0"
                        :style="{ backgroundColor: tag.color + '20' }">
                        <span class="text-lg font-bold" :style="{ color: tag.color }">#</span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-white dark:text-white truncate">{{ tag.name }}</p>
                        <div class="flex items-center gap-3 mt-0.5">
                            <Link :href="`/entries?tag=${tag.name}`"
                                class="text-xs text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors">
                                📝 {{ tag.entries_count }}
                            </Link>
                            <Link :href="`/tasks?tag=${tag.name}`"
                                class="text-xs text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 transition-colors">
                                ✅ {{ tag.tasks_count }}
                            </Link>
                        </div>
                    </div>

                    <!-- Badge de color + acciones -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="h-4 w-4 rounded-full border border-gray-200 dark:border-gray-700"
                            :style="{ backgroundColor: tag.color }" />

                        <div class="flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEdit(tag)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="deleteTag(tag)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:bg-red-900/30 dark:bg-red-900/30 transition-colors">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cloud visual de tags -->
            <div v-if="tags.length > 0" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm dark:shadow-md">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide mb-4">Nube de tags</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="tag in [...tags].sort((a,b) => (b.entries_count + b.tasks_count) - (a.entries_count + a.tasks_count))"
                        :key="tag.id"
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium text-white cursor-default select-none transition-transform hover:scale-105"
                        :style="{
                            backgroundColor: tag.color,
                            fontSize: `${Math.max(11, Math.min(16, 11 + (tag.entries_count + tag.tasks_count)))}px`
                        }">
                        # {{ tag.name }}
                        <span class="opacity-70 text-xs">({{ tag.entries_count + tag.tasks_count }})</span>
                    </span>
                </div>
            </div>

        </div>

        <!-- ── Modal crear / editar ── -->
        <Transition name="fade">
            <div v-if="modalOpen" @click.self="closeModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-800 shadow-2xl" @click.stop>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white dark:text-white">
                            {{ editTarget ? 'Editar tag' : 'Nuevo tag' }}
                        </h2>
                        <button @click="closeModal"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="px-6 py-5 space-y-5">

                        <!-- Preview -->
                        <div class="flex items-center justify-center py-2">
                            <span class="rounded-full px-4 py-2 text-sm font-bold text-white"
                                :style="{ backgroundColor: form.color }">
                                # {{ form.name || 'nombre-tag' }}
                            </span>
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.name" type="text" autofocus
                                placeholder="Ej: backend, bug, urgent..."
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                :class="{ 'border-red-400': form.errors.name }" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="c in PRESET_COLORS" :key="c"
                                    type="button"
                                    @click="form.color = c"
                                    class="h-7 w-7 rounded-full border-2 transition-all hover:scale-110"
                                    :class="form.color === c ? 'border-gray-900 scale-110' : 'border-transparent'"
                                    :style="{ backgroundColor: c }" />
                                <label class="relative h-7 w-7 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 dark:border-gray-600 flex items-center justify-center cursor-pointer hover:border-gray-400 overflow-hidden">
                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <input type="color" v-model="form.color" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" />
                                </label>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                                {{ editTarget ? 'Guardar' : 'Crear tag' }}
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
