<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskCard from '@/Components/TaskCard.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { debounce } from 'lodash';
import { useKeyboardShortcuts } from '@/Composables/useKeyboardShortcuts';

const props = defineProps<{
    tasks: {
        data: Array<{
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
        }>;
        links: any[];
    };
    filters: {
        search?: string;
        status?: string;
        priority?: string;
        category_id?: string;
        due?: string;
    };
    categories: Array<{ id: number; name: string }>;
    statuses: Record<string, string>;
    priorities: Record<string, string>;
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const priority = ref(props.filters.priority || '');
const category_id = ref(props.filters.category_id || '');
const due = ref(props.filters.due || '');
const searchInput = ref<HTMLInputElement | null>(null);

const clearFilters = () => {
    search.value = '';
    status.value = '';
    priority.value = '';
    category_id.value = '';
    due.value = '';
};

const performSearch = debounce(() => {
    router.get(
        route('tasks.index'),
        {
            search: search.value,
            status: status.value,
            priority: priority.value,
            category_id: category_id.value,
            due: due.value,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch([search, status, priority, category_id, due], performSearch);

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
    description: 'Clear search and filters',
    handler: clearFilters,
});

// Clean up shortcuts when component unmounts
onUnmounted(() => {
    unregister('n');
    unregister('/');
    unregister('Escape');
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
                        
                        <button v-if="search || status || priority || category_id || due" @click="clearFilters" class="text-sm font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
                            Clear filters
                        </button>
                    </div>
                </div>

                <!-- Task List -->
                <EmptyState
                    v-if="tasks.data.length === 0"
                    :icon="search || status || priority || category_id || due ? 'search' : 'tasks'"
                    :title="search || status || priority || category_id || due ? 'No tasks match your filters' : 'No tasks yet'"
                    :description="search || status || priority || category_id || due ? 'Try adjusting your filters or clear them to see all tasks.' : 'Create your first task to get started with TaskFlow.'"
                    :action-text="!search && !status && !priority && !category_id && !due ? 'Create Task' : undefined"
                    :action-href="!search && !status && !priority && !category_id && !due ? route('tasks.create') : undefined"
                >
                    <template v-if="search || status || priority || category_id || due" #action>
                        <button @click="clearFilters" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 font-medium rounded-xl transition-colors">
                            Clear Filters
                        </button>
                    </template>
                </EmptyState>

                <div v-else class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        <TaskCard v-for="task in tasks.data" :key="task.id" :task="task" />
                    </div>
                    
                    <div class="flex justify-center mt-10">
                        <Pagination :links="tasks.links" />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
