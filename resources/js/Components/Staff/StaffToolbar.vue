<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

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
const isFilterOpen = ref(false);
const filterDropdownRef = ref(null);

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

const activeFiltersCount = computed(() => {
    let count = 0;
    if (props.activeStatus && props.activeStatus !== 'semua') count++;
    if (props.activeUnit) count++;
    if (props.activeJenjang && props.activeJenjang.length > 0) count += props.activeJenjang.length;
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

function onPerPageChange(event) {
    emit('update:per-page', Number(event.target.value));
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
        <!-- ROW 1: Segmented Status Tabs (Left) + Unit & Per Page Controls (Right) -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Segmented Status Tabs (Default: Aktif) -->
            <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto no-scrollbar max-w-full border border-slate-200/90 shrink-0">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    type="button"
                    @click="selectStatusTab(tab.key)"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-[13px] transition-all duration-150 cursor-pointer shrink-0',
                        activeStatus === tab.key
                            ? 'bg-white text-slate-900 font-bold shadow-xs ring-1 ring-slate-200/80'
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

            <!-- Right Controls: Unit Dropdown + Per Page Selector -->
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

        <!-- ROW 2: Search Bar (Gaya Database Siswa: rounded-xl, border-slate-300) + Filter Button Dropdown -->
        <div class="flex items-center gap-2.5 relative">
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

            <!-- Filters Button dengan Dropdown Checklist Multi-select -->
            <div class="relative" ref="filterDropdownRef">
                <button
                    type="button"
                    @click="isFilterOpen = !isFilterOpen"
                    :class="[
                        'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-[13px] font-semibold transition-all shadow-2xs cursor-pointer shrink-0',
                        isFilterOpen || activeFiltersCount > 0
                            ? 'bg-emerald-50 border-emerald-600 text-emerald-950 ring-2 ring-emerald-600/10'
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
                    <span class="material-symbols-outlined text-[16px] text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isFilterOpen }">
                        expand_more
                    </span>
                </button>

                <!-- Filter Checklist Dropdown Menu -->
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
                        class="absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-slate-200 shadow-xl z-50 p-4 space-y-3.5"
                    >
                        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pilih Filter</span>
                            <button
                                v-if="activeFiltersCount > 0"
                                type="button"
                                @click="resetAll"
                                class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-950 cursor-pointer"
                            >
                                Reset Semua
                            </button>
                        </div>

                        <!-- Jenjang Kepegawaian Checklist (Multi-select) -->
                        <div>
                            <span class="text-[12px] font-bold text-slate-700 block mb-1">Jenjang Pegawai</span>
                            <span class="text-[11px] text-slate-400 block mb-2">Bisa pilih lebih dari 1</span>
                            <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                                <label
                                    v-for="j in availableJenjang"
                                    :key="j"
                                    class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer text-[12.5px] transition-colors"
                                >
                                    <span class="text-slate-700 font-medium">{{ j }}</span>
                                    <input
                                        type="checkbox"
                                        :checked="activeJenjang.includes(j)"
                                        @change="toggleJenjang(j)"
                                        class="w-4 h-4 rounded text-emerald-800 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Status Keaktifan Checklist -->
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-[12px] font-bold text-slate-700 block mb-2">Status Keaktifan</span>
                            <div class="space-y-1">
                                <label
                                    v-for="s in statusTabs"
                                    :key="s.key"
                                    class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer text-[12.5px] transition-colors"
                                >
                                    <span class="text-slate-700 font-medium">{{ s.label }}</span>
                                    <input
                                        type="radio"
                                        name="status_radio"
                                        :checked="activeStatus === s.key"
                                        @change="selectStatusTab(s.key)"
                                        class="w-4 h-4 text-emerald-800 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- ROW 3: Active Filter Chips Muncul Di Atas Tabel (Sesuai Referensi Gambar) -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
            <div class="flex flex-wrap items-center gap-1.5 min-h-[30px]">
                <!-- Status Chip (Muncul jika status bukan Aktif atau jika ada pilihan) -->
                <div
                    v-if="activeStatus && activeStatus !== 'semua'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-500">Status:</span>
                    <strong class="text-slate-800 font-semibold capitalize">{{ activeStatus }}</strong>
                    <button
                        type="button"
                        @click="selectStatusTab('semua')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                        title="Hapus filter status"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Unit Chip (Muncul saat unit dipilih) -->
                <div
                    v-if="activeUnit"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-500">Unit:</span>
                    <strong class="text-slate-800 font-semibold">{{ activeUnit }}</strong>
                    <button
                        type="button"
                        @click="emit('filter-unit', '')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                        title="Hapus filter unit"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Jenjang Chips (Muncul untuk setiap jenjang yang dipilih pada dropdown) -->
                <template v-if="activeJenjang && activeJenjang.length > 0">
                    <div
                        v-for="j in activeJenjang"
                        :key="j"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-[12.5px] shadow-2xs"
                    >
                        <span class="text-slate-500">Jenjang:</span>
                        <strong class="text-slate-800 font-semibold">{{ j }}</strong>
                        <button
                            type="button"
                            @click="removeJenjang(j)"
                            class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                            :title="`Hapus filter jenjang ${j}`"
                        >
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>
                </template>

                <!-- Tombol Reset Filter jika ada filter aktif -->
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
/* Hilangkan scrollbar horizontal pada filter namun tetap bisa di-scroll / swipe pada perangkat mobile */
.no-scrollbar {
    -ms-overflow-style: none; /* IE and Edge */
    scrollbar-width: none; /* Firefox */
}

.no-scrollbar::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}
</style>
