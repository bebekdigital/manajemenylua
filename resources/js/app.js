import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from './Layouts/AppLayout.vue';
import { usePwaInstall } from './Composables/usePwaInstall';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];

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
