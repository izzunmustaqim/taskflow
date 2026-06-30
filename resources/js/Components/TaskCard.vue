<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StatusBadge from './StatusBadge.vue';
import PriorityBadge from './PriorityBadge.vue';
import { formatDistanceToNow, isPast } from 'date-fns';

const props = defineProps<{
    task: {
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
    };
    isTrash?: boolean;
    selectable?: boolean;
    selected?: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-select', id: number): void;
}>();

const formatDueTime = (dateString: string | null) => {
    if (!dateString) return null;
    return formatDistanceToNow(new Date(dateString), { addSuffix: true });
};

const isOverdue = (dateString: string | null, status: string) => {
    if (!dateString || status === 'completed') return false;
    return isPast(new Date(dateString));
};

const handleCheckboxClick = (e: Event) => {
    e.stopPropagation();
    emit('toggle-select', props.task.id);
};

const handleCardClick = () => {
    if (props.selectable) {
        emit('toggle-select', props.task.id);
    }
};
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-2xl bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl border p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
        :class="[
            selected
                ? 'border-indigo-300 dark:border-indigo-600 ring-2 ring-indigo-200 dark:ring-indigo-800'
                : 'border-gray-100 dark:border-gray-700 hover:border-indigo-100 dark:hover:border-indigo-900',
            selectable ? 'cursor-pointer' : ''
        ]"
        @click="handleCardClick"
    >
        <!-- Decoration Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-transparent dark:from-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

        <!-- Selection Checkbox -->
        <div v-if="selectable" class="absolute top-3 left-3 z-20" @click="handleCheckboxClick">
            <div
                class="w-5 h-5 rounded-md border-2 flex items-center justify-center transition-all duration-200"
                :class="[
                    selected
                        ? 'bg-indigo-600 border-indigo-600'
                        : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 group-hover:border-indigo-400 dark:group-hover:border-indigo-500'
                ]"
            >
                <svg v-if="selected" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
        </div>

        <div class="relative z-10 flex flex-col h-full" :class="selectable ? 'pl-4' : ''">
            <!-- Header -->
            <div class="flex justify-between items-start gap-4 mb-3">
                <div class="flex flex-col gap-1.5">
                    <h3 class="font-semibold text-lg text-gray-900 dark:text-white leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        <Link v-if="!isTrash" :href="route('tasks.edit', task.id)" class="focus:outline-none">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            {{ task.title }}
                        </Link>
                        <span v-else>{{ task.title }}</span>
                    </h3>
                    
                    <div v-if="task.category" class="flex items-center gap-1.5 text-xs font-medium">
                        <span class="w-2.5 h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: task.category.color }"></span>
                        <span class="text-gray-600 dark:text-gray-400">{{ task.category.name }}</span>
                    </div>
                </div>
                
                <div class="flex gap-2 shrink-0 z-20">
                    <StatusBadge :status="task.status" />
                </div>
            </div>

            <!-- Body -->
            <p v-if="task.description" class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-4 grow leading-relaxed">
                {{ task.description }}
            </p>
            <div v-else class="grow"></div>

            <!-- Footer -->
            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100 dark:border-gray-700/50">
                <PriorityBadge :priority="task.priority" />
                
                <div v-if="task.due_at" class="flex items-center gap-1.5 text-xs font-medium" :class="isOverdue(task.due_at, task.status) ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ formatDueTime(task.due_at) }}</span>
                </div>
            </div>
            
            <!-- Actions slot for Trash view -->
            <div v-if="isTrash" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/50 flex flex-wrap justify-end gap-2 z-20 relative">
                 <slot name="actions"></slot>
            </div>
        </div>
    </div>
</template>
