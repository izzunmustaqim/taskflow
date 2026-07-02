<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    template: {
        id: number;
        name: string;
        title: string;
        description: string | null;
        priority: string;
        category_id: number | null;
    };
    categories: Array<{ id: number; name: string; color: string }>;
    priorities: Record<string, string>;
}>();

const form = useForm({
    name: props.template.name,
    title: props.template.title,
    description: props.template.description || '',
    priority: props.template.priority,
    category_id: props.template.category_id || '',
});

const submit = () => {
    form.put(route('templates.update', props.template.id));
};
</script>

<template>
    <Head :title="`Edit: ${template.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('templates.index')" class="p-2 -ml-2 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Edit Template</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-3xl p-8 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Template Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" v-model="form.name" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.name}" />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <!-- Task Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Task Title <span class="text-red-500">*</span></label>
                            <input type="text" id="title" v-model="form.title" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.title}" />
                            <p v-if="form.errors.title" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.title }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Priority -->
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                                <select id="priority" v-model="form.priority" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors">
                                    <option v-for="(label, val) in priorities" :key="val" :value="val">{{ label }}</option>
                                </select>
                            </div>

                            <!-- Category -->
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                <select id="category_id" v-model="form.category_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors">
                                    <option value="">No Category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-end gap-3">
                            <Link :href="route('templates.index')" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </Link>
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
