<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    show: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    danger?: boolean;
}>();

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="emit('cancel')"></div>

        <!-- Modal panel -->
        <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all sm:my-8 sm:w-full border border-gray-100 dark:border-gray-700">
            <div class="flex items-start gap-4">
                <div class="shrink-0 rounded-full p-2.5 flex items-center justify-center" :class="danger ? 'bg-red-100 dark:bg-red-900/30' : 'bg-indigo-100 dark:bg-indigo-900/30'">
                    <svg v-if="danger" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg v-else class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </div>
                
                <div class="pt-0.5 w-full">
                    <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">{{ title }}</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ message }}</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="inline-flex w-full justify-center rounded-xl bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto transition-colors" @click="emit('cancel')">
                    {{ cancelText || 'Cancel' }}
                </button>
                <button type="button" class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-sm sm:w-auto transition-colors" :class="danger ? 'bg-red-600 hover:bg-red-500' : 'bg-indigo-600 hover:bg-indigo-500'" @click="emit('confirm')">
                    {{ confirmText || 'Confirm' }}
                </button>
            </div>
        </div>
    </div>
</template>
