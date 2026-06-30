<script setup lang="ts">
import { onMounted } from 'vue';
import { useDarkMode } from '@/Composables/useDarkMode';

const { isDark, initialize, toggle } = useDarkMode();

onMounted(() => {
    initialize();
});
</script>

<template>
    <button
        @click="toggle"
        class="relative inline-flex h-9 w-14 items-center rounded-full transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        :class="isDark ? 'bg-indigo-600' : 'bg-gray-200'"
        :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        aria-label="Toggle dark mode"
    >
        <!-- Track -->
        <span class="sr-only">Toggle dark mode</span>
        
        <!-- Thumb with icon -->
        <span
            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white shadow-lg transform transition-all duration-300"
            :class="isDark ? 'translate-x-7' : 'translate-x-1'"
        >
            <!-- Sun icon -->
            <svg
                v-if="!isDark"
                class="h-4 w-4 text-amber-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>
            <!-- Moon icon -->
            <svg
                v-else
                class="h-4 w-4 text-indigo-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
        </span>
    </button>
</template>
