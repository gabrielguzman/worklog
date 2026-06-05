<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificar correo — WorkLog" />

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mb-2">
                📧 Verifica tu correo
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                ¡Bienvenido a WorkLog! Necesitamos verificar tu correo electrónico para continuar.
            </p>
        </div>

        <!-- Success Message -->
        <div v-if="verificationLinkSent" class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                ✅ Se envió un nuevo enlace de verificación a tu correo electrónico.
            </p>
        </div>

        <!-- Instructions -->
        <div class="mb-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-900 dark:text-blue-200">
                Hemos enviado un enlace de verificación a tu correo. Haz click en el enlace para confirmar tu cuenta.
                <br><br>
                <strong>¿No recibiste el email?</strong> Podemos enviar otro.
            </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
            >
                <span v-if="!form.processing">📨 Enviar enlace de verificación</span>
                <span v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enviando...
                </span>
            </button>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full py-2.5 px-4 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors"
            >
                🚪 Cerrar sesión
            </Link>
        </form>
    </GuestLayout>
</template>
