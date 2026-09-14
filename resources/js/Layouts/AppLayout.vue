<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SidebarNav from '@/Components/Sidebar/SidebarNav.vue';
import TopNav from '@/Components/TopNav/TopNav.vue';

const isSidebarOpen = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
    isSidebarOpen.value = false;
};

// Auto-detect active route from URL
const page = usePage();
const activeRoute = computed(() => {
    const url = page.url;
    if (url === '/' || url === '') return 'overview';
    if (url.startsWith('/students')) return 'students';
    if (url.startsWith('/administration')) return 'administration';
    if (url.startsWith('/staff')) return 'staff';
    if (url.startsWith('/portal')) return 'portal';
    if (url.startsWith('/analytics')) return 'analytics';
    if (url.startsWith('/settings')) return 'settings';
    return 'overview';
});

const activeRouteLabel = computed(() => {
    const route = activeRoute.value;
    if (route === 'overview') return 'Overview';
    if (route === 'students') return 'Student Data';
    if (route === 'administration') return 'Administrasi & Cetak Dokumen';
    if (route === 'staff') return 'Staff Records';
    if (route === 'portal') return 'Manajemen Portal';
    if (route === 'analytics') return 'Analytics';
    if (route === 'settings') return 'Settings';
    return 'Overview';
});
</script>

<template>
    <div class="bg-gray-100 text-on-background font-body-md min-h-screen flex flex-col md:flex-row antialiased">
        <!-- Persistent Sidebar -->
        <SidebarNav :current-route="activeRoute" :is-open="isSidebarOpen" @close="closeSidebar" />

        <!-- Main Workspace Area -->
        <div class="flex-grow md:ml-64 flex flex-col min-h-screen transition-all duration-300 min-w-0 overflow-x-hidden">
            <!-- Sticky Header -->
            <TopNav :page-title="activeRouteLabel" @toggle-sidebar="toggleSidebar" />

            <!-- Page Slot -->
            <main class="flex-grow relative z-0">
                <slot />
            </main>
        </div>
    </div>
</template>
