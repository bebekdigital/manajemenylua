<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import SidebarMenuItem from './SidebarMenuItem.vue';
import { usePendingApprovals } from '@/Composables/usePendingApprovals';

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

const {
    pendingApprovalsCount,
} = usePendingApprovals();

function isRouteActive(prefix) {
    if (prefix === '/beranda') {
        return page.url === '/' || page.url.startsWith('/beranda');
    }
    return page.url.startsWith(prefix);
}
</script>

<template>
    <!-- Desktop Sidebar: Hidden on mobile, visible on md+ -->
    <nav class="hidden md:flex bg-emerald-900 border-r border-emerald-800 h-screen w-64 fixed left-0 top-0 flex-col p-3.5 z-40 select-none shadow-sm">
        <!-- Brand Header -->
        <div class="px-2 pt-2 pb-5 border-b border-emerald-800/80 shrink-0">
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

        <!-- Navigation Categories & Links (Kategori Utama dengan Garis Pemisah) -->
        <div class="flex-grow overflow-y-auto custom-scrollbar px-1 py-3 space-y-3">
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

            <!-- Garis Pemisah Kategori -->
            <div class="h-[1px] bg-emerald-800/80 mx-2"></div>

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

            <!-- Garis Pemisah Kategori -->
            <div class="h-[1px] bg-emerald-800/80 mx-2"></div>

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
        </div>

        <!-- Bottom Pinned Section: Pengaturan & Logout (Terpisah di bagian bawah) -->
        <div class="pt-3 border-t border-emerald-800/80 mt-auto shrink-0 px-1">
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
    </nav>





    <!-- Mobile Bottom Navigation Bar (Beranda, Agenda, Info, Persetujuan) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200/90 safe-area-bottom shadow-lg">
        <div class="flex items-center justify-around px-2 py-1.5 gap-1 bg-white">

            <!-- Beranda -->
            <Link
                href="/beranda"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group"
                :class="isRouteActive('/beranda') ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200"
                    :class="isRouteActive('/beranda') ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': isRouteActive('/beranda') }"
                    >
                        grid_view
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Beranda</span>
            </Link>

            <!-- Agenda -->
            <Link
                href="/agenda"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                :class="isRouteActive('/agenda') ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200"
                    :class="isRouteActive('/agenda') ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': isRouteActive('/agenda') }"
                    >
                        calendar_month
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Agenda</span>
            </Link>

            <!-- Info -->
            <Link
                href="/info"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                :class="isRouteActive('/info') ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200"
                    :class="isRouteActive('/info') ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': isRouteActive('/info') }"
                    >
                        campaign
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Info</span>
            </Link>

            <!-- Persetujuan -->
            <Link
                href="/persetujuan"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                :class="isRouteActive('/persetujuan') ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200 relative"
                    :class="isRouteActive('/persetujuan') ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': isRouteActive('/persetujuan') }"
                    >
                        task_alt
                    </span>

                    <!-- Badge Realtime Pengajuan Pending (Bulat Sempurna tanpa kedap-kedip) -->
                    <span
                        v-if="pendingApprovalsCount > 0"
                        class="absolute -top-1 -right-0.5 w-[18px] h-[18px] min-w-[18px] min-h-[18px] aspect-square rounded-full text-[10px] font-bold bg-rose-600 text-white flex items-center justify-center shadow-xs ring-2 ring-white"
                    >
                        {{ pendingApprovalsCount }}
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Persetujuan</span>
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
