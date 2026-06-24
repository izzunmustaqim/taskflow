<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps<{
    categories: Array<{ id: number; name: string; color: string }>;
    statuses: Record<string, string>;
    priorities: Record<string, string>;
}>();

const form = useForm({
    title: '',
    description: '',
    status: 'pending',
    priority: 'medium',
    category_id: '' as number | string,
    due_at: '',
});

const submit = () => {
    form.post(route('tasks.store'));
};
</script>

<template>
    <Head title="Create Task" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('tasks.index')" class="p-2 -ml-2 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Create Task</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-3xl p-8 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <input type="text" id="title" v-model="form.title" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.title}" autofocus />
                            </div>
                            <p v-if="form.errors.title" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.title }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <div class="mt-1">
                                <textarea id="description" v-model="form.description" rows="4" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.description}"></textarea>
                            </div>
                            <p v-if="form.errors.description" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.description }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-100 dark:border-gray-700/50 pt-6">
                            <!-- Category -->
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                <div class="mt-1">
                                    <select id="category_id" v-model="form.category_id" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.category_id}">
                                        <option value="">No Category</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>
                                <p v-if="form.errors.category_id" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.category_id }}</p>
                            </div>

                            <!-- Due Date -->
                            <div>
                                <label for="due_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                                <div class="mt-1 relative">
                                    <input type="datetime-local" id="due_at" v-model="form.due_at" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.due_at}" />
                                </div>
                                <p v-if="form.errors.due_at" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.due_at }}</p>
                            </div>
                            
                            <!-- Priority -->
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priority</label>
                                <div class="mt-1">
                                    <select id="priority" v-model="form.priority" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.priority}">
                                        <option v-for="(label, val) in priorities" :key="val" :value="val">{{ label }}</option>
                                    </select>
                                </div>
                                <p v-if="form.errors.priority" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.priority }}</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <div class="mt-1">
                                    <select id="status" v-model="form.status" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.status}">
                                        <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
                                    </select>
                                </div>
                                <p v-if="form.errors.status" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.status }}</p>
                            </div>
                        </div>

                        <div class="pt-6 mt-6 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-end gap-3">
                            <Link :href="route('tasks.index')" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </Link>
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Create Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
