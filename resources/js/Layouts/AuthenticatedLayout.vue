<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import LoadingBar from '@/Components/LoadingBar.vue';
import KeyboardShortcutsHelp from '@/Components/KeyboardShortcutsHelp.vue';
import DarkModeToggle from '@/Components/DarkModeToggle.vue';
import { useKeyboardShortcuts } from '@/Composables/useKeyboardShortcuts';
import { useDarkMode } from '@/Composables/useDarkMode';

const showingNavigationDropdown = ref(false);
const { register } = useKeyboardShortcuts();
const { toggle: toggleDarkMode } = useDarkMode();
const page = usePage();
const unreadCount = ref(0);

const fetchUnreadCount = async () => {
    try {
        const response = await fetch(route('notifications.unread-count'));
        const data = await response.json();
        unreadCount.value = data.count;
    } catch (e) {
        // Silently fail
    }
};

onMounted(() => {
    fetchUnreadCount();
    // Poll for new notifications every 60 seconds
    setInterval(fetchUnreadCount, 60000);
});

// Register global shortcuts
register({
    key: 'd',
    description: 'Go to Dashboard',
    handler: () => router.visit(route('dashboard')),
});

register({
    key: 't',
    description: 'Go to Tasks',
    handler: () => router.visit(route('tasks.index')),
});

register({
    key: 'c',
    description: 'Go to Categories',
    handler: () => router.visit(route('categories.index')),
});

register({
    key: 'm',
    description: 'Go to Templates',
    handler: () => router.visit(route('templates.index')),
});

register({
    key: 'n',
    ctrl: true,
    description: 'Create new task',
    handler: () => router.visit(route('tasks.create')),
});

register({
    key: 'b',
    shift: true,
    description: 'Toggle dark mode',
    handler: toggleDarkMode,
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        <LoadingBar />
        <FlashMessage />
        <KeyboardShortcutsHelp />
        
        <nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border-b border-gray-100 dark:border-gray-700 sticky top-0 z-40 transition-colors duration-200">
            <!-- Primary Navigation Menu -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="flex items-center gap-2 group">
                                <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold group-hover:scale-105 transition-transform shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="font-bold text-xl text-gray-900 dark:text-white tracking-tight">TaskFlow</span>
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <Link :href="route('dashboard')" :class="route().current('dashboard') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                                Dashboard
                            </Link>
                            <Link :href="route('tasks.index')" :class="route().current('tasks.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                                Tasks
                            </Link>
                            <Link :href="route('categories.index')" :class="route().current('categories.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                                Categories
                            </Link>
                            <Link :href="route('labels.index')" :class="route().current('labels.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                                Labels
                            </Link>
                            <Link :href="route('templates.index')" :class="route().current('templates.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                                Templates
                            </Link>
                            <Link :href="route('notifications.index')" :class="route().current('notifications.*') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors">
                                <span class="relative">
                                    🔔
                                    <span v-if="unreadCount > 0" class="absolute -top-1 -right-2 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-600 rounded-full">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                                </span>
                                <span class="ms-1">Notifications</span>
                            </Link>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <!-- Dark Mode Toggle -->
                        <DarkModeToggle />

                        <!-- Settings Dropdown -->
                        <div class="ms-3 relative group">
                            <div class="flex items-center gap-3 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10">
                                <span>{{ $page.props.auth.user.name }}</span>

                                <Link :href="route('logout')" method="post" as="button" class="text-xs font-semibold text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 ml-2">
                                    Log Out
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden border-b border-gray-200 dark:border-gray-700">
                <div class="pt-2 pb-3 space-y-1">
                    <Link :href="route('dashboard')" :class="route().current('dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/50 border-indigo-500 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:hover:text-gray-200'" class="block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out">
                        Dashboard
                    </Link>
                    <Link :href="route('tasks.index')" :class="route().current('tasks.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 border-indigo-500 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:hover:text-gray-200'" class="block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out">
                        Tasks
                    </Link>
                    <Link :href="route('categories.index')" :class="route().current('categories.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 border-indigo-500 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:hover:text-gray-200'" class="block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out">
                        Categories
                    </Link>
                    <Link :href="route('labels.index')" :class="route().current('labels.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 border-indigo-500 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:hover:text-gray-200'" class="block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out">
                        Labels
                    </Link>
                    <Link :href="route('notifications.index')" :class="route().current('notifications.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 border-indigo-500 text-indigo-700 dark:text-indigo-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:text-gray-800 dark:hover:text-gray-200'" class="block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium transition duration-150 ease-in-out">
                        🔔 Notifications
                        <span v-if="unreadCount > 0" class="ml-2 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold text-white bg-red-600 rounded-full">{{ unreadCount }}</span>
                    </Link>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                        <div class="font-medium text-sm text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <!-- Dark Mode Toggle for Mobile -->
                        <div class="flex items-center justify-between ps-3 pe-4 py-2">
                            <span class="text-base font-medium text-gray-600 dark:text-gray-400">Dark Mode</span>
                            <DarkModeToggle />
                        </div>

                        <Link :href="route('logout')" method="post" as="button" class="block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition duration-150 ease-in-out">
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header class="bg-white/50 dark:bg-gray-800/50 shadow-sm border-b border-gray-100 dark:border-gray-700/50 backdrop-blur-md relative z-30" v-if="$slots.header">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main class="relative z-20 pb-12">
            <slot />
        </main>
        
        <!-- Background decorative elements -->
        <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-100/30 dark:bg-indigo-900/10 blur-3xl"></div>
            <div class="absolute top-[20%] -right-[10%] w-[40%] h-[60%] rounded-full bg-purple-100/30 dark:bg-purple-900/10 blur-3xl"></div>
        </div>
    </div>
</template>
