<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = function (event) {
            if (!(el == event.target || el.contains(event.target))) {
                binding.value(event, el);
            }
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.body.removeEventListener('click', el.clickOutsideEvent);
    },
};

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

const statuses = [
    { value: 'aktif', label: 'Aktif', colorClass: 'text-primary bg-primary/10 border-primary/20 hover:bg-primary/20', dotClass: 'bg-primary' },
    { value: 'tidak_aktif', label: 'Tidak Aktif', colorClass: 'text-error bg-error/10 border-error/20 hover:bg-error/20', dotClass: 'bg-error' },
    { value: 'semua', label: 'Semua Status', colorClass: 'text-slate-700 bg-slate-100 border-slate-300 hover:bg-slate-200', dotClass: 'bg-slate-500' },
];

const showStatusDropdown = ref(false);

const currentStatusObj = computed(() => {
    return statuses.find(s => s.value === selectedStatus.value) || statuses[0];
});

function selectStatus(val) {
    selectedStatus.value = val;
    showStatusDropdown.value = false;
    onStatusChange();
}

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

                <!-- Custom Status Filter (Moved to left) -->
                <div v-if="!hideStatusFilter" class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[130px] flex-grow sm:flex-grow-0 shrink-0" v-click-outside="() => showStatusDropdown = false">
                    <button
                        type="button"
                        @click="showStatusDropdown = !showStatusDropdown"
                        :class="[
                            'flex items-center justify-between w-full px-3 py-2 sm:py-2 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-semibold border transition-all cursor-pointer',
                            currentStatusObj.colorClass
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full" :class="currentStatusObj.dotClass"></span>
                            {{ currentStatusObj.label }}
                        </div>
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px] transition-transform duration-200" :class="{ 'rotate-180': showStatusDropdown }">expand_more</span>
                    </button>
                    
                    <div v-show="showStatusDropdown" class="absolute z-[100] top-full left-0 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg py-1 overflow-hidden">
                        <button
                            v-for="s in statuses"
                            :key="s.value"
                            type="button"
                            @click="selectStatus(s.value)"
                            class="flex items-center gap-2 w-full px-3 py-2 text-[12px] sm:text-sm text-left hover:bg-slate-50 transition-colors font-medium text-slate-700"
                        >
                            <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full" :class="s.dotClass"></span>
                            {{ s.label }}
                        </button>
                    </div>
                </div>

                <!-- Grade Filter (Dinamis sesuai unit) -->
                <div class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[100px] lg:min-w-[90px] flex-grow sm:flex-grow-0 shrink-0">
                    <select
                        v-model="selectedGrade"
                        class="w-full pl-2 sm:pl-3 pr-6 sm:pr-8 py-2 sm:py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                        @change="onGradeChange"
                    >
                        <option v-for="g in grades" :key="g.value" :value="g.value">{{ g.label }}</option>
                    </select>
                </div>

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
