<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SidebarMenuItem from './SidebarMenuItem.vue';

const props = defineProps({
    currentRoute: {
        type: String,
        default: 'overview',
    },
});

const page = usePage();

const canViewEmployee = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.role === 'superadmin') return true;
    return Boolean(page.props.auth?.permissions?.can_view_employee ?? user.can_view_employee ?? false);
});

const isMobileSubMenuOpen = ref(false);
const mobileActiveTab = ref(props.currentRoute === 'overview' ? 'overview'
    : ['students', 'staff', 'administration', 'statistics'].includes(props.currentRoute) ? 'data'
    : props.currentRoute);

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
    <!-- Desktop Sidebar: Hidden on mobile, visible on md+ -->
    <nav class="hidden md:flex bg-emerald-900 border-r border-emerald-800 h-screen w-64 fixed left-0 top-0 flex-col p-3.5 z-40 select-none shadow-sm">
        <!-- Brand Header -->
        <div class="px-2 pt-2 pb-5 border-b border-emerald-800/80">
            <Link href="/beranda" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 rounded-xl bg-yellow-200 flex items-center justify-center text-emerald-950 shadow-sm shrink-0 group-hover:scale-105 transition-transform duration-200">
                    <span class="material-symbols-outlined filled-icon text-[22px]">school</span>
                </div>
                <div class="min-w-0">
                    <h2 class="text-white leading-tight text-[13.5px] font-bold truncate">Yayasan Li Ulil Albab</h2>
                    <p class="text-[11px] text-emerald-200/90 font-medium truncate">Karanganyar</p>
                </div>
            </Link>
        </div>

        <!-- Navigation Categories & Links -->
        <div class="flex-grow overflow-y-auto custom-scrollbar px-1 py-3 space-y-4">
            <!-- Category: WORKSPACE -->
            <div>
                <div class="px-3 pb-1 text-[11px] font-bold text-emerald-300/90 tracking-wider uppercase">
                    Workspace
                </div>
                <ul class="space-y-0.5">
                    <SidebarMenuItem
                        href="/beranda"
                        icon="grid_view"
                        label="Beranda"
                        :active="currentRoute === 'overview'"
                    />
                </ul>
            </div>

            <!-- Category: DATABASE -->
            <div>
                <div class="px-3 pb-1 text-[11px] font-bold text-emerald-300/90 tracking-wider uppercase">
                    Database
                </div>
                <ul class="space-y-0.5">
                    <SidebarMenuItem
                        href="/students"
                        icon="groups"
                        label="Data Siswa"
                        :active="currentRoute === 'students'"
                    />
                    <SidebarMenuItem
                        v-if="canViewEmployee"
                        href="/staff"
                        icon="badge"
                        label="Data Pegawai"
                        :active="currentRoute === 'staff'"
                    />
                    <SidebarMenuItem
                        href="/administration"
                        icon="description"
                        label="Administrasi"
                        :active="currentRoute === 'administration'"
                    />
                    <SidebarMenuItem
                        href="/statistics"
                        icon="bar_chart"
                        label="Statistik"
                        :active="currentRoute === 'statistics'"
                    />
                    <SidebarMenuItem
                        href="https://assetulil.com"
                        icon="inventory_2"
                        label="Aset"
                        :external="true"
                    />
                </ul>
            </div>

            <!-- Category: AKADEMIK -->
            <div>
                <div class="px-3 pb-1 text-[11px] font-bold text-emerald-300/90 tracking-wider uppercase">
                    Akademik
                </div>
                <ul class="space-y-0.5">
                    <SidebarMenuItem
                        href="/pembelajaran/kelas"
                        icon="menu_book"
                        label="Pembelajaran"
                        :active="currentRoute === 'pembelajaran'"
                    />
                    <SidebarMenuItem
                        href="#"
                        icon="diversity_3"
                        label="Kesiswaan"
                        :active="currentRoute === 'kesiswaan'"
                    />
                </ul>
            </div>

            <!-- Category: SISTEM -->
            <div>
                <div class="px-3 pb-1 text-[11px] font-bold text-emerald-300/90 tracking-wider uppercase">
                    Sistem
                </div>
                <ul class="space-y-0.5">
                    <SidebarMenuItem
                        href="/portal"
                        icon="settings"
                        label="Pengaturan"
                        :active="currentRoute === 'portal'"
                    />
                    <li>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group text-emerald-100/90 hover:text-rose-200 hover:bg-rose-900/40 font-medium active:scale-[0.98]"
                        >
                            <div class="flex items-center space-x-3 min-w-0">
                                <span class="material-symbols-outlined text-[20px] text-emerald-300 group-hover:text-rose-300 transition-colors duration-150 shrink-0">
                                    logout
                                </span>
                                <span class="text-[13.5px] truncate leading-none">Logout</span>
                            </div>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-slate-200/80 safe-area-bottom">

        <!-- Sub-menu panel (slides up from above the nav bar) -->
        <Transition
            enter-active-class="transition-all duration-250 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-48 opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="max-h-48 opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-show="isMobileSubMenuOpen" class="overflow-hidden bg-white rounded-t-3xl border-t border-slate-200/80 pb-2 pt-1 relative -top-[1px]">
                <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto my-2"></div>
                <div class="flex items-center justify-around px-4 py-2 gap-2">
                    <Link
                        href="/students"
                        @click="closeMobileSubMenu"
                        :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'students' ? 'text-emerald-950 bg-emerald-100 font-bold' : 'text-slate-500 active:text-emerald-900']"
                    >
                        <span class="material-symbols-outlined text-[20px]" :class="{ 'filled-icon': currentRoute === 'students' }">groups</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Data Siswa</span>
                    </Link>
                    <Link
                        v-if="canViewEmployee"
                        href="/staff"
                        @click="closeMobileSubMenu"
                        :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'staff' ? 'text-emerald-950 bg-emerald-100 font-bold' : 'text-slate-500 active:text-emerald-900']"
                    >
                        <span class="material-symbols-outlined text-[20px]" :class="{ 'filled-icon': currentRoute === 'staff' }">badge</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Data Pegawai</span>
                    </Link>
                    <Link
                        href="/administration"
                        @click="closeMobileSubMenu"
                        :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'administration' ? 'text-emerald-950 bg-emerald-100 font-bold' : 'text-slate-500 active:text-emerald-900']"
                    >
                        <span class="material-symbols-outlined text-[20px]" :class="{ 'filled-icon': currentRoute === 'administration' }">description</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Administrasi</span>
                    </Link>
                    <Link
                        href="/statistics"
                        @click="closeMobileSubMenu"
                        :class="['flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all', currentRoute === 'statistics' ? 'text-emerald-950 bg-emerald-100 font-bold' : 'text-slate-500 active:text-emerald-900']"
                    >
                        <span class="material-symbols-outlined text-[20px]" :class="{ 'filled-icon': currentRoute === 'statistics' }">bar_chart</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Statistik</span>
                    </Link>
                    <a
                        href="https://assetulil.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click="closeMobileSubMenu"
                        class="flex flex-col items-center justify-center px-3 py-2 rounded-xl flex-1 transition-all text-slate-500 active:text-emerald-900"
                    >
                        <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                        <span class="text-[10px] font-semibold mt-1 leading-tight">Aset</span>
                    </a>
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
                :class="mobileActiveTab === 'overview' ? 'text-emerald-900' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'overview' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'overview' }">grid_view</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'overview' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Beranda</span>
            </Link>

            <!-- Database -->
            <button
                @click="handleMobileTab('data')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group cursor-pointer"
                :class="mobileActiveTab === 'data' || ['students', 'staff', 'administration', 'statistics'].includes(currentRoute) ? 'text-emerald-900' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'data' || ['students', 'staff', 'administration', 'statistics'].includes(currentRoute) ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'data' || ['students', 'staff', 'administration', 'statistics'].includes(currentRoute) }">folder</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'data' || ['students', 'staff', 'administration', 'statistics'].includes(currentRoute) ? 'font-bold opacity-100' : 'font-medium opacity-80'">Database</span>
            </button>

            <!-- Pembelajaran -->
            <Link
                href="/pembelajaran/kelas"
                @click="handleMobileTab('pembelajaran')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group"
                :class="mobileActiveTab === 'pembelajaran' ? 'text-emerald-900' : 'text-slate-500 hover:text-emerald-900'"
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
                :class="mobileActiveTab === 'kesiswaan' ? 'text-emerald-900' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'kesiswaan' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'kesiswaan' }">diversity_3</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'kesiswaan' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Kesiswaan</span>
            </Link>

            <!-- Manajemen / Pengaturan -->
            <Link
                href="/portal"
                @click="handleMobileTab('manajemen')"
                class="flex flex-col items-center justify-center w-full py-1.5 transition-all duration-300 relative group"
                :class="mobileActiveTab === 'manajemen' || currentRoute === 'portal' ? 'text-emerald-900' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-300" :class="mobileActiveTab === 'manajemen' || currentRoute === 'portal' ? 'bg-emerald-100' : 'bg-transparent'">
                    <span class="material-symbols-outlined text-[22px] transition-all duration-300 group-active:scale-90" :class="{ 'filled-icon': mobileActiveTab === 'manajemen' || currentRoute === 'portal' }">settings</span>
                </div>
                <span class="text-[10px] mt-1 transition-all duration-300" :class="mobileActiveTab === 'manajemen' || currentRoute === 'portal' ? 'font-bold opacity-100' : 'font-medium opacity-80'">Pengaturan</span>
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

/* Custom Scrollbar for Sidebar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #047857 transparent; /* emerald-700 */
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #047857; /* emerald-700 */
    border-radius: 9999px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #059669; /* emerald-600 */
}
</style>
