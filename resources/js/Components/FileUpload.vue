<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface Attachment {
    id: number;
    original_name: string;
    mime_type: string;
    size: string | number;
    created_at: string;
}

const props = defineProps<{
    taskId: number;
    attachments: Attachment[];
}>();

const isDragging = ref(false);
const isUploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadProgress = ref(0);
const error = ref<string | null>(null);

const allowedTypes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain', 'text/csv', 'application/zip',
];

const maxSize = 10 * 1024 * 1024;

const getFileIcon = (mimeType: string): string => {
    if (mimeType.startsWith('image/')) return '\u{1F5BC}️';
    if (mimeType.includes('pdf')) return '\u{1F4C4}';
    if (mimeType.includes('word') || mimeType.includes('document')) return '\u{1F4DD}';
    if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '\u{1F4CA}';
    if (mimeType.includes('zip')) return '\u{1F4E6}';
    return '\u{1F4CE}';
};

const formatSize = (size: string | number): string => {
    if (typeof size === 'string') return size;
    const units = ['B', 'KB', 'MB', 'GB'];
    let bytes = size;
    let unitIndex = 0;
    while (bytes >= 1024 && unitIndex < units.length - 1) {
        bytes /= 1024;
        unitIndex++;
    }
    return bytes.toFixed(unitIndex === 0 ? 0 : 1) + ' ' + units[unitIndex];
};

const validateFile = (file: File): boolean => {
    error.value = null;
    if (!allowedTypes.includes(file.type)) {
        error.value = 'File type not allowed.';
        return false;
    }
    if (file.size > maxSize) {
        error.value = 'File size exceeds 10MB limit.';
        return false;
    }
    return true;
};

const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    if (!files || files.length === 0) return;
    for (const file of Array.from(files)) {
        await uploadFile(file);
    }
    if (fileInput.value) fileInput.value.value = '';
};

const uploadFile = async (file: File) => {
    if (!validateFile(file)) return;
    isUploading.value = true;
    uploadProgress.value = 0;
    try {
        const formData = new FormData();
        formData.append('file', file);
        router.post(route('tasks.attachments.store', props.taskId), formData, {
            forceFormData: true,
            onSuccess: () => {
                isUploading.value = false;
                uploadProgress.value = 0;
            },
            onError: (errors) => {
                error.value = errors.file || 'Failed to upload file.';
                isUploading.value = false;
            },
        });
    } catch {
        error.value = 'An error occurred while uploading.';
        isUploading.value = false;
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => { isDragging.value = false; };

const handleDrop = async (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
    const files = event.dataTransfer?.files;
    if (!files || files.length === 0) return;
    for (const file of Array.from(files)) {
        await uploadFile(file);
    }
};

const deleteAttachment = (attachmentId: number) => {
    if (confirm('Are you sure you want to delete this attachment?')) {
        router.delete(route('attachments.destroy', attachmentId));
    }
};

const downloadAttachment = (attachmentId: number) => {
    window.location.href = route('attachments.download', attachmentId);
};

const triggerFileInput = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div class="space-y-4">
        <!-- Drop Zone -->
        <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            @click="triggerFileInput"
            :class="[
                'border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all duration-200',
                isDragging
                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-800/50'
            ]"
        >
            <input ref="fileInput" type="file" multiple @change="handleFileSelect" class="hidden" />
            <div class="space-y-2">
                <div class="text-4xl">📎</div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span class="text-indigo-600 dark:text-indigo-400">Click to upload</span> or drag and drop
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, XLS, Images, TXT, CSV, ZIP (max 10MB)</div>
            </div>
        </div>

        <!-- Upload Progress -->
        <div v-if="isUploading" class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4">
            <div class="flex items-center gap-3">
                <div class="animate-spin rounded-full h-5 w-5 border-2 border-indigo-600 border-t-transparent"></div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Uploading...</div>
                    <div class="mt-2 h-2 bg-indigo-200 dark:bg-indigo-800 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-600 transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                    </div>
                </div>
                <span class="text-sm font-medium text-indigo-700">{{ uploadProgress }}%</span>
            </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 flex items-center gap-3">
            <span class="text-red-600">⚠️</span>
            <span class="text-sm text-red-700 dark:text-red-300">{{ error }}</span>
            <button @click="error = null" class="ml-auto text-red-500 hover:text-red-700">×</button>
        </div>

        <!-- Attachments List -->
        <div v-if="attachments.length > 0" class="space-y-2">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Attached Files ({{ attachments.length }})
            </div>
            <div
                v-for="attachment in attachments"
                :key="attachment.id"
                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors group"
            >
                <span class="text-2xl">{{ getFileIcon(attachment.mime_type) }}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ attachment.original_name }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatSize(attachment.size) }}</div>
                </div>
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="downloadAttachment(attachment.id)"
                        class="p-2 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors" title="Download">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </button>
                    <button @click="deleteAttachment(attachment.id)"
                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
