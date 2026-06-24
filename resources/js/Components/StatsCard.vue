<script setup lang="ts">
defineProps<{
    title: string;
    value: number | string;
    icon: string;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
    color: 'indigo' | 'emerald' | 'amber' | 'rose' | 'slate';
}>();

const colorStyles = {
    indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
    emerald: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
    slate: 'bg-slate-50 text-slate-600 dark:bg-slate-900/30 dark:text-slate-400',
};
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ title }}</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ value }}</p>
                    <span v-if="trend && trendValue" class="text-sm font-medium" :class="{
                        'text-emerald-600 dark:text-emerald-400': trend === 'up',
                        'text-rose-600 dark:text-rose-400': trend === 'down',
                        'text-gray-500 dark:text-gray-400': trend === 'neutral'
                    }">
                        <span v-if="trend === 'up'">↑</span>
                        <span v-if="trend === 'down'">↓</span>
                        {{ trendValue }}
                    </span>
                </div>
            </div>
            <div class="p-3 rounded-xl" :class="colorStyles[color]">
                <span class="text-2xl" v-html="icon"></span>
            </div>
        </div>
        
        <!-- Decorative blob -->
        <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full opacity-10 blur-2xl" :class="colorStyles[color].split(' ')[1]"></div>
    </div>
</template>
