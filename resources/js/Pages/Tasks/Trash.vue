<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskCard from '@/Components/TaskCard.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ref, computed } from 'vue';

const props = defineProps<{
    tasks: {
        data: Array<any>;
        links: any[];
    };
}>();

// Single task actions
const showForceDeleteModal = ref(false);
const taskToForceDelete = ref<number | null>(null);
const restoringId = ref<number | null>(null);

// Bulk selection state
const selectedIds = ref<Set<number>>(new Set());
const showBulkRestoreModal = ref(false);
const showBulkForceDeleteModal = ref(false);
const isProcessing = ref(false);

const hasSelection = computed(() => selectedIds.value.size > 0);
const selectedCount = computed(() => selectedIds.value.size);

const allSelected = computed(() => {
    if (props.tasks.data.length === 0) return false;
    return props.tasks.data.every(task => selectedIds.value.has(task.id));
});

const someSelected = computed(() => {
    return selectedIds.value.size > 0 && !allSelected.value;
});

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedIds.value.clear();
    } else {
        props.tasks.data.forEach(task => selectedIds.value.add(task.id));
    }
};

const toggleSelect = (id: number) => {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id);
    } else {
        selectedIds.value.add(id);
    }
};

const clearSelection = () => {
    selectedIds.value.clear();
};

// Single task actions
const confirmForceDelete = (id: number) => {
    taskToForceDelete.value = id;
    showForceDeleteModal.value = true;
};

const executeForceDelete = () => {
    if (taskToForceDelete.value) {
        router.delete(route('tasks.force-destroy', taskToForceDelete.value), {
            onStart: () => {
                showForceDeleteModal.value = true;
            },
            onFinish: () => {
                showForceDeleteModal.value = false;
                taskToForceDelete.value = null;
            },
        });
    }
};

const restoreTask = (id: number) => {
    restoringId.value = id;
    router.post(route('tasks.restore', id), {}, {
        onFinish: () => {
            restoringId.value = null;
        },
    });
};

// Bulk actions
const openBulkRestoreModal = () => {
    showBulkRestoreModal.value = true;
};

const executeBulkRestore = () => {
    if (selectedIds.value.size === 0) return;

    isProcessing.value = true;
    router.post(route('tasks.bulk-restore'), {
        ids: Array.from(selectedIds.value),
    }, {
        onFinish: () => {
            isProcessing.value = false;
            showBulkRestoreModal.value = false;
            clearSelection();
        },
    });
};

const openBulkForceDeleteModal = () => {
    showBulkForceDeleteModal.value = true;
};

const executeBulkForceDelete = () => {
    if (selectedIds.value.size === 0) return;

    isProcessing.value = true;
    router.post(route('tasks.bulk-force-delete'), {
        ids: Array.from(selectedIds.value),
    }, {
        onFinish: () => {
            isProcessing.value = false;
            showBulkForceDeleteModal.value = false;
            clearSelection();
        },
    });
};
</script>

<template>
    <Head title="Trash" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('tasks.index')" class="p-2 -ml-2 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Trash</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <EmptyState
                    v-if="tasks.data.length === 0"
                    icon="trash"
                    title="Trash is empty"
                    description="Deleted tasks will appear here. You can restore them or delete them permanently."
                    action-text="Back to Tasks"
                    :action-href="route('tasks.index')"
                />

                <div v-else class="space-y-6">
                    <!-- Bulk Action Toolbar -->
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="hasSelection" class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded-2xl p-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-800/50">
                                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ selectedCount }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                        task{{ selectedCount !== 1 ? 's' : '' }} selected
                                    </span>
                                    <button @click="clearSelection" class="text-xs font-medium text-emerald-500 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 underline transition-colors">
                                        Clear
                                    </button>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button @click="openBulkRestoreModal" :disabled="isProcessing" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-emerald-200 dark:border-emerald-700 rounded-xl text-sm font-medium text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                        </svg>
                                        Restore Selected
                                    </button>
                                    <button @click="openBulkForceDeleteModal" :disabled="isProcessing" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-700 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        Delete Forever
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Select All Bar -->
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer group">
                            <div
                                class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all duration-200"
                                :class="[
                                    allSelected
                                        ? 'bg-emerald-600 border-emerald-600'
                                        : someSelected
                                            ? 'bg-emerald-600/50 border-emerald-600'
                                            : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 group-hover:border-emerald-400 dark:group-hover:border-emerald-500'
                                ]"
                                @click="toggleSelectAll"
                            >
                                <svg v-if="allSelected" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <div v-else-if="someSelected" class="w-2 h-0.5 bg-white rounded-full"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                                {{ allSelected ? 'Deselect all' : 'Select all' }}
                            </span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <TaskCard
                            v-for="task in tasks.data"
                            :key="task.id"
                            :task="task"
                            isTrash
                            selectable
                            :selected="selectedIds.has(task.id)"
                            @toggle-select="toggleSelect"
                        >
                            <template #actions>
                                <div class="flex gap-2">
                                    <button @click.stop="restoreTask(task.id)" :disabled="restoringId === task.id" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:text-emerald-300 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 px-2.5 py-1.5 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <LoadingSpinner v-if="restoringId === task.id" size="sm" color="currentColor" />
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                        </svg>
                                        Restore
                                    </button>
                                    <button @click.stop="confirmForceDelete(task.id)" class="inline-flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:hover:text-red-300 dark:bg-red-900/30 dark:hover:bg-red-900/50 px-2.5 py-1.5 rounded-md transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        Delete Forever
                                    </button>
                                </div>
                            </template>
                        </TaskCard>
                    </div>
                    
                    <div class="flex justify-center mt-10">
                        <Pagination :links="tasks.links" />
                    </div>
                </div>

            </div>
        </div>

        <ConfirmModal
            :show="showForceDeleteModal"
            title="Delete Forever"
            message="Are you sure you want to permanently delete this task? This action cannot be undone."
            confirm-text="Delete Forever"
            danger
            @cancel="showForceDeleteModal = false; taskToForceDelete = null"
            @confirm="executeForceDelete"
        />

        <!-- Bulk Restore Modal -->
        <ConfirmModal
            :show="showBulkRestoreModal"
            title="Restore Tasks"
            :message="`Restore ${selectedCount} selected task${selectedCount !== 1 ? 's' : ''} back to your task list?`"
            confirm-text="Restore"
            @cancel="showBulkRestoreModal = false"
            @confirm="executeBulkRestore"
        />

        <!-- Bulk Force Delete Modal -->
        <ConfirmModal
            :show="showBulkForceDeleteModal"
            title="Delete Forever"
            :message="`Permanently delete ${selectedCount} selected task${selectedCount !== 1 ? 's' : ''}? This action cannot be undone.`"
            confirm-text="Delete Forever"
            danger
            @cancel="showBulkForceDeleteModal = false"
            @confirm="executeBulkForceDelete"
        />
    </AuthenticatedLayout>
</template>
