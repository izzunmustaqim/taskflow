<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1 mt-6">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 rounded-lg cursor-not-allowed" v-html="link.label" />
            <Link v-else class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors" 
                  :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'" 
                  :href="link.url" v-html="link.label" />
        </template>
    </div>
</template>
