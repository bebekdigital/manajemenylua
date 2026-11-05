<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AppSelect from '@/Components/Shared/AppSelect.vue';

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
        default: 'aktif',
    },
    activeUnit: {
        type: String,
        default: '',
    },
    activeJenjang: {
        type: Array,
        default: () => [],
    },
    searchQuery: {
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

const searchQuery = ref(props.searchQuery || '');
const isFilterOpen = ref(false);
const filterDropdownRef = ref(null);

watch(() => props.searchQuery, (newVal) => {
    if (newVal !== searchQuery.value) {
        searchQuery.value = newVal || '';
    }
});

// Status counts (Semua, Aktif, Pensiun, Non-Aktif - tanpa Cuti)
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

// Daftar jenjang yang tersedia (untuk checklist dropdown Filter)
const availableJenjang = computed(() => {
    return [...new Set(props.employees.map(e => e.jenjang_kepegawaian).filter(Boolean))].sort();
});

const perPageOptions = [
    { value: 10, label: '10 Data' },
    { value: 15, label: '15 Data' },
    { value: 25, label: '25 Data' },
    { value: 50, label: '50 Data' },
    { value: 100, label: '100 Data' },
    { value: 0, label: 'Semua Data' },
];

const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (props.activeUnit) count++;
    if (props.activeJenjang && props.activeJenjang.length > 0) count += props.activeJenjang.length;
    return count;
});

// Cek apakah ada filter tambahan di luar filter status keaktifan (Unit, Jenjang, atau Pencarian)
const hasAdditionalFilters = computed(() => {
    return Boolean(
        (props.activeUnit && props.activeUnit.trim()) ||
        (props.activeJenjang && props.activeJenjang.length > 0) ||
        (searchQuery.value && searchQuery.value.trim())
    );
});

// Angka penyebut ("dari ... pegawai")
// Jika hanya filter keaktifan, diambil dari total semua pegawai (misal: 80 dari 102).
// Jika ada tambahan filter lain, diambil dari total keaktifan yang dipilih (misal: 25 dari 80).
const displayTotalCount = computed(() => {
    if (hasAdditionalFilters.value) {
        const key = props.activeStatus?.toLowerCase() || 'aktif';
        if (statusCounts.value[key] !== undefined) {
            return statusCounts.value[key];
        }
    }
    return props.totalCount;
});

const activeFilterChips = computed(() => {
    const chips = [];
    if (props.activeUnit) {
        chips.push({
            id: 'unit',
            label: `Unit: ${props.activeUnit}`,
            onRemove: () => emit('filter-unit', ''),
        });
    }
    props.activeJenjang.forEach(j => {
        chips.push({
            id: `jenjang-${j}`,
            label: `Jenjang: ${j}`,
            onRemove: () => removeJenjang(j),
        });
    });
    if (searchQuery.value.trim()) {
        chips.push({
            id: 'search',
            label: `Cari: "${searchQuery.value.trim()}"`,
            onRemove: () => clearSearch(),
        });
    }
    return chips;
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

function onUnitChange(val) {
    const value = typeof val === 'object' && val !== null && 'target' in val ? val.target.value : val;
    emit('filter-unit', value);
}

// Multi-select toggle for Jenjang
function toggleJenjang(j) {
    const current = [...props.activeJenjang];
    const index = current.indexOf(j);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(j);
    }
    emit('filter-jenjang', current);
}

function removeJenjang(j) {
    const current = props.activeJenjang.filter(item => item !== j);
    emit('filter-jenjang', current);
}

function onPerPageChange(val) {
    const value = typeof val === 'object' && val !== null && 'target' in val ? val.target.value : val;
    emit('update:per-page', Number(value));
}

function resetAll() {
    searchQuery.value = '';
    emit('reset-filters');
    isFilterOpen.value = false;
}

function handleClickOutside(event) {
    if (filterDropdownRef.value && !filterDropdownRef.value.contains(event.target)) {
        isFilterOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="mb-4 space-y-3">
        <!-- ROW 1: Segmented Status Tabs (DI ATAS SEARCH) - Sesuai Style Mobile + Per Page & Counter (Kanan) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Status Tabs bergaya mobile pill buttons -->
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    type="button"
                    @click="selectStatusTab(tab.key)"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all shrink-0 cursor-pointer',
                        activeStatus === tab.key
                            ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 border border-transparent'
                    ]"
                >
                    <span v-if="tab.key === 'aktif'" class="w-2 h-2 rounded-full bg-emerald-600 inline-block"></span>
                    <span>{{ tab.label }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[10.5px] font-bold',
                            activeStatus === tab.key
                                ? 'bg-emerald-900 text-yellow-200'
                                : 'bg-slate-200 text-slate-600'
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- Right Controls: Per Page Selector & Counter -->
            <div class="flex items-center gap-3 shrink-0 ml-auto sm:ml-0">
                <div class="hidden lg:block text-xs text-slate-500 font-medium">
                    Menampilkan <strong class="text-slate-900 font-bold">{{ filteredCount }}</strong> dari {{ displayTotalCount }} pegawai
                </div>

                <AppSelect
                    :model-value="perPage"
                    :options="perPageOptions"
                    title="Tampilkan jumlah baris data per halaman"
                    placement="right"
                    @update:model-value="onPerPageChange"
                />
            </div>
        </div>

        <!-- ROW 2: Search Bar + Filter Button (Unit & Jenjang ada di dalam tombol Filter seperti mobile) -->
        <div class="flex items-center gap-2.5 relative">
            <!-- Search Bar -->
            <div class="relative flex-1 min-w-0">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[19px] text-slate-400 pointer-events-none">
                    search
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama, NIPY, jabatan, unit kerja, nomor WA..."
                    class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-300 bg-white text-xs sm:text-[13px] text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-700 shadow-2xs transition-all"
                    @input="onSearchInput"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 rounded-full cursor-pointer"
                    title="Hapus pencarian"
                >
                    <span class="material-symbols-outlined text-[16px] block">close</span>
                </button>
            </div>

            <!-- Filter Button Dropdown (Unit & Jenjang) -->
            <div class="relative" ref="filterDropdownRef">
                <button
                    type="button"
                    @click="isFilterOpen = !isFilterOpen"
                    :class="[
                        'h-[42px] px-3.5 sm:px-4 rounded-xl border flex items-center gap-2 transition-all cursor-pointer shrink-0 shadow-2xs',
                        isFilterOpen || activeExtraFiltersCount > 0
                            ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold'
                            : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'
                    ]"
                    title="Buka filter lanjutan"
                >
                    <span class="material-symbols-outlined text-[19px] text-emerald-800">filter_alt</span>
                    <span>Filter</span>
                    <span
                        v-if="activeExtraFiltersCount > 0"
                        class="w-5 h-5 rounded-full bg-emerald-900 text-yellow-200 text-[10.5px] font-bold flex items-center justify-center leading-none"
                    >
                        {{ activeExtraFiltersCount }}
                    </span>
                    <span
                        class="material-symbols-outlined text-[16px] text-slate-400 transition-transform duration-200"
                        :class="{ 'rotate-180': isFilterOpen }"
                    >
                        expand_more
                    </span>
                </button>

                <!-- Popover Filter Menu -->
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                    enter-to-class="transform scale-100 opacity-100 translate-y-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="transform scale-100 opacity-100 translate-y-0"
                    leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                >
                    <div
                        v-if="isFilterOpen"
                        class="absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-slate-200 shadow-xl z-50 p-4 space-y-4 animate-in fade-in"
                    >
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-800">
                                <span class="material-symbols-outlined text-[18px] text-emerald-800">tune</span>
                                <span>Filter Pegawai</span>
                            </div>
                            <button
                                v-if="activeExtraFiltersCount > 0"
                                type="button"
                                @click="emit('filter-unit', ''); emit('filter-jenjang', [])"
                                class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-950 cursor-pointer"
                            >
                                Reset Filter Ini
                            </button>
                        </div>

                        <!-- Unit Kerja Selection -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Unit Kerja
                            </label>
                            <select
                                :value="activeUnit"
                                @change="onUnitChange"
                                class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium text-slate-800 bg-white focus:ring-2 focus:ring-emerald-700 focus:outline-none cursor-pointer"
                            >
                                <option value="">Semua Unit Kerja</option>
                                <option v-for="u in units.filter(x => x.value)" :key="u.value" :value="u.value">
                                    {{ u.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Jenjang Kepegawaian Checkboxes -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Jenjang Pegawai
                                </label>
                                <span class="text-[11px] text-slate-400">Pilih multi</span>
                            </div>
                            <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                                <label
                                    v-for="j in availableJenjang"
                                    :key="j"
                                    class="flex items-center justify-between p-2 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs transition-colors"
                                >
                                    <span class="text-slate-800 font-medium">{{ j }}</span>
                                    <input
                                        type="checkbox"
                                        :checked="activeJenjang.includes(j)"
                                        @change="toggleJenjang(j)"
                                        class="w-4 h-4 rounded text-emerald-900 border-slate-300 focus:ring-emerald-700 cursor-pointer"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Tombol Selesai -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
                            <button
                                type="button"
                                @click="isFilterOpen = false"
                                class="px-4 py-1.5 rounded-xl bg-emerald-900 text-yellow-200 font-bold text-xs hover:bg-emerald-800 transition-colors cursor-pointer shadow-2xs"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- ROW 3: Active Filter Chips & Counter (DI BAWAH SEARCH BAR) -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
            <!-- Active Chips -->
            <div class="flex flex-wrap items-center gap-1.5">
                <template v-if="activeFilterChips.length > 0">
                    <span class="text-[11px] font-medium text-slate-400">Filter Aktif:</span>
                    <span
                        v-for="chip in activeFilterChips"
                        :key="chip.id"
                        class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100/90 text-emerald-950 border border-emerald-300/80 shadow-2xs"
                    >
                        <span>{{ chip.label }}</span>
                        <button
                            type="button"
                            @click="chip.onRemove"
                            class="w-3.5 h-3.5 rounded-full hover:bg-emerald-200 text-emerald-800 flex items-center justify-center cursor-pointer"
                            title="Hapus filter ini"
                        >
                            <span class="material-symbols-outlined text-[12px]">close</span>
                        </button>
                    </span>
                    <button
                        type="button"
                        @click="resetAll"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-colors cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[13px]">restart_alt</span>
                        <span>Reset Semua</span>
                    </button>
                </template>
            </div>

            <!-- Counter for smaller screens when hidden in Row 1 -->
            <div class="lg:hidden text-xs text-slate-500 font-medium ml-auto">
                Menampilkan <strong class="text-slate-900 font-bold">{{ filteredCount }}</strong> dari {{ displayTotalCount }} pegawai
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
