<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import SidebarNav from '@/Components/Sidebar/SidebarNav.vue';
import TopNav from '@/Components/TopNav/TopNav.vue';
import ForceChangePasswordModal from '@/Components/Shared/ForceChangePasswordModal.vue';
import PwaInstallModal from '@/Components/Shared/PwaInstallModal.vue';
import GlobalPageSkeleton from '@/Components/Shared/GlobalPageSkeleton.vue';

// Skeleton Loading State saat perpindahan halaman
const isPageLoading = ref(false);
const skeletonType = ref('general');
let loadingTimer = null;

let removeStartListener = null;
let removeFinishListener = null;

onMounted(() => {
    removeStartListener = router.on('start', (event) => {
        const visitUrl = event.detail.visit.url.pathname || event.detail.visit.url.toString() || '';
        if (visitUrl.match(/\/staff\/\d+/) || visitUrl.match(/\/students\/[^\/]+/)) {
            skeletonType.value = 'detail';
        } else if (visitUrl.includes('/staff') || visitUrl.includes('/students')) {
            skeletonType.value = 'table';
        } else {
            skeletonType.value = 'general';
        }

        // Tunda 60ms agar transisi instan dari cache tidak berkedip
        clearTimeout(loadingTimer);
        loadingTimer = setTimeout(() => {
            isPageLoading.value = true;
        }, 60);
    });

    removeFinishListener = router.on('finish', () => {
        clearTimeout(loadingTimer);
        isPageLoading.value = false;
    });
});

onUnmounted(() => {
    clearTimeout(loadingTimer);
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
});

// Auto-detect active route from URL
const page = usePage();
const activeRoute = computed(() => {
    const url = page.url;
    if (url === '/' || url === '' || url.startsWith('/beranda')) return 'overview';
    if (url.startsWith('/agenda')) return 'agenda';
    if (url.startsWith('/info')) return 'info';
    if (url.startsWith('/persetujuan')) return 'persetujuan';
    if (url.startsWith('/students/statistics') || url === '/statistics') return 'student-statistics';
    if (url.startsWith('/students')) return 'students';
    if (url.startsWith('/administration')) return 'administration';
    if (url.startsWith('/staff/statistics')) return 'staff-statistics';
    if (url.startsWith('/staff')) return 'staff';
    if (url.startsWith('/portal')) return 'portal';
    if (url.startsWith('/pembelajaran')) return 'pembelajaran';
    if (url.startsWith('/kesiswaan')) return 'kesiswaan';
    if (url.startsWith('/profile')) return 'profile';
    if (url.startsWith('/section')) return 'section';
    return 'overview';
});

const activeRouteLabel = computed(() => {
    const route = activeRoute.value;
    if (route === 'overview') return 'Beranda';
    if (route === 'agenda') return 'Agenda Kegiatan';
    if (route === 'info') return 'Pusat Informasi';
    if (route === 'persetujuan') return 'Persetujuan Perizinan';
    if (route === 'student-statistics') return 'Statistik Siswa';
    if (route === 'students') return 'Data Siswa';
    if (route === 'administration') return 'Administrasi & Cetak Dokumen';
    if (route === 'staff-statistics') return 'Statistik Pegawai';
    if (route === 'staff') return 'Data Pegawai';
    if (route === 'portal') return 'Pengaturan';
    if (route === 'pembelajaran') return 'Pembelajaran';
    if (route === 'kesiswaan') return 'Kesiswaan';
    if (route === 'profile') return 'Profil Saya';
    if (route === 'section') return 'Kategori Menu';
    return 'Beranda';
});
</script>

<template>
    <div class="bg-white text-slate-900 font-body-md min-h-screen flex flex-col md:flex-row antialiased">
        <!-- Persistent Sidebar (Desktop Only) -->
        <SidebarNav :current-route="activeRoute" />

        <!-- Main Workspace Area (pb-36 di mobile untuk memastikan konten paling bawah tidak tertutup floating bottom nav) -->
        <div class="flex-grow md:ml-64 flex flex-col min-h-screen transition-all duration-300 min-w-0 overflow-x-hidden pb-36 md:pb-0 bg-white">
            <!-- Sticky Header -->
            <TopNav
                :page-title="activeRouteLabel"
                :is-home-overlay="activeRoute === 'overview'"
                :hide-mobile="activeRoute === 'staff' || activeRoute === 'students'"
            />

            <!-- Page Slot -->
            <main class="flex-grow relative bg-white">
                <!-- Skeleton Transition Overlay saat proses loading/delay -->
                <Transition
                    enter-active-class="transition-opacity duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="isPageLoading"
                        class="absolute inset-0 z-30 bg-white p-4 md:p-margin-desktop overflow-y-auto"
                    >
                        <GlobalPageSkeleton :type="skeletonType" />
                    </div>
                </Transition>

                <slot />
            </main>
        </div>

        <!-- Locked Modal: Force Change Password after Reset by SuperAdmin -->
        <ForceChangePasswordModal :show="Boolean(page.props.auth?.user?.must_change_password)" />

        <!-- PWA Install Guide Modal -->
        <PwaInstallModal />
    </div>
</template>

