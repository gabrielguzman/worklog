<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    duration: {
        type: Number,
        default: 1500,
    },
})

const emit = defineEmits(['complete'])

const isVisible = ref(false)

onMounted(() => {
    if (props.show) {
        isVisible.value = true
        setTimeout(() => {
            isVisible.value = false
            emit('complete')
        }, props.duration)
    }
})

const handleAnimationEnd = () => {
    isVisible.value = false
    emit('complete')
}
</script>

<template>
    <Teleport to="body">
        <transition name="success-pop">
            <div v-if="isVisible" @animationend="handleAnimationEnd"
                class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-50 pointer-events-none">

                <!-- Círculo de éxito animado -->
                <div class="relative w-32 h-32">
                    <!-- Fondo -->
                    <div class="absolute inset-0 rounded-full bg-green-500 animate-success-background"></div>

                    <!-- Checkmark -->
                    <svg class="absolute inset-0 w-32 h-32 text-white animate-success-scale"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>

                    <!-- Partículas (opcional) -->
                    <div v-for="i in 6" :key="i"
                        class="absolute w-2 h-2 bg-green-400 rounded-full"
                        :style="{ '--i': i, animation: `success-particle 0.6s ease-out ${i * 0.05}s forwards` }"
                    ></div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
@keyframes success-pop {
    0% {
        transform: translate(-50%, -50%) scale(0.3);
        opacity: 0;
    }
    70% {
        transform: translate(-50%, -50%) scale(1.15);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

@keyframes success-background {
    0% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    }
    70% {
        box-shadow: 0 0 0 60px rgba(34, 197, 94, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
    }
}

@keyframes success-scale {
    0% {
        transform: scale(0);
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
    }
    50% {
        stroke-dasharray: 100;
        stroke-dashoffset: 0;
    }
    100% {
        transform: scale(1);
        stroke-dasharray: 0;
        stroke-dashoffset: 0;
    }
}

@keyframes success-particle {
    0% {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(
            calc(cos(var(--i) * 60deg) * 80px),
            calc(sin(var(--i) * 60deg) * 80px)
        ) scale(0);
    }
}

.animate-success-background {
    animation: success-background 0.8s ease-out forwards;
}

.animate-success-scale {
    animation: success-scale 0.6s ease-in-out forwards;
}

.success-pop-enter-active,
.success-pop-leave-active {
    transition: opacity 0.3s ease;
}

.success-pop-enter-from,
.success-pop-leave-to {
    opacity: 0;
}
</style>
