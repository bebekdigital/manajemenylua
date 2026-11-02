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
        default: 15,
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
const showFilters = ref(false);

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
        { value: '', label: 'Semua Unit Kerja' },
        ...uniqueUnits.map(u => ({ value: u, label: u })),
    ];
});

const jenjangList = computed(() => {
    const uniqueJenjang = [...new Set(props.employees.map(e => e.jenjang_kepegawaian).filter(Boolean))].sort();
    return uniqueJenjang.length > 0 ? uniqueJenjang : ['GTY', 'GTT', 'PTY', 'PTT'];
});

const perPageOptions = [
    { value: 10, label: '10 Data' },
    { value: 15, label: '15 Data' },
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
    <div class="mb-4 space-y-3">
        <!-- ROW 1: Segmented Status Tabs (Left) + Unit & Per Page Controls (Right) -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Segmented Status Tabs (Custom Clean Scrollbar - Tanpa Cuti) -->
            <div class="bg-slate-200/60 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto custom-tab-scrollbar max-w-full border border-slate-200/80 shrink-0">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    type="button"
                    @click="selectStatusTab(tab.key)"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[13px] transition-all duration-150 cursor-pointer shrink-0',
                        activeStatus === tab.key
                            ? 'bg-white text-slate-900 font-bold shadow-2xs'
                            : 'text-slate-600 hover:text-slate-900 font-medium'
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[11px] leading-none transition-colors',
                            activeStatus === tab.key
                                ? 'bg-emerald-900 text-yellow-200 font-bold'
                                : 'bg-slate-200/90 text-slate-600 font-semibold'
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- Right Controls: Unit Dropdown + Per Page Selector (Tanpa Sort Nama) -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Unit Kerja Dropdown -->
                <div class="relative flex-1 sm:flex-initial">
                    <span class="material-symbols-outlined text-[16px] text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none">domain</span>
                    <select
                        :value="activeUnit"
                        @change="onUnitChange"
                        class="w-full sm:w-auto pl-9 pr-8 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-[12.5px] sm:text-[13px] font-medium text-slate-700 focus:outline-none focus:border-emerald-500 shadow-2xs cursor-pointer appearance-none transition-colors"
                    >
                        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
                    </select>
                    <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>

                <!-- Per Page Selector -->
                <div class="relative hidden sm:block">
                    <select
                        :value="perPage"
                        @change="onPerPageChange"
                        class="pl-3.5 pr-7 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-[12.5px] sm:text-[13px] font-medium text-slate-700 focus:outline-none focus:border-emerald-500 shadow-2xs cursor-pointer appearance-none transition-colors"
                    >
                        <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <span class="material-symbols-outlined text-[15px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>
            </div>
        </div>

        <!-- ROW 2: Search Bar (Gaya Database Siswa: rounded-xl, border-slate-300) + Filter Button -->
        <div class="flex items-center gap-2.5">
            <!-- Search Bar bergaya Database Siswa -->
            <div class="relative flex-1 min-w-0">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[19px] pointer-events-none">
                    search
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama, NIPY, jabatan, unit, atau nomor WA..."
                    class="w-full pl-11 pr-10 py-2.5 bg-white hover:bg-slate-50 focus:bg-white border border-slate-300 rounded-xl text-[13px] sm:text-[13.5px] text-slate-700 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all shadow-2xs"
                    @input="onSearchInput"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 p-1 rounded-full hover:bg-slate-100 transition-colors cursor-pointer"
                    title="Hapus pencarian"
                >
                    <span class="material-symbols-outlined text-[17px]">close</span>
                </button>
            </div>

            <!-- Filters Button dengan Badge -->
            <button
                type="button"
                @click="showFilters = !showFilters"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[13px] font-semibold transition-all shadow-2xs cursor-pointer shrink-0',
                    showFilters || activeFiltersCount > 0
                        ? 'bg-emerald-50 border-emerald-600 text-emerald-950'
                        : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'
                ]"
            >
                <span class="material-symbols-outlined text-[18px]">tune</span>
                <span>Filter</span>
                <span
                    v-if="activeFiltersCount > 0"
                    class="px-1.5 py-0.5 rounded-full bg-emerald-900 text-yellow-200 text-[11px] font-bold leading-none"
                >
                    {{ activeFiltersCount }}
                </span>
            </button>
        </div>

        <!-- ROW 3: Active Filter Chips (Left) + Entry Counter (Right) -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
            <div class="flex flex-wrap items-center gap-1.5">
                <!-- Status Chip -->
                <div
                    v-if="activeStatus && activeStatus !== 'semua'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-slate-200 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-400">Status:</span>
                    <strong class="text-slate-800 font-semibold capitalize">{{ activeStatus }}</strong>
                    <button
                        type="button"
                        @click="selectStatusTab('semua')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Unit Chip -->
                <div
                    v-if="activeUnit"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-slate-200 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-400">Unit:</span>
                    <strong class="text-slate-800 font-semibold">{{ activeUnit }}</strong>
                    <button
                        type="button"
                        @click="emit('filter-unit', '')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Jenjang Chip -->
                <div
                    v-if="activeJenjang"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white border border-slate-200 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-400">Kategori:</span>
                    <strong class="text-slate-800 font-semibold">{{ activeJenjang }}</strong>
                    <button
                        type="button"
                        @click="emit('filter-jenjang', '')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Quick Filter Jenjang Pills -->
                <div class="inline-flex items-center gap-1 flex-wrap">
                    <button
                        v-for="j in jenjangList"
                        :key="j"
                        type="button"
                        @click="selectJenjang(j)"
                        :class="[
                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border text-[12px] font-medium transition-all cursor-pointer',
                            activeJenjang === j
                                ? 'bg-emerald-900 border-emerald-900 text-yellow-200 font-bold shadow-2xs'
                                : 'border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-900 bg-white'
                        ]"
                    >
                        <span v-if="activeJenjang !== j" class="text-slate-400">+</span>
                        <span>{{ j }}</span>
                    </button>
                </div>

                <!-- Reset Filter Link -->
                <button
                    v-if="activeFiltersCount > 0"
                    type="button"
                    @click="resetAll"
                    class="text-[12.5px] font-semibold text-emerald-800 hover:text-emerald-950 ml-1 cursor-pointer transition-colors underline underline-offset-2"
                >
                    Reset semua
                </button>
            </div>

            <!-- Right Entry Counter (Sesuai Referensi Gambar) -->
            <div class="text-[12.5px] text-slate-500 ml-auto shrink-0">
                <strong class="text-slate-900 font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} pegawai
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom Tab Scrollbar without arrows and minimal height */
.custom-tab-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.custom-tab-scrollbar::-webkit-scrollbar {
    height: 4px;
}

.custom-tab-scrollbar::-webkit-scrollbar-button {
    display: none;
    width: 0;
    height: 0;
}

.custom-tab-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-tab-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}

.custom-tab-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #047857;
}
</style>
