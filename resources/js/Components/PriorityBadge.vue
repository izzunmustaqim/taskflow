<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    priority: string;
}>();

const config = computed(() => {
    switch (props.priority) {
        case 'low':
            return {
                label: 'Low',
                classes: 'bg-slate-50 text-slate-700 ring-slate-600/20 dark:bg-slate-900/30 dark:text-slate-400 dark:ring-slate-400/20',
                icon: 'M12 19V5M5 12l7-7 7 7' // Arrow up, but we'll rotate it down in the template
            };
        case 'medium':
            return {
                label: 'Medium',
                classes: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-400/20',
                icon: 'M5 12h14' // Dash
            };
        case 'high':
            return {
                label: 'High',
                classes: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-400/20',
                icon: 'M12 5v14M5 12l7-7 7 7' // Arrow up
            };
        default:
            return {
                label: props.priority,
                classes: 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-900/30 dark:text-gray-400 dark:ring-gray-400/20',
                icon: 'M5 12h14'
            };
    }
});
</script>

<template>
    <span :class="[
        'inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset shadow-sm transition-colors',
        config.classes
    ]">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" 
             class="w-3.5 h-3.5" :class="{'rotate-180': priority === 'low'}">
            <path stroke-linecap="round" stroke-linejoin="round" :d="config.icon" />
        </svg>
        {{ config.label }}
    </span>
</template>
