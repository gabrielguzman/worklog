<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer contraseña — WorkLog" />

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mb-2">
                🔑 Restablecer contraseña
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Ingresa tu nueva contraseña para recuperar acceso a tu cuenta.
            </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email Field (Read-only) -->
            <div class="space-y-2">
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-white dark:text-white placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all disabled:opacity-75"
                    v-model="form.email"
                    disabled
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <InputLabel for="password" value="Nueva contraseña" />
                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:text-white placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        v-model="form.password"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                        aria-label="Mostrar/ocultar contraseña"
                    >
                        <span v-if="showPassword">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Confirm Password Field -->
            <div class="space-y-2">
                <InputLabel for="password_confirmation" value="Confirmar contraseña" />
                <div class="relative">
                    <TextInput
                        id="password_confirmation"
                        :type="showPasswordConfirm ? 'text' : 'password'"
                        class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:text-white placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        v-model="form.password_confirmation"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirm = !showPasswordConfirm"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors"
                        aria-label="Mostrar/ocultar contraseña"
                    >
                        <span v-if="showPasswordConfirm">👁️</span>
                        <span v-else>👁️‍🗨️</span>
                    </button>
                </div>
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit Button -->
            <button
                :disabled="form.processing"
                class="w-full py-3 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
            >
                <span v-if="!form.processing">🔐 Restablecer contraseña</span>
                <span v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Restableciendo...
                </span>
            </button>

            <!-- Divider -->
            <div class="relative py-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300 dark:border-gray-600 dark:border-gray-600"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400">o</span>
                </div>
            </div>

            <!-- Back to Login -->
            <Link
                :href="route('login')"
                class="block w-full text-center py-2.5 px-4 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors"
            >
                🔙 Volver al inicio de sesión
            </Link>
        </form>
    </GuestLayout>
</template>
