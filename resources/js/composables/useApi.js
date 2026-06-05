import { ref } from 'vue'
import axios from 'axios'
import { useNotification } from './useNotification'

/**
 * Composable para llamadas API con error handling
 */
export function useApi() {
    const { error: showError } = useNotification()
    const isLoading = ref(false)
    const error = ref(null)

    const handleError = (e) => {
        console.error('API Error:', e)

        let message = 'Error desconocido'

        if (e.response?.status === 403) {
            message = 'No tienes permiso para hacer esto'
        } else if (e.response?.status === 404) {
            message = 'Recurso no encontrado'
        } else if (e.response?.status === 422) {
            // Validation errors
            const errors = e.response?.data?.errors
            if (errors) {
                message = Object.values(errors).flat().join(', ')
            } else {
                message = 'Datos inválidos'
            }
        } else if (e.response?.status === 429) {
            message = 'Demasiadas solicitudes. Intenta más tarde'
        } else if (e.response?.status >= 500) {
            message = 'Error del servidor. Intenta más tarde'
        } else if (e.message === 'Network Error') {
            message = 'Error de conexión. Verifica tu internet'
        } else if (e.response?.data?.message) {
            message = e.response.data.message
        }

        error.value = message
        showError(message)

        return message
    }

    /**
     * GET request con error handling
     */
    const get = async (url, config = {}) => {
        isLoading.value = true
        error.value = null

        try {
            const response = await axios.get(url, config)
            return response.data
        } catch (e) {
            handleError(e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    /**
     * POST request con error handling
     */
    const post = async (url, data = {}, config = {}) => {
        isLoading.value = true
        error.value = null

        try {
            const response = await axios.post(url, data, config)
            return response.data
        } catch (e) {
            handleError(e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    /**
     * PATCH request con error handling
     */
    const patch = async (url, data = {}, config = {}) => {
        isLoading.value = true
        error.value = null

        try {
            const response = await axios.patch(url, data, config)
            return response.data
        } catch (e) {
            handleError(e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    /**
     * DELETE request con error handling
     */
    const del = async (url, config = {}) => {
        isLoading.value = true
        error.value = null

        try {
            const response = await axios.delete(url, config)
            return response.data
        } catch (e) {
            handleError(e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    const clearError = () => {
        error.value = null
    }

    return {
        isLoading,
        error,
        get,
        post,
        patch,
        del,
        handleError,
        clearError,
    }
}
