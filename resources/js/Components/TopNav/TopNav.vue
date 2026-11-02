<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { usePendingApprovals } from '@/Composables/usePendingApprovals';

defineProps({
    pageTitle: {
        type: String,
        default: 'Overview',
    },
});

defineEmits(['toggle-sidebar']);

const page = usePage();

// State dropdown untuk menu di Topbar (Agenda, Info, Persetujuan)
const activeDropdown = ref(null); // 'agenda' | 'info' | 'persetujuan' | null
const agendaMenuRef = ref(null);
const infoMenuRef = ref(null);
const persetujuanMenuRef = ref(null);

const isProfileMenuOpen = ref(false);
const profileMenuRef = ref(null);

// ============================================================
// Realtime Pending Approvals (Persetujuan Perizinan Atasan)
// ============================================================
const {
    pendingApprovalsCount,
    pendingApprovalsList,
    fetchPendingApprovals,
    startApprovalsPolling,
    stopApprovalsPolling,
    setCount,
} = usePendingApprovals();

watch(() => page.props.pending_approvals_count, (newVal) => {
    if (typeof newVal === 'number') {
        setCount(newVal);
    }
}, { immediate: true });

function toggleMenu(menuName) {
    if (activeDropdown.value === menuName) {
        activeDropdown.value = null;
    } else {
        activeDropdown.value = menuName;
        isProfileMenuOpen.value = false;
        if (menuName === 'persetujuan') {
            fetchPendingApprovals();
        }
    }
}

function closeAllMenus() {
    activeDropdown.value = null;
    isProfileMenuOpen.value = false;
}

function toggleProfileMenu() {
    isProfileMenuOpen.value = !isProfileMenuOpen.value;
    if (isProfileMenuOpen.value) {
        activeDropdown.value = null;
    }
}

function closeProfileMenu() {
    isProfileMenuOpen.value = false;
}

function handleClickOutside(event) {
    if (
        activeDropdown.value &&
        !agendaMenuRef.value?.contains(event.target) &&
        !infoMenuRef.value?.contains(event.target) &&
        !persetujuanMenuRef.value?.contains(event.target)
    ) {
        activeDropdown.value = null;
    }
    if (profileMenuRef.value && !profileMenuRef.value.contains(event.target)) {
        closeProfileMenu();
    }
}

function handleKeydown(event) {
    if (event.key === 'Escape') {
        closeAllMenus();
    }
}

function onWindowFocus() {
    fetchPendingApprovals();
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('focus', onWindowFocus);
    startApprovalsPolling();
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('focus', onWindowFocus);
    stopApprovalsPolling();
});
</script>

<template>
    <header class="bg-surface-container-lowest shadow-sm docked full-width top-0 sticky z-30 border-b border-outline-variant/30 transition-all duration-300">
        <div class="flex items-center justify-between h-16 px-4 md:px-gutter max-w-container-max mx-auto gap-2 md:gap-4 w-full">
            
            <!-- Left: Title & Topbar Navigation Menus -->
            <div class="flex items-center gap-3 sm:gap-6 shrink-0">
                <!-- Page Title -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <div class="w-8 h-8 rounded-xl bg-yellow-200 md:hidden flex items-center justify-center text-emerald-950 shadow-2xs shrink-0">
                        <span class="material-symbols-outlined filled-icon text-[18px]">school</span>
                    </div>
                    <div class="flex flex-col md:block">
                        <h1 class="text-sm font-bold text-slate-800 leading-tight">{{ pageTitle }}</h1>
                        <span class="text-[10px] text-slate-400 font-medium md:hidden leading-tight">Yayasan Li Ulil Albab</span>
                    </div>
                </div>

                <!-- Topbar Menu Buttons: Agenda, Info, Persetujuan (Desktop Only, di mobile ada di bottom nav) -->
                <div class="hidden md:flex items-center gap-1.5 sm:gap-2">
                    <!-- Menu: Agenda -->
                    <div class="relative" ref="agendaMenuRef">
                        <button
                            type="button"
                            @click="toggleMenu('agenda')"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12.5px] sm:text-[13px] font-semibold transition-all cursor-pointer border',
                                activeDropdown === 'agenda'
                                    ? 'bg-emerald-50 text-emerald-950 border-emerald-600 shadow-2xs'
                                    : 'bg-white text-slate-700 border-slate-200/90 hover:bg-slate-50 hover:text-emerald-900 shadow-2xs'
                            ]"
                            title="Menu Agenda"
                        >
                            <span class="material-symbols-outlined text-[17px] text-emerald-700">calendar_month</span>
                            <span>Agenda</span>
                        </button>

                        <!-- Dropdown Menu Agenda (Isinya nanti) -->
                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                            enter-to-class="transform scale-100 opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform scale-100 opacity-100 translate-y-0"
                            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                        >
                            <div
                                v-if="activeDropdown === 'agenda'"
                                class="absolute left-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-4 animate-in fade-in"
                            >
                                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-emerald-700">calendar_month</span>
                                        <h4 class="text-[13px] font-bold text-slate-900">Agenda Kegiatan</h4>
                                    </div>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Segera Hadir</span>
                                </div>
                                <div class="py-6 text-center">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-1.5 block">event_upcoming</span>
                                    <p class="text-[12.5px] font-medium text-slate-600">Belum ada agenda kegiatan.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Jadwal dan kalender kegiatan akan ditampilkan di sini.</p>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Menu: Info -->
                    <div class="relative" ref="infoMenuRef">
                        <button
                            type="button"
                            @click="toggleMenu('info')"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12.5px] sm:text-[13px] font-semibold transition-all cursor-pointer border',
                                activeDropdown === 'info'
                                    ? 'bg-emerald-50 text-emerald-950 border-emerald-600 shadow-2xs'
                                    : 'bg-white text-slate-700 border-slate-200/90 hover:bg-slate-50 hover:text-emerald-900 shadow-2xs'
                            ]"
                            title="Menu Info"
                        >
                            <span class="material-symbols-outlined text-[17px] text-emerald-700">campaign</span>
                            <span>Info</span>
                        </button>

                        <!-- Dropdown Menu Info (Isinya nanti) -->
                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                            enter-to-class="transform scale-100 opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform scale-100 opacity-100 translate-y-0"
                            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                        >
                            <div
                                v-if="activeDropdown === 'info'"
                                class="absolute left-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-4 animate-in fade-in"
                            >
                                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-emerald-700">campaign</span>
                                        <h4 class="text-[13px] font-bold text-slate-900">Pusat Informasi</h4>
                                    </div>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Segera Hadir</span>
                                </div>
                                <div class="py-6 text-center">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-1.5 block">notifications_paused</span>
                                    <p class="text-[12.5px] font-medium text-slate-600">Belum ada pengumuman terbaru.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Informasi dan berita penting akan ditampilkan di sini.</p>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Menu: Persetujuan (Terhubung Realtime dengan Pengajuan Perizinan Bawahan) -->
                    <div class="relative" ref="persetujuanMenuRef">
                        <button
                            type="button"
                            @click="toggleMenu('persetujuan')"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[12.5px] sm:text-[13px] font-semibold transition-all cursor-pointer border relative',
                                activeDropdown === 'persetujuan'
                                    ? 'bg-emerald-50 text-emerald-950 border-emerald-600 shadow-2xs'
                                    : 'bg-white text-slate-700 border-slate-200/90 hover:bg-slate-50 hover:text-emerald-900 shadow-2xs'
                            ]"
                            title="Menu Persetujuan Perizinan"
                        >
                            <span class="material-symbols-outlined text-[17px] text-emerald-700">task_alt</span>
                            <span>Persetujuan</span>

                            <!-- Badge Realtime jika ada pengajuan pending (Bulat Sempurna) -->
                            <span
                                v-if="pendingApprovalsCount > 0"
                                class="w-[18px] h-[18px] min-w-[18px] min-h-[18px] aspect-square rounded-full flex items-center justify-center text-[10px] font-bold bg-rose-600 text-white leading-none shadow-2xs animate-pulse shrink-0 ml-0.5"
                            >
                                {{ pendingApprovalsCount }}
                            </span>
                        </button>

                        <!-- Dropdown Menu Persetujuan (Realtime List Pengajuan Perizinan) -->
                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                            enter-to-class="transform scale-100 opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform scale-100 opacity-100 translate-y-0"
                            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                        >
                            <div
                                v-if="activeDropdown === 'persetujuan'"
                                class="absolute left-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 p-3.5 space-y-2 animate-in fade-in"
                            >
                                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] text-emerald-700">task_alt</span>
                                        <h4 class="text-[13px] font-bold text-slate-900">Persetujuan Perizinan</h4>
                                    </div>
                                    <span
                                        :class="[
                                            'text-[10.5px] font-bold px-2 py-0.5 rounded-full border',
                                            pendingApprovalsCount > 0
                                                ? 'bg-rose-50 text-rose-700 border-rose-200'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        ]"
                                    >
                                        {{ pendingApprovalsCount }} Menunggu
                                    </span>
                                </div>

                                <!-- List Item Pengajuan Perizinan -->
                                <div v-if="pendingApprovalsList.length > 0" class="max-h-80 overflow-y-auto space-y-1.5 pr-0.5">
                                    <Link
                                        v-for="item in pendingApprovalsList"
                                        :key="item.id"
                                        :href="item.url"
                                        @click="activeDropdown = null"
                                        class="p-2.5 rounded-xl border border-slate-200/80 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all flex items-start justify-between gap-2.5 group cursor-pointer block"
                                        title="Buka tab perizinan pegawai ini"
                                    >
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-900 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs">
                                                {{ item.employee_nama ? item.employee_nama.substring(0, 2).toUpperCase() : 'PG' }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="text-[12.5px] font-bold text-slate-900 group-hover:text-emerald-950 truncate">
                                                        {{ item.employee_nama }}
                                                    </span>
                                                    <span class="text-[10.5px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium">
                                                        {{ item.employee_unit }}
                                                    </span>
                                                </div>
                                                <div class="text-[12px] font-semibold text-emerald-800 mt-0.5">
                                                    {{ item.jenis }}
                                                    <span v-if="item.detail" class="font-normal text-slate-500">({{ item.detail }})</span>
                                                </div>
                                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
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
                                </div>

                                <!-- Empty State jika tidak ada pengajuan -->
                                <div v-else class="py-6 text-center">
                                    <span class="material-symbols-outlined text-4xl text-slate-300 mb-1.5 block">fact_check</span>
                                    <p class="text-[12.5px] font-medium text-slate-600">Tidak ada persetujuan tertunda.</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Semua pengajuan perizinan pegawai telah diproses.</p>
                                </div>

                                <div class="pt-2 border-t border-slate-100 text-center">
                                    <p class="text-[10.5px] text-slate-400">
                                        Klik pengajuan untuk membuka tab perizinan pegawai dan memberikan persetujuan.
                                    </p>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- Right Actions: Role, Notifications & Profile -->
            <div class="flex items-center space-x-1 sm:space-x-3 shrink-0">
                <button
                    type="button"
                    aria-label="Notifications"
                    class="text-on-surface-variant hover:text-primary transition-colors duration-200 cursor-pointer p-2 rounded-full hover:bg-surface-variant flex items-center justify-center"
                >
                    <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
                </button>

                <!-- Desktop Profile Link (Hidden on mobile) -->
                <Link
                    href="/profile"
                    class="hidden lg:flex items-center gap-2.5 p-1 pr-3 bg-surface-container-low hover:bg-surface-container-low/80 border border-outline-variant/30 rounded-full transition-colors cursor-pointer group"
                >
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0 group-hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">person</span>
                    </div>
                    <div class="flex flex-col items-start">
                        <span class="text-xs font-bold text-on-surface leading-tight capitalize">{{ page.props.auth?.user?.name || 'Guest' }}</span>
                        <span class="text-[10px] font-medium text-on-surface-variant leading-tight capitalize">{{ page.props.auth?.user?.role || 'Guest' }}</span>
                    </div>
                </Link>

                <!-- Mobile Profile Dropdown (Hidden on desktop) -->
                <div class="relative lg:hidden" ref="profileMenuRef">
                    <button
                        type="button"
                        @click="toggleProfileMenu"
                        aria-label="Account profile"
                        class="text-on-surface-variant hover:text-primary transition-colors duration-200 cursor-pointer p-2 rounded-full hover:bg-surface-variant flex items-center justify-center"
                        :class="{ 'bg-surface-variant text-primary': isProfileMenuOpen }"
                    >
                        <span class="material-symbols-outlined" data-icon="account_circle">account_circle</span>
                    </button>

                    <!-- Profile Dropdown -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                        enter-to-class="transform scale-100 opacity-100 translate-y-0"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="transform scale-100 opacity-100 translate-y-0"
                        leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="isProfileMenuOpen"
                            class="absolute right-0 mt-2 w-56 bg-white border border-outline-variant/40 rounded-2xl shadow-[0px_10px_35px_rgba(0,40,20,0.15)] z-50 overflow-hidden"
                        >
                            <!-- Profile Header -->
                            <div class="p-4 bg-surface-container-low/70 border-b border-outline-variant/30 flex flex-col items-center text-center">
                                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-2">
                                    <span class="material-symbols-outlined text-[24px]">person</span>
                                </div>
                                <h4 class="text-sm font-bold text-on-surface capitalize leading-tight">{{ page.props.auth?.user?.name || 'Guest' }}</h4>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 capitalize">{{ page.props.auth?.user?.role || 'Guest' }}</p>
                            </div>

                            <!-- Menu Items -->
                            <div class="p-1.5 space-y-0.5">
                                <Link
                                    href="/profile"
                                    @click="closeProfileMenu"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-low hover:text-primary rounded-xl transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px]">person</span>
                                    <span>Profil</span>
                                </Link>
                                <Link
                                    href="/portal"
                                    @click="closeProfileMenu"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-low hover:text-primary rounded-xl transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px]">settings</span>
                                    <span>Pengaturan</span>
                                </Link>
                                <div class="h-[1px] bg-outline-variant/30 my-1 mx-2"></div>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-sm font-medium text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[18px]">logout</span>
                                    <span>Logout</span>
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>
