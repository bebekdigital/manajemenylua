<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StaffToolbar from '@/Components/Staff/StaffToolbar.vue';
import StaffTable from '@/Components/Staff/StaffTable.vue';
import StaffImportModal from '@/Components/Staff/StaffImportModal.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
    isTeacher: {
        type: Boolean,
        default: false,
    },
    teacherEmployee: {
        type: Object,
        default: null,
    },
    permissions: {
        type: Object,
        default: () => ({
            can_create: true,
            can_update: true,
            can_export: true,
            can_import: true,
        }),
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
});

const page = usePage();

// ============================================================
// State
// ============================================================
const searchQuery = ref('');
const unitFilter = ref('');
const statusFilter = ref('aktif'); // Default Aktif sesuai permintaan user
const jenjangFilters = ref([]); // Multi-select array untuk Jenjang
const sortBy = ref('nama');
const sortDirection = ref('asc');
const currentPage = ref(1);
const perPage = ref(15);
const showImportModal = ref(false);
const dismissedFlash = ref(false);
const isKelolaOpen = ref(false);
const kelolaDropdownRef = ref(null);
const isMobileFilterDrawerOpen = ref(false);

const pageContainerRef = ref(null);
const mainTitleRef = ref(null);
const isScrolledPastTitle = ref(false);
let titleObserver = null;

function checkTitleVisibility() {
    if (mainTitleRef.value) {
        const rect = mainTitleRef.value.getBoundingClientRect();
        isScrolledPastTitle.value = rect.bottom < 15;
    }
}

function handleClickOutside(event) {
    if (kelolaDropdownRef.value && !kelolaDropdownRef.value.contains(event.target)) {
        isKelolaOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('scroll', checkTitleVisibility, { passive: true });

    if (pageContainerRef.value) {
        pageContainerRef.value.addEventListener('scroll', checkTitleVisibility, { passive: true });
    }

    if ('IntersectionObserver' in window && mainTitleRef.value) {
        titleObserver = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting && entry.boundingClientRect.top < 50) {
                    isScrolledPastTitle.value = true;
                } else if (entry.isIntersecting) {
                    isScrolledPastTitle.value = false;
                }
            },
            {
                root: null,
                threshold: 0,
            }
        );
        titleObserver.observe(mainTitleRef.value);
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', checkTitleVisibility);
    if (pageContainerRef.value) {
        pageContainerRef.value.removeEventListener('scroll', checkTitleVisibility);
    }
    if (titleObserver) {
        titleObserver.disconnect();
    }
});

const flashSuccess = computed(() => !dismissedFlash.value ? page.props.flash?.success : null);
const flashError = computed(() => !dismissedFlash.value ? page.props.flash?.error : null);

const uniqueUnitsCount = computed(() => {
    return new Set(props.employees.map(e => e.unit_kerja).filter(Boolean)).size;
});

// Rekap Status Keaktifan (Semua, Aktif, Pensiun, Non-Aktif)
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

const availableJenjang = computed(() => {
    return [...new Set(props.employees.map(e => e.jenjang_kepegawaian).filter(Boolean))].sort();
});

const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (unitFilter.value) count++;
    if (jenjangFilters.value.length > 0) count += jenjangFilters.value.length;
    return count;
});

const hasActiveFilters = computed(() => {
    return Boolean(
        unitFilter.value ||
        jenjangFilters.value.length > 0 ||
        searchQuery.value.trim() ||
        (statusFilter.value && statusFilter.value !== 'aktif')
    );
});

const hasAdditionalFilters = computed(() => {
    return Boolean(
        (unitFilter.value && unitFilter.value.trim()) ||
        (jenjangFilters.value && jenjangFilters.value.length > 0) ||
        (searchQuery.value && searchQuery.value.trim())
    );
});

const displayTotalCount = computed(() => {
    if (hasAdditionalFilters.value) {
        const key = statusFilter.value?.toLowerCase() || 'aktif';
        if (statusCounts.value[key] !== undefined) {
            return statusCounts.value[key];
        }
    }
    return props.employees.length;
});

const activeFilterChips = computed(() => {
    const chips = [];
    if (unitFilter.value) {
        chips.push({
            id: 'unit',
            label: `Unit: ${unitFilter.value}`,
            onRemove: () => {
                unitFilter.value = '';
                currentPage.value = 1;
            },
        });
    }
    jenjangFilters.value.forEach(j => {
        chips.push({
            id: `jenjang-${j}`,
            label: `Jenjang: ${j}`,
            onRemove: () => {
                jenjangFilters.value = jenjangFilters.value.filter(item => item !== j);
                currentPage.value = 1;
            },
        });
    });
    if (searchQuery.value.trim()) {
        chips.push({
            id: 'search',
            label: `Cari: "${searchQuery.value.trim()}"`,
            onRemove: () => {
                searchQuery.value = '';
                currentPage.value = 1;
            },
        });
    }
    return chips;
});

function getInitials(name) {
    if (!name) return 'PG';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
}

const pastelPalette = [
    'bg-[#cfe4fc] text-[#1e40af] border-[#bfdbfe]',
    'bg-[#fde2e4] text-[#be185d] border-[#fbcfe8]',
    'bg-[#edd8f8] text-[#6b21a8] border-[#e9d5ff]',
    'bg-[#d1fae5] text-[#065f46] border-[#a7f3d0]',
    'bg-[#fef3c7] text-[#92400e] border-[#fde68a]',
    'bg-[#e0e7ff] text-[#3730a3] border-[#c7d2fe]',
    'bg-[#ccfbf1] text-[#115e59] border-[#99f6e4]',
];

function getAvatarStyle(emp) {
    const id = emp.id || 0;
    const name = emp.nama || '';
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash + id) % pastelPalette.length;
    return pastelPalette[index];
}

const filteredEmployees = computed(() => {
    let result = [...props.employees];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(e =>
            (e.nama || '').toLowerCase().includes(query) ||
            (e.nipy || '').toLowerCase().includes(query) ||
            (e.jabatan || '').toLowerCase().includes(query) ||
            (e.unit_kerja || '').toLowerCase().includes(query) ||
            (e.no_wa || '').toLowerCase().includes(query)
        );
    }

    if (unitFilter.value) {
        result = result.filter(e => e.unit_kerja === unitFilter.value);
    }

    // Multi-select jenjang filter
    if (jenjangFilters.value.length > 0) {
        const activeList = jenjangFilters.value.map(j => j.toUpperCase());
        result = result.filter(e => activeList.includes((e.jenjang_kepegawaian || '').toUpperCase()));
    }

    if (statusFilter.value && statusFilter.value !== 'semua') {
        const val = statusFilter.value.toLowerCase();
        if (val === 'aktif') {
            result = result.filter(e => !e.status_keaktifan || e.status_keaktifan.toLowerCase() === 'aktif');
        } else if (val === 'tidak_aktif' || val === 'non-aktif') {
            result = result.filter(e => e.status_keaktifan && ['non-aktif', 'tidak aktif', 'tidak_aktif', 'resign', 'keluar'].includes(e.status_keaktifan.toLowerCase()));
        } else {
            result = result.filter(e => (e.status_keaktifan || '').toLowerCase() === val);
        }
    }

    // Sorting via table header
    result.sort((a, b) => {
        const field = sortBy.value;
        const valA = (a[field] || '').toString().toLowerCase();
        const valB = (b[field] || '').toString().toLowerCase();
        if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

const totalEntries = computed(() => filteredEmployees.value.length);

const totalPages = computed(() => {
    if (perPage.value === 0) return 1;
    return Math.ceil(filteredEmployees.value.length / perPage.value) || 1;
});

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    if (total <= 5) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    if (current <= 3) {
        return [1, 2, 3, '...', total];
    }
    if (current >= total - 2) {
        return [1, '...', total - 2, total - 1, total];
    }
    return [1, '...', current, '...', total];
});

const paginatedEmployees = computed(() => {
    if (perPage.value === 0) return filteredEmployees.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredEmployees.value.slice(start, start + perPage.value);
});

function onPerPageChange(val) {
    perPage.value = Number(val);
    currentPage.value = 1;
}

function onSearch(query) {
    searchQuery.value = query;
    currentPage.value = 1;
}

function onFilterUnit(unit) {
    unitFilter.value = unit;
    currentPage.value = 1;
}

function onFilterStatus(status) {
    statusFilter.value = status;
    currentPage.value = 1;
}

function onFilterJenjang(jenjangs) {
    jenjangFilters.value = jenjangs;
    currentPage.value = 1;
}

function onSortChange({ field, direction }) {
    if (sortBy.value === field && !direction) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDirection.value = direction || 'asc';
    }
    currentPage.value = 1;
}

function onResetFilters() {
    searchQuery.value = '';
    unitFilter.value = '';
    statusFilter.value = 'aktif';
    jenjangFilters.value = [];
    sortBy.value = 'nama';
    sortDirection.value = 'asc';
    currentPage.value = 1;
}

function onPageChange(p) {
    if (p < 1 || p > totalPages.value) return;
    currentPage.value = p;
    if (window.innerWidth < 768) {
        window.scrollTo({ top: 120, behavior: 'smooth' });
    }
}
</script>

<template>
    <div ref="pageContainerRef" class="flex-1 overflow-y-auto px-4 pt-6 pb-12 md:px-8 md:py-7 bg-white min-h-screen">
        <Head title="Database Pegawai - Foundation Data Center" />
        <div class="max-w-[1440px] mx-auto">
            <!-- Teacher Role Scoping Notice Banner -->
            <div
                v-if="isTeacher"
                class="mb-4 p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex items-start gap-3.5"
            >
                <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">shield_person</span>
                <div class="flex-1">
                    <h4 class="text-[13.5px] font-bold text-emerald-950">Mode Akses Guru</h4>
                    <p v-if="teacherEmployee" class="text-[12.5px] text-emerald-800 mt-0.5 leading-relaxed">
                        Sesuai kebijakan hak akses, Anda hanya dapat melihat dan mengelola data kepegawaian atas nama Anda sendiri:
                        <strong class="font-semibold text-emerald-950 underline">{{ teacherEmployee.nama }}</strong>.
                    </p>
                    <p v-else class="text-[12.5px] text-amber-800 mt-0.5 leading-relaxed">
                        Akun login Anda belum terhubung dengan data profil pegawai di sistem. Harap hubungi Superadmin atau Bagian SDM untuk menautkan NIPY atau Nama Anda.
                    </p>
                </div>
            </div>

            <!-- Flash Notifications -->
            <div
                v-if="flashSuccess"
                class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">check_circle</span>
                    <div>
                        <h4 class="text-[13.5px] font-bold text-emerald-900">Import Berhasil</h4>
                        <p class="text-[12.5px] text-emerald-800 mt-0.5 leading-relaxed">{{ flashSuccess }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="dismissedFlash = true"
                    class="text-emerald-700 hover:text-emerald-950 p-1 rounded-full hover:bg-emerald-100 transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <div
                v-if="flashError"
                class="mb-4 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
            >
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-rose-600 text-2xl shrink-0 mt-0.5">error</span>
                    <div>
                        <h4 class="text-[13.5px] font-bold text-rose-900">Import Gagal</h4>
                        <p class="text-[12.5px] text-rose-800 mt-0.5 leading-relaxed">{{ flashError }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="dismissedFlash = true"
                    class="text-rose-600 hover:text-rose-900 p-1 rounded-full hover:bg-rose-100 transition-colors cursor-pointer"
                >
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <!-- Sticky Mini Topbar Judul Tengah Saat Di-scroll Keluar Layar -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="-translate-y-full opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="-translate-y-full opacity-0"
            >
                <div
                    v-if="isScrolledPastTitle"
                    class="md:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md shadow-md border-b border-slate-200/80 px-4 py-2.5 flex items-center justify-center pt-[max(0.75rem,env(safe-area-inset-top))]"
                >
                    <div class="text-center min-w-0">
                        <h2 class="text-sm font-bold text-slate-900 tracking-tight truncate">
                            Database Pegawai
                        </h2>
                        <p class="text-[10px] text-slate-500 font-medium truncate -mt-0.5">
                            {{ filteredEmployees.length }} dari {{ displayTotalCount }} pegawai
                        </p>
                    </div>
                </div>
            </Transition>

            <!-- ============================================================ -->
            <!-- TAMPILAN MOBILE (md:hidden) SESUAI STYLE FOTO REFERENSI      -->
            <!-- ============================================================ -->
            <div class="md:hidden pb-16">
                <!-- 1. Header: Judul Database Pegawai + Tombol Kelola Samping Judul -->
                <div class="flex items-center justify-between gap-3 pt-3 sm:pt-4 mb-6">
                    <div class="min-w-0">
                        <h1 ref="mainTitleRef" class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Database Pegawai
                        </h1>
                        <p class="text-xs text-slate-500 mt-1 font-medium truncate">
                            {{ employees.length }} pegawai • {{ uniqueUnitsCount }} unit kerja
                        </p>
                    </div>

                    <!-- Tombol Kelola Sesuai Hak Akses di Samping Judul -->
                    <div
                        v-if="!isTeacher && (permissions?.can_export || permissions?.can_import || permissions?.can_create)"
                        class="relative shrink-0"
                        ref="kelolaDropdownRef"
                    >
                        <button
                            type="button"
                            @click="isKelolaOpen = !isKelolaOpen"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs shadow-xs transition-all cursor-pointer active:scale-95 border border-emerald-950/30"
                        >
                            <span class="material-symbols-outlined text-[17px]">tune</span>
                            <span>Kelola</span>
                            <span class="material-symbols-outlined text-[16px] transition-transform duration-200" :class="{ 'rotate-180': isKelolaOpen }">expand_more</span>
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
                            enter-to-class="transform scale-100 opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="transform scale-100 opacity-100 translate-y-0"
                            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
                        >
                            <div
                                v-if="isKelolaOpen"
                                class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-50 animate-in fade-in"
                            >
                                <a
                                    v-if="permissions?.can_export"
                                    href="/staff/template"
                                    download
                                    @click="isKelolaOpen = false"
                                    class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-emerald-900 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">download</span>
                                    <span>Download Template</span>
                                </a>
                                <button
                                    v-if="permissions?.can_import"
                                    type="button"
                                    @click="isKelolaOpen = false; showImportModal = true"
                                    class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-xs font-semibold text-emerald-950 hover:bg-emerald-50 transition-colors cursor-pointer text-left border-t border-slate-100"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-800">upload_file</span>
                                    <span>Import Data Pegawai</span>
                                </button>
                            </div>
                        </Transition>
                    </div>

                    <!-- Tombol Profil Saya Khusus Role Guru -->
                    <div v-else-if="teacherEmployee" class="shrink-0">
                        <Link
                            :href="`/staff/${teacherEmployee.id}`"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs shadow-xs"
                        >
                            <span class="material-symbols-outlined text-[16px]">person</span>
                            <span>Profil</span>
                        </Link>
                    </div>
                </div>

                <!-- 2. Filter Status Keaktifan (DI ATAS SEARCH) -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 mb-3">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.key"
                        type="button"
                        @click="onFilterStatus(tab.key)"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all shrink-0 cursor-pointer',
                            statusFilter === tab.key
                                ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80 border border-transparent'
                        ]"
                    >
                        <span v-if="tab.key === 'aktif'" class="w-2 h-2 rounded-full bg-emerald-600 inline-block"></span>
                        <span>{{ tab.label }}</span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded-full text-[10.5px] font-bold',
                                statusFilter === tab.key
                                    ? 'bg-emerald-900 text-yellow-200'
                                    : 'bg-slate-200 text-slate-600'
                            ]"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- 3. Search Bar + Tombol Filter -->
                <div class="flex items-center gap-2 mb-3">
                    <div class="relative flex-1">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px] text-slate-400">
                            search
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, NIPY, jabatan..."
                            class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-slate-300 bg-white text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-700 shadow-2xs transition-all"
                            @input="currentPage = 1"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            @click="searchQuery = ''; currentPage = 1"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 rounded-full"
                        >
                            <span class="material-symbols-outlined text-[16px] block">close</span>
                        </button>
                    </div>

                    <!-- Tombol Filter Popover/Drawer -->
                    <button
                        type="button"
                        @click="isMobileFilterDrawerOpen = !isMobileFilterDrawerOpen"
                        :class="[
                            'h-[42px] px-3.5 rounded-xl border flex items-center gap-1.5 transition-all cursor-pointer shrink-0 shadow-2xs',
                            activeExtraFiltersCount > 0
                                ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold'
                                : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'
                        ]"
                        title="Buka filter lanjutan"
                    >
                        <span class="material-symbols-outlined text-[18px]">filter_alt</span>
                        <span
                            v-if="activeExtraFiltersCount > 0"
                            class="w-5 h-5 rounded-full bg-emerald-900 text-yellow-200 text-[10px] font-bold flex items-center justify-center"
                        >
                            {{ activeExtraFiltersCount }}
                        </span>
                        <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    </button>
                </div>

                <!-- 4. Controller Filter Mana Saja yang Dipilih (DI BAWAH SEARCH) -->
                <div v-if="activeFilterChips.length > 0" class="flex items-center gap-1.5 flex-wrap mb-4">
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
                        @click="onResetFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-colors cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[13px]">restart_alt</span>
                        <span>Reset</span>
                    </button>
                </div>

                <!-- 5. Card List Container: DATA PEGAWAI Sesuai Referensi -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 shadow-xs">
                    <!-- Header Kotak Card -->
                    <div class="flex items-center justify-between mb-3 px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Pegawai</h3>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">
                            {{ filteredEmployees.length }} dari {{ displayTotalCount }}
                        </span>
                    </div>

                    <!-- Daftar Card Pegawai -->
                    <div v-if="paginatedEmployees.length > 0" class="space-y-3">
                        <Link
                            v-for="emp in paginatedEmployees"
                            :key="emp.id"
                            :href="`/staff/${emp.id}`"
                            class="bg-white rounded-2xl border border-slate-200/90 p-3.5 shadow-2xs hover:border-emerald-400 hover:shadow-xs transition-all flex items-center justify-between gap-3 group active:scale-[0.99] cursor-pointer"
                        >
                            <!-- Avatar Kotak Rounded Warna Pastel Halus + Inisial 2 Huruf -->
                            <div
                                :class="[
                                    'w-13 h-13 rounded-2xl flex items-center justify-center font-bold text-base shrink-0 border shadow-2xs',
                                    getAvatarStyle(emp)
                                ]"
                            >
                                <span>{{ getInitials(emp.nama) }}</span>
                            </div>

                            <!-- Detail Pegawai -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[13.5px] font-bold text-slate-900 group-hover:text-emerald-950 transition-colors leading-tight">
                                        {{ emp.nama }}
                                    </span>
                                    <span
                                        v-if="emp.jk"
                                        :class="[
                                            'text-[9.5px] font-bold px-1.5 py-0.5 rounded-md border shrink-0',
                                            emp.jk === 'L'
                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                : 'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                    >
                                        {{ emp.jk }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium mt-1 truncate">
                                    <span>NIPY: {{ emp.nipy || '-' }}</span>
                                    <span>•</span>
                                    <span class="inline-flex items-center gap-1 truncate">
                                        <span class="material-symbols-outlined text-[13px] text-slate-400 shrink-0">apartment</span>
                                        <span class="truncate">{{ emp.unit_kerja || 'Yayasan' }}</span>
                                    </span>
                                </div>

                                <div v-if="emp.jabatan" class="text-[11px] text-slate-400 truncate mt-0.5">
                                    {{ emp.jabatan }}
                                </div>
                            </div>

                            <!-- Panah Chevron Kanan -->
                            <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all shrink-0">
                                chevron_right
                            </span>
                        </Link>
                    </div>

                    <!-- State Kosong Mobile -->
                    <div v-else class="py-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                            <span class="material-symbols-outlined text-2xl">search_off</span>
                        </div>
                        <p class="text-sm font-bold text-slate-700">Tidak ada pegawai ditemukan</p>
                        <p class="text-xs text-slate-400 mt-1">Coba sesuaikan filter atau kata kunci pencarian.</p>
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="onResetFilters"
                            class="mt-3 px-3 py-1.5 rounded-full text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 cursor-pointer"
                        >
                            Reset Filter
                        </button>
                    </div>

                    <!-- Paginasi Mobile Lengkap & Mudah Digunakan -->
                    <div v-if="totalPages > 1" class="mt-5 pt-4 border-t border-slate-100 space-y-3">
                        <div class="flex items-center justify-between gap-1.5">
                            <button
                                type="button"
                                :disabled="currentPage <= 1"
                                @click="onPageChange(currentPage - 1)"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold text-xs disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer shadow-2xs hover:bg-slate-50 active:scale-95 transition-all"
                            >
                                <span class="material-symbols-outlined text-[16px]">chevron_left</span>
                                <span class="hidden sm:inline">Sebelumnya</span>
                            </button>

                            <!-- Nomor Halaman -->
                            <div class="flex items-center gap-1">
                                <template v-for="(p, idx) in visiblePages" :key="idx">
                                    <span v-if="p === '...'" class="px-1 text-slate-400 text-xs font-bold">...</span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="onPageChange(p)"
                                        :class="[
                                            'w-8 h-8 rounded-xl text-xs font-bold flex items-center justify-center transition-all cursor-pointer',
                                            currentPage === p
                                                ? 'bg-emerald-900 text-yellow-200 shadow-2xs'
                                                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                                        ]"
                                    >
                                        {{ p }}
                                    </button>
                                </template>
                            </div>

                            <button
                                type="button"
                                :disabled="currentPage >= totalPages"
                                @click="onPageChange(currentPage + 1)"
                                class="inline-flex items-center gap-1 px-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold text-xs disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer shadow-2xs hover:bg-slate-50 active:scale-95 transition-all"
                            >
                                <span class="hidden sm:inline">Berikutnya</span>
                                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                            </button>
                        </div>

                        <!-- Keterangan Jumlah Data Halaman -->
                        <div class="text-center text-[11px] text-slate-400 font-medium">
                            Menampilkan {{ ((currentPage - 1) * perPage) + 1 }} - {{ Math.min(currentPage * perPage, filteredEmployees.length) }} dari {{ filteredEmployees.length }} pegawai
                        </div>
                    </div>
                </div>

                <!-- Bottom Sheet / Modal Filter Mobile untuk Jenjang & Unit -->
                <div
                    v-if="isMobileFilterDrawerOpen"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/40 backdrop-blur-xs animate-in fade-in"
                    @click.self="isMobileFilterDrawerOpen = false"
                >
                    <div class="w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-3xl p-5 shadow-2xl border border-slate-200 max-h-[85vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px] text-emerald-800">filter_alt</span>
                                <h3 class="text-sm font-bold text-slate-900">Filter Pegawai</h3>
                            </div>
                            <button
                                type="button"
                                @click="isMobileFilterDrawerOpen = false"
                                class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>

                        <!-- Unit Kerja Selection: Langsung ditampilkan semua pilihan seperti pada Jenjang -->
                        <div class="mb-5">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Unit Kerja
                                </label>
                                <span class="text-[11px] text-slate-400">Pilih 1 unit</span>
                            </div>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                <!-- Pilihan Semua Unit Kerja -->
                                <label
                                    class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer text-xs transition-colors"
                                    :class="!unitFilter ? 'border-emerald-500 bg-emerald-50/60 font-bold text-emerald-950 shadow-2xs' : 'border-slate-200 hover:bg-slate-50 text-slate-800'"
                                >
                                    <span>Semua Unit Kerja</span>
                                    <input
                                        type="radio"
                                        name="mobile_unit_filter"
                                        value=""
                                        v-model="unitFilter"
                                        class="w-4 h-4 text-emerald-900 focus:ring-emerald-700 border-slate-300 cursor-pointer"
                                    />
                                </label>

                                <!-- Pilihan Setiap Unit Kerja -->
                                <label
                                    v-for="u in units.filter(x => x.value)"
                                    :key="u.value"
                                    class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer text-xs transition-colors"
                                    :class="unitFilter === u.value ? 'border-emerald-500 bg-emerald-50/60 font-bold text-emerald-950 shadow-2xs' : 'border-slate-200 hover:bg-slate-50 text-slate-800'"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[16px] text-slate-400">apartment</span>
                                        <span>{{ u.label }}</span>
                                    </div>
                                    <input
                                        type="radio"
                                        name="mobile_unit_filter"
                                        :value="u.value"
                                        v-model="unitFilter"
                                        class="w-4 h-4 text-emerald-900 focus:ring-emerald-700 border-slate-300 cursor-pointer"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Jenjang Kepegawaian Checkboxes -->
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Jenjang Kepegawaian
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                <label
                                    v-for="j in availableJenjang"
                                    :key="j"
                                    class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs"
                                >
                                    <span class="font-medium text-slate-800">{{ j }}</span>
                                    <input
                                        type="checkbox"
                                        :value="j"
                                        v-model="jenjangFilters"
                                        class="w-4 h-4 rounded text-emerald-900 focus:ring-emerald-700 border-slate-300 cursor-pointer"
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Tombol Aksi Modal Filter -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="unitFilter = ''; jenjangFilters = []; currentPage = 1"
                                class="flex-1 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
                            >
                                Reset
                            </button>
                            <button
                                type="button"
                                @click="isMobileFilterDrawerOpen = false; currentPage = 1"
                                class="flex-1 py-2 rounded-xl bg-emerald-900 text-yellow-200 font-bold text-xs shadow-xs hover:bg-emerald-800 cursor-pointer"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAMPILAN DESKTOP (hidden md:block)                           -->
            <!-- ============================================================ -->
            <div class="hidden md:block">
                <!-- Page Header Desktop -->
                <div class="mb-5 sm:mb-6 flex flex-row items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl md:text-[26px] font-bold text-slate-900 tracking-tight leading-tight">
                            Database Pegawai
                        </h1>
                        <p class="text-xs sm:text-[13.5px] text-slate-500 mt-1 sm:mt-1.5 font-medium truncate">
                            <strong class="text-slate-700 font-semibold">{{ employees.length }}</strong> pegawai di <strong class="text-slate-700 font-semibold">{{ uniqueUnitsCount }}</strong> unit kerja
                        </p>
                    </div>

                    <!-- Desktop Action Buttons -->
                    <div v-if="!isTeacher" class="flex items-center gap-2.5 shrink-0">
                        <a
                            v-if="permissions?.can_export"
                            href="/staff/template"
                            download
                            class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 hover:border-slate-400 font-semibold text-[13px] transition-all shadow-2xs"
                            title="Download template XLSX untuk import data pegawai"
                        >
                            <span class="material-symbols-outlined text-[18px] text-emerald-700">download</span>
                            <span>Download Template</span>
                        </a>

                        <button
                            v-if="permissions?.can_import"
                            type="button"
                            @click="showImportModal = true"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-semibold text-[13px] transition-all shadow-xs cursor-pointer active:scale-95"
                            title="Import data pegawai dari file template Excel"
                        >
                            <span class="material-symbols-outlined text-[18px]">upload_file</span>
                            <span>Import Data Pegawai</span>
                        </button>
                    </div>
                    <div v-else-if="teacherEmployee" class="flex items-center gap-2 shrink-0">
                        <Link
                            :href="`/staff/${teacherEmployee.id}`"
                            class="inline-flex items-center justify-center gap-2 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-semibold text-xs sm:text-[13px] transition-all shadow-xs"
                        >
                            <span class="material-symbols-outlined text-[17px] sm:text-[18px]">person</span>
                            <span>Buka Profil Saya</span>
                        </Link>
                    </div>
                </div>

                <!-- Desktop Toolbar -->
                <StaffToolbar
                    :employees="employees"
                    :total-count="employees.length"
                    :filtered-count="totalEntries"
                    :per-page="perPage"
                    :active-status="statusFilter"
                    :active-unit="unitFilter"
                    :active-jenjang="jenjangFilters"
                    @update:per-page="onPerPageChange"
                    @search="onSearch"
                    @filter-unit="onFilterUnit"
                    @filter-status="onFilterStatus"
                    @filter-jenjang="onFilterJenjang"
                    @reset-filters="onResetFilters"
                />

                <!-- Desktop Table -->
                <StaffTable
                    :employees="paginatedEmployees"
                    :current-page="currentPage"
                    :per-page="perPage"
                    :total-entries="totalEntries"
                    :sort-by="sortBy"
                    :sort-direction="sortDirection"
                    :can-update="permissions?.can_update ?? true"
                    @update:per-page="onPerPageChange"
                    @page-change="onPageChange"
                    @sort-change="onSortChange"
                />
            </div>
        </div>

        <!-- Import Modal -->
        <StaffImportModal
            :show="showImportModal"
            @close="showImportModal = false"
            @success="dismissedFlash = false"
        />
    </div>
</template>
