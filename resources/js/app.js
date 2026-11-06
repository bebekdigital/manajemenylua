import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import AppLayout from './Layouts/AppLayout.vue';
import { usePwaInstall } from './Composables/usePwaInstall';

// Pastikan tema status bar mobile selalu putih saat berpindah halaman SPA
router.on('navigate', () => {
    if (typeof document !== 'undefined') {
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta && meta.getAttribute('content') !== '#ffffff') {
            meta.setAttribute('content', '#ffffff');
        }
    }
});

createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        const page = await pages[`./Pages/${name}.vue`]();

        // Use AppLayout as default layout if not explicitly set
        if (page && page.default && page.default.layout === undefined) {
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
