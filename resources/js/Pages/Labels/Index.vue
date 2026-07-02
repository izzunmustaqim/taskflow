<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ref } from 'vue';

defineProps<{
    labels: Array<{
        id: number;
        name: string;
        color: string;
        tasks_count?: number;
    }>;
}>();

const showDeleteModal = ref(false);
const labelToDelete = ref<number | null>(null);

const confirmDelete = (id: number) => {
    labelToDelete.value = id;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (labelToDelete.value) {
        router.delete(route('labels.destroy', labelToDelete.value), {
            onSuccess: () => {
                showDeleteModal.value = false;
                labelToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Labels" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Labels</h2>
                
                <Link :href="route('labels.create')" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-500 font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Label
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

                <EmptyState
                    v-if="labels.length === 0"
                    icon="categories"
                    title="No labels yet"
                    description="Create labels to tag and organize your tasks for better filtering."
                    action-text="Create Label"
                    :action-href="route('labels.create')"
                />

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="label in labels" :key="label.id" class="group relative overflow-hidden bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                        <!-- Decorative top accent -->
                        <div class="absolute top-0 left-0 right-0 h-1" :style="{ backgroundColor: label.color }"></div>
                        
                        <div class="flex items-center gap-3 mb-4 mt-2">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full shadow-sm" :style="{ backgroundColor: label.color }"></span>
                                <h3 class="font-bold text-xl text-gray-900 dark:text-white">{{ label.name }}</h3>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700/50 mt-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ label.tasks_count ?? 0 }} task{{ (label.tasks_count ?? 0) !== 1 ? 's' : '' }}
                            </span>
                            <div class="flex gap-2">
                                <Link :href="route('labels.edit', label.id)" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 px-2 py-1 rounded-md hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                                    Edit
                                </Link>
                                <button @click="confirmDelete(label.id)" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 px-2 py-1 rounded-md hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
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
            title="Delete Label"
            message="Are you sure you want to delete this label? It will be removed from all tasks, but the tasks themselves will not be deleted."
            confirm-text="Delete Label"
            danger
            @cancel="showDeleteModal = false; labelToDelete = null"
            @confirm="executeDelete"
        />
    </AuthenticatedLayout>
</template>
