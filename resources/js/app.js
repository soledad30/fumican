import './bootstrap';
import '../css/app.css';
import '../css/theme.css';
import '../css/vet-theme.css';
import '../css/theme-bridge.css';
import '../css/theme-contrast.css';
import '../css/ninos-overrides.css';
import '../css/jovenes-overrides.css';
import '../css/adultos-overrides.css';
import { applyThemeEarly } from '@/Composables/useTheme';
import { initAccessibilityEarly } from '@/Composables/useAccessibility';

applyThemeEarly();
initAccessibilityEarly();
import '../../node_modules/flowbite-vue/dist/index.css'
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import axios from 'axios' 
window.axios = axios 
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#059669',
    },
});
