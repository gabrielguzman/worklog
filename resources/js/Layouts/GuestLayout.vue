<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark } = useDarkMode();

const features = [
    { icon: '⚡', title: 'Rápido', desc: 'Interface ligera y responsiva' },
    { icon: '🎯', title: 'Productivo', desc: 'Organiza tareas eficientemente' },
    { icon: '📊', title: 'Analítica', desc: 'Visualiza tu progreso' },
];
</script>

<template>
    <div class="min-h-screen flex bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-950 dark:via-gray-900 dark:to-purple-950">

        <!-- Left Side - Features & Branding (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12 relative overflow-hidden">
            <!-- Animated background shapes -->
            <div class="absolute -top-20 -right-20 w-96 h-96 bg-blue-200 dark:bg-blue-900 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-purple-200 dark:bg-purple-900 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>

            <!-- Logo & Header -->
            <div class="relative z-10">
                <Link href="/">
                    <div class="flex items-center gap-3 mb-2">
                        <ApplicationLogo class="h-12 w-12 fill-current text-blue-600 dark:text-blue-400 dark:text-blue-400" />
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">
                            WorkLog
                        </h1>
                    </div>
                </Link>
                <p class="text-gray-600 dark:text-gray-400 font-medium">Tu asistente de productividad</p>
            </div>

            <!-- Features -->
            <div class="relative z-10 space-y-6">
                <div v-for="(feature, idx) in features" :key="idx" class="group">
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-white dark:bg-gray-800 bg-opacity-50 dark:bg-opacity-30 backdrop-blur-sm border border-white dark:border-gray-700 border-opacity-50 group-hover:bg-opacity-100 dark:group-hover:bg-opacity-50 transition-all duration-300 hover:shadow-lg">
                        <span class="text-4xl">{{ feature.icon }}</span>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white dark:text-white mb-1">{{ feature.title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ feature.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-10 text-sm text-gray-600 dark:text-gray-400">
                <p>© 2026 WorkLog. Todos los derechos reservados.</p>
            </div>
        </div>

        <!-- Right Side - Auth Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-8">
            <div class="w-full max-w-md space-y-8">

                <!-- Mobile Logo -->
                <div class="lg:hidden flex flex-col items-center gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <ApplicationLogo class="h-10 w-10 fill-current text-blue-600 dark:text-blue-400 dark:text-blue-400" />
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">
                            WorkLog
                        </h1>
                    </div>
                </div>

                <!-- Form Container -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl dark:shadow-2xl border border-gray-200 dark:border-gray-700 p-8 space-y-8 backdrop-blur-xl">
                    <slot />
                </div>

                <!-- Footer text -->
                <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <p>Secure • Privacy-First • Open Source</p>
                </div>
            </div>
        </div>
    </div>
</template>
