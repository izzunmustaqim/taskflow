<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    category: {
        id: number;
        name: string;
        color: string;
    };
}>();

const form = useForm({
    name: props.category.name,
    color: props.category.color,
});

const submit = () => {
    form.put(route('categories.update', props.category.id));
};

const suggestedColors = [
    '#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', 
    '#3b82f6', '#6366f1', '#a855f7', '#ec4899', '#64748b'
];
</script>

<template>
    <Head :title="`Edit: ${category.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('categories.index')" class="p-2 -ml-2 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight truncate">Edit Category</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-3xl p-8 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Preview -->
                        <div class="mb-8 flex justify-center">
                            <div class="w-full max-w-xs group relative overflow-hidden bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-2xl p-6 shadow-sm">
                                <div class="absolute top-0 left-0 right-0 h-1 transition-colors duration-300" :style="{ backgroundColor: form.color }"></div>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl shadow-inner flex items-center justify-center border border-white/20 transition-colors duration-300" :style="{ backgroundColor: form.color }">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-white mix-blend-overlay">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" />
                                        </svg>
                                    </div>
                                    <h3 class="font-bold text-xl text-gray-900 dark:text-white truncate">
                                        {{ form.name || 'Category Name' }}
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                            <div class="mt-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" />
                                    </svg>
                                </div>
                                <input type="text" id="name" v-model="form.name" class="block w-full pl-10 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.name}" />
                            </div>
                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <!-- Color picker -->
                        <div>
                            <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color <span class="text-red-500">*</span></label>
                            
                            <!-- Suggested Colors -->
                            <div class="mt-3 flex flex-wrap gap-3">
                                <button v-for="c in suggestedColors" :key="c" type="button" @click="form.color = c" class="w-8 h-8 rounded-full shadow-sm ring-2 ring-offset-2 dark:ring-offset-gray-800 transition-all hover:scale-110" :class="form.color === c ? 'ring-gray-400 dark:ring-gray-500 scale-110' : 'ring-transparent'" :style="{ backgroundColor: c }">
                                    <span class="sr-only">Select color {{ c }}</span>
                                    <svg v-if="form.color === c" class="w-5 h-5 mx-auto text-white drop-shadow-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                            
                            <div class="mt-4 flex items-center gap-3">
                                <div class="relative rounded-xl overflow-hidden shadow-sm border border-gray-300 dark:border-gray-600 w-12 h-10 shrink-0">
                                    <input type="color" id="color" v-model="form.color" class="absolute -top-2 -left-2 w-16 h-16 cursor-pointer border-0 p-0" />
                                </div>
                                <input type="text" v-model="form.color" class="block w-full max-w-[120px] rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors font-mono uppercase" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.color}" />
                            </div>
                            <p v-if="form.errors.color" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.color }}</p>
                        </div>

                        <div class="pt-6 mt-6 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-end gap-3">
                            <Link :href="route('categories.index')" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </Link>
                            <button type="submit" :disabled="form.processing || !form.isDirty" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
