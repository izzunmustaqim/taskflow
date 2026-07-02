<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed } from 'vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import ActivityLog from '@/Components/ActivityLog.vue';
import FileUpload from '@/Components/FileUpload.vue';

const props = defineProps<{
    task: {
        id: number;
        title: string;
        description: string | null;
        status: string;
        priority: string;
        category_id: number | null;
        due_at: string | null;
        recurrence_type: string | null;
        recurrence_interval: number;
        labels?: Array<{ id: number; name: string; color: string }>;
        attachments?: Array<{ id: number; original_name: string; mime_type: string; size: string | number; created_at: string }>;
    };
    categories: Array<{ id: number; name: string; color: string }>;
    labels: Array<{ id: number; name: string; color: string }>;
    statuses: Record<string, string>;
    priorities: Record<string, string>;
    recurrenceTypes: Record<string, string>;
    activityLog: {
        data: Array<{
            id: number;
            type: string;
            description: string;
            properties: any;
            created_at: string;
        }>;
        links: any[];
    };
}>();

const showActivityLog = ref(false);
const showRecurrence = ref(false);

// Format datetime string for input type="datetime-local" if it exists
const formattedDueAt = props.task.due_at
    ? new Date(props.task.due_at).toISOString().slice(0, 16)
    : '';

const form = useForm({
    title: props.task.title,
    description: props.task.description || '',
    status: props.task.status,
    priority: props.task.priority,
    category_id: props.task.category_id || '',
    due_at: formattedDueAt,
    label_ids: (props.task.labels || []).map(l => l.id),
    recurrence_type: props.task.recurrence_type || 'none',
    recurrence_interval: props.task.recurrence_interval || 1,
});

const isRecurring = computed(() => form.recurrence_type && form.recurrence_type !== 'none');

const toggleLabel = (id: number) => {
    const index = form.label_ids.indexOf(id);
    if (index === -1) {
        form.label_ids.push(id);
    } else {
        form.label_ids.splice(index, 1);
    }
};

const submit = () => {
    form.put(route('tasks.update', props.task.id));
};

const showDeleteModal = ref(false);

const deleteTask = () => {
    router.delete(route('tasks.destroy', props.task.id), {
        onSuccess: () => showDeleteModal.value = false,
    });
};
</script>

<template>
    <Head :title="`Edit: ${task.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('tasks.index')" class="p-2 -ml-2 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight truncate">Edit Task</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-3xl p-8 shadow-sm">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <input type="text" id="title" v-model="form.title" class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" :class="{'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.title}" />
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

                        <!-- Labels -->
                        <div v-if="labels.length > 0" class="border-t border-gray-100 dark:border-gray-700/50 pt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Labels</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="label in labels"
                                    :key="label.id"
                                    type="button"
                                    @click="toggleLabel(label.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border-2 transition-all duration-200"
                                    :class="form.label_ids.includes(label.id)
                                        ? 'border-current shadow-sm scale-105'
                                        : 'border-transparent bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    :style="form.label_ids.includes(label.id) ? { color: label.color, backgroundColor: label.color + '20' } : {}"
                                >
                                    <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: label.color }"></span>
                                    {{ label.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Recurrence -->
                        <div class="border-t border-gray-100 dark:border-gray-700/50 pt-6">
                            <button type="button" @click="showRecurrence = !showRecurrence" class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <svg :class="{ 'rotate-90': showRecurrence }" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                                Recurrence Settings
                                <span v-if="isRecurring" class="px-2 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full">Active</span>
                            </button>

                            <div v-if="showRecurrence" class="mt-4 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Repeat</label>
                                        <select v-model="form.recurrence_type" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors">
                                            <option value="none">No Repeat</option>
                                            <option v-for="(label, val) in recurrenceTypes" :key="val" :value="val">{{ label }}</option>
                                        </select>
                                    </div>

                                    <div v-if="isRecurring">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Every</label>
                                        <div class="mt-1 flex items-center gap-2">
                                            <input type="number" v-model="form.recurrence_interval" min="1" max="365" class="block w-24 rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900/50 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5 transition-colors" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ form.recurrence_interval === 1 ? form.recurrence_type?.replace(/s$/, '') : form.recurrence_type }}(s)</span>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="isRecurring" class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                    ℹ️ When this task is completed, a new task will be automatically created with the next due date.
                                </p>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div class="border-t border-gray-100 dark:border-gray-700/50 pt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Attachments</label>
                            <FileUpload :task-id="task.id" :attachments="task.attachments || []" />
                        </div>

                        <div class="pt-6 mt-6 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
                            <button type="button" @click="showDeleteModal = true" class="inline-flex items-center gap-1.5 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 text-sm font-medium px-3 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Delete
                            </button>
                            
                            <div class="flex items-center gap-3">
                                <Link :href="route('tasks.index')" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
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
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Activity Log Section -->
        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border border-gray-100 dark:border-gray-700 rounded-3xl shadow-sm overflow-hidden">
                    <!-- Header -->
                    <button
                        @click="showActivityLog = !showActivityLog"
                        class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-left">
                                <h3 class="font-semibold text-gray-900 dark:text-white">Activity Log</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ activityLog.data.length }} activities recorded</p>
                            </div>
                        </div>
                        <svg
                            :class="{ 'rotate-180': showActivityLog }"
                            class="w-5 h-5 text-gray-400 transition-transform duration-200"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <!-- Content -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-[1000px] opacity-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="max-h-[1000px] opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-if="showActivityLog" class="overflow-hidden">
                            <div class="px-6 pb-6 border-t border-gray-100 dark:border-gray-700">
                                <div class="pt-6">
                                    <ActivityLog :activities="activityLog" />
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Task"
            message="Are you sure you want to delete this task? It will be moved to the trash and can be restored later."
            confirm-text="Move to Trash"
            danger
            @cancel="showDeleteModal = false"
            @confirm="deleteTask"
        />
    </AuthenticatedLayout>
</template>
