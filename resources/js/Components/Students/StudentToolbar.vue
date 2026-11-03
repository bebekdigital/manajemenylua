<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

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
        default: 15,
    },
    activeStatus: {
        type: String,
        default: 'aktif',
    },
    activeAngkatan: {
        type: Array,
        default: () => [],
    },
    activeJenjang: {
        type: Array,
        default: () => [],
    },
    activeJk: {
        type: String,
        default: '',
    },
});

const emit = defineEmits([
    'search',
    'filter-status',
    'filter-angkatan',
    'filter-jenjang',
    'filter-jk',
    'update:per-page',
    'reset-filters',
]);

const searchQuery = ref('');
const isFilterOpen = ref(false);
const filterDropdownRef = ref(null);

// Status counts (Semua, Aktif, Tidak Aktif)
const statusCounts = computed(() => {
    const all = props.students.length;
    let aktif = 0;
    let tidakAktif = 0;

    props.students.forEach(s => {
        const status = (s.student_status || 'aktif').toLowerCase();
        if (status === 'aktif') {
            aktif++;
        } else {
            tidakAktif++;
        }
    });

    return {
        semua: all,
        aktif,
        tidak_aktif: tidakAktif,
    };
});

const statusTabs = computed(() => [
    { key: 'semua', label: 'Semua', count: statusCounts.value.semua },
    { key: 'aktif', label: 'Aktif', count: statusCounts.value.aktif },
    { key: 'tidak_aktif', label: 'Tidak Aktif', count: statusCounts.value.tidak_aktif },
]);

// Daftar angkatan unik
const availableAngkatan = computed(() => {
    return [...new Set(props.students.map(s => s.angkatan).filter(Boolean))].sort((a, b) => b - a);
});

// Daftar jenjang unik
const availableJenjang = computed(() => {
    return [...new Set(props.students.map(s => s.jenjang).filter(Boolean))].sort();
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
    if (props.activeAngkatan && props.activeAngkatan.length > 0) count += props.activeAngkatan.length;
    if (props.activeJenjang && props.activeJenjang.length > 0) count += props.activeJenjang.length;
    if (props.activeJk) count++;
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

// Multi-select toggle for Angkatan
function toggleAngkatan(ang) {
    const current = [...props.activeAngkatan];
    const index = current.indexOf(ang);
    if (index > -1) {
        current.splice(index, 1);
    } else {
        current.push(ang);
    }
    emit('filter-angkatan', current);
}

function removeAngkatan(ang) {
    const current = props.activeAngkatan.filter(item => item !== ang);
    emit('filter-angkatan', current);
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

function selectJk(jk) {
    emit('filter-jk', jk);
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
        <!-- ROW 1: Segmented Status Tabs (Left) + Per Page Selector (Right) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Segmented Status Tabs (Default: Aktif) -->
            <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto custom-tab-scrollbar max-w-full border border-slate-200/90 shrink-0">
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

            <!-- Right Controls: Per Page Selector -->
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                <div class="relative">
                    <select
                        :value="perPage"
                        @change="onPerPageChange"
                        class="pl-3.5 pr-8 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-[12.5px] sm:text-[13px] font-medium text-slate-700 focus:outline-none focus:border-emerald-500 shadow-2xs cursor-pointer appearance-none transition-colors"
                        title="Tampilkan jumlah baris data per halaman"
                    >
                        <option v-for="opt in perPageOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                    <span class="material-symbols-outlined text-[15px] text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none">
                        expand_more
                    </span>
                </div>
            </div>
        </div>

        <!-- ROW 2: Search Bar (Database Siswa Style) + Filter Dropdown Button -->
        <div class="flex items-center gap-2.5 relative">
            <!-- Search Bar -->
            <div class="relative flex-1 min-w-0">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[19px] pointer-events-none">
                    search
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama siswa, NISN, NIPD, angkatan, nomor WA..."
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
                        class="absolute right-0 mt-2 w-72 sm:w-80 bg-white rounded-2xl border border-slate-200 shadow-xl z-50 p-4 space-y-3.5 max-h-[80vh] overflow-y-auto"
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

                        <!-- Angkatan Checklist (Multi-select) -->
                        <div v-if="availableAngkatan.length > 0">
                            <span class="text-[12px] font-bold text-slate-700 block mb-1">Angkatan</span>
                            <span class="text-[11px] text-slate-400 block mb-2">Bisa pilih lebih dari 1</span>
                            <div class="space-y-1 max-h-40 overflow-y-auto pr-1">
                                <label
                                    v-for="ang in availableAngkatan"
                                    :key="ang"
                                    class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer text-[12.5px] transition-colors"
                                >
                                    <span class="text-slate-700 font-medium">Angkatan {{ ang }}</span>
                                    <input
                                        type="checkbox"
                                        :checked="activeAngkatan.includes(ang)"
                                        @change="toggleAngkatan(ang)"
                                        class="w-4 h-4 rounded text-emerald-800 border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Jenjang Checklist (Jika ada lebih dari 1) -->
                        <div v-if="availableJenjang.length > 1" class="pt-2 border-t border-slate-100">
                            <span class="text-[12px] font-bold text-slate-700 block mb-1">Jenjang</span>
                            <div class="space-y-1 max-h-32 overflow-y-auto pr-1">
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

                        <!-- Jenis Kelamin Radio -->
                        <div class="pt-2 border-t border-slate-100">
                            <span class="text-[12px] font-bold text-slate-700 block mb-2">Jenis Kelamin</span>
                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    type="button"
                                    @click="selectJk('')"
                                    :class="[
                                        'py-1.5 px-2 rounded-lg text-[12px] font-medium transition-all text-center cursor-pointer border',
                                        activeJk === ''
                                            ? 'bg-emerald-900 text-yellow-200 border-emerald-900 font-bold'
                                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                                    ]"
                                >
                                    Semua
                                </button>
                                <button
                                    type="button"
                                    @click="selectJk('L')"
                                    :class="[
                                        'py-1.5 px-2 rounded-lg text-[12px] font-medium transition-all text-center cursor-pointer border',
                                        activeJk === 'L'
                                            ? 'bg-blue-600 text-white border-blue-600 font-bold'
                                            : 'bg-blue-50/50 text-blue-700 border-blue-200 hover:bg-blue-50'
                                    ]"
                                >
                                    Laki-laki
                                </button>
                                <button
                                    type="button"
                                    @click="selectJk('P')"
                                    :class="[
                                        'py-1.5 px-2 rounded-lg text-[12px] font-medium transition-all text-center cursor-pointer border',
                                        activeJk === 'P'
                                            ? 'bg-rose-600 text-white border-rose-600 font-bold'
                                            : 'bg-rose-50/50 text-rose-700 border-rose-200 hover:bg-rose-50'
                                    ]"
                                >
                                    Perempuan
                                </button>
                            </div>
                        </div>

                        <!-- Status Keaktifan Radio -->
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
                                        name="student_status_radio"
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

        <!-- ROW 3: Active Filter Chips Muncul Di Atas Tabel -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-0.5">
            <div class="flex flex-wrap items-center gap-1.5 min-h-[30px]">
                <!-- Status Chip -->
                <div
                    v-if="activeStatus && activeStatus !== 'semua'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-500">Status:</span>
                    <strong class="text-slate-800 font-semibold capitalize">{{ activeStatus === 'tidak_aktif' ? 'Tidak Aktif' : activeStatus }}</strong>
                    <button
                        type="button"
                        @click="selectStatusTab('semua')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                        title="Hapus filter status"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

                <!-- Angkatan Chips (Multi-select) -->
                <template v-if="activeAngkatan && activeAngkatan.length > 0">
                    <div
                        v-for="ang in activeAngkatan"
                        :key="ang"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-[12.5px] shadow-2xs"
                    >
                        <span class="text-slate-500">Angkatan:</span>
                        <strong class="text-slate-800 font-semibold">{{ ang }}</strong>
                        <button
                            type="button"
                            @click="removeAngkatan(ang)"
                            class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                            :title="`Hapus filter angkatan ${ang}`"
                        >
                            <span class="material-symbols-outlined text-[15px]">close</span>
                        </button>
                    </div>
                </template>

                <!-- Jenjang Chips (Multi-select) -->
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

                <!-- JK Chip -->
                <div
                    v-if="activeJk"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-50 border border-slate-300 text-[12.5px] shadow-2xs"
                >
                    <span class="text-slate-500">JK:</span>
                    <strong class="text-slate-800 font-semibold">{{ activeJk === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong>
                    <button
                        type="button"
                        @click="selectJk('')"
                        class="text-slate-400 hover:text-slate-700 ml-0.5 flex items-center cursor-pointer"
                        title="Hapus filter jenis kelamin"
                    >
                        <span class="material-symbols-outlined text-[15px]">close</span>
                    </button>
                </div>

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

            <!-- Right Entry Counter -->
            <div class="text-[12.5px] text-slate-500 ml-auto shrink-0">
                <strong class="text-slate-900 font-bold">{{ filteredCount }}</strong> dari {{ totalCount }} siswa
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
