<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    students: {
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
});

const emit = defineEmits([
    'search',
    'filter-unit',
    'filter-grade',
    'filter-status',
    'update:per-page',
    'reset-filters',
    'open-import',
]);

const searchQuery = ref('');
const selectedUnit = ref('');
const selectedGrade = ref('');
const selectedStatus = ref('');

// Build dynamic unit options from actual student data
const units = computed(() => {
    const uniqueUnits = [...new Set(props.students.map(s => s.unit).filter(Boolean))];
    return [
        { value: '', label: 'Semua Unit' },
        ...uniqueUnits.map(u => ({ value: u, label: u })),
    ];
});

// Build dynamic grade/kelas options filtered by selected unit
const grades = computed(() => {
    let filtered = props.students;
    if (selectedUnit.value) {
        filtered = filtered.filter(s => s.unit === selectedUnit.value);
    }
    const uniqueGrades = [...new Set(filtered.map(s => s.kelas).filter(Boolean))].sort();
    return [
        { value: '', label: 'Semua Kelas' },
        ...uniqueGrades.map(g => ({ value: g, label: `Kelas ${g}` })),
    ];
});

const statuses = [
    { value: '', label: 'Semua Status' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'mutasi_masuk', label: 'Mutasi Masuk' },
    { value: 'mutasi_keluar', label: 'Mutasi Keluar' },
    { value: 'lulus', label: 'Lulus' },
    { value: 'mengulang', label: 'Mengulang' },
    { value: 'dropout', label: 'Dropout' },
];

const perPageOptions = [
    { value: 10, label: 'Tampilkan 10 data' },
    { value: 25, label: 'Tampilkan 25 data' },
    { value: 50, label: 'Tampilkan 50 data' },
    { value: 100, label: 'Tampilkan 100 data' },
    { value: 0, label: 'Tampilkan Semua data' },
];

const hasActiveFilters = computed(() => {
    return Boolean(
        searchQuery.value.trim() !== '' ||
        selectedUnit.value !== '' ||
        selectedGrade.value !== '' ||
        selectedStatus.value !== ''
    );
});

function onSearchInput() {
    emit('search', searchQuery.value);
}

function clearSearch() {
    searchQuery.value = '';
    emit('search', '');
}

function onUnitChange() {
    // Reset grade when unit changes since available classes differ per unit
    selectedGrade.value = '';
    emit('filter-unit', selectedUnit.value);
    emit('filter-grade', '');
}

function onGradeChange() {
    emit('filter-grade', selectedGrade.value);
}

function onStatusChange() {
    emit('filter-status', selectedStatus.value);
}

function onPerPageChange(event) {
    emit('update:per-page', Number(event.target.value));
}

function resetAll() {
    searchQuery.value = '';
    selectedUnit.value = '';
    selectedGrade.value = '';
    selectedStatus.value = '';
    emit('search', '');
    emit('filter-unit', '');
    emit('filter-grade', '');
    emit('filter-status', '');
    emit('reset-filters');
}
</script>

<template>
    <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 mb-4 sm:mb-6 shadow-sm border border-slate-200 flex flex-col gap-3 sm:gap-4">
        <!-- Main Row: Search and Filters -->
        <div class="flex flex-col xl:flex-row gap-2 sm:gap-3 items-start xl:items-center justify-between w-full">
            
            <!-- Left Side: Filter Unit & Kelas -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 w-full xl:w-auto flex-grow justify-start">
                <!-- Filter Label -->
                <div class="hidden sm:flex items-center gap-1.5 text-on-surface-variant mr-1">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-primary">tune</span>
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Filter:</span>
                </div>

                <!-- Unit Filter -->
                <div class="relative min-w-[110px] xl:min-w-[100px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedUnit"
                        class="w-full pl-2 sm:pl-3 pr-6 sm:pr-8 py-1.5 sm:py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[11px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                        @change="onUnitChange"
                    >
                        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
                    </select>
                </div>

                <!-- Grade Filter (Dinamis sesuai unit) -->
                <div class="relative min-w-[100px] xl:min-w-[90px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedGrade"
                        class="w-full pl-2 sm:pl-3 pr-6 sm:pr-8 py-1.5 sm:py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[11px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                        @change="onGradeChange"
                    >
                        <option v-for="g in grades" :key="g.value" :value="g.value">{{ g.label }}</option>
                    </select>
                </div>

                <!-- Reset Filter Button -->
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    @click="resetAll"
                    class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-semibold text-error hover:bg-error-container/30 border border-error/30 transition-colors whitespace-nowrap cursor-pointer"
                    title="Hapus seluruh filter dan pencarian"
                >
                    <span class="material-symbols-outlined text-[14px] sm:text-[16px]">restart_alt</span>
                    <span class="hidden sm:inline">Reset</span>
                </button>
            </div>

            <!-- Right Side: Search & Tampilkan Data -->
            <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-3 w-full xl:w-auto shrink-0 justify-end">
                <!-- Search Bar with Clear Button -->
                <div class="relative w-full sm:w-64 xl:w-56 2xl:w-72 shrink-0">
                    <span class="material-symbols-outlined absolute left-2.5 sm:left-3.5 top-1/2 -translate-y-1/2 text-outline text-[16px] sm:text-[20px] pointer-events-none">
                        search
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, NISN..."
                        class="w-full pl-8 sm:pl-11 pr-8 sm:pr-10 py-1.5 sm:py-2.5 bg-white hover:bg-slate-50 focus:bg-white border border-slate-300 rounded-lg sm:rounded-xl font-body-sm sm:font-body-md text-[11px] sm:text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
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
                        <span class="material-symbols-outlined text-[14px] sm:text-[18px]">close</span>
                    </button>
                </div>

                <!-- Tampilkan ... Data Selector Filter -->
                <div class="relative min-w-[120px] w-full sm:w-auto flex-grow sm:flex-grow-0">
                    <div class="relative flex items-center">
                        <select
                            :value="perPage"
                            class="w-full pl-7 sm:pl-8 pr-6 sm:pr-8 py-1.5 sm:py-2 bg-white hover:bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-lg sm:rounded-xl text-[11px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors appearance-none"
                            @change="onPerPageChange"
                            title="Tampilkan jumlah baris data per halaman"
                        >
                            <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <span class="material-symbols-outlined text-primary text-[14px] sm:text-[18px] pointer-events-none absolute left-2 sm:left-2.5">
                            format_list_numbered
                        </span>
                        <span class="material-symbols-outlined text-outline text-[14px] sm:text-[16px] pointer-events-none absolute right-2 sm:right-2.5">
                            expand_more
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result Counter / Filter Summary -->
        <div class="pt-2 sm:pt-3 border-t border-outline-variant/30 text-[10px] sm:text-xs text-on-surface-variant font-medium flex justify-start sm:justify-end shrink-0">
            <span v-if="hasActiveFilters" class="inline-flex items-center gap-1">
                Menampilkan <strong class="text-primary font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} siswa
            </span>
            <span v-else class="text-outline">
                Total: <strong class="text-on-surface font-semibold">{{ totalCount }}</strong> peserta didik
            </span>
        </div>
    </div>
</template>
