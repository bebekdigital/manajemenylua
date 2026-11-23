<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    tableReady: {
        type: Boolean,
        default: true,
    },
    retentionDays: {
        type: Number,
        default: 7,
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    menuStats: {
        type: Array,
        default: () => [],
    },
    psbBreakdown: {
        type: Object,
        default: () => ({}),
    },
    userAdoption: {
        type: Array,
        default: () => [],
    },
    logs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filterOptions: {
        type: Object,
        default: () => ({ users: [], modules: [] }),
    },
    filters: {
        type: Object,
        default: () => ({
            module: 'all',
            user_id: 'all',
            action: 'all',
            search: '',
        }),
    },
});

const moduleFilter = ref(props.filters.module || 'all');
const userFilter = ref(props.filters.user_id || 'all');
const actionFilter = ref(props.filters.action || 'all');
const searchFilter = ref(props.filters.search || '');
const isPruning = ref(false);

watch(
    () => props.filters,
    (next) => {
        moduleFilter.value = next?.module || 'all';
        userFilter.value = next?.user_id || 'all';
        actionFilter.value = next?.action || 'all';
        searchFilter.value = next?.search || '';
    }
);

const maxMenuVisits = computed(() => {
    const max = Math.max(...props.menuStats.map((m) => Number(m.visits || 0)), 1);
    return max;
});

function applyFilters() {
    router.get(
        '/activity-logs',
        {
            module: moduleFilter.value,
            user_id: userFilter.value,
            action: actionFilter.value,
            search: searchFilter.value.trim(),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function resetFilters() {
    moduleFilter.value = 'all';
    userFilter.value = 'all';
    actionFilter.value = 'all';
    searchFilter.value = '';
    applyFilters();
}

function filterByModule(moduleKey) {
    moduleFilter.value = moduleKey;
    actionFilter.value = 'all';
    applyFilters();
}

function filterByUser(userId) {
    userFilter.value = String(userId);
    applyFilters();
}

function triggerPruneNow() {
    if (isPruning.value) return;
    isPruning.value = true;
    router.delete('/activity-logs/prune', {
        preserveScroll: true,
        onFinish: () => {
            isPruning.value = false;
        },
    });
}

function formatDateTime(iso) {
    if (!iso) return '-';
    try {
        return new Date(iso).toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return '-';
    }
}

function getActionBadge(action) {
    if (action === 'login') {
        return { label: 'Login', cls: 'bg-sky-50 text-sky-800 border-sky-200' };
    }
    if (action === 'psb_export_excel') {
        return { label: 'Export Excel PSB', cls: 'bg-amber-50 text-amber-900 border-amber-200' };
    }
    if (action === 'psb_mode_welfare') {
        return { label: 'PSB: Domisili', cls: 'bg-emerald-50 text-emerald-800 border-emerald-200' };
    }
    if (action === 'psb_mode_school') {
        return { label: 'PSB: Asal Sekolah', cls: 'bg-emerald-50 text-emerald-800 border-emerald-200' };
    }
    return { label: 'Kunjungan Menu', cls: 'bg-slate-100 text-slate-700 border-slate-200' };
}
</script>

<template>
    <div>
        <Head title="Log Aktivitas User" />

        <div class="flex-grow max-w-container-max mx-auto w-full p-4 md:p-margin-desktop bg-white min-h-screen space-y-5">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 md:pt-0">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Log Aktivitas User</h1>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-bold">
                            Superadmin Only
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Pantau login user serta perbandingan pemakaian <span class="font-bold text-emerald-800">Pemetaan PSB</span> terhadap menu utama lainnya.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                        <span class="material-symbols-outlined text-[16px] text-emerald-700">auto_delete</span>
                        <span>Retensi Otomatis: {{ retentionDays }} Hari</span>
                    </span>
                    <button
                        type="button"
                        @click="triggerPruneNow"
                        :disabled="isPruning || !tableReady"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
                        title="Hapus manual log yang lebih dari 7 hari"
                    >
                        <span class="material-symbols-outlined text-[16px]" :class="{ 'animate-spin': isPruning }">cleaning_services</span>
                        <span>{{ isPruning ? 'Membersihkan...' : 'Bersihkan > 7 Hari' }}</span>
                    </button>
                </div>
            </div>

            <!-- Banner jika migrasi belum dijalankan -->
            <div
                v-if="!tableReady"
                class="p-5 rounded-3xl bg-amber-50 border border-amber-200 text-amber-950 text-sm"
            >
                <p class="font-bold">Tabel `user_activity_logs` belum tersedia di database.</p>
                <p class="text-xs mt-1 text-amber-800">Jalankan migrasi database agar pencatatan log aktivitas mulai aktif.</p>
            </div>

            <template v-else>
                <!-- 4 KPI Ringkasan 7 Hari Terakhir -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-200 flex items-center justify-center text-sky-800 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">login</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">User Aktif (7 Hari)</p>
                            <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ summary.active_users || 0 }}</p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                Total {{ summary.total_logins || 0 }} kali login tercatat
                            </p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-emerald-50/60 border border-emerald-200 shadow-2xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-900 text-yellow-200 flex items-center justify-center shrink-0 shadow-xs">
                            <span class="material-symbols-outlined text-[24px]">analytics</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Pemakai Pemetaan PSB</p>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <p class="text-2xl font-extrabold text-emerald-950 leading-tight">{{ summary.psb_users || 0 }}</p>
                                <span class="text-xs font-bold text-emerald-800 bg-white px-1.5 py-0.5 rounded border border-emerald-200">
                                    {{ summary.psb_adoption_pct || 0 }}%
                                </span>
                            </div>
                            <p class="text-[11px] text-emerald-800/80 font-medium mt-0.5 truncate">
                                {{ summary.psb_visits || 0 }} kunjungan • {{ summary.psb_interactions || 0 }} aksi interaksi
                            </p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">leaderboard</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Porsi Pemetaan PSB</p>
                            <div class="flex items-baseline gap-2 mt-0.5">
                                <p class="text-2xl font-extrabold text-slate-900 leading-tight">{{ summary.psb_share_pct || 0 }}%</p>
                                <span v-if="summary.psb_rank" class="text-[11px] font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                    Peringkat #{{ summary.psb_rank }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                Dari {{ summary.total_menu_visits || 0 }} total kunjungan menu
                            </p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-800 shrink-0">
                            <span class="material-symbols-outlined text-[24px]">database</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kapasitas Log DB</p>
                            <p class="text-2xl font-extrabold text-slate-900 leading-tight mt-0.5">{{ (summary.total_rows_7d || 0).toLocaleString('id-ID') }}</p>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                Baris tersimpan (jeda 5 mnt/menu)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Grid 2 Kolom: Perbandingan Menu Utama & Rincian User -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Kolom Kiri: Perbandingan Pemakaian Menu Utama -->
                    <div class="lg:col-span-6 bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs space-y-4">
                        <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900">Perbandingan Pemakaian Menu (7 Hari)</h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">Klik baris menu untuk memfilter tabel riwayat di bawah</p>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                                {{ summary.total_menu_visits || 0 }} kunjungan
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            <button
                                v-for="(item, idx) in menuStats"
                                :key="item.module"
                                type="button"
                                @click="filterByModule(item.module)"
                                :class="[
                                    'w-full text-left p-3 rounded-2xl border transition-all cursor-pointer',
                                    item.is_psb
                                        ? 'bg-emerald-50/70 border-emerald-300 hover:border-emerald-400 ring-1 ring-emerald-200/60'
                                        : 'bg-slate-50/60 border-slate-200/80 hover:border-slate-300 hover:bg-slate-100/60',
                                ]"
                            >
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-[11px] font-bold text-slate-400 w-5 shrink-0">#{{ idx + 1 }}</span>
                                        <span class="text-xs font-extrabold truncate" :class="item.is_psb ? 'text-emerald-950' : 'text-slate-800'">
                                            {{ item.label }}
                                        </span>
                                        <span v-if="item.is_psb" class="px-2 py-0.5 rounded-full bg-emerald-900 text-yellow-200 text-[10px] font-bold shrink-0">
                                            Target Pantauan
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs shrink-0">
                                        <span class="font-extrabold" :class="item.is_psb ? 'text-emerald-900' : 'text-slate-900'">
                                            {{ item.visits }}x
                                        </span>
                                        <span class="text-[11px] font-semibold text-slate-500">
                                            ({{ item.unique_users }} user • {{ item.share_pct }}%)
                                        </span>
                                    </div>
                                </div>
                                <div class="w-full h-2 rounded-full bg-white border border-slate-200/70 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="item.is_psb ? 'bg-emerald-600' : 'bg-slate-500'"
                                        :style="{ width: `${item.visits > 0 ? Math.max((item.visits / maxMenuVisits) * 100, 3) : 0}%` }"
                                    ></div>
                                </div>
                            </button>
                        </div>

                        <!-- Rincian Interaksi Pemetaan PSB -->
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Rincian Aktivitas Pemetaan PSB</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80">
                                    <div class="text-base font-extrabold text-slate-900">{{ psbBreakdown.visits || 0 }}</div>
                                    <div class="text-[10.5px] font-medium text-slate-500">Buka Halaman</div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200">
                                    <div class="text-base font-extrabold text-emerald-900">{{ psbBreakdown.mode_school || 0 }}</div>
                                    <div class="text-[10.5px] font-medium text-emerald-800">Tab Asal Sekolah</div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200">
                                    <div class="text-base font-extrabold text-emerald-900">{{ psbBreakdown.mode_welfare || 0 }}</div>
                                    <div class="text-[10.5px] font-medium text-emerald-800">Tab Domisili</div>
                                </div>
                                <div class="p-2.5 rounded-xl bg-amber-50/80 border border-amber-200">
                                    <div class="text-base font-extrabold text-amber-900">{{ psbBreakdown.export_excel || 0 }}</div>
                                    <div class="text-[10.5px] font-medium text-amber-800">Unduh Excel</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Rekap Per User (Login vs Akses Pemetaan PSB) -->
                    <div class="lg:col-span-6 bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs flex flex-col">
                        <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100 mb-3">
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-900">User Login vs Akses Pemetaan PSB (7 Hari)</h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">Lihat siapa yang aktif login dan seberapa sering membuka Pemetaan PSB</p>
                            </div>
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                                {{ userAdoption.length }} user
                            </span>
                        </div>

                        <div v-if="userAdoption.length > 0" class="overflow-y-auto max-h-[460px] space-y-2 pr-1">
                            <button
                                v-for="u in userAdoption"
                                :key="u.user_id"
                                type="button"
                                @click="filterByUser(u.user_id)"
                                class="w-full text-left p-3 rounded-2xl border border-slate-200/80 hover:border-emerald-300 hover:bg-slate-50/80 transition-all cursor-pointer"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-extrabold text-slate-900 truncate">{{ u.user_name }}</span>
                                            <span class="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-600">
                                                {{ u.user_role }}
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-0.5">
                                            <span>Login: <strong class="text-slate-700">{{ u.login_count }}x</strong></span>
                                            <span>Menu lain: <strong class="text-slate-700">{{ u.menu_visits }}x</strong></span>
                                            <span v-if="u.last_activity_at">Terakhir: {{ formatDateTime(u.last_activity_at) }}</span>
                                        </div>
                                        <div v-if="u.top_menus && u.top_menus.length > 0" class="flex flex-wrap gap-1 mt-1.5">
                                            <span
                                                v-for="tm in u.top_menus"
                                                :key="tm.label"
                                                class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-600"
                                            >
                                                {{ tm.label }} ({{ tm.count }}x)
                                            </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 text-right">
                                        <span
                                            v-if="u.psb_visits > 0 || u.psb_actions > 0"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-900 text-[11px] font-extrabold"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            PSB {{ u.psb_visits }}x
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-500 text-[10.5px] font-bold"
                                        >
                                            Belum buka PSB
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>
                        <div v-else class="py-12 text-center text-xs text-slate-400">
                            Belum ada aktivitas user tercatat dalam 7 hari terakhir.
                        </div>
                    </div>
                </div>

                <!-- Tabel Detail Riwayat Aktivitas -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-5 shadow-xs space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900">Riwayat Aktivitas Detail (7 Hari Terakhir)</h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kunjungan menu dibatasi jeda 5 menit per user agar riwayat bersih dan tidak membebani database.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <select
                                v-model="userFilter"
                                @change="applyFilters"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="all">Semua User</option>
                                <option v-for="u in filterOptions.users" :key="u.id" :value="u.id">
                                    {{ u.name }} ({{ u.role }})
                                </option>
                            </select>

                            <select
                                v-model="moduleFilter"
                                @change="applyFilters"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="all">Semua Menu</option>
                                <option v-for="m in filterOptions.modules" :key="m.value" :value="m.value">
                                    {{ m.label }}
                                </option>
                            </select>

                            <select
                                v-model="actionFilter"
                                @change="applyFilters"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                                <option value="all">Semua Aksi</option>
                                <option value="login">Login</option>
                                <option value="page_visit">Kunjungan Menu</option>
                                <option value="psb_action">Interaksi Pemetaan PSB</option>
                            </select>

                            <form @submit.prevent="applyFilters" class="relative">
                                <input
                                    v-model="searchFilter"
                                    type="text"
                                    placeholder="Cari user / menu..."
                                    class="w-44 rounded-xl border border-slate-200 bg-white pl-3 pr-8 py-1.5 text-xs text-slate-800 focus:outline-none focus:border-emerald-600"
                                />
                                <button
                                    type="submit"
                                    class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-[16px] text-slate-400 hover:text-emerald-700 cursor-pointer"
                                >
                                    search
                                </button>
                            </form>

                            <button
                                v-if="moduleFilter !== 'all' || userFilter !== 'all' || actionFilter !== 'all' || searchFilter !== ''"
                                type="button"
                                @click="resetFilters"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 cursor-pointer"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <div v-if="logs.data && logs.data.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-2.5 px-3">Waktu</th>
                                    <th class="py-2.5 px-3">User</th>
                                    <th class="py-2.5 px-3">Jenis Aksi</th>
                                    <th class="py-2.5 px-3">Menu</th>
                                    <th class="py-2.5 px-3">Keterangan</th>
                                    <th class="py-2.5 px-3 text-right">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/70">
                                    <td class="py-2.5 px-3 whitespace-nowrap text-slate-500 font-medium">
                                        {{ log.created_at_human || formatDateTime(log.created_at) }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-slate-900">{{ log.user_name }}</div>
                                        <div class="text-[10.5px] text-slate-400">{{ log.user_role }}</div>
                                    </td>
                                    <td class="py-2.5 px-3 whitespace-nowrap">
                                        <span :class="['px-2 py-0.5 rounded-full text-[10.5px] font-bold border', getActionBadge(log.action).cls]">
                                            {{ getActionBadge(log.action).label }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 whitespace-nowrap">
                                        <span
                                            class="font-bold"
                                            :class="log.module === 'psb' ? 'text-emerald-800' : 'text-slate-800'"
                                        >
                                            {{ log.menu_label }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-600">
                                        {{ log.description || '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                        {{ log.ip_address || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-10 text-center text-xs text-slate-400">
                        Tidak ada riwayat log aktivitas yang cocok dengan filter ini.
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="logs.links && logs.links.length > 3"
                        class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                    >
                        <span class="text-slate-500 font-medium">
                            Menampilkan {{ logs.from || 0 }}–{{ logs.to || 0 }} dari {{ logs.total || 0 }} log
                        </span>
                        <div class="flex flex-wrap items-center gap-1">
                            <template v-for="(link, idx) in logs.links" :key="idx">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    :class="[
                                        'px-2.5 py-1 rounded-lg border text-xs font-bold transition-colors',
                                        link.active
                                            ? 'bg-emerald-900 text-yellow-200 border-emerald-950'
                                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-2.5 py-1 rounded-lg border border-slate-100 text-slate-300 text-xs font-bold"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
