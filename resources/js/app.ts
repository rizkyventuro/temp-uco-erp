import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import '../css/app.css';
import AppWrapper from './components/AppWrapper.vue';
import { initializeTheme } from './composables/useAppearance';
import axios from 'axios';

// Configure Axios Timeout
axios.defaults.timeout = 60000; // 1 minute
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (
            error.code === 'ECONNABORTED' &&
            error.message.includes('timeout')
        ) {
            window.dispatchEvent(new Event('app:timeout'));
        }
        return Promise.reject(error);
    },
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(AppWrapper, { App, props }) });
        app.use(plugin);
        app.component('Toaster', Toaster);
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
