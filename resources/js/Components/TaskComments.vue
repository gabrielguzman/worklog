<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    taskId: Number,
    user: Object,
})

const comments = ref([])
const newComment = ref('')
const loading = ref(false)
const error = ref(null)
const editingId = ref(null)
const editingContent = ref('')

const hasComments = computed(() => comments.value.length > 0)
const canAddComment = computed(() => newComment.value.trim().length > 0)

onMounted(() => {
    fetchComments()
})

const fetchComments = async () => {
    try {
        const res = await axios.get(`/api/tasks/${props.taskId}/comments`)
        comments.value = res.data
        error.value = null
    } catch (e) {
        error.value = 'Error al cargar comentarios'
        console.error(e)
    }
}

const submitComment = async () => {
    if (!canAddComment.value) return

    loading.value = true
    try {
        const res = await axios.post(`/api/tasks/${props.taskId}/comments`, {
            content: newComment.value,
        })
        comments.value.unshift(res.data)
        newComment.value = ''
        error.value = null
    } catch (e) {
        error.value = 'Error al agregar comentario'
        console.error(e)
    } finally {
        loading.value = false
    }
}

const startEdit = (comment) => {
    editingId.value = comment.id
    editingContent.value = comment.content
}

const cancelEdit = () => {
    editingId.value = null
    editingContent.value = ''
}

const saveEdit = async (comment) => {
    if (!editingContent.value.trim()) return

    try {
        await axios.patch(`/api/comments/${comment.id}`, {
            content: editingContent.value,
        })
        comment.content = editingContent.value
        editingId.value = null
        error.value = null
    } catch (e) {
        error.value = 'Error al actualizar comentario'
        console.error(e)
    }
}

const deleteComment = async (commentId) => {
    if (!confirm('¿Eliminar este comentario?')) return

    try {
        await axios.delete(`/api/comments/${commentId}`)
        comments.value = comments.value.filter(c => c.id !== commentId)
        error.value = null
    } catch (e) {
        error.value = 'Error al eliminar comentario'
        console.error(e)
    }
}
</script>

<template>
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            💬 Comentarios {{ hasComments ? `(${comments.length})` : '' }}
        </h3>

        <!-- Error -->
        <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">
            {{ error }}
        </div>

        <!-- Agregar comentario -->
        <div class="mb-6 flex gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div class="flex-1">
                <textarea
                    v-model="newComment"
                    placeholder="Escribe un comentario..."
                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 resize-none"
                    rows="3"
                />
                <div class="mt-2 flex gap-2 justify-end">
                    <button
                        @click="newComment = ''"
                        type="button"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="submitComment"
                        :disabled="!canAddComment || loading"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white transition-colors"
                    >
                        {{ loading ? 'Guardando...' : 'Comentar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Lista de comentarios -->
        <div v-if="hasComments" class="space-y-4">
            <div v-for="comment in comments" :key="comment.id" class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center text-white text-xs font-bold shrink-0 flex-grow-0">
                    {{ comment.user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline gap-2">
                        <p class="font-medium text-gray-900 dark:text-white text-sm">
                            {{ comment.user.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ comment.created_at }}
                        </p>
                    </div>

                    <!-- Modo visualización -->
                    <div v-if="editingId !== comment.id" class="mt-1">
                        <p class="text-gray-700 dark:text-gray-300 text-sm break-words whitespace-pre-wrap">
                            {{ comment.content }}
                        </p>

                        <!-- Acciones -->
                        <div v-if="comment.is_owner" class="mt-2 flex gap-2">
                            <button
                                @click="startEdit(comment)"
                                class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                Editar
                            </button>
                            <button
                                @click="deleteComment(comment.id)"
                                class="text-xs text-red-600 dark:text-red-400 hover:underline"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <!-- Modo edición -->
                    <div v-else class="mt-2">
                        <textarea
                            v-model="editingContent"
                            class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 text-sm focus:border-blue-400 dark:focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900 resize-none"
                            rows="3"
                        />
                        <div class="mt-2 flex gap-2 justify-end">
                            <button
                                @click="cancelEdit"
                                type="button"
                                class="px-2 py-1 text-xs font-medium rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="saveEdit(comment)"
                                class="px-2 py-1 text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sin comentarios -->
        <div v-else class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                No hay comentarios aún. ¡Sé el primero en comentar!
            </p>
        </div>
    </div>
</template>
