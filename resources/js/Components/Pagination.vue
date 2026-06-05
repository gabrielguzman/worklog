<script setup>
defineProps({
    currentPage: Number,
    totalPages: Number,
    hasNextPage: Boolean,
    hasPrevPage: Boolean,
})

defineEmits(['prev', 'next', 'goto'])
</script>

<template>
    <div class="flex items-center justify-between gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <!-- Info -->
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Página <span class="font-semibold">{{ currentPage }}</span> de <span class="font-semibold">{{ totalPages }}</span>
        </p>

        <!-- Buttons -->
        <div class="flex gap-2">
            <button @click="$emit('prev')" :disabled="!hasPrevPage"
                aria-label="Página anterior"
                :class="[
                    'px-3 py-1 text-sm font-medium rounded-lg border transition-colors',
                    hasPrevPage ? 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100' : 'border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed'
                ]">
                ← Anterior
            </button>

            <div class="flex gap-1">
                <button v-for="page in Math.min(totalPages, 5)" :key="page"
                    @click="$emit('goto', page)"
                    :class="[
                        'w-8 h-8 rounded-lg text-sm font-medium transition-colors',
                        currentPage === page
                            ? 'bg-blue-600 text-white'
                            : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100'
                    ]">
                    {{ page }}
                </button>
                <span v-if="totalPages > 5" class="flex items-center text-gray-500 dark:text-gray-400">...</span>
            </div>

            <button @click="$emit('next')" :disabled="!hasNextPage"
                aria-label="Página siguiente"
                :class="[
                    'px-3 py-1 text-sm font-medium rounded-lg border transition-colors',
                    hasNextPage ? 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100' : 'border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed'
                ]">
                Siguiente →
            </button>
        </div>
    </div>
</template>
