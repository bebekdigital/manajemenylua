<script setup>
import { ref, computed } from 'vue';
import StatusFilterDropdown from '@/Components/Shared/StatusFilterDropdown.vue';

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
    hideStatusFilter: {
        type: Boolean,
        default: false,
    },
    hidePerPage: {
        type: Boolean,
        default: false,
    },
    hideDefaultActions: {
        type: Boolean,
        default: false,
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
const selectedStatus = ref('aktif');

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



const perPageOptions = [
    { value: 10, label: '10 Data' },
    { value: 25, label: '25 Data' },
    { value: 50, label: '50 Data' },
    { value: 100, label: '100 Data' },
    { value: 0, label: 'Semua Data' },
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
    selectedStatus.value = 'aktif';
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
        <div class="flex flex-col lg:flex-row gap-3 sm:gap-4 items-start lg:items-center justify-between w-full">
            
            <!-- Left Side: Filters -->
            <div class="flex flex-wrap lg:flex-nowrap items-center gap-2 w-full lg:w-auto flex-grow justify-start">
                <!-- Filter Label -->
                <div class="hidden sm:flex items-center gap-1.5 text-on-surface-variant mr-1 shrink-0">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-primary">tune</span>
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Filter:</span>
                </div>

                <!-- Custom Status Filter (Reusable Component) -->
                <StatusFilterDropdown
                    v-if="!hideStatusFilter"
                    v-model="selectedStatus"
                    @change="onStatusChange"
                />


                <!-- Extra Filters Slot -->
                <slot name="extra-filters"></slot>


            </div>

            <!-- Right Side: Search & Tampilkan Data -->
            <div class="flex items-center gap-2 w-full lg:w-auto shrink-0 justify-between flex-nowrap">
                <!-- Search Bar with Clear Button -->
                <div class="relative flex-1 min-w-0 sm:flex-none sm:w-64 lg:w-56 2xl:w-72">
                    <span class="material-symbols-outlined absolute left-2.5 sm:left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] sm:text-[20px] pointer-events-none">
                        search
                    </span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, NISN..."
                        class="w-full pl-8 sm:pl-11 pr-8 sm:pr-10 py-2 sm:py-2.5 bg-white hover:bg-slate-50 focus:bg-white border border-slate-300 rounded-lg sm:rounded-xl font-body-sm sm:font-body-md text-[12px] sm:text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
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
                <div v-if="!hidePerPage" class="relative w-[95px] shrink-0 sm:w-auto sm:min-w-[120px]">
                    <div class="relative flex items-center">
                        <select
                            :value="perPage"
                            class="w-full pl-6 sm:pl-8 pr-6 sm:pr-8 py-2 sm:py-2 bg-white hover:bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors appearance-none"
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


    </div>
</template>
