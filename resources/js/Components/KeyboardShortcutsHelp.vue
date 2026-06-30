<script setup lang="ts">
import { useKeyboardShortcuts } from '@/Composables/useKeyboardShortcuts';

const { shortcuts, showHelp } = useKeyboardShortcuts();

const formatKey = (shortcut: { key: string; ctrl?: boolean; shift?: boolean; alt?: boolean }) => {
    const keys: string[] = [];
    if (shortcut.ctrl) keys.push('Ctrl');
    if (shortcut.alt) keys.push('Alt');
    if (shortcut.shift) keys.push('Shift');
    keys.push(shortcut.key.toUpperCase());
    return keys;
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showHelp" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="shortcuts-title">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showHelp = false"></div>

                <!-- Modal -->
                <Transition
                    enter-active-class="ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showHelp" class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 id="shortcuts-title" class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                                <button @click="showHelp = false" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Shortcuts List -->
                        <div class="px-6 py-4 max-h-96 overflow-y-auto">
                            <div class="space-y-3">
                                <div v-for="shortcut in shortcuts" :key="`${shortcut.key}-${shortcut.ctrl}-${shortcut.shift}-${shortcut.alt}`" class="flex items-center justify-between py-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ shortcut.description }}</span>
                                    <div class="flex items-center gap-1">
                                        <kbd 
                                            v-for="(key, index) in formatKey(shortcut)" 
                                            :key="index"
                                            class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded"
                                        >
                                            {{ key }}
                                        </kbd>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="shortcuts.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No keyboard shortcuts available on this page.
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                Press <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-200 dark:bg-gray-600 rounded">?</kbd> anytime to toggle this help
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
