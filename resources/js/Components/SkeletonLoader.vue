<script setup>
defineProps({
    count: {
        type: Number,
        default: 3,
    },
    type: {
        type: String,
        default: 'card', // 'card', 'text', 'list', 'grid'
        validator: (value) => ['card', 'text', 'list', 'grid'].includes(value),
    },
})
</script>

<template>
    <!-- Card skeleton -->
    <div v-if="type === 'card'" class="space-y-4">
        <div v-for="i in count" :key="i"
            class="rounded-xl border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-5 animate-pulse">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-lg bg-gray-200 dark:bg-gray-600 dark:bg-gray-700"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-1/2"></div>
                </div>
            </div>
            <!-- Content -->
            <div class="space-y-3">
                <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-full"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-5/6"></div>
            </div>
        </div>
    </div>

    <!-- Text skeleton -->
    <div v-else-if="type === 'text'" class="space-y-3">
        <div v-for="i in count" :key="i"
            class="space-y-2 animate-pulse">
            <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-full"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-5/6"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-4/6"></div>
        </div>
    </div>

    <!-- List skeleton -->
    <div v-else-if="type === 'list'" class="space-y-2">
        <div v-for="i in count" :key="i"
            class="flex items-center gap-3 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse">
            <div class="h-10 w-10 rounded bg-gray-200 dark:bg-gray-600 dark:bg-gray-700"></div>
            <div class="flex-1 space-y-2">
                <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-1/3"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-1/4"></div>
            </div>
        </div>
    </div>

    <!-- Grid skeleton -->
    <div v-else-if="type === 'grid'"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in count" :key="i"
            class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 animate-pulse">
            <div class="aspect-video bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded mb-3"></div>
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-600 dark:bg-gray-700 rounded w-1/2"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes skeleton-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
