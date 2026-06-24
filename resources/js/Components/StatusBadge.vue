<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
}>();

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
</script>

<template>
    <span :class="[
        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset shadow-sm transition-colors',
        config.classes
    ]">
        <span class="w-1.5 h-1.5 rounded-full" :class="config.dot"></span>
        {{ config.label }}
    </span>
</template>
