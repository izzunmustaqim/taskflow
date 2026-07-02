<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { formatDistanceToNow } from 'date-fns';

interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    data: { task_id?: number; task_title?: string } | null;
    is_read: boolean;
    read_at: string | null;
    created_at: string;
}

interface NotificationPreference {
    id: number;
    email_due_soon: boolean;
    email_overdue: boolean;
    email_reminders: boolean;
    in_app_due_soon: boolean;
    in_app_overdue: boolean;
    in_app_reminders: boolean;
    reminder_hours_before: number;
}

interface PaginatedNotifications {
    data: Notification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    notifications: PaginatedNotifications;
    unreadCount: number;
    preferences: NotificationPreference;
}>();

const activeTab = ref<'notifications' | 'preferences'>('notifications');
const preferencesForm = ref({ ...props.preferences });

const markAsRead = (notification: Notification) => {
    router.post(route('notifications.mark-read', notification.id), {}, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.post(route('notifications.mark-all-read'), {}, { preserveScroll: true });
};

const deleteNotification = (notification: Notification) => {
    if (confirm('Are you sure you want to delete this notification?')) {
        router.delete(route('notifications.destroy', notification.id), { preserveScroll: true });
    }
};

const savePreferences = () => {
    router.put(route('notifications.preferences'), preferencesForm.value, {
        preserveScroll: true,
        onSuccess: () => alert('Preferences saved successfully!'),
    });
};

const getNotificationIcon = (type: string) => {
    return { due_soon: '⏰', overdue: '⚠️', reminder: '🔔' }[type] || '📌';
};

const getNotificationColor = (type: string) => {
    return { due_soon: 'bg-yellow-100 text-yellow-800', overdue: 'bg-red-100 text-red-800', reminder: 'bg-blue-100 text-blue-800' }[type] || 'bg-gray-100';
};

const goToTask = (taskId: number) => {
    router.visit(route('tasks.show', taskId));
};
</script>

<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Notifications
                    <span v-if="unreadCount > 0" class="ml-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ unreadCount }}</span>
                </h2>
                <button v-if="unreadCount > 0" @click="markAllAsRead" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">Mark all as read</button>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex space-x-8">
                        <button @click="activeTab = 'notifications'" :class="['py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'notifications' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">Notifications</button>
                        <button @click="activeTab = 'preferences'" :class="['py-4 px-1 border-b-2 font-medium text-sm', activeTab === 'preferences' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300']">Preferences</button>
                    </nav>
                </div>
                <div v-if="activeTab === 'notifications'" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                    <div v-if="notifications.data.length === 0" class="p-12 text-center">
                        <div class="text-6xl mb-4">🔔</div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No notifications</h3>
                        <p class="text-gray-500 dark:text-gray-400">You are all caught up! Notifications about your tasks will appear here.</p>
                    </div>
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="notification in notifications.data" :key="notification.id" :class="['p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors', !notification.is_read && 'bg-indigo-50 dark:bg-indigo-900/20']">
                            <div class="flex items-start space-x-4">
                                <div :class="['flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-lg', getNotificationColor(notification.type)]">{{ getNotificationIcon(notification.type) }}</div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p :class="['text-sm font-medium', notification.is_read ? 'text-gray-600 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100']">{{ notification.title }}</p>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDistanceToNow(new Date(notification.created_at), { addSuffix: true }) }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ notification.message }}</p>
                                    <div class="mt-3 flex items-center space-x-3">
                                        <button v-if="notification.data?.task_id" @click="goToTask(notification.data.task_id)" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View Task</button>
                                        <button v-if="!notification.is_read" @click="markAsRead(notification)" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Mark as read</button>
                                        <button @click="deleteNotification(notification)" class="text-sm text-red-500 hover:text-red-700 dark:text-red-400">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="notifications.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Page {{ notifications.current_page }} of {{ notifications.last_page }}</span>
                        <div class="flex space-x-2">
                            <button v-if="notifications.current_page > 1" @click="router.get(route('notifications.index'), { page: notifications.current_page - 1 })" class="px-3 py-1 text-sm border rounded-md">Previous</button>
                            <button v-if="notifications.current_page < notifications.last_page" @click="router.get(route('notifications.index'), { page: notifications.current_page + 1 })" class="px-3 py-1 text-sm border rounded-md">Next</button>
                        </div>
                    </div>
                </div>
                <div v-if="activeTab === 'preferences'" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">Notification Preferences</h3>
                    <form @submit.prevent="savePreferences" class="space-y-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Email Notifications</h4>
                            <div class="space-y-3">
                                <label class="flex items-center"><input type="checkbox" v-model="preferencesForm.email_due_soon" class="rounded border-gray-300 text-indigo-600" /><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Send email when tasks are due soon</span></label>
                                <label class="flex items-center"><input type="checkbox" v-model="preferencesForm.email_overdue" class="rounded border-gray-300 text-indigo-600" /><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Send email when tasks become overdue</span></label>
                                <label class="flex items-center"><input type="checkbox" v-model="preferencesForm.email_reminders" class="rounded border-gray-300 text-indigo-600" /><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Send email reminders before due date</span></label>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">In-App Notifications</h4>
                            <div class="space-y-3">
                                <label class="flex items-center"><input type="checkbox" v-model="preferencesForm.in_app_due_soon" class="rounded border-gray-300 text-indigo-600" /><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Show notification when tasks are due soon</span></label>
                                <label class="flex items-center"><input type="checkbox" v-model="preferencesForm.in_app_overdue" class="rounded border-gray-300 text-indigo-600" /><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Show notification when tasks become overdue</span></label>
                                <label class="flex items-center"><input type="checkbox" v-model="preferencesForm.in_app_reminders" class="rounded border-gray-300 text-indigo-600" /><span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Show reminders before due date</span></label>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Reminder Timing</h4>
                            <select v-model="preferencesForm.reminder_hours_before" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                <option :value="1">1 hour before</option>
                                <option :value="6">6 hours before</option>
                                <option :value="12">12 hours before</option>
                                <option :value="24">1 day before</option>
                                <option :value="48">2 days before</option>
                                <option :value="72">3 days before</option>
                                <option :value="168">1 week before</option>
                            </select>
                        </div>
                        <div class="pt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-indigo-700">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
