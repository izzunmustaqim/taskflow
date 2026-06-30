<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const visible = ref(false);
const progress = ref(0);

let interval: ReturnType<typeof setInterval> | null = null;

const start = () => {
    visible.value = true;
    progress.value = 0;

    interval = setInterval(() => {
        if (progress.value < 90) {
            progress.value += Math.random() * 15;
        }
    }, 200);
};

const finish = () => {
    if (interval) clearInterval(interval);
    progress.value = 100;
    setTimeout(() => {
        visible.value = false;
        progress.value = 0;
    }, 300);
};

const fail = () => {
    if (interval) clearInterval(interval);
    progress.value = 100;
    setTimeout(() => {
        visible.value = false;
        progress.value = 0;
    }, 300);
};

// Listen to Inertia events via the window
onMounted(() => {
    window.addEventListener('inertia:start', start as EventListener);
    window.addEventListener('inertia:finish', finish as EventListener);
    window.addEventListener('inertia:cancel', finish as EventListener);
    window.addEventListener('inertia:error', fail as EventListener);
});

onUnmounted(() => {
    window.removeEventListener('inertia:start', start as EventListener);
    window.removeEventListener('inertia:finish', finish as EventListener);
    window.removeEventListener('inertia:cancel', finish as EventListener);
    window.removeEventListener('inertia:error', fail as EventListener);
    if (interval) clearInterval(interval);
});
</script>

<template>
    <div class="fixed top-0 left-0 right-0 z-[9999] h-1 pointer-events-none">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="visible" class="h-full bg-indigo-600 shadow-[0_0_10px_rgba(99,102,241,0.5)]" :style="{ width: `${Math.min(progress, 100)}%`, transition: 'width 0.3s ease-out' }"></div>
        </Transition>
    </div>
</template>