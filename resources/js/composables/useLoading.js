import { ref } from 'vue'

/**
 * Composable para manejar estados de carga
 * Proporciona loading state durante operaciones async
 */
export function useLoading() {
    const isLoading = ref(false)
    const error = ref(null)

    /**
     * Ejecutar función async con loading state automático
     */
    const withLoading = async (fn) => {
        isLoading.value = true
        error.value = null
        try {
            return await fn()
        } catch (e) {
            error.value = e.message || 'Error desconocido'
            throw e
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Limpiar error manual
     */
    const clearError = () => {
        error.value = null
    }

    return {
        isLoading,
        error,
        withLoading,
        clearError,
    }
}
