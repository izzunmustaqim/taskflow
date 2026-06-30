import { reactive } from 'vue';

export interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
    timeout?: ReturnType<typeof setTimeout>;
}

const toasts = reactive<Toast[]>([]);
let toastId = 0;

const getDuration = (type: Toast['type']) => {
    switch (type) {
        case 'error': return 6000;
        case 'warning': return 5000;
        case 'info': return 4000;
        case 'success': return 3500;
        default: return 4000;
    }
};

const removeToast = (id: number) => {
    const index = toasts.findIndex(t => t.id === id);
    if (index !== -1) {
        if (toasts[index].timeout) {
            clearTimeout(toasts[index].timeout);
        }
        toasts.splice(index, 1);
    }
};

const addToast = (message: string, type: Toast['type']) => {
    const id = ++toastId;
    const timeout = setTimeout(() => removeToast(id), getDuration(type));

    toasts.push({ id, message, type, timeout });

    // Keep max 3 toasts visible
    if (toasts.length > 3) {
        removeToast(toasts[0].id);
    }
};

export function useToast() {
    const success = (message: string) => addToast(message, 'success');
    const error = (message: string) => addToast(message, 'error');
    const warning = (message: string) => addToast(message, 'warning');
    const info = (message: string) => addToast(message, 'info');
    const dismiss = (id: number) => removeToast(id);
    const clear = () => {
        while (toasts.length > 0) {
            removeToast(toasts[0].id);
        }
    };

    return {
        toasts,
        success,
        error,
        warning,
        info,
        dismiss,
        clear,
    };
}
