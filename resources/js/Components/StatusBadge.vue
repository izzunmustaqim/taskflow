<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    status: string;
    animate?: boolean;
}>();

const isAnimating = ref(false);
const previousStatus = ref(props.status);

const config = computed(() => {
    switch (props.status) {
        case 'pending':
            return {
                label: 'Pending',
                classes: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-400/20',
                dot: 'bg-yellow-500'
            };
        case 'in_progress':
            return {
                label: 'In Progress',
                classes: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/20',
                dot: 'bg-blue-500'
            };
        case 'completed':
            return {
                label: 'Completed',
                classes: 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/20',
                dot: 'bg-green-500'
            };
        default:
            return {
                label: props.status,
                classes: 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-400/20',
                dot: 'bg-gray-500'
            };
    }
});

// Animate when status changes
watch(() => props.status, (newStatus, oldStatus) => {
    if (newStatus !== oldStatus && props.animate !== false) {
        isAnimating.value = true;
        setTimeout(() => {
            isAnimating.value = false;
        }, 600);
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset shadow-sm transition-all duration-300',
            config.classes,
            { 'animate-pulse-status': isAnimating }
        ]"
    >
        <span
            class="w-1.5 h-1.5 rounded-full transition-colors duration-300"
            :class="[config.dot, { 'animate-dot-pulse': isAnimating }]"
        ></span>
        {{ config.label }}
    </span>
</template>

<style scoped>
@keyframes pulse-status {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0);
    }
}

@keyframes dot-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.5);
    }
}

.animate-pulse-status {
    animation: pulse-status 0.6s ease-in-out;
}

.animate-dot-pulse {
    animation: dot-pulse 0.6s ease-in-out;
}
</style>
