import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { usePwaInstall } from './Composables/usePwaInstall';
import Login from './Pages/Auth/Login.vue';

// Pastikan tema status bar mobile selalu putih saat berpindah halaman SPA
router.on('navigate', () => {
    if (typeof document !== 'undefined') {
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta && meta.getAttribute('content') !== '#ffffff') {
            meta.setAttribute('content', '#ffffff');
        }
    }
});

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    resolve: async (name) => {
        // Eager-resolve Login page to eliminate secondary roundtrip on initial landing
        if (name === 'Auth/Login') {
            return Login;
        }

        const page = await pages[`./Pages/${name}.vue`]();

        // Use AppLayout as default layout if not explicitly set
        if (page && page.default && page.default.layout === undefined) {
            const AppLayout = (await import('./Layouts/AppLayout.vue')).default;
            page.default.layout = AppLayout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        // Inisialisasi Service Worker & event beforeinstallprompt
        usePwaInstall().initPwa();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
