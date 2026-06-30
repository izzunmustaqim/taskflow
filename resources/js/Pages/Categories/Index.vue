<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ref } from 'vue';

defineProps<{
    categories: Array<{
        id: number;
        name: string;
        color: string;
        tasks_count?: number;
    }>;
}>();

const showDeleteModal = ref(false);
const categoryToDelete = ref<number | null>(null);

const confirmDelete = (id: number) => {
    categoryToDelete.value = id;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (categoryToDelete.value) {
        router.delete(route('categories.destroy', categoryToDelete.value), {
            onSuccess: () => {
                showDeleteModal.value = false;
                categoryToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Categories" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Categories</h2>
                
                <Link :href="route('categories.create')" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-500 font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Category
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

                <EmptyState
                    v-if="categories.length === 0"
                    icon="categories"
                    title="No categories yet"
                    description="Create categories to organize your tasks and keep them tidy."
                    action-text="Create Category"
                    :action-href="route('categories.create')"
                />

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="category in categories" :key="category.id" class="group relative overflow-hidden bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                        <!-- Decorative top accent -->
                        <div class="absolute top-0 left-0 right-0 h-1" :style="{ backgroundColor: category.color }"></div>
                        
                        <div class="flex items-center gap-3 mb-4 mt-2">
                            <div class="w-10 h-10 rounded-xl shadow-inner flex items-center justify-center border border-white/20" :style="{ backgroundColor: category.color }">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-white mix-blend-overlay">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-xl text-gray-900 dark:text-white">{{ category.name }}</h3>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700/50 mt-4">
                            <div class="flex gap-2">
                                <Link :href="route('categories.edit', category.id)" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 px-2 py-1 rounded-md hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                                    Edit
                                </Link>
                                <button @click="confirmDelete(category.id)" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 px-2 py-1 rounded-md hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <ConfirmModal 
            :show="showDeleteModal"
            title="Delete Category"
            message="Are you sure you want to delete this category? Any tasks associated with it will have their category removed, but the tasks themselves will not be deleted."
            confirm-text="Delete Category"
            danger
            @cancel="showDeleteModal = false; categoryToDelete = null"
            @confirm="executeDelete"
        />
    </AuthenticatedLayout>
</template>
