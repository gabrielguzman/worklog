<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión — WorkLog" />

        <!-- Status Message -->
        <div v-if="status" class="mb-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ status }}</p>
        </div>

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mb-2">
                👋 Bienvenido de vuelta
            </h2>
            <p class="text-gray-600 dark:text-gray-400">Inicia sesión en tu cuenta para continuar</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email Field -->
            <div class="space-y-2">
                <InputLabel for="email" value="Correo electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:text-white placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    v-model="form.email"
                    placeholder="tu@email.com"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:text-white placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    v-model="form.password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <label class="flex items-center cursor-pointer group">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:text-white dark:group-hover:text-gray-300 transition-colors">
                        Recuérdame
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <button
                :disabled="form.processing"
                class="w-full py-3 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
            >
                <span v-if="!form.processing">🔓 Iniciar sesión</span>
                <span v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Iniciando...
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

            <!-- Links -->
            <div class="space-y-3">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="block w-full text-center py-2.5 px-4 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-colors"
                >
                    🔐 ¿Olvidaste tu contraseña?
                </Link>

                <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                    ¿No tienes cuenta?
                    <Link
                        :href="route('register')"
                        class="font-semibold text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                    >
                        Regístrate aquí
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
