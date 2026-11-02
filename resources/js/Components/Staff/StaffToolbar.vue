<script setup>
import { ref, computed, watch } from 'vue';

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
    sortBy: {
        type: String,
        default: 'nama',
    },
    sortDirection: {
        type: String,
        default: 'asc',
    },
});

const emit = defineEmits([
    'search',
    'filter-unit',
    'filter-status',
    'filter-jenjang',
    'sort-change',
    'update:per-page',
    'reset-filters',
]);

const searchQuery = ref('');
const searchInputRef = ref(null);
const showFilterPanel = ref(false);

// Compute counts per status tab
const statusCounts = computed(() => {
    const all = props.employees.length;
    let aktif = 0;
    let cuti = 0;
    let pensiun = 0;
    let nonAktif = 0;

    props.employees.forEach(e => {
        const s = (e.status_keaktifan || 'Aktif').toLowerCase();
        if (s === 'aktif') aktif++;
        else if (s === 'cuti') cuti++;
        else if (s === 'pensiun') pensiun++;
        else nonAktif++;
    });

    return {
        semua: all,
        aktif,
        cuti,
        pensiun,
        'non-aktif': nonAktif,
    };
});

const statusTabs = computed(() => [
    { key: 'semua', label: 'Semua', count: statusCounts.value.semua },
    { key: 'aktif', label: 'Aktif', count: statusCounts.value.aktif },
    { key: 'cuti', label: 'Cuti', count: statusCounts.value.cuti },
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

const sortOptions = [
    { value: 'nama', label: 'Urutkan: Nama' },
    { value: 'nipy', label: 'Urutkan: NIPY' },
    { value: 'unit_kerja', label: 'Urutkan: Unit' },
    { value: 'jabatan', label: 'Urutkan: Jabatan' },
    { value: 'jenjang_kepegawaian', label: 'Urutkan: Jenjang' },
];

const perPageOptions = [
    { value: 15, label: '15 Baris' },
    { value: 25, label: '25 Baris' },
    { value: 50, label: '50 Baris' },
    { value: 100, label: '100 Baris' },
    { value: 0, label: 'Semua' },
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

function onSortSelect(event) {
    emit('sort-change', { field: event.target.value, direction: props.sortDirection });
}

function toggleSortDir() {
    emit('sort-change', {
        field: props.sortBy,
        direction: props.sortDirection === 'asc' ? 'desc' : 'asc',
    });
}

function onPerPageChange(event) {
    emit('update:per-page', Number(event.target.value));
}

function resetAll() {
    searchQuery.value = '';
    emit('reset-filters');
}

function focusSearch() {
    searchInputRef.value?.focus();
}
</script>

<template>
    <div class="mb-4 space-y-3">
        <!-- ROW 1: Status Segmented Tabs (Left) + Unit & Sort Controls (Right) -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Segmented Status Tabs -->
            <div class="bg-slate-200/60 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto max-w-full border border-slate-200/80">
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

            <!-- Right Controls: Unit Dropdown + Sort Dropdown + Per Page -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Unit Kerja Pill Select -->
                <div class="relative flex-1 sm:flex-initial">
                    <span class="material-symbols-outlined text-[16px] text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none">domain</span>
                    <select
                        :value="activeUnit"
                        @change="onUnitChange"
                        class="w-full sm:w-auto pl-9 pr-8 py-1.5 bg-white hover:bg-slate-50 border border-slate-200/90 rounded-full text-[13px] font-medium text-slate-700 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer appearance-none transition-colors"
                    >
                        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
                    </select>
                    <span class="material-symbols-outlined text-[16px] text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>

                <!-- Sort Pill Select -->
                <div class="inline-flex items-center bg-white border border-slate-200/90 rounded-full shadow-2xs overflow-hidden">
                    <button
                        type="button"
                        @click="toggleSortDir"
                        class="pl-3 pr-1.5 py-1.5 text-slate-500 hover:text-emerald-800 transition-colors cursor-pointer flex items-center"
                        :title="sortDirection === 'asc' ? 'Urutan A-Z (Klik untuk Z-A)' : 'Urutan Z-A (Klik untuk A-Z)'"
                    >
                        <span class="material-symbols-outlined text-[16px]">swap_vert</span>
                    </button>
                    <select
                        :value="sortBy"
                        @change="onSortSelect"
                        class="pr-7 pl-1 py-1.5 bg-transparent text-[13px] font-medium text-slate-700 focus:outline-none cursor-pointer appearance-none"
                    >
                        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>

                <!-- Per Page Pill Select -->
                <div class="relative hidden sm:block">
                    <select
                        :value="perPage"
                        @change="onPerPageChange"
                        class="pl-3.5 pr-7 py-1.5 bg-white hover:bg-slate-50 border border-slate-200/90 rounded-full text-[13px] font-medium text-slate-700 focus:outline-none focus:border-emerald-600 shadow-2xs cursor-pointer appearance-none transition-colors"
                    >
                        <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <span class="material-symbols-outlined text-[15px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">expand_more</span>
                </div>
            </div>
        </div>

        <!-- ROW 2: Full-Width Search Bar + Filters Button -->
        <div class="flex items-center gap-2.5">
            <div
                @click="focusSearch"
                class="flex-1 bg-white border border-slate-200/90 hover:border-slate-300 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/10 rounded-full px-4 py-2.5 flex items-center gap-2.5 shadow-[0_1px_2px_rgba(0,0,0,0.03)] transition-all cursor-text"
            >
                <span class="material-symbols-outlined text-slate-400 text-[20px] shrink-0">search</span>

                <!-- Active Jenjang Chip inside Search Bar (just like `amount: >1000` in reference) -->
                <span
                    v-if="activeJenjang"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-900/10 text-emerald-950 text-[12px] font-mono font-semibold shrink-0"
                >
                    <span>jenjang:{{ activeJenjang }}</span>
                    <button
                        type="button"
                        @click.stop="emit('filter-jenjang', '')"
                        class="hover:text-rose-600 cursor-pointer flex items-center"
                    >
                        <span class="material-symbols-outlined text-[14px]">close</span>
                    </button>
                </span>

                <input
                    ref="searchInputRef"
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama pegawai, NIPY, jabatan, unit, atau nomor WA..."
                    class="flex-1 min-w-0 bg-transparent text-[13.5px] text-slate-800 placeholder:text-slate-400 focus:outline-none"
                    @input="onSearchInput"
                />

                <button
                    v-if="searchQuery"
                    type="button"
                    @click.stop="clearSearch"
                    class="text-slate-400 hover:text-slate-700 p-0.5 rounded-full hover:bg-slate-100 transition-colors cursor-pointer shrink-0 flex items-center"
                >
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
                <div v-else class="hidden md:flex items-center gap-2 shrink-0 text-[12px] text-slate-400 select-none">
                    <span>Coba ketik "GTY" atau nama unit</span>
                    <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-200/80 rounded text-[11px] font-mono text-slate-500">⌘K</kbd>
                </div>
            </div>

            <!-- Filters Button with Count Badge -->
            <button
                type="button"
                @click="showFilterPanel = !showFilterPanel"
                :class="[
                    'inline-flex items-center gap-2 px-4 py-2.5 rounded-full border text-[13px] font-semibold transition-all shadow-2xs cursor-pointer shrink-0',
                    showFilterPanel || activeFiltersCount > 0
                        ? 'bg-white border-emerald-600/40 text-slate-800 hover:bg-slate-50'
                        : 'bg-white border-slate-200/90 text-slate-700 hover:bg-slate-50'
                ]"
            >
                <span class="material-symbols-outlined text-[18px] text-slate-600">filter_list</span>
                <span>Filter</span>
                <span
                    v-if="activeFiltersCount > 0"
                    class="px-1.5 py-0.5 rounded-full bg-emerald-800 text-yellow-200 text-[11px] font-bold leading-none"
                >
                    {{ activeFiltersCount }}
                </span>
            </button>
        </div>

        <!-- ROW 3: Active Filter Chips (Left) + Entry Counter (Right) -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Status Chip -->
                <div
                    v-if="activeStatus && activeStatus !== 'semua'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200/90 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-400">Status adalah</span>
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
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200/90 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-400">Unit adalah</span>
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
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200/90 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-400">Jenjang adalah</span>
                    <strong class="text-slate-800 font-semibold">{{ activeJenjang }}</strong>
                    <button
                        type="button"
                        @click="emit('filter-jenjang', '')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Quick Filter Jenjang Pills (or + Add filter) -->
                <div class="inline-flex items-center gap-1 flex-wrap">
                    <button
                        v-for="j in jenjangList"
                        :key="j"
                        type="button"
                        @click="selectJenjang(j)"
                        :class="[
                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-[12px] font-medium transition-colors cursor-pointer',
                            activeJenjang === j
                                ? 'bg-emerald-900 border-emerald-900 text-yellow-200 font-semibold'
                                : 'border-dashed border-slate-300 text-slate-600 hover:border-slate-400 hover:text-slate-900 bg-white/60'
                        ]"
                    >
                        <span v-if="activeJenjang !== j" class="text-slate-400">+</span>
                        <span>{{ j }}</span>
                    </button>
                </div>

                <!-- Clear All Link -->
                <button
                    v-if="activeFiltersCount > 0"
                    type="button"
                    @click="resetAll"
                    class="text-[12.5px] font-semibold text-emerald-700 hover:text-emerald-900 ml-1 cursor-pointer transition-colors"
                >
                    Reset semua
                </button>
            </div>

            <!-- Right Entry Counter (e.g. "24 of 248 reports") -->
            <div class="text-[12.5px] text-slate-500 ml-auto shrink-0">
                <strong class="text-slate-900 font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} pegawai
            </div>
        </div>
    </div>
</template>
