<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps<{
    label: {
        id: number;
        name: string;
        color: string;
    };
}>();

const form = useForm({
    name: props.label.name,
    color: props.label.color,
});

const predefinedColors = [
    '#6366f1', '#8b5cf6', '#ec4899', '#ef4444', '#f97316',
    '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6',
];

const submit = () => {
    form.put(route('labels.update', props.label.id));
};
</script>

<template>
    <Head title="Edit Label" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Edit Label</h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-2xl p-8 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Label Name</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="block w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                placeholder="e.g., Bug, Feature, Urgent"
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                            <div class="flex flex-wrap gap-3 mb-3">
                                <button
                                    v-for="color in predefinedColors"
                                    :key="color"
                                    type="button"
                                    @click="form.color = color"
                                    class="w-8 h-8 rounded-full border-2 transition-all duration-200 hover:scale-110"
                                    :class="form.color === color ? 'border-gray-900 dark:border-white ring-2 ring-offset-2 ring-gray-300 dark:ring-gray-600 scale-110' : 'border-transparent'"
                                    :style="{ backgroundColor: color }"
                                />
                            </div>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model="form.color"
                                    type="color"
                                    class="w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer"
                                />
                                <input
                                    v-model="form.color"
                                    type="text"
                                    class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-mono transition-colors"
                                    placeholder="#6366f1"
                                />
                            </div>
                            <p v-if="form.errors.color" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.color }}</p>
                        </div>

                        <!-- Preview -->
                        <div class="flex items-center gap-2 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: form.color }"></span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ form.name || 'Label Preview' }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700/50">
                            <a :href="route('labels.index')" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </a>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 text-white hover:bg-indigo-500 font-semibold rounded-xl transition-all shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Updating...' : 'Update Label' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
