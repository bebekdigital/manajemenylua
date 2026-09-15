<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SidebarNav from '@/Components/Sidebar/SidebarNav.vue';
import TopNav from '@/Components/TopNav/TopNav.vue';

// Auto-detect active route from URL
const page = usePage();
const activeRoute = computed(() => {
    const url = page.url;
    if (url === '/' || url === '' || url.startsWith('/beranda')) return 'overview';
    if (url.startsWith('/students')) return 'students';
    if (url.startsWith('/administration')) return 'administration';
    if (url.startsWith('/staff')) return 'staff';
    if (url.startsWith('/portal')) return 'portal';
    if (url.startsWith('/pembelajaran')) return 'pembelajaran';
    if (url.startsWith('/kesiswaan')) return 'kesiswaan';
    if (url.startsWith('/profile')) return 'profile';
    return 'overview';
});

const activeRouteLabel = computed(() => {
    const route = activeRoute.value;
    if (route === 'overview') return 'Beranda';
    if (route === 'students') return 'Data Siswa';
    if (route === 'administration') return 'Administrasi & Cetak Dokumen';
    if (route === 'staff') return 'Data Pegawai';
    if (route === 'portal') return 'Pengaturan';
    if (route === 'pembelajaran') return 'Pembelajaran';
    if (route === 'kesiswaan') return 'Kesiswaan';
    if (route === 'profile') return 'Profil Saya';
    return 'Beranda';
});
</script>

<template>
    <div class="bg-white text-slate-900 font-body-md min-h-screen flex flex-col md:flex-row antialiased">
        <!-- Persistent Sidebar (Desktop Only) -->
        <SidebarNav :current-route="activeRoute" />

        <!-- Main Workspace Area -->
        <div class="flex-grow md:ml-64 flex flex-col min-h-screen transition-all duration-300 min-w-0 overflow-x-hidden pb-16 md:pb-0">
            <!-- Sticky Header -->
            <TopNav :page-title="activeRouteLabel" />

            <!-- Page Slot -->
            <main class="flex-grow relative z-0">
                <slot />
            </main>
        </div>
    </div>
</template>

