import { createInertiaApp } from '@inertiajs/vue3';
import { route, ZiggyVue } from 'ziggy-js';

import { initializeTheme } from '@/composables/useAppearance';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SettingsLayout from '@/Layouts/settings/Layout.vue';
import { setSessionExpiredHandler } from '@/lib/axios';
import { initializeFlashToast } from '@/lib/flashToast';
import pinia from '@/stores';
import { useAuthStore } from '@/stores/useAuthStore';

const appName = import.meta.env.VITE_APP_NAME || 'Riraa';

createInertiaApp({
    pages: './Pages',
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('Admin/Auth/'):
                return AuthLayout;
            case name.startsWith('Admin/'):
                return AdminLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    withApp: (app) => {
        app.use(pinia);
        app.use(ZiggyVue);
        app.config.globalProperties.route = route;
        app.provide('route', route);

        const authStore = useAuthStore(pinia);

        setSessionExpiredHandler(() => authStore.setUser(null));
        void authStore.fetchProfile();
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
