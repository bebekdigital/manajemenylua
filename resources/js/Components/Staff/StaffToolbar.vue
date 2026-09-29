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
});

const emit = defineEmits([
    'search',
    'filter-unit',
    'filter-status',
    'update:per-page',
    'reset-filters',
]);

const searchQuery = ref('');
const selectedUnit = ref('');
const selectedStatus = ref('');

const units = computed(() => {
    const uniqueUnits = [...new Set(props.employees.map(e => e.unit).filter(Boolean))].sort();
    return [
        { value: '', label: 'Semua Unit' },
        ...uniqueUnits.map(u => ({ value: u, label: u })),
    ];
});

const statuses = [
    { value: '', label: 'Semua Status' },
    { value: 'Aktif', label: 'Aktif' },
    { value: 'Non-Aktif', label: 'Non-Aktif' },
    { value: 'Pensiun', label: 'Pensiun' },
    { value: 'Cuti', label: 'Cuti' },
];

const perPageOptions = [
    { value: 10, label: '10 Data' },
    { value: 25, label: '25 Data' },
    { value: 50, label: '50 Data' },
    { value: 100, label: '100 Data' },
    { value: 0, label: 'Semua Data' },
];

const hasActiveFilters = computed(() =>
    searchQuery.value.trim() !== '' ||
    selectedUnit.value !== '' ||
    selectedStatus.value !== ''
);

function onSearchInput() {
    emit('search', searchQuery.value);
}

function clearSearch() {
    searchQuery.value = '';
    emit('search', '');
}

function onUnitChange() {
    emit('filter-unit', selectedUnit.value);
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
    selectedStatus.value = '';
    emit('search', '');
    emit('filter-unit', '');
    emit('filter-status', '');
    emit('reset-filters');
}
</script>

<template>
    <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 md:p-5 mb-4 sm:mb-6 shadow-sm border border-slate-200 flex flex-col gap-3 sm:gap-4">
        <!-- Main Row -->
        <div class="flex flex-col xl:flex-row gap-3 sm:gap-4 items-start xl:items-center justify-between w-full">

            <!-- Left: Filters -->
            <div class="flex flex-wrap items-center gap-2 w-full xl:w-auto flex-grow justify-start">
                <div class="hidden sm:flex items-center gap-1.5 text-on-surface-variant mr-1 w-full sm:w-auto">
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-primary">tune</span>
                    <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Filter:</span>
                </div>

                <!-- Unit Filter -->
                <div class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[130px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedUnit"
                        class="w-full pl-2 sm:pl-3 pr-6 sm:pr-8 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                        @change="onUnitChange"
                    >
                        <option v-for="u in units" :key="u.value" :value="u.value">{{ u.label }}</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[120px] flex-grow sm:flex-grow-0">
                    <select
                        v-model="selectedStatus"
                        class="w-full pl-2 sm:pl-3 pr-6 sm:pr-8 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                        @change="onStatusChange"
                    >
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="w-full sm:w-auto mt-1 sm:mt-0 flex justify-end" v-if="hasActiveFilters">
                    <button
                        type="button"
                        @click="resetAll"
                        class="inline-flex items-center justify-center gap-1 w-full sm:w-auto px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg sm:rounded-xl text-[11px] sm:text-xs font-semibold text-error hover:bg-error-container/30 border border-error/30 transition-colors whitespace-nowrap cursor-pointer"
                        title="Hapus seluruh filter dan pencarian"
                    >
                        <span class="material-symbols-outlined text-[15px] sm:text-[16px]">restart_alt</span>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Right: Search & Per Page -->
            <div class="flex items-center gap-2 w-full xl:w-auto shrink-0 justify-between flex-nowrap">
                <!-- Search -->
                <div class="relative flex-1 min-w-0 sm:flex-none sm:w-64 xl:w-56 2xl:w-72">
                    <span class="material-symbols-outlined absolute left-2.5 sm:left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] sm:text-[20px] pointer-events-none">search</span>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, NIPY..."
                        class="w-full pl-8 sm:pl-11 pr-8 sm:pr-10 py-2 sm:py-2.5 bg-white hover:bg-slate-50 focus:bg-white border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                        @input="onSearchInput"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-variant transition-colors cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">close</span>
                    </button>
                </div>

                <!-- Per Page -->
                <div class="relative w-[95px] shrink-0 sm:w-auto sm:min-w-[120px]">
                    <div class="relative flex items-center">
                        <select
                            :value="perPage"
                            class="w-full pl-6 sm:pl-8 pr-6 sm:pr-8 py-2 bg-white hover:bg-slate-50 border border-slate-300 focus:border-emerald-500 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors appearance-none"
                            @change="onPerPageChange"
                        >
                            <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <span class="material-symbols-outlined text-primary text-[15px] sm:text-[18px] pointer-events-none absolute left-1.5 sm:left-2.5">format_list_numbered</span>
                        <span class="material-symbols-outlined text-outline text-[15px] sm:text-[16px] pointer-events-none absolute right-1.5 sm:right-2.5">expand_more</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Summary -->
        <div v-if="hasActiveFilters" class="pt-2 sm:pt-3 border-t border-outline-variant/30 text-[10px] sm:text-xs text-on-surface-variant font-medium flex justify-start sm:justify-end shrink-0">
            <span class="inline-flex items-center gap-1">
                Menampilkan <strong class="text-primary font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} pegawai
            </span>
        </div>
    </div>
</template>
