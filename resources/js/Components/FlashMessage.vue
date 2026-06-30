<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const { toasts, success, error, warning, info, dismiss } = useToast();

const flash = computed(() => page.props.flash as Record<string, string>);

const getDuration = (type: string) => {
    switch (type) {
        case 'error': return 6000;
        case 'warning': return 5000;
        case 'info': return 4000;
        case 'success': return 3500;
        default: return 4000;
    }
};

watch(flash, (newFlash) => {
    if (newFlash?.success) {
        success(newFlash.success);
    } else if (newFlash?.error) {
        error(newFlash.error);
    } else if (newFlash?.warning) {
        warning(newFlash.warning);
    } else if (newFlash?.info) {
        info(newFlash.info);
    }
}, { deep: true });
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col-reverse gap-3 pointer-events-none max-h-[calc(100vh-2rem)] overflow-hidden">
            <TransitionGroup
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-x-full opacity-0 scale-95"
                enter-to-class="translate-x-0 opacity-100 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-x-0 opacity-100 scale-100"
                leave-to-class="translate-x-full opacity-0 scale-95"
                move-class="transition-transform duration-300"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto w-full sm:min-w-[360px] sm:max-w-md overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10 border border-gray-100 dark:border-gray-700 backdrop-blur-xl bg-opacity-95 dark:bg-opacity-95"
                >
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-0.5">
                                <div
                                    class="w-8 h-8 rounded-xl flex items-center justify-center"
                                    :class="{
                                        'bg-emerald-100 dark:bg-emerald-900/50': toast.type === 'success',
                                        'bg-red-100 dark:bg-red-900/50': toast.type === 'error',
                                        'bg-amber-100 dark:bg-amber-900/50': toast.type === 'warning',
                                        'bg-blue-100 dark:bg-blue-900/50': toast.type === 'info',
                                    }"
                                >
                                    <!-- Success Icon -->
                                    <svg v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <!-- Error Icon -->
                                    <svg v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                    <!-- Warning Icon -->
                                    <svg v-else-if="toast.type === 'warning'" class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <!-- Info Icon -->
                                    <svg v-else class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Message -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white leading-tight">{{ toast.message }}</p>
                            </div>

                            <!-- Close Button -->
                            <button
                                type="button"
                                @click="dismiss(toast.id)"
                                class="flex-shrink-0 p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="h-1 w-full bg-gray-100 dark:bg-gray-700">
                        <div
                            class="h-full rounded-full animate-progress"
                            :class="{
                                'bg-emerald-500': toast.type === 'success',
                                'bg-red-500': toast.type === 'error',
                                'bg-amber-500': toast.type === 'warning',
                                'bg-blue-500': toast.type === 'info',
                            }"
                            :style="{ animationDuration: `${getDuration(toast.type)}ms` }"
                        ></div>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes progress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

.animate-progress {
    animation: progress linear forwards;
}
</style>
