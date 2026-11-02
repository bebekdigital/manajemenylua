<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    employees: {
        type: Array,
        required: true,
    },
    totalCount: {
        type: Number,
        default: 0,
    },
    filteredCount: {
        type: Number,
        default: 0,
    },
    perPage: {
        type: Number,
        default: 10,
    },
    activeStatus: {
        type: String,
        default: 'semua',
    },
    activeUnit: {
        type: String,
        default: '',
    },
    activeJenjang: {
        type: String,
        default: '',
    },
});

const emit = defineEmits([
    'search',
    'filter-unit',
    'filter-status',
    'filter-jenjang',
    'update:per-page',
    'reset-filters',
]);

const searchQuery = ref('');

// Compute counts per status tab (Tanpa Cuti)
const statusCounts = computed(() => {
    const all = props.employees.length;
    let aktif = 0;
    let pensiun = 0;
    let nonAktif = 0;

    props.employees.forEach(e => {
        const s = (e.status_keaktifan || 'Aktif').toLowerCase();
        if (s === 'aktif') aktif++;
        else if (s === 'pensiun') pensiun++;
        else if (['non-aktif', 'tidak aktif', 'tidak_aktif', 'resign', 'keluar'].includes(s)) nonAktif++;
    });

    return {
        semua: all,
        aktif,
        pensiun,
        'non-aktif': nonAktif,
    };
});

const statusTabs = computed(() => [
    { key: 'semua', label: 'Semua', count: statusCounts.value.semua },
    { key: 'aktif', label: 'Aktif', count: statusCounts.value.aktif },
    { key: 'pensiun', label: 'Pensiun', count: statusCounts.value.pensiun },
    { key: 'non-aktif', label: 'Non-Aktif', count: statusCounts.value['non-aktif'] },
]);

const units = computed(() => {
    const uniqueUnits = [...new Set(props.employees.map(e => e.unit_kerja).filter(Boolean))].sort();
    return [
        { value: '', label: 'Semua Unit' },
        ...uniqueUnits.map(u => ({ value: u, label: u })),
    ];
});

const jenjangList = computed(() => {
    const uniqueJenjang = [...new Set(props.employees.map(e => e.jenjang_kepegawaian).filter(Boolean))].sort();
    return uniqueJenjang.length > 0 ? uniqueJenjang : ['GTY', 'GTT', 'PTY', 'PTT'];
});

const perPageOptions = [
    { value: 10, label: '10 Data' },
    { value: 25, label: '25 Data' },
    { value: 50, label: '50 Data' },
    { value: 100, label: '100 Data' },
    { value: 0, label: 'Semua Data' },
];

const activeFiltersCount = computed(() => {
    let count = 0;
    if (props.activeStatus && props.activeStatus !== 'semua') count++;
    if (props.activeUnit) count++;
    if (props.activeJenjang) count++;
    if (searchQuery.value.trim()) count++;
    return count;
});

function selectStatusTab(key) {
    emit('filter-status', key);
}

function onSearchInput() {
    emit('search', searchQuery.value);
}

function clearSearch() {
    searchQuery.value = '';
    emit('search', '');
}

function onUnitChange(event) {
    emit('filter-unit', event.target.value);
}

function selectJenjang(val) {
    const next = props.activeJenjang === val ? '' : val;
    emit('filter-jenjang', next);
}

function onPerPageChange(event) {
    emit('update:per-page', Number(event.target.value));
}

function resetAll() {
    searchQuery.value = '';
    emit('reset-filters');
}
</script>

<template>
    <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 mb-4 sm:mb-6 shadow-sm border border-slate-200 flex flex-col gap-3 sm:gap-4">
        <!-- Main Row: Filters (Left) + Search & Per Page seperti Database Siswa (Right) -->
        <div class="flex flex-col xl:flex-row gap-3 sm:gap-4 items-start xl:items-center justify-between w-full">

            <!-- Left Side: Segmented Status Tabs & Unit Filter -->
            <div class="flex flex-wrap items-center gap-2.5 w-full xl:w-auto flex-grow justify-start">
                <!-- Segmented Status Tabs (Tanpa Cuti + Custom Horizontal Scrollbar) -->
                <div class="bg-slate-100 p-1 rounded-xl inline-flex items-center gap-1 overflow-x-auto custom-scrollbar-x max-w-full border border-slate-200/80">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.key"
                        type="button"
                        @click="selectStatusTab(tab.key)"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[12.5px] sm:text-[13px] transition-all duration-150 cursor-pointer shrink-0',
                            activeStatus === tab.key
                                ? 'bg-white text-slate-900 font-bold shadow-2xs'
                                : 'text-slate-600 hover:text-slate-900 font-medium'
                        ]"
                    >
                        <span>{{ tab.label }}</span>
                        <span
                            :class="[
                                'px-1.5 py-0.5 rounded-full text-[11px] leading-none transition-colors',
                                activeStatus === tab.key
                                    ? 'bg-emerald-900 text-yellow-200 font-bold'
                                    : 'bg-slate-200/90 text-slate-600 font-semibold'
                            ]"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- Unit Kerja Filter (Gaya Database Siswa) -->
                <div class="relative w-full sm:w-auto sm:min-w-[150px] flex-grow sm:flex-grow-0">
                    <select
                        :value="activeUnit"
                        class="w-full pl-3 pr-8 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-[13.5px] font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                        @change="onUnitChange"
                    >
                        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
                    </select>
                </div>
            </div>

            <!-- Right Side: Search Bar & Per Page (Kembalikan seperti Database Siswa) -->
            <div class="flex items-center gap-2 w-full xl:w-auto shrink-0 justify-between flex-nowrap">
                <!-- Search Bar with Clear Button -->
                <div class="relative flex-1 min-w-0 sm:flex-none sm:w-64 xl:w-64 2xl:w-72">
                    <span class="material-symbols-outlined absolute left-2.5 sm:left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] sm:text-[20px] pointer-events-none">
                        search
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, NIPY..."
                        class="w-full pl-8 sm:pl-11 pr-8 sm:pr-10 py-2 sm:py-2.5 bg-white hover:bg-slate-50 focus:bg-white border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-[13.5px] text-slate-700 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                        @input="onSearchInput"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-variant transition-colors cursor-pointer"
                        title="Hapus pencarian"
                        aria-label="Hapus kata kunci pencarian"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">close</span>
                    </button>
                </div>

                <!-- Tampilkan ... Data Selector Filter -->
                <div class="relative w-[95px] shrink-0 sm:w-auto sm:min-w-[125px]">
                    <div class="relative flex items-center">
                        <select
                            :value="perPage"
                            class="w-full pl-6 sm:pl-8 pr-6 sm:pr-8 py-2 sm:py-2.5 bg-white hover:bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-lg sm:rounded-xl text-[12px] sm:text-[13.5px] font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors appearance-none"
                            @change="onPerPageChange"
                            title="Tampilkan jumlah baris data per halaman"
                        >
                            <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <span class="material-symbols-outlined text-primary text-[15px] sm:text-[18px] pointer-events-none absolute left-1.5 sm:left-2.5">
                            format_list_numbered
                        </span>
                        <span class="material-symbols-outlined text-outline text-[15px] sm:text-[16px] pointer-events-none absolute right-1.5 sm:right-2.5">
                            expand_more
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub-row: Quick Filter Jenjang Pills & Active Filter Summary -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11.5px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Kategori:</span>
                <button
                    v-for="j in jenjangList"
                    :key="j"
                    type="button"
                    @click="selectJenjang(j)"
                    :class="[
                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[12px] font-semibold transition-all cursor-pointer',
                        activeJenjang === j
                            ? 'bg-emerald-900 border-emerald-900 text-yellow-200 shadow-2xs'
                            : 'border-slate-200 text-slate-600 hover:border-emerald-600/50 hover:text-emerald-900 bg-slate-50/70'
                    ]"
                >
                    <span>{{ j }}</span>
                </button>

                <button
                    v-if="activeFiltersCount > 0"
                    type="button"
                    @click="resetAll"
                    class="text-[12px] font-semibold text-emerald-700 hover:text-emerald-950 ml-2 cursor-pointer transition-colors underline underline-offset-2"
                >
                    Reset filter
                </button>
            </div>

            <div class="text-[12.5px] text-slate-500 ml-auto shrink-0">
                Menampilkan <strong class="text-slate-900 font-bold">{{ filteredCount }}</strong> dari <strong class="text-slate-800 font-semibold">{{ totalCount }}</strong> pegawai
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom Horizontal Scrollbar */
.custom-scrollbar-x {
    scrollbar-width: thin;
    scrollbar-color: #10b981 transparent;
}

.custom-scrollbar-x::-webkit-scrollbar {
    height: 4px;
}

.custom-scrollbar-x::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb:hover {
    background-color: #059669;
}
</style>
