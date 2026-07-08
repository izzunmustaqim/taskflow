<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskCard from '@/Components/TaskCard.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { debounce } from 'lodash';
import { useKeyboardShortcuts } from '@/Composables/useKeyboardShortcuts';
import draggable from 'vuedraggable';

interface TaskData {
    id: number;
    title: string;
    description: string | null;
    status: string;
    priority: string;
    due_at: string | null;
    category?: {
        id: number;
        name: string;
        color: string;
    } | null;
}

const props = defineProps<{
    tasks: {
        data: TaskData[];
        links: any[];
    };
    filters: {
        search?: string;
        status?: string;
        priority?: string;
        category_id?: string;
        label_id?: string;
        due?: string;
        sort?: string;
        order?: string;
    };
    categories: Array<{ id: number; name: string }>;
    labels: Array<{ id: number; name: string; color: string }>;
    statuses: Record<string, string>;
    priorities: Record<string, string>;
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const priority = ref(props.filters.priority || '');
const category_id = ref(props.filters.category_id || '');
const label_id = ref(props.filters.label_id || '');
const due = ref(props.filters.due || '');
const sort = ref(props.filters.sort || 'created_at');
const order = ref(props.filters.order || 'desc');
const searchInput = ref<HTMLInputElement | null>(null);

// Drag-and-drop state
const taskList = ref<TaskData[]>([...props.tasks.data]);
const isDragging = ref(false);

watch(() => props.tasks.data, (newData) => {
    taskList.value = [...newData];
});

const isDraggable = computed(() => sort.value === 'sort_order' || sort.value === 'created_at');

const onDragEnd = () => {
    isDragging.value = false;
    const orderedIds = taskList.value.map(t => t.id);
    router.post(route('tasks.reorder'), { ids: orderedIds }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Bulk selection state
const selectedIds = ref<Set<number>>(new Set());
const showBulkStatusModal = ref(false);
const showBulkDeleteModal = ref(false);
const bulkStatusValue = ref('');
const isProcessing = ref(false);

// Import modal state
const showImportModal = ref(false);
const importFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const importErrors = ref<string[]>([]);
const isImporting = ref(false);

// Restore modal state
const showRestoreModal = ref(false);
const restoreFile = ref<File | null>(null);
const restoreFileInput = ref<HTMLInputElement | null>(null);
const restoreErrors = ref<string[]>([]);
const isRestoring = ref(false);

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

const openBulkStatusModal = () => {
    bulkStatusValue.value = '';
    showBulkStatusModal.value = true;
};

const executeBulkStatus = () => {
    if (!bulkStatusValue.value || selectedIds.value.size === 0) return;

    isProcessing.value = true;
    router.post(route('tasks.bulk-status'), {
        ids: Array.from(selectedIds.value),
        status: bulkStatusValue.value,
    }, {
        onFinish: () => {
            isProcessing.value = false;
            showBulkStatusModal.value = false;
            clearSelection();
        },
    });
};

const openBulkDeleteModal = () => {
    showBulkDeleteModal.value = true;
};

const executeBulkDelete = () => {
    if (selectedIds.value.size === 0) return;

    isProcessing.value = true;
    router.post(route('tasks.bulk-delete'), {
        ids: Array.from(selectedIds.value),
    }, {
        onFinish: () => {
            isProcessing.value = false;
            showBulkDeleteModal.value = false;
            clearSelection();
        },
    });
};

// Import functions
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        importFile.value = target.files[0];
        importErrors.value = [];
    }
};

const submitImport = () => {
    if (!importFile.value) return;

    isImporting.value = true;
    importErrors.value = [];

    const formData = new FormData();
    formData.append('csv_file', importFile.value);

    router.post(route('tasks.import.csv'), formData, {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
        },
        onError: (errors) => {
            if (errors.csv_file) {
                importErrors.value = [errors.csv_file];
            } else {
                importErrors.value = Object.values(errors);
            }
        },
        onFinish: () => {
            isImporting.value = false;
        },
    });
};

const closeImportModal = () => {
    showImportModal.value = false;
    importFile.value = null;
    importErrors.value = [];
};

// Restore functions
const handleRestoreFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        restoreFile.value = target.files[0];
        restoreErrors.value = [];
    }
};

const submitRestore = () => {
    if (!restoreFile.value) return;

    isRestoring.value = true;
    restoreErrors.value = [];

    const formData = new FormData();
    formData.append('backup_file', restoreFile.value);

    router.post(route('tasks.restore-backup'), formData, {
        forceFormData: true,
        onSuccess: () => {
            showRestoreModal.value = false;
            restoreFile.value = null;
        },
        onError: (errors) => {
            if (errors.backup_file) {
                restoreErrors.value = [errors.backup_file];
            } else {
                restoreErrors.value = Object.values(errors);
            }
        },
        onFinish: () => {
            isRestoring.value = false;
        },
    });
};

const closeRestoreModal = () => {
    showRestoreModal.value = false;
    restoreFile.value = null;
    restoreErrors.value = [];
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    priority.value = '';
    category_id.value = '';
    label_id.value = '';
    due.value = '';
    sort.value = 'created_at';
    order.value = 'desc';
};

const performSearch = debounce(() => {
    router.get(
        route('tasks.index'),
        {
            search: search.value,
            status: status.value,
            priority: priority.value,
            category_id: category_id.value,
            label_id: label_id.value,
            due: due.value,
            sort: sort.value,
            order: order.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch([search, status, priority, category_id, label_id, due, sort, order], performSearch);

const { register, unregister } = useKeyboardShortcuts();

// Register page-specific shortcuts
register({
    key: 'n',
    description: 'Create new task',
    handler: () => router.visit(route('tasks.create')),
});

register({
    key: '/',
    description: 'Focus search',
    handler: () => searchInput.value?.focus(),
});

register({
    key: 'Escape',
    description: 'Clear selection or search/filters',
    handler: () => {
        if (hasSelection.value) {
            clearSelection();
        } else {
            clearFilters();
        }
    },
});

register({
    key: 'a',
    description: 'Select all tasks',
    handler: toggleSelectAll,
});

register({
    key: 'Delete',
    description: 'Delete selected tasks',
    handler: () => {
        if (hasSelection.value) openBulkDeleteModal();
    },
});

// Clean up shortcuts when component unmounts
onUnmounted(() => {
    unregister('n');
    unregister('/');
    unregister('Escape');
    unregister('a');
    unregister('Delete');
});
</script>

<template>
    <Head title="Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">Tasks</h2>

                <div class="flex flex-wrap gap-3">
                    <Link :href="route('tasks.trash')" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-medium text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        <span class="hidden sm:inline">Trash</span>
                    </Link>
                    <a :href="route('tasks.export.csv', { status: status, priority: priority, category_id: category_id, label_id: label_id, search: search, due: due })" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-medium text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span class="hidden sm:inline">Export CSV</span>
                    </a>
                    <a :href="route('tasks.export.pdf', { status: status, priority: priority, category_id: category_id, label_id: label_id, search: search, due: due })" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-medium text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span class="hidden sm:inline">Export PDF</span>
                    </a>
                    <button @click="showImportModal = true" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-medium text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span class="hidden sm:inline">Import CSV</span>
                    </button>
                    <a :href="route('tasks.backup')" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-medium text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                        <span class="hidden sm:inline">Backup</span>
                    </a>
                    <button @click="showRestoreModal = true" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-medium text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                        </svg>
                        <span class="hidden sm:inline">Restore</span>
                    </button>
                    <Link :href="route('tasks.create')" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-500 font-semibold rounded-xl transition-all shadow-sm hover:shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span class="hidden sm:inline">New Task</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filters -->
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-2xl p-6 shadow-sm mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input ref="searchInput" v-model="search" type="text" placeholder="Search tasks... (Press / to focus)" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors" />
                        </div>

                        <!-- Status -->
                        <div>
                            <select v-model="status" class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                <option value="">All Statuses</option>
                                <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div>
                            <select v-model="priority" class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                <option value="">All Priorities</option>
                                <option v-for="(label, val) in priorities" :key="val" :value="val">{{ label }}</option>
                            </select>
                        </div>

                        <!-- Category -->
                        <div>
                            <select v-model="category_id" class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                <option value="">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                        </div>

                        <!-- Label -->
                        <div>
                            <select v-model="label_id" class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                <option value="">All Labels</option>
                                <option v-for="label in labels" :key="label.id" :value="label.id">{{ label.name }}</option>
                            </select>
                        </div>

                        <!-- Sort -->
                        <div class="flex gap-2">
                            <select v-model="sort" class="flex-1 block w-full py-2.5 pl-3 pr-10 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors">
                                <option value="created_at">Created</option>
                                <option value="due_date">Due Date</option>
                                <option value="priority">Priority</option>
                                <option value="status">Status</option>
                            </select>
                            <button
                                @click="order = order === 'asc' ? 'desc' : 'asc'"
                                class="inline-flex items-center justify-center w-10 shrink-0 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                :title="order === 'asc' ? 'Ascending' : 'Descending'"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': order === 'asc' }">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between border-t border-gray-100 dark:border-gray-700/50 pt-4 gap-3">
                        <div class="flex flex-wrap gap-2 sm:gap-4">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" v-model="due" value="" class="sr-only peer">
                                <div class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-500 dark:text-gray-400 peer-checked:bg-indigo-50 peer-checked:text-indigo-700 dark:peer-checked:bg-indigo-900/30 dark:peer-checked:text-indigo-400 transition-colors">All Time</div>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" v-model="due" value="soon" class="sr-only peer">
                                <div class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-500 dark:text-gray-400 peer-checked:bg-amber-50 peer-checked:text-amber-700 dark:peer-checked:bg-amber-900/30 dark:peer-checked:text-amber-400 transition-colors">Due Soon</div>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" v-model="due" value="overdue" class="sr-only peer">
                                <div class="px-3 py-1.5 text-sm font-medium rounded-lg text-gray-500 dark:text-gray-400 peer-checked:bg-rose-50 peer-checked:text-rose-700 dark:peer-checked:bg-rose-900/30 dark:peer-checked:text-rose-400 transition-colors">Overdue</div>
                            </label>
                        </div>
                        
                        <button v-if="search || status || priority || category_id || due || sort !== 'created_at' || order !== 'desc'" @click="clearFilters" class="text-sm font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
                            Clear filters
                        </button>
                    </div>
                </div>

                <!-- Task List -->
                <EmptyState
                    v-if="tasks.data.length === 0"
                    :icon="search || status || priority || category_id || due || sort !== 'created_at' || order !== 'desc' ? 'search' : 'tasks'"
                    :title="search || status || priority || category_id || due || sort !== 'created_at' || order !== 'desc' ? 'No tasks match your filters' : 'No tasks yet'"
                    :description="search || status || priority || category_id || due || sort !== 'created_at' || order !== 'desc' ? 'Try adjusting your filters or clear them to see all tasks.' : 'Create your first task to get started with TaskFlow.'"
                    :action-text="!search && !status && !priority && !category_id && !due && sort === 'created_at' && order === 'desc' ? 'Create Task' : undefined"
                    :action-href="!search && !status && !priority && !category_id && !due && sort === 'created_at' && order === 'desc' ? route('tasks.create') : undefined"
                >
                    <template v-if="search || status || priority || category_id || due || sort !== 'created_at' || order !== 'desc'" #action>
                        <button @click="clearFilters" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 font-medium rounded-xl transition-colors">
                            Clear Filters
                        </button>
                    </template>
                </EmptyState>

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
                        <div v-if="hasSelection" class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-2xl p-4 shadow-sm">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-800/50">
                                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ selectedCount }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                                        task{{ selectedCount !== 1 ? 's' : '' }} selected
                                    </span>
                                    <button @click="clearSelection" class="text-xs font-medium text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 underline transition-colors">
                                        Clear
                                    </button>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button @click="openBulkStatusModal" :disabled="isProcessing" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 rounded-xl text-sm font-medium text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-6L16.5 19m0 0L12 14.5m4.5 4.5V7.5" />
                                        </svg>
                                        Change Status
                                    </button>
                                    <button @click="openBulkDeleteModal" :disabled="isProcessing" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-700 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        Move to Trash
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
                                        ? 'bg-indigo-600 border-indigo-600'
                                        : someSelected
                                            ? 'bg-indigo-600/50 border-indigo-600'
                                            : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 group-hover:border-indigo-400 dark:group-hover:border-indigo-500'
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
                        <span class="text-xs text-gray-400 dark:text-gray-500">Press <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-[10px] font-mono">A</kbd> to toggle</span>
                    </div>

                    <draggable
                        v-model="taskList"
                        item-key="id"
                        handle=".drag-handle"
                        ghost-class="opacity-40"
                        :disabled="!isDraggable"
                        class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6"
                        @start="isDragging = true"
                        @end="onDragEnd"
                    >
                        <template #item="{ element }">
                            <TaskCard
                                :task="element"
                                :selectable="!isDraggable || isDragging"
                                :selected="selectedIds.has(element.id)"
                                :draggable="isDraggable"
                                @toggle-select="toggleSelect"
                            />
                        </template>
                    </draggable>
                    
                    <div class="flex justify-center mt-10">
                        <Pagination :links="tasks.links" />
                    </div>
                </div>

            </div>
        </div>

        <!-- Bulk Status Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showBulkStatusModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="showBulkStatusModal = false"></div>
                    <Transition
                        enter-active-class="ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div v-if="showBulkStatusModal" class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all sm:my-8 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 rounded-full p-2.5 flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-6L16.5 19m0 0L12 14.5m4.5 4.5V7.5" />
                                    </svg>
                                </div>
                                <div class="pt-0.5 w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white">Change Status</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Update status for {{ selectedCount }} selected task{{ selectedCount !== 1 ? 's' : '' }}.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Status</label>
                                <select
                                    v-model="bulkStatusValue"
                                    class="block w-full py-2.5 pl-3 pr-10 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors"
                                >
                                    <option value="">Select a status...</option>
                                    <option v-for="(label, val) in statuses" :key="val" :value="val">{{ label }}</option>
                                </select>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-xl bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="showBulkStatusModal = false"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!bulkStatusValue || isProcessing"
                                    @click="executeBulkStatus"
                                >
                                    {{ isProcessing ? 'Updating...' : 'Update Status' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Bulk Delete Modal -->
        <ConfirmModal
            :show="showBulkDeleteModal"
            title="Move to Trash"
            :message="`Are you sure you want to move ${selectedCount} task${selectedCount !== 1 ? 's' : ''} to trash? You can restore them later from the Trash.`"
            confirm-text="Move to Trash"
            danger
            @cancel="showBulkDeleteModal = false"
            @confirm="executeBulkDelete"
        />

        <!-- Import CSV Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="closeImportModal"></div>
                    <Transition
                        enter-active-class="ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div v-if="showImportModal" class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all sm:my-8 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 rounded-full p-2.5 flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                    </svg>
                                </div>
                                <div class="pt-0.5 w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white">Import Tasks from CSV</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Upload a CSV file to import tasks. The file should have columns: title, description, status, priority, category, due date, labels.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
                                    <input
                                        type="file"
                                        accept=".csv,.txt"
                                        class="hidden"
                                        ref="fileInput"
                                        @change="handleFileSelect"
                                    />
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <button type="button" @click="fileInput?.click()" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                            Click to select file
                                        </button>
                                        or drag and drop
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">CSV files up to 10MB</p>
                                    <p v-if="importFile" class="mt-3 text-sm text-indigo-600 dark:text-indigo-400 font-medium">
                                        {{ importFile.name }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="importErrors.length > 0" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                <p class="text-sm font-medium text-red-800 dark:text-red-400">Import Errors:</p>
                                <ul class="mt-2 text-xs text-red-700 dark:text-red-300 list-disc list-inside">
                                    <li v-for="(error, index) in importErrors" :key="index">{{ error }}</li>
                                </ul>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-xl bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="closeImportModal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!importFile || isImporting"
                                    @click="submitImport"
                                >
                                    {{ isImporting ? 'Importing...' : 'Import Tasks' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Restore Backup Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showRestoreModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-0" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="closeRestoreModal"></div>
                    <Transition
                        enter-active-class="ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div v-if="showRestoreModal" class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all sm:my-8 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-start gap-4">
                                <div class="shrink-0 rounded-full p-2.5 flex items-center justify-center bg-amber-100 dark:bg-amber-900/30">
                                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
                                    </svg>
                                </div>
                                <div class="pt-0.5 w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white">Restore from Backup</h3>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        Upload a JSON backup file to restore your tasks, categories, and labels. This will add data to your account (not replace it).
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-amber-400 dark:hover:border-amber-500 transition-colors">
                                    <input
                                        type="file"
                                        accept=".json"
                                        class="hidden"
                                        ref="restoreFileInput"
                                        @change="handleRestoreFileSelect"
                                    />
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <button type="button" @click="restoreFileInput?.click()" class="font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-400">
                                            Click to select file
                                        </button>
                                        or drag and drop
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">JSON backup files only</p>
                                    <p v-if="restoreFile" class="mt-3 text-sm text-amber-600 dark:text-amber-400 font-medium">
                                        {{ restoreFile.name }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="restoreErrors.length > 0" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                <p class="text-sm font-medium text-red-800 dark:text-red-400">Restore Errors:</p>
                                <ul class="mt-2 text-xs text-red-700 dark:text-red-300 list-disc list-inside">
                                    <li v-for="(error, index) in restoreErrors" :key="index">{{ error }}</li>
                                </ul>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-xl bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                    @click="closeRestoreModal"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-amber-500 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!restoreFile || isRestoring"
                                    @click="submitRestore"
                                >
                                    {{ isRestoring ? 'Restoring...' : 'Restore Backup' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
