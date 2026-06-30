<script setup lang="ts">
import { formatDistanceToNow } from 'date-fns';

interface Activity {
    id: number;
    type: string;
    description: string;
    properties: {
        old_status?: string;
        new_status?: string;
        changes?: Record<string, { old: any; new: any }>;
    } | null;
    created_at: string;
}

defineProps<{
    activities: {
        data: Activity[];
        links: any[];
    };
}>();

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'created':
            return {
                bg: 'bg-emerald-100 dark:bg-emerald-900/30',
                text: 'text-emerald-600 dark:text-emerald-400',
                icon: 'M12 4.5v15m7.5-7.5h-15'
            };
        case 'updated':
            return {
                bg: 'bg-blue-100 dark:bg-blue-900/30',
                text: 'text-blue-600 dark:text-blue-400',
                icon: 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'
            };
        case 'status_changed':
            return {
                bg: 'bg-amber-100 dark:bg-amber-900/30',
                text: 'text-amber-600 dark:text-amber-400',
                icon: 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'
            };
        case 'deleted':
            return {
                bg: 'bg-red-100 dark:bg-red-900/30',
                text: 'text-red-600 dark:text-red-400',
                icon: 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'
            };
        case 'restored':
            return {
                bg: 'bg-purple-100 dark:bg-purple-900/30',
                text: 'text-purple-600 dark:text-purple-400',
                icon: 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3'
            };
        case 'force_deleted':
            return {
                bg: 'bg-gray-100 dark:bg-gray-700',
                text: 'text-gray-600 dark:text-gray-400',
                icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'
            };
        default:
            return {
                bg: 'bg-gray-100 dark:bg-gray-700',
                text: 'text-gray-600 dark:text-gray-400',
                icon: 'M12 6v12m6-6H6'
            };
    }
};

const formatStatus = (status: string) => {
    return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            <li v-for="(activity, activityIdx) in activities.data" :key="activity.id">
                <div class="relative pb-8">
                    <!-- Vertical line -->
                    <span v-if="activityIdx !== activities.data.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                    
                    <div class="relative flex space-x-3">
                        <!-- Icon -->
                        <div>
                            <span :class="[getActivityIcon(activity.type).bg, getActivityIcon(activity.type).text]" class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="getActivityIcon(activity.type).icon" />
                                </svg>
                            </span>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ activity.description }}
                                </p>
                                
                                <!-- Status change details -->
                                <div v-if="activity.type === 'status_changed' && activity.properties?.old_status && activity.properties?.new_status" class="mt-1">
                                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 font-medium">{{ formatStatus(activity.properties.old_status) }}</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                        <span class="px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 font-medium">{{ formatStatus(activity.properties.new_status) }}</span>
                                    </span>
                                </div>
                                
                                <!-- Other changes -->
                                <div v-if="activity.properties?.changes" class="mt-1 space-y-1">
                                    <div v-for="(change, field) in activity.properties.changes" :key="field" class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">{{ field }}:</span>
                                        <span class="line-through text-red-500/70 dark:text-red-400/70">{{ change.old ?? 'empty' }}</span>
                                        <span class="mx-1">→</span>
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ change.new ?? 'empty' }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="whitespace-nowrap text-right text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDistanceToNow(new Date(activity.created_at), { addSuffix: true }) }}
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
        
        <!-- Empty state -->
        <div v-if="activities.data.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No activity recorded yet.</p>
        </div>
    </div>
</template>
