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
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

function resolveAppUrl(href) {
    if (typeof href !== 'string' || /^https?:\/\//i.test(href)) {
        return href;
    }

    const base = (window.Ziggy?.url ?? '').replace(/\/$/, '');
    if (base && href.startsWith('/')) {
        return `${base}${href}`;
    }

    return href;
}

const originalVisit = router.visit.bind(router);
router.visit = (href, options = {}) => {
    if (typeof href === 'string') {
        return originalVisit(resolveAppUrl(href), options);
    }

    if (href && typeof href === 'object' && typeof href.url === 'string') {
        return originalVisit({ ...href, url: resolveAppUrl(href.url) }, options);
    }

    return originalVisit(href, options);
};
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
