<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirm = ref(false);

// Password strength indicator
const passwordStrength = computed(() => {
    const pwd = form.password;
    if (!pwd) return { level: 0, label: '', color: '' };

    let strength = 0;
    if (pwd.length >= 8) strength++;
    if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength++;
    if (/[0-9]/.test(pwd)) strength++;
    if (/[^a-zA-Z0-9]/.test(pwd)) strength++;

    const levels = [
        { level: 0, label: '', color: '' },
        { level: 1, label: 'Débil', color: 'bg-red-500' },
        { level: 2, label: 'Media', color: 'bg-yellow-500' },
        { level: 3, label: 'Fuerte', color: 'bg-blue-500' },
        { level: 4, label: 'Muy fuerte', color: 'bg-green-500' },
    ];

    return levels[strength];
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta — WorkLog" />

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white mb-2">
                🚀 ¡Bienvenido a WorkLog!
            </h2>
            <p class="text-gray-600 dark:text-gray-400">Crea tu cuenta para empezar a ser productivo</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Name Field -->
            <div class="space-y-2">
                <InputLabel for="name" value="Nombre completo" />
                <TextInput
                    id="name"
                    type="text"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white dark:text-white placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 dark:placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    v-model="form.name"
                    placeholder="Juan Pérez"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" />
            </div>

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
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div class="space-y-2">
                <InputLabel for="password" value="Contraseña" />
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

                <!-- Password Strength Indicator -->
                <div v-if="form.password" class="space-y-2">
                    <div class="flex gap-1">
                        <div v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full bg-gray-200 dark:bg-gray-600" :class="i <= passwordStrength.level ? passwordStrength.color : ''"></div>
                    </div>
                    <p class="text-xs font-medium" :class="passwordStrength.color === 'bg-red-500' ? 'text-red-600 dark:text-red-400' : passwordStrength.color === 'bg-yellow-500' ? 'text-yellow-600 dark:text-yellow-400' : passwordStrength.color === 'bg-blue-500' ? 'text-blue-600 dark:text-blue-400 dark:text-blue-400' : 'text-green-600 dark:text-green-400'">
                        {{ passwordStrength.label }}
                    </p>
                </div>
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

            <!-- Terms Checkbox -->
            <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
                <input type="checkbox" id="terms" required class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-400" />
                <label for="terms" class="text-sm text-gray-700 dark:text-gray-300">
                    Acepto los
                    <Link href="#" class="text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline">términos de servicio</Link>
                    y la
                    <Link href="#" class="text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:underline">política de privacidad</Link>
                </label>
            </div>

            <!-- Submit Button -->
            <button
                :disabled="form.processing"
                class="w-full py-3 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl"
            >
                <span v-if="!form.processing">🎉 Crear cuenta</span>
                <span v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creando cuenta...
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

            <!-- Login Link -->
            <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                ¿Ya tienes cuenta?
                <Link
                    :href="route('login')"
                    class="font-semibold text-blue-600 dark:text-blue-400 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                >
                    Inicia sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
