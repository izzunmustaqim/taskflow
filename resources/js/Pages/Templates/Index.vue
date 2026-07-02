<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps<{
    templates: {
        data: Array<{
            id: number;
            name: string;
            title: string;
            description: string | null;
            priority: string;
            status: string;
            category?: { id: number; name: string; color: string } | null;
            created_at: string;
        }>;
        links: any[];
    };
}>();

const showDeleteModal = ref(false);
const templateToDelete = ref<number | null>(null);

const confirmDelete = (id: number) => {
    templateToDelete.value = id;
    showDeleteModal.value = true;
};

const deleteTemplate = () => {
    if (templateToDelete.value) {
        router.delete(route('templates.destroy', templateToDelete.value), {
            onSuccess: () => {
                showDeleteModal.value = false;
                templateToDelete.value = null;
            },
        });
    }
};

const useTemplate = (id: number) => {
    router.post(route('templates.use', id));
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        case 'medium': return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'low': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        default: return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
    }
};
</script>

<template>
    <Head title="Task Templates" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Task Templates</h2>
                <Link :href="route('templates.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-500 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Template
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Empty State -->
                <div v-if="templates.data.length === 0" class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-3xl p-12 shadow-sm text-center">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No templates yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Create templates to quickly generate recurring tasks.</p>
                    <Link :href="route('templates.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-500 transition-colors text-sm font-medium">
                        Create your first template
                    </Link>
                </div>

                <!-- Templates Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="template in templates.data" :key="template.id" class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ template.name }}</h3>
                            <span :class="[getPriorityColor(template.priority), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                {{ template.priority }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 truncate">{{ template.title }}</p>

                        <div v-if="template.category" class="mb-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: template.category.color }"></span>
                                {{ template.category.name }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="useTemplate(template.id)" class="flex-1 px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 transition-colors text-sm font-medium">
                                Use Template
                            </button>
                            <Link :href="route('templates.edit', template.id)" class="px-3 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </Link>
                            <button @click="confirmDelete(template.id)" class="px-3 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Template"
            message="Are you sure you want to delete this template?"
            confirm-text="Delete"
            danger
            @cancel="showDeleteModal = false"
            @confirm="deleteTemplate"
        />
    </AuthenticatedLayout>
</template>
