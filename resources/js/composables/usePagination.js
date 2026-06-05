import { ref, computed } from 'vue'

/**
 * Composable para manejar paginación
 */
export function usePagination(items = [], perPage = 50) {
    const currentPage = ref(1)
    const itemsPerPage = ref(perPage)

    const totalPages = computed(() => {
        if (!items || items.length === 0) return 1
        return Math.ceil(items.length / itemsPerPage.value)
    })

    const paginatedItems = computed(() => {
        if (!items || items.length === 0) return []
        const start = (currentPage.value - 1) * itemsPerPage.value
        const end = start + itemsPerPage.value
        return items.slice(start, end)
    })

    const hasNextPage = computed(() => currentPage.value < totalPages.value)
    const hasPrevPage = computed(() => currentPage.value > 1)

    const nextPage = () => {
        if (hasNextPage.value) {
            currentPage.value++
        }
    }

    const prevPage = () => {
        if (hasPrevPage.value) {
            currentPage.value--
        }
    }

    const goToPage = (page) => {
        const pageNum = Math.max(1, Math.min(page, totalPages.value))
        currentPage.value = pageNum
    }

    const resetPage = () => {
        currentPage.value = 1
    }

    return {
        currentPage,
        itemsPerPage,
        totalPages,
        paginatedItems,
        hasNextPage,
        hasPrevPage,
        nextPage,
        prevPage,
        goToPage,
        resetPage,
    }
}
