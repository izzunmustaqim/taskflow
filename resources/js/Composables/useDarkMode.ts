import { ref, watch } from 'vue';

const isDark = ref(false);
const isInitialized = ref(false);
let watchCreated = false;

export function useDarkMode() {
    const initialize = () => {
        if (isInitialized.value) return;

        // Check localStorage first
        const stored = localStorage.getItem('darkMode');

        if (stored !== null) {
            isDark.value = stored === 'true';
        } else {
            // Check system preference
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        // Apply initial state
        applyTheme();
        isInitialized.value = true;

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only auto-switch if user hasn't set a preference
            if (localStorage.getItem('darkMode') === null) {
                isDark.value = e.matches;
            }
        });
    };

    const applyTheme = () => {
        if (isDark.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    const toggle = () => {
        isDark.value = !isDark.value;
        localStorage.setItem('darkMode', String(isDark.value));
        applyTheme();
    };

    const setDark = (value: boolean) => {
        isDark.value = value;
        localStorage.setItem('darkMode', String(isDark.value));
        applyTheme();
    };

    // Only create the watch once
    if (!watchCreated) {
        watch(isDark, (newValue) => {
            localStorage.setItem('darkMode', String(newValue));
            applyTheme();
        });
        watchCreated = true;
    }

    return {
        isDark,
        isInitialized,
        initialize,
        toggle,
        setDark,
    };
}
