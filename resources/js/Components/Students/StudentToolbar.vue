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
    <div class="bg-surface-container-lowest rounded-2xl p-4 md:p-5 mb-6 shadow-[0px_4px_20px_rgba(0,40,20,0.06)] border border-primary/10 flex flex-col gap-4">
        <!-- Row 1: Search Input & Action Downloads -->
        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            <!-- Search Bar with Clear Button -->
            <div class="relative flex-grow max-w-xl">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[20px] pointer-events-none">
                    search
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari berdasarkan nama, NISN, atau NIPD..."
                    class="w-full pl-11 pr-10 py-2.5 bg-surface-container-low hover:bg-surface-container focus:bg-surface-container-lowest border border-outline-variant/60 rounded-xl font-body-md text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                    @input="onSearchInput"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-variant transition-colors cursor-pointer"
                    title="Hapus pencarian"
                    aria-label="Hapus kata kunci pencarian"
                >
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Download & Action Buttons Group -->
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap">
                <a
                    href="/students/template"
                    download
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-secondary/50 text-secondary bg-secondary/5 hover:bg-secondary/10 active:bg-secondary/20 font-semibold text-xs sm:text-sm transition-all shadow-2xs hover:shadow-sm"
                    title="Download template XLSX resmi untuk import data siswa"
                >
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    <span>Download Template</span>
                </a>

                <button
                    type="button"
                    @click="$emit('open-import')"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                    title="Import data siswa dari file template Excel"
                >
                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                    <span>Import Data Siswa</span>
                </button>
            </div>
        </div>

        <!-- Row 2: Filter Dropdowns, Tampilkan Data Selector, Reset Action & Summary Counter -->
        <div class="pt-3 border-t border-outline-variant/30 flex flex-col lg:flex-row gap-3 items-start lg:items-center justify-between">
            <!-- Filter Dropdowns & Limit -->
            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                <!-- Filter Label -->
                <div class="hidden sm:flex items-center gap-1.5 text-on-surface-variant mr-1">
                    <span class="material-symbols-outlined text-[18px] text-primary">tune</span>
                    <span class="text-xs font-bold uppercase tracking-wider">Filter:</span>
                </div>

                <!-- Unit Filter -->
                <div class="relative min-w-[135px] sm:min-w-[155px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedUnit"
                        class="w-full pl-3 pr-8 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer transition-colors"
                        @change="onUnitChange"
                    >
                        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
                    </select>
                </div>

                <!-- Grade Filter (Dinamis sesuai unit) -->
                <div class="relative min-w-[115px] sm:min-w-[135px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedGrade"
                        class="w-full pl-3 pr-8 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer transition-colors"
                        @change="onGradeChange"
                    >
                        <option v-for="g in grades" :key="g.value" :value="g.value">{{ g.label }}</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="relative min-w-[125px] sm:min-w-[145px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedStatus"
                        class="w-full pl-3 pr-8 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer transition-colors"
                        @change="onStatusChange"
                    >
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>

                <!-- Tampilkan ... Data Selector Filter -->
                <div class="relative min-w-[150px] sm:min-w-[170px] flex-grow sm:flex-grow-0">
                    <div class="relative flex items-center">
                        <select
                            :value="perPage"
                            class="w-full pl-8 pr-8 py-2 bg-surface-container-low hover:bg-surface-container border border-outline-variant/60 focus:border-primary rounded-xl text-xs sm:text-sm font-medium text-on-surface focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer transition-colors appearance-none"
                            @change="onPerPageChange"
                            title="Tampilkan jumlah baris data per halaman"
                        >
                            <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                        <span class="material-symbols-outlined text-primary text-[18px] pointer-events-none absolute left-2.5">
                            format_list_numbered
                        </span>
                        <span class="material-symbols-outlined text-outline text-[16px] pointer-events-none absolute right-2.5">
                            expand_more
                        </span>
                    </div>
                </div>

                <!-- Reset Filter Button (Only shows when filters/search active) -->
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    @click="resetAll"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-semibold text-error hover:bg-error-container/30 border border-error/30 transition-colors whitespace-nowrap cursor-pointer"
                    title="Hapus seluruh filter dan pencarian"
                >
                    <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                    <span>Reset Filter</span>
                </button>
            </div>

            <!-- Result Counter / Filter Summary -->
            <div class="text-xs text-on-surface-variant font-medium self-end lg:self-center shrink-0">
                <span v-if="hasActiveFilters" class="inline-flex items-center gap-1">
                    Menampilkan <strong class="text-primary font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} siswa
                </span>
                <span v-else class="text-outline">
                    Total: <strong class="text-on-surface font-semibold">{{ totalCount }}</strong> peserta didik
                </span>
            </div>
        </div>
    </div>
</template>
