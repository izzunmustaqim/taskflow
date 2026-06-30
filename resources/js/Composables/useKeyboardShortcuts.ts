import { ref } from 'vue';

export interface KeyboardShortcut {
    key: string;
    ctrl?: boolean;
    shift?: boolean;
    alt?: boolean;
    description: string;
    handler: (event: KeyboardEvent) => void;
}

const shortcuts = ref<KeyboardShortcut[]>([]);
const showHelp = ref(false);
let listenerAdded = false;

const handleKeydown = (event: KeyboardEvent) => {
    // Don't trigger shortcuts when typing in inputs
    const target = event.target as HTMLElement;
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable) {
        // Allow Escape even in inputs
        if (event.key !== 'Escape') {
            return;
        }
    }

    // Toggle help modal with ?
    if (event.key === '?' && !event.ctrlKey && !event.altKey && !event.shiftKey) {
        showHelp.value = !showHelp.value;
        return;
    }

    // Close help with Escape
    if (event.key === 'Escape' && showHelp.value) {
        showHelp.value = false;
        return;
    }

    for (const shortcut of shortcuts.value) {
        const ctrlMatch = shortcut.ctrl ? (event.ctrlKey || event.metaKey) : true;
        const shiftMatch = shortcut.shift ? event.shiftKey : true;
        const altMatch = shortcut.alt ? event.altKey : true;

        if (event.key.toLowerCase() === shortcut.key.toLowerCase() && ctrlMatch && shiftMatch && altMatch) {
            event.preventDefault();
            shortcut.handler(event);
            return;
        }
    }
};

// Add listener once at module level
if (!listenerAdded && typeof document !== 'undefined') {
    document.addEventListener('keydown', handleKeydown);
    listenerAdded = true;
}

export function useKeyboardShortcuts() {
    const register = (shortcut: KeyboardShortcut) => {
        shortcuts.value.push(shortcut);
    };

    const unregister = (key: string, ctrl = false, shift = false, alt = false) => {
        const index = shortcuts.value.findIndex(
            s => s.key === key && s.ctrl === ctrl && s.shift === shift && s.alt === alt
        );
        if (index !== -1) {
            shortcuts.value.splice(index, 1);
        }
    };

    return {
        shortcuts,
        showHelp,
        register,
        unregister,
    };
}
