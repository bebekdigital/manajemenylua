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

// Mobile Bottom Navigation Sheets: 'agenda' | 'info' | 'persetujuan' | null
const mobileActiveSheet = ref(null);

const {
    pendingApprovalsCount,
    pendingApprovalsList,
    fetchPendingApprovals,
} = usePendingApprovals();

function toggleMobileSheet(sheet) {
    if (mobileActiveSheet.value === sheet) {
        mobileActiveSheet.value = null;
    } else {
        mobileActiveSheet.value = sheet;
        if (sheet === 'persetujuan') {
            fetchPendingApprovals();
        }
    }
}

function closeMobileSheet() {
    mobileActiveSheet.value = null;
}

function handleKeydown(event) {
    if (event.key === 'Escape' && mobileActiveSheet.value) {
        closeMobileSheet();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
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

    <!-- Mobile Bottom Navigation Backdrop Overlay -->
    <Transition
        enter-active-class="transition-opacity duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="mobileActiveSheet"
            @click="closeMobileSheet"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 md:hidden"
        ></div>
    </Transition>

    <!-- Mobile Drawer / Bottom Sheet (Agenda, Info, Persetujuan) -->
    <Transition
        enter-active-class="transition-all duration-250 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="mobileActiveSheet"
            class="fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-3xl border-t border-slate-200/90 shadow-2xl overflow-hidden flex flex-col md:hidden max-h-[82vh] safe-area-bottom pb-4"
        >
            <!-- Pull bar indicator -->
            <div class="w-12 h-1 bg-slate-200 rounded-full mx-auto my-2.5 shrink-0"></div>

            <!-- Sheet Content: AGENDA -->
            <div v-if="mobileActiveSheet === 'agenda'" class="flex flex-col h-full overflow-hidden">
                <div class="flex items-center justify-between px-5 pb-3 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200/60">
                            <span class="material-symbols-outlined text-[19px]">calendar_month</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 leading-tight">Agenda Kegiatan</h4>
                            <p class="text-[11px] text-slate-400">Jadwal kegiatan akademik & yayasan</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeMobileSheet"
                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                    >
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="p-4 overflow-y-auto space-y-2.5">
                    <div class="p-3 rounded-2xl bg-gradient-to-br from-emerald-50/60 to-amber-50/40 border border-emerald-100 flex items-start gap-3">
                        <div class="flex flex-col items-center justify-center w-10 h-10 rounded-xl bg-white border border-emerald-200/60 shadow-2xs shrink-0">
                            <span class="text-[8px] font-bold text-amber-600 uppercase">Nov</span>
                            <span class="text-sm font-extrabold text-slate-900 leading-none">10</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <h5 class="font-bold text-slate-900 text-xs truncate">Rapat Koordinasi Awal Bulan</h5>
                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-blue-100 text-blue-700 border border-blue-200 shrink-0">Rapat</span>
                            </div>
                            <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                <span>08:00 - 10:00 WIB</span> • <span class="truncate">Ruang Rapat Utama</span>
                            </p>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-gradient-to-br from-emerald-50/60 to-amber-50/40 border border-emerald-100 flex items-start gap-3">
                        <div class="flex flex-col items-center justify-center w-10 h-10 rounded-xl bg-white border border-emerald-200/60 shadow-2xs shrink-0">
                            <span class="text-[8px] font-bold text-amber-600 uppercase">Nov</span>
                            <span class="text-sm font-extrabold text-slate-900 leading-none">15</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <h5 class="font-bold text-slate-900 text-xs truncate">Pelaksanaan UTS Gasal</h5>
                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-700 border border-emerald-200 shrink-0">Akademik</span>
                            </div>
                            <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                <span>15 - 20 Nov 2023</span> • <span class="truncate">Seluruh Unit Kelas</span>
                            </p>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-gradient-to-br from-emerald-50/60 to-amber-50/40 border border-emerald-100 flex items-start gap-3">
                        <div class="flex flex-col items-center justify-center w-10 h-10 rounded-xl bg-white border border-emerald-200/60 shadow-2xs shrink-0">
                            <span class="text-[8px] font-bold text-amber-600 uppercase">Nov</span>
                            <span class="text-sm font-extrabold text-slate-900 leading-none">25</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <h5 class="font-bold text-slate-900 text-xs truncate">Batas Akhir Input Nilai UTS</h5>
                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-orange-100 text-orange-700 border border-orange-200 shrink-0">Deadline</span>
                            </div>
                            <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                                <span>23:59 WIB</span> • <span class="truncate">Sistem Akademik Online</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sheet Content: INFO -->
            <div v-if="mobileActiveSheet === 'info'" class="flex flex-col h-full overflow-hidden">
                <div class="flex items-center justify-between px-5 pb-3 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-200/60">
                            <span class="material-symbols-outlined text-[19px]">campaign</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 leading-tight">Pusat Informasi</h4>
                            <p class="text-[11px] text-slate-400">Pengumuman & berita resmi sekolah</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeMobileSheet"
                        class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                    >
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="p-4 overflow-y-auto space-y-2.5">
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-bold text-emerald-800 px-2 py-0.5 rounded-full bg-emerald-100/80">Yayasan</span>
                            <span class="text-[10.5px] text-slate-400">Hari ini</span>
                        </div>
                        <h5 class="text-xs font-bold text-slate-900 leading-snug">Pemberitahuan Libur Nasional & Cuti Bersama</h5>
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">Segenap civitas yayasan diharap memperhatikan jadwal operasional sekolah selama masa cuti bersama.</p>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-bold text-blue-800 px-2 py-0.5 rounded-full bg-blue-100/80">IT Support</span>
                            <span class="text-[10.5px] text-slate-400">Kemarin</span>
                        </div>
                        <h5 class="text-xs font-bold text-slate-900 leading-snug">Pembaruan Sistem Manajemen Sekolah v2.4</h5>
                        <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">Fitur perizinan online dan persetujuan atasan realtime telah aktif di dashboard.</p>
                    </div>
                </div>
            </div>

            <!-- Sheet Content: PERSETUJUAN -->
            <div v-if="mobileActiveSheet === 'persetujuan'" class="flex flex-col h-full overflow-hidden">
                <div class="flex items-center justify-between px-5 pb-3 border-b border-slate-100 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200/60">
                            <span class="material-symbols-outlined text-[19px]">task_alt</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 leading-tight">Persetujuan Perizinan</h4>
                            <p class="text-[11px] text-slate-400">Pengajuan izin bawahan menunggu verifikasi</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            :class="[
                                'text-[10.5px] font-bold px-2 py-0.5 rounded-full border',
                                pendingApprovalsCount > 0
                                    ? 'bg-rose-50 text-rose-700 border-rose-200 animate-pulse'
                                    : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                            ]"
                        >
                            {{ pendingApprovalsCount }} Menunggu
                        </span>
                        <button
                            type="button"
                            @click="closeMobileSheet"
                            class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>
                </div>

                <!-- List Item Pengajuan Perizinan -->
                <div v-if="pendingApprovalsList.length > 0" class="p-4 overflow-y-auto space-y-2 max-h-[60vh]">
                    <Link
                        v-for="item in pendingApprovalsList"
                        :key="item.id"
                        :href="item.url"
                        @click="closeMobileSheet"
                        class="p-3 rounded-2xl border border-slate-200/90 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all flex items-start justify-between gap-2.5 group cursor-pointer block bg-white shadow-2xs"
                    >
                        <div class="flex items-start gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-900 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                {{ item.employee_nama ? item.employee_nama.substring(0, 2).toUpperCase() : 'PG' }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900 group-hover:text-emerald-950 truncate">
                                        {{ item.employee_nama }}
                                    </span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium">
                                        {{ item.employee_unit }}
                                    </span>
                                </div>
                                <div class="text-[11.5px] font-semibold text-emerald-800 mt-0.5">
                                    {{ item.jenis }}
                                    <span v-if="item.detail" class="font-normal text-slate-500">({{ item.detail }})</span>
                                </div>
                                <div class="text-[10.5px] text-slate-400 mt-0.5 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">calendar_today</span>
                                    <span>{{ item.tanggal }}</span>
                                    <span v-if="item.created_at_human" class="text-slate-400">• {{ item.created_at_human }}</span>
                                </div>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shrink-0">
                            Diajukan
                        </span>
                    </Link>

                    <p class="text-[10.5px] text-slate-400 text-center pt-2">
                        Ketuk pengajuan untuk membuka tab perizinan pegawai dan memberikan persetujuan.
                    </p>
                </div>

                <!-- Empty State jika tidak ada pengajuan -->
                <div v-else class="py-10 text-center px-4">
                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-2 block">fact_check</span>
                    <p class="text-sm font-semibold text-slate-700">Tidak ada persetujuan tertunda</p>
                    <p class="text-xs text-slate-400 mt-0.5">Semua pengajuan perizinan pegawai telah diproses.</p>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Mobile Bottom Navigation Bar (Beranda, Agenda, Info, Persetujuan) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200/90 safe-area-bottom shadow-lg">
        <div class="flex items-center justify-around px-2 py-1.5 gap-1 bg-white">

            <!-- Beranda -->
            <Link
                href="/beranda"
                @click="closeMobileSheet"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group"
                :class="currentRoute === 'overview' && !mobileActiveSheet ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200"
                    :class="currentRoute === 'overview' && !mobileActiveSheet ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': currentRoute === 'overview' && !mobileActiveSheet }"
                    >
                        grid_view
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Beranda</span>
            </Link>

            <!-- Agenda -->
            <button
                type="button"
                @click="toggleMobileSheet('agenda')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                :class="mobileActiveSheet === 'agenda' ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200"
                    :class="mobileActiveSheet === 'agenda' ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': mobileActiveSheet === 'agenda' }"
                    >
                        calendar_month
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Agenda</span>
            </button>

            <!-- Info -->
            <button
                type="button"
                @click="toggleMobileSheet('info')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                :class="mobileActiveSheet === 'info' ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200"
                    :class="mobileActiveSheet === 'info' ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': mobileActiveSheet === 'info' }"
                    >
                        campaign
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Info</span>
            </button>

            <!-- Persetujuan -->
            <button
                type="button"
                @click="toggleMobileSheet('persetujuan')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 relative group cursor-pointer"
                :class="mobileActiveSheet === 'persetujuan' ? 'text-emerald-950 font-bold' : 'text-slate-500 hover:text-emerald-900'"
            >
                <div
                    class="flex items-center justify-center px-4 py-1 rounded-full transition-all duration-200 relative"
                    :class="mobileActiveSheet === 'persetujuan' ? 'bg-emerald-100' : 'bg-transparent'"
                >
                    <span
                        class="material-symbols-outlined text-[22px] transition-transform duration-200 group-active:scale-90"
                        :class="{ 'filled-icon': mobileActiveSheet === 'persetujuan' }"
                    >
                        task_alt
                    </span>

                    <!-- Badge Realtime Pengajuan Pending (Bulat Sempurna) -->
                    <span
                        v-if="pendingApprovalsCount > 0"
                        class="absolute -top-1 -right-0.5 w-[18px] h-[18px] min-w-[18px] min-h-[18px] aspect-square rounded-full text-[10px] font-bold bg-rose-600 text-white flex items-center justify-center shadow-xs animate-pulse ring-2 ring-white"
                    >
                        {{ pendingApprovalsCount }}
                    </span>
                </div>
                <span class="text-[10.5px] mt-0.5 leading-tight">Persetujuan</span>
            </button>

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
