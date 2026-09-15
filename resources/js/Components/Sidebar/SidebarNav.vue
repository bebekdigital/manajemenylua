<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import SidebarMenuItem from './SidebarMenuItem.vue';

const props = defineProps({
    currentRoute: {
        type: String,
        default: 'overview',
    },
});

const isDataMenuOpen = ref(false);
const isMobileSubMenuOpen = ref(false);
// Track which mobile item is "active/selected" for the label reveal effect
const mobileActiveTab = ref(props.currentRoute === 'overview' ? 'overview'
    : ['students', 'staff', 'administration'].includes(props.currentRoute) ? 'data'
    : props.currentRoute);

function toggleDataMenu() {
    isDataMenuOpen.value = !isDataMenuOpen.value;
}

function handleMobileTab(tabId) {
    if (tabId === 'data') {
        isMobileSubMenuOpen.value = mobileActiveTab.value !== 'data' ? true : !isMobileSubMenuOpen.value;
    } else {
        isMobileSubMenuOpen.value = false;
    }
    mobileActiveTab.value = tabId;
}

function closeMobileSubMenu() {
    isMobileSubMenuOpen.value = false;
}
</script>

<template>
    <!-- Sidebar: Hidden on mobile, visible on md+ -->
    <nav class="hidden md:flex bg-emerald-900 border-r border-emerald-800 h-screen w-64 fixed left-0 top-0 flex-col p-4 space-y-2 z-40">
        <!-- Brand Header -->
        <div class="mb-8 px-2 flex items-center space-x-3 mt-4">
            <div class="w-10 h-10 rounded-full bg-yellow-200 flex items-center justify-center text-emerald-900 shadow-sm shrink-0">
                <span class="material-symbols-outlined filled-icon">school</span>
            </div>
            <div>
                <h2 class="text-white leading-tight text-[15px] font-bold">Yayasan Li Ulil Albab Karanganyar</h2>
            </div>
        </div>

        <!-- Main Navigation Links -->
        <ul class="flex-grow space-y-1 overflow-y-auto">
            <!-- Beranda -->
            <SidebarMenuItem href="/beranda" icon="home" label="Beranda" :active="currentRoute === 'overview'" />

            <!-- Database (with sub-menu) -->
            <li>
                <button
                    @click="toggleDataMenu"
                    :class="[
                        'w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all cursor-pointer active:scale-95 duration-200',
                        ['students', 'staff', 'administration'].includes(currentRoute)
                            ? 'bg-emerald-800 text-yellow-200 font-semibold shadow-sm'
                            : 'text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 font-medium'
                    ]"
                >
                    <div class="flex items-center space-x-3">
                        <span class="material-symbols-outlined text-[22px] transition-all duration-200" :class="{ 'filled-icon': ['students', 'staff', 'administration'].includes(currentRoute) }">folder</span>
                        <span>Database</span>
                    </div>
                    <span class="material-symbols-outlined text-[18px] transition-transform duration-200" :class="{ 'rotate-180': isDataMenuOpen, 'filled-icon': ['students', 'staff', 'administration'].includes(currentRoute) }">expand_more</span>
                </button>

                <!-- Sub Menu -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-48 opacity-100"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="max-h-48 opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <ul v-show="isDataMenuOpen || ['students', 'staff', 'administration'].includes(currentRoute)" class="mt-1 ml-4 pl-4 border-l border-emerald-700 space-y-0.5 overflow-hidden">
                        <li>
                            <Link href="/students" :class="['flex items-center space-x-2.5 px-3 py-2 rounded-lg transition-all cursor-pointer active:scale-95 duration-200 text-[13px]', currentRoute === 'students' ? 'bg-emerald-800 text-yellow-200 font-semibold shadow-sm' : 'text-emerald-100/60 hover:bg-emerald-800 hover:text-yellow-200 font-medium']">
                                <span class="material-symbols-outlined text-[17px] transition-all duration-200" :class="{ 'filled-icon': currentRoute === 'students' }">groups</span>
                                <span>Data Siswa</span>
                            </Link>
                        </li>
                        <li>
                            <a href="#" :class="['flex items-center space-x-2.5 px-3 py-2 rounded-lg transition-all cursor-pointer active:scale-95 duration-200 text-[13px]', currentRoute === 'staff' ? 'bg-emerald-800 text-yellow-200 font-semibold shadow-sm' : 'text-emerald-100/60 hover:bg-emerald-800/50 hover:text-white font-medium']">
                                <span class="material-symbols-outlined text-[17px] transition-all duration-200" :class="{ 'filled-icon': currentRoute === 'staff' }">badge</span>
                                <span>Data Pegawai</span>
                            </a>
                        </li>
                        <li>
                            <Link href="/administration" :class="['flex items-center space-x-2.5 px-3 py-2 rounded-lg transition-all cursor-pointer active:scale-95 duration-200 text-[13px]', currentRoute === 'administration' ? 'bg-emerald-800 text-yellow-200 font-semibold shadow-sm' : 'text-emerald-100/60 hover:bg-emerald-800 hover:text-yellow-200 font-medium']">
                                <span class="material-symbols-outlined text-[17px] transition-all duration-200" :class="{ 'filled-icon': currentRoute === 'administration' }">description</span>
                                <span>Administrasi</span>
                            </Link>
                        </li>
                    </ul>
                </Transition>
            </li>

            <!-- Pembelajaran -->
            <SidebarMenuItem href="#" icon="menu_book" label="Pembelajaran" :active="currentRoute === 'pembelajaran'" />

            <!-- Kesiswaan -->
            <SidebarMenuItem href="#" icon="diversity_3" label="Kesiswaan" :active="currentRoute === 'kesiswaan'" />

            <!-- Manajemen -->
            <SidebarMenuItem href="#" icon="admin_panel_settings" label="Manajemen" :active="currentRoute === 'manajemen'" />
        </ul>

        <!-- Bottom Action & Utilities -->
        <div class="mt-auto pt-4 border-t border-emerald-800 space-y-2">
            <button class="w-full bg-yellow-200 text-emerald-900 py-2 px-4 rounded-full hover:bg-yellow-300 transition-colors mb-4 flex items-center justify-center space-x-2 shadow-sm cursor-pointer font-semibold text-sm">
                <span class="material-symbols-outlined text-[18px] filled-icon">add_circle</span>
                <span>Generate Report</span>
            </button>
            <ul class="space-y-1">
                <li>
                    <Link href="/portal" class="flex items-center space-x-3 px-4 py-2 text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 rounded-xl transition-colors duration-200 cursor-pointer font-medium text-sm">
                        <span class="material-symbols-outlined text-[20px] filled-icon">settings</span>
                        <span>Pengaturan</span>
                    </Link>
                </li>
                <li>
                    <Link href="/logout" method="post" as="button" class="w-full flex items-center space-x-3 px-4 py-2 text-emerald-100/80 hover:bg-emerald-800 hover:text-yellow-200 rounded-xl transition-colors duration-200 cursor-pointer font-medium text-sm">
                        <span class="material-symbols-outlined text-[20px] filled-icon">logout</span>
                        <span>Logout</span>
                    </Link>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-outline-variant/30 safe-area-bottom">

        <!-- Sub-menu panel (slides up from above the nav bar) -->
        <Transition
            enter-active-class="transition-all duration-250 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-48 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="max-h-48 opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-show="isMobileSubMenuOpen" class="overflow-hidden bg-white rounded-t-3xl border-t border-outline-variant/30 pb-2 pt-1 relative -top-[1px]">
                <div class="w-12 h-1 bg-outline-variant/40 rounded-full mx-auto my-2"></div>
                <div class="flex items-center justify-around px-4 py-2 gap-2">
                    <Link
                        href="/students"
                        @click="closeMobileSubMenu"
                        :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'students' ? 'text-emerald-900 bg-emerald-50' : 'text-on-surface-variant active:text-emerald-900']"
                    >
                        <span class="material-symbols-outlined text-[20px] filled-icon">groups</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Data Siswa</span>
                    </Link>
                    <a href="#" :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'staff' ? 'text-emerald-900 bg-emerald-50' : 'text-on-surface-variant active:text-emerald-900']">
                        <span class="material-symbols-outlined text-[20px] filled-icon">badge</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Data Pegawai</span>
                    </a>
                    <Link
                        href="/administration"
                        @click="closeMobileSubMenu"
                        :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'administration' ? 'text-emerald-900 bg-emerald-50' : 'text-on-surface-variant active:text-emerald-900']"
                    >
                        <span class="material-symbols-outlined text-[20px] filled-icon">description</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Administrasi</span>
                    </Link>
                </div>
            </div>
        </Transition>

        <!-- Main bottom nav items -->
        <div class="flex items-center justify-around px-2 py-2 gap-1 bg-white relative z-10">

            <!-- Beranda -->
            <Link
                href="/beranda"
                @click="handleMobileTab('overview')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group"
                :class="mobileActiveTab === 'overview' ? 'text-emerald-900' : 'text-on-surface-variant hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'overview' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'overview' }">home</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'overview' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Beranda</span>
            </Link>

            <!-- Database -->
            <button
                @click="handleMobileTab('data')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group cursor-pointer"
                :class="mobileActiveTab === 'data' || ['students', 'staff', 'administration'].includes(currentRoute) ? 'text-emerald-900' : 'text-on-surface-variant hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'data' || ['students', 'staff', 'administration'].includes(currentRoute) ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'data' || ['students', 'staff', 'administration'].includes(currentRoute) }">folder</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'data' || ['students', 'staff', 'administration'].includes(currentRoute) ? 'font-bold opacity-100' : 'font-medium opacity-80'">Database</span>
            </button>

            <!-- Pembelajaran -->
            <Link
                href="#"
                @click="handleMobileTab('pembelajaran')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group"
                :class="mobileActiveTab === 'pembelajaran' ? 'text-emerald-900' : 'text-on-surface-variant hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'pembelajaran' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'pembelajaran' }">menu_book</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'pembelajaran' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Belajar</span>
            </Link>

            <!-- Kesiswaan -->
            <Link
                href="#"
                @click="handleMobileTab('kesiswaan')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group"
                :class="mobileActiveTab === 'kesiswaan' ? 'text-emerald-900' : 'text-on-surface-variant hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'kesiswaan' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'kesiswaan' }">diversity_3</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'kesiswaan' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Kesiswaan</span>
            </Link>

            <!-- Manajemen -->
            <Link
                href="#"
                @click="handleMobileTab('manajemen')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group"
                :class="mobileActiveTab === 'manajemen' ? 'text-emerald-900' : 'text-on-surface-variant hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'manajemen' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'manajemen' }">admin_panel_settings</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'manajemen' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Manajemen</span>
            </Link>

        </div>
    </nav>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
.filled-icon {
    font-variation-settings: 'FILL' 1;
}
</style>
