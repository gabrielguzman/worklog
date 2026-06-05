import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { useDarkMode } from './composables/useDarkMode';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize dark mode IMMEDIATELY on script load (before Vue app mounts)
console.log('🌙 Initializing dark mode...');
try {
    const { initTheme } = useDarkMode();
    initTheme();
    console.log('🌙 Dark mode initialized successfully');
} catch (e) {
    console.error('🌙 Error initializing dark mode:', e);
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
