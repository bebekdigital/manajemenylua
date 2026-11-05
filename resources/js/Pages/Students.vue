<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StudentToolbar from '@/Components/Students/StudentToolbar.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';
import StudentImportModal from '@/Components/Students/StudentImportModal.vue';
import StudentPhotoUploadModal from '@/Components/Students/StudentPhotoUploadModal.vue';
import EditDataButton from '@/Components/Shared/EditDataButton.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
    selectedAcademicYear: {
        type: Object,
        default: null,
    },
});

const page = usePage();

// ============================================================
// Jenjang Pendidikan / Unit Options
// ============================================================
const unitTabs = [
    { id: 'all', label: 'Semua Jenjang Pendidikan', icon: 'school' },
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain' },
];

const activeUnitTab = ref(localStorage.getItem('activeUnitTab') || 'all');

watch(activeUnitTab, (newVal) => {
    localStorage.setItem('activeUnitTab', newVal);
});

function unitStudentCount(unitId) {
    if (unitId === 'all') return props.students.length;

    return props.students.filter(s => {
        if (!s.unit) return false;
        const u = s.unit.toUpperCase();
        if (unitId === 'SDIT') {
            return u.includes('SDIT') || u.includes('SD IT') || u === 'SD';
        }
        if (unitId === 'SMPIT') {
            return u.includes('SMPIT') || u.includes('SMP IT') || u === 'SMP';
        }
        return false;
    }).length;
}

const unitTabStudents = computed(() => {
    if (activeUnitTab.value === 'all') return props.students;

    return props.students.filter(s => {
        if (!s.unit) return false;
        const u = s.unit.toUpperCase();
        if (activeUnitTab.value === 'SDIT') {
            return u.includes('SDIT') || u.includes('SD IT') || u === 'SD';
        }
        if (activeUnitTab.value === 'SMPIT') {
            return u.includes('SMPIT') || u.includes('SMP IT') || u === 'SMP';
        }
        return false;
    });
});

const activeUnitTabLabel = computed(() => {
    if (activeUnitTab.value === 'all') return 'Semua Jenjang Pendidikan';
    return unitTabs.find(t => t.id === activeUnitTab.value)?.label ?? activeUnitTab.value;
});

const unitOptions = computed(() => [
    { value: 'all', label: `Semua Jenjang Pendidikan (${unitStudentCount('all')})` },
    { value: 'SDIT', label: `SDIT (${unitStudentCount('SDIT')})` },
    { value: 'SMPIT', label: `SMPIT (${unitStudentCount('SMPIT')})` },
]);

function switchUnitTab(unitId) {
    activeUnitTab.value = unitId;
    searchQuery.value = '';
    statusFilter.value = 'aktif';
    angkatanFilters.value = [];
    jenjangFilters.value = [];
    jkFilter.value = '';
    sortBy.value = 'nama';
    sortDirection.value = 'asc';
    currentPage.value = 1;
}

// ============================================================
// Search, Filter & Sort State
// ============================================================
const searchQuery = ref('');
const statusFilter = ref('aktif');
const angkatanFilters = ref([]);
const jenjangFilters = ref([]);
const jkFilter = ref('');
const sortBy = ref('nama');
const sortDirection = ref('asc');
const currentPage = ref(1);
const perPage = ref(15);

// Modal, Drawer & Dropdown states
const isKelolaOpen = ref(false);
const isMobileFilterDrawerOpen = ref(false);
const showImportModal = ref(false);
const showPhotoUploadModal = ref(false);
const dismissedFlash = ref(false);
const showEditDropdown = ref(false);
const showTemplateDropdown = ref(false);
const isDownloadingTemplate = ref(false);

// Scroll & Sticky Title Observer
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

onMounted(() => {
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

function onImportSuccess() {
    dismissedFlash.value = false;
}

// Rekap Status Keaktifan
const statusCounts = computed(() => {
    const list = unitTabStudents.value;
    const all = list.length;
    let aktif = 0;
    let tidakAktif = 0;

    list.forEach(s => {
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

// Hitung filter tambahan aktif di mobile
const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (activeUnitTab.value !== 'all') count++;
    if (angkatanFilters.value.length > 0) count += angkatanFilters.value.length;
    if (jenjangFilters.value.length > 0) count += jenjangFilters.value.length;
    if (jkFilter.value) count++;
    return count;
});

const hasActiveFilters = computed(() => {
    return Boolean(
        activeUnitTab.value !== 'all' ||
        angkatanFilters.value.length > 0 ||
        jenjangFilters.value.length > 0 ||
        jkFilter.value ||
        (statusFilter.value && statusFilter.value !== 'aktif') ||
        (searchQuery.value && searchQuery.value.trim())
    );
});

const hasAdditionalFilters = computed(() => {
    return Boolean(
        activeUnitTab.value !== 'all' ||
        angkatanFilters.value.length > 0 ||
        jenjangFilters.value.length > 0 ||
        jkFilter.value ||
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
    return unitTabStudents.value.length;
});

const activeFilterChips = computed(() => {
    const chips = [];
    if (activeUnitTab.value !== 'all') {
        chips.push({
            id: 'unit',
            label: `Unit: ${activeUnitTabLabel.value}`,
            onRemove: () => {
                switchUnitTab('all');
            },
        });
    }
    angkatanFilters.value.forEach(a => {
        chips.push({
            id: `angkatan-${a}`,
            label: `Angkatan ${a}`,
            onRemove: () => {
                angkatanFilters.value = angkatanFilters.value.filter(item => item !== a);
                currentPage.value = 1;
            },
        });
    });
    jenjangFilters.value.forEach(j => {
        chips.push({
            id: `jenjang-${j}`,
            label: `Kelas ${j}`,
            onRemove: () => {
                jenjangFilters.value = jenjangFilters.value.filter(item => item !== j);
                currentPage.value = 1;
            },
        });
    });
    if (jkFilter.value) {
        chips.push({
            id: 'jk',
            label: `JK: ${jkFilter.value === 'L' ? 'Laki-laki' : 'Perempuan'}`,
            onRemove: () => {
                jkFilter.value = '';
                currentPage.value = 1;
            },
        });
    }
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
    if (!name) return 'SW';
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

function getAvatarStyle(item) {
    const id = item.id || 0;
    const name = item.nama || '';
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash + id) % pastelPalette.length;
    return pastelPalette[index];
}

const filteredStudents = computed(() => {
    let result = unitTabStudents.value;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(s =>
            (s.nama || '').toLowerCase().includes(query) ||
            (s.nisn || '').includes(query) ||
            (s.nipd || '').includes(query) ||
            (s.angkatan || '').toString().toLowerCase().includes(query) ||
            (s.no_wa || '').includes(query)
        );
    }

    // Status filter
    if (statusFilter.value && statusFilter.value !== 'semua') {
        const val = statusFilter.value.toLowerCase();
        if (val === 'aktif') {
            result = result.filter(s => !s.student_status || s.student_status.toLowerCase() === 'aktif');
        } else if (val === 'tidak_aktif' || val === 'non-aktif') {
            result = result.filter(s => s.student_status && s.student_status.toLowerCase() !== 'aktif');
        } else {
            result = result.filter(s => (s.student_status || '').toLowerCase() === val);
        }
    }

    // Angkatan filter (multi-select)
    if (angkatanFilters.value.length > 0) {
        result = result.filter(s => angkatanFilters.value.includes(s.angkatan));
    }

    // Jenjang filter (multi-select)
    if (jenjangFilters.value.length > 0) {
        result = result.filter(s => jenjangFilters.value.includes(s.jenjang));
    }

    // JK filter
    if (jkFilter.value) {
        result = result.filter(s => s.jk === jkFilter.value);
    }

    // Sorting via table header
    result = [...result].sort((a, b) => {
        const field = sortBy.value;
        const valA = (a[field] || '').toString().toLowerCase();
        const valB = (b[field] || '').toString().toLowerCase();
        if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});

const totalEntries = computed(() => filteredStudents.value.length);

const paginatedStudents = computed(() => {
    if (perPage.value === 0) return filteredStudents.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredStudents.value.slice(start, start + perPage.value);
});

const totalPages = computed(() => {
    if (perPage.value === 0) return 1;
    return Math.ceil(filteredStudents.value.length / perPage.value) || 1;
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

function onPerPageChange(val) {
    perPage.value = Number(val);
    currentPage.value = 1;
}

function onSearch(query) {
    searchQuery.value = query;
    currentPage.value = 1;
}

function onFilterStatus(status) {
    statusFilter.value = status;
    currentPage.value = 1;
}

function onFilterAngkatan(angkatans) {
    angkatanFilters.value = angkatans;
    currentPage.value = 1;
}

function onFilterJenjang(jenjangs) {
    jenjangFilters.value = jenjangs;
    currentPage.value = 1;
}

function onFilterJk(jk) {
    jkFilter.value = jk;
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
    statusFilter.value = 'aktif';
    angkatanFilters.value = [];
    jenjangFilters.value = [];
    jkFilter.value = '';
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

const downloadTemplate = async () => {
    showTemplateDropdown.value = false;
    isDownloadingTemplate.value = true;
    try {
        const url = `/students/template?tab=${activeUnitTab.value}`;
        const response = await fetch(url, { method: 'GET' });
        if (!response.ok) throw new Error('Network response was not ok');

        let filename = 'Template_Data_Siswa.xlsx';
        const disposition = response.headers.get('Content-Disposition');
        if (disposition && disposition.indexOf('attachment') !== -1) {
            const matches = /filename="([^"]*)"/.exec(disposition);
            if (matches != null && matches[1]) filename = matches[1];
            else {
                const matches2 = /filename=([^;]*)/.exec(disposition);
                if (matches2 != null && matches2[1]) filename = matches2[1];
            }
        }

        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(blobUrl);
        document.body.removeChild(a);
    } catch (error) {
        console.error('Error downloading template:', error);
        alert('Gagal mendownload template. Silakan coba lagi.');
    } finally {
        isDownloadingTemplate.value = false;
    }
};
</script>

<template>
    <div ref="pageContainerRef" class="flex-1 overflow-y-auto px-4 pt-6 pb-12 md:px-8 md:py-7 bg-white min-h-screen">
        <Head title="Database Siswa - Foundation Data Center" />

        <div class="max-w-[1440px] mx-auto">
            <!-- Sticky Title Header Mobile (Muncul otomatis di tengah atas saat judul utama ter-scroll keluar layar) -->
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
                    class="md:hidden fixed top-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md px-4 py-3 flex items-center justify-center pt-[max(0.85rem,env(safe-area-inset-top))] transition-all shadow-2xs"
                >
                    <h2 class="text-base font-bold text-slate-900 tracking-tight text-center truncate">
                        Database Siswa
                    </h2>
                </div>
            </Transition>

            <!-- ============================================================ -->
            <!-- TAMPILAN MOBILE (md:hidden) SESUAI STYLE DATA PEGAWAI        -->
            <!-- ============================================================ -->
            <div class="md:hidden pb-16">
                <!-- 1. Header: Judul Database Siswa + Tombol Kelola Gabungan Samping Judul -->
                <div class="flex items-center justify-between gap-3 pt-3 sm:pt-4 mb-6">
                    <div class="min-w-0">
                        <h1 ref="mainTitleRef" class="text-2xl font-extrabold text-slate-900 tracking-tight leading-tight">
                            Database Siswa
                        </h1>
                        <p class="text-xs text-slate-500 mt-1 font-medium truncate">
                            {{ unitTabStudents.length }} siswa • {{ activeUnitTabLabel }}
                        </p>
                    </div>

                    <!-- Tombol Kelola Gabungan di Samping Judul (Edit Data, Impor, Template) -->
                    <div class="shrink-0">
                        <button
                            type="button"
                            @click="isKelolaOpen = true"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs shadow-xs transition-all cursor-pointer active:scale-95 border border-emerald-950/30"
                        >
                            <span class="material-symbols-outlined text-[17px]">tune</span>
                            <span>Kelola</span>
                            <span class="material-symbols-outlined text-[16px] transition-transform duration-200" :class="{ 'rotate-180': isKelolaOpen }">expand_more</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Filter Status Keaktifan (DI ATAS SEARCH) Sesuai Referensi -->
                <div class="mb-3">
                    <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-0.5 overflow-x-auto no-scrollbar max-w-full border border-slate-200/90 shrink-0">
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.key"
                            type="button"
                            @click="onFilterStatus(tab.key)"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs transition-all duration-150 cursor-pointer shrink-0',
                                statusFilter === tab.key
                                    ? 'bg-emerald-100 text-emerald-950 font-bold border border-emerald-300 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'
                            ]"
                        >
                            <span>{{ tab.label }}</span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10.5px] leading-none transition-colors font-bold',
                                    statusFilter === tab.key
                                        ? 'bg-emerald-900 text-yellow-200'
                                        : 'bg-slate-200 text-slate-600 font-semibold'
                                ]"
                            >
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- 3. Toolbar Pencarian & Tombol Filter Sesuai Data Pegawai -->
                <div class="flex items-center gap-2 mb-3">
                    <div class="relative flex-1 min-w-0">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-[20px] pointer-events-none">
                            search
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, NISN, NIPD, angkatan, no. WA..."
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

                    <!-- Tombol Filter Popover/Drawer dengan Icon filter_alt -->
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

                <!-- 4. Chip Filter Aktif (DI BAWAH SEARCH) Sesuai Data Pegawai -->
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

                <!-- 5. Card List Container: DATA SISWA Sesuai Referensi Data Pegawai -->
                <div class="bg-white rounded-3xl border border-slate-200/90 p-4 shadow-xs">
                    <!-- Header Kotak Card -->
                    <div class="flex items-center justify-between mb-3 px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Data Siswa</h3>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">
                            {{ filteredStudents.length }} dari {{ displayTotalCount }}
                        </span>
                    </div>

                    <!-- Daftar Card Siswa -->
                    <div v-if="paginatedStudents.length > 0" class="space-y-3">
                        <Link
                            v-for="student in paginatedStudents"
                            :key="student.id"
                            :href="`/students/${student.nisn || student.id}`"
                            :prefetch="['mount', 'hover']"
                            cache-for="5m"
                            class="bg-white rounded-2xl border border-slate-200/90 p-3.5 shadow-2xs hover:border-emerald-400 hover:shadow-xs transition-all flex items-center justify-between gap-3 group active:scale-[0.99] cursor-pointer"
                        >
                            <!-- Avatar Siswa -->
                            <img
                                v-if="student.foto"
                                :src="student.foto"
                                :alt="student.nama"
                                class="w-13 h-13 rounded-2xl object-cover border border-slate-200 shrink-0 shadow-2xs"
                            />
                            <div
                                v-else
                                :class="[
                                    'w-13 h-13 rounded-2xl flex items-center justify-center font-bold text-base shrink-0 border shadow-2xs transition-transform group-hover:scale-105',
                                    getAvatarStyle(student)
                                ]"
                            >
                                <span>{{ getInitials(student.nama) }}</span>
                            </div>

                            <!-- Detail Siswa -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[13.5px] font-bold text-slate-900 group-hover:text-emerald-950 transition-colors leading-tight">
                                        {{ student.nama }}
                                    </span>
                                    <span
                                        v-if="student.jk"
                                        :class="[
                                            'text-[9.5px] font-bold px-1.5 py-0.5 rounded-md border shrink-0',
                                            student.jk === 'L'
                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                : 'bg-rose-50 text-rose-700 border-rose-200'
                                        ]"
                                    >
                                        {{ student.jk }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium mt-1 truncate">
                                    <span>NISN: {{ student.nisn || '-' }}</span>
                                    <span>•</span>
                                    <span class="truncate">{{ student.unit || '-' }} {{ student.kelas ? '• ' + student.kelas : '' }}</span>
                                </div>

                                <div v-if="student.angkatan" class="text-[11px] text-slate-400 truncate mt-0.5">
                                    Angkatan {{ student.angkatan }}
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
                        <p class="text-sm font-bold text-slate-700">Tidak ada siswa ditemukan</p>
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

                    <!-- Paginasi Mobile -->
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
                            Menampilkan {{ ((currentPage - 1) * perPage) + 1 }} - {{ Math.min(currentPage * perPage, filteredStudents.length) }} dari {{ filteredStudents.length }} siswa
                        </div>
                    </div>
                </div>

                <!-- Bottom Sheet / Modal Filter Mobile untuk Siswa (Teleport ke body agar di atas Floating Bottom Nav) -->
                <Teleport to="body">
                    <div
                        v-if="isMobileFilterDrawerOpen"
                        class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/40 backdrop-blur-xs animate-in fade-in"
                        @click.self="isMobileFilterDrawerOpen = false"
                    >
                        <div class="w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-3xl p-5 pb-[max(2rem,env(safe-area-inset-bottom))] sm:pb-5 shadow-2xl border border-slate-200 max-h-[85vh] overflow-y-auto animate-in slide-in-from-bottom duration-200">
                            <!-- Top Indicator Drag Bar -->
                            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-3 sm:hidden"></div>

                            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[20px] text-emerald-800">filter_alt</span>
                                    <h3 class="text-sm font-bold text-slate-900">Filter Siswa</h3>
                                </div>
                                <button
                                    type="button"
                                    @click="isMobileFilterDrawerOpen = false"
                                    class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>

                            <!-- Jenjang Pendidikan / Unit Radio Selection -->
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Jenjang Pendidikan (Unit)
                                    </label>
                                    <span class="text-[11px] text-slate-400">Pilih 1 jenjang</span>
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        v-for="u in unitOptions"
                                        :key="u.value"
                                        class="flex items-center justify-between p-2.5 rounded-xl border cursor-pointer text-xs transition-colors"
                                        :class="activeUnitTab === u.value ? 'border-emerald-500 bg-emerald-50/60 font-bold text-emerald-950 shadow-2xs' : 'border-slate-200 hover:bg-slate-50 text-slate-800'"
                                    >
                                        <span>{{ u.label }}</span>
                                        <input
                                            type="radio"
                                            name="mobile_student_unit"
                                            :value="u.value"
                                            :checked="activeUnitTab === u.value"
                                            @change="switchUnitTab(u.value)"
                                            class="w-4 h-4 text-emerald-900 focus:ring-emerald-700 border-slate-300 cursor-pointer"
                                        />
                                    </label>
                                </div>
                            </div>

                            <!-- Angkatan Checkboxes (Multi-select) -->
                            <div v-if="availableAngkatan.length > 0" class="mb-5">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Angkatan
                                </label>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    <label
                                        v-for="ang in availableAngkatan"
                                        :key="ang"
                                        class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs"
                                    >
                                        <span class="font-medium text-slate-800">Angkatan {{ ang }}</span>
                                        <input
                                            type="checkbox"
                                            :value="ang"
                                            v-model="angkatanFilters"
                                            class="w-4 h-4 rounded text-emerald-900 focus:ring-emerald-700 border-slate-300 cursor-pointer"
                                        />
                                    </label>
                                </div>
                            </div>

                            <!-- Jenjang Kelas Checkboxes (Multi-select) -->
                            <div v-if="availableJenjang.length > 0" class="mb-5">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Jenjang Kelas
                                </label>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
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

                            <!-- Jenis Kelamin Radio Selection -->
                            <div class="mb-5">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Jenis Kelamin
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        @click="jkFilter = ''"
                                        :class="[
                                            'py-2 px-3 rounded-xl border text-xs font-semibold cursor-pointer transition-colors text-center',
                                            !jkFilter
                                                ? 'border-emerald-600 bg-emerald-50 text-emerald-950 font-bold'
                                                : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                                        ]"
                                    >
                                        Semua
                                    </button>
                                    <button
                                        type="button"
                                        @click="jkFilter = 'L'"
                                        :class="[
                                            'py-2 px-3 rounded-xl border text-xs font-semibold cursor-pointer transition-colors text-center',
                                            jkFilter === 'L'
                                                ? 'border-blue-600 bg-blue-50 text-blue-950 font-bold'
                                                : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                                        ]"
                                    >
                                        Laki-laki
                                    </button>
                                    <button
                                        type="button"
                                        @click="jkFilter = 'P'"
                                        :class="[
                                            'py-2 px-3 rounded-xl border text-xs font-semibold cursor-pointer transition-colors text-center',
                                            jkFilter === 'P'
                                                ? 'border-rose-600 bg-rose-50 text-rose-950 font-bold'
                                                : 'border-slate-200 hover:bg-slate-50 text-slate-700'
                                        ]"
                                    >
                                        Perempuan
                                    </button>
                                </div>
                            </div>

                            <!-- Tombol Aksi Modal Filter -->
                            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="onResetFilters"
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
                </Teleport>

                <!-- Bottom Sheet / Modal Kelola Mobile (Gabungan: Edit Data, Impor, Template) -->
                <Teleport to="body">
                    <div
                        v-if="isKelolaOpen"
                        class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/40 backdrop-blur-xs animate-in fade-in"
                        @click.self="isKelolaOpen = false"
                    >
                        <div class="w-full sm:max-w-md bg-white rounded-t-3xl sm:rounded-3xl p-5 pb-[max(2rem,env(safe-area-inset-bottom))] sm:pb-5 shadow-2xl border border-slate-200 max-h-[85vh] overflow-y-auto animate-in slide-in-from-bottom duration-200">
                            <!-- Top Indicator Drag Bar -->
                            <div class="w-12 h-1.5 bg-slate-200 rounded-full mx-auto mb-3 sm:hidden"></div>

                            <!-- Header Kelola -->
                            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[20px] text-emerald-800">tune</span>
                                    <h3 class="text-sm font-bold text-slate-900">Kelola Data Siswa</h3>
                                </div>
                                <button
                                    type="button"
                                    @click="isKelolaOpen = false"
                                    class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                </button>
                            </div>

                            <!-- Pilihan Aksi Kelola Gabungan -->
                            <div class="space-y-2.5">
                                <!-- 1. Download Template -->
                                <button
                                    type="button"
                                    @click="isKelolaOpen = false; downloadTemplate()"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 bg-white transition-all cursor-pointer group shadow-2xs text-left"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-800 group-hover:bg-emerald-100 transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-[22px]">download</span>
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 group-hover:text-emerald-950 truncate">Download Template</h4>
                                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Format file XLSX untuk import data siswa</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-emerald-800 transition-colors shrink-0">chevron_right</span>
                                </button>

                                <!-- 2. Import Data Siswa -->
                                <button
                                    type="button"
                                    @click="isKelolaOpen = false; showImportModal = true"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 bg-white transition-all cursor-pointer group shadow-2xs text-left"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-800 group-hover:bg-teal-100 transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-[22px]">upload_file</span>
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 group-hover:text-teal-950 truncate">Import Data Siswa</h4>
                                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Unggah data massal siswa via Excel</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-teal-800 transition-colors shrink-0">chevron_right</span>
                                </button>

                                <!-- 3. Edit Massal Siswa -->
                                <Link
                                    href="/students/inline-edit"
                                    :prefetch="['mount', 'hover']"
                                    cache-for="5m"
                                    @click="isKelolaOpen = false"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 bg-white transition-all cursor-pointer group shadow-2xs text-left"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-800 group-hover:bg-amber-100 transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-[22px]">edit_note</span>
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 group-hover:text-amber-950 truncate">Edit Massal Siswa</h4>
                                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Edit langsung data siswa secara massal</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-amber-800 transition-colors shrink-0">chevron_right</span>
                                </Link>

                                <!-- 4. Petakan Riwayat (Bulking) -->
                                <Link
                                    href="/students/mapping"
                                    :prefetch="['mount', 'hover']"
                                    cache-for="5m"
                                    @click="isKelolaOpen = false"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 bg-white transition-all cursor-pointer group shadow-2xs text-left"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-800 group-hover:bg-indigo-100 transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-[22px]">route</span>
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 group-hover:text-indigo-950 truncate">Petakan Riwayat (Bulking)</h4>
                                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Pemetaan kelas dan status siswa</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-indigo-800 transition-colors shrink-0">chevron_right</span>
                                </Link>

                                <!-- 5. Upload Foto Siswa -->
                                <button
                                    type="button"
                                    @click="isKelolaOpen = false; showPhotoUploadModal = true"
                                    class="w-full flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 bg-white transition-all cursor-pointer group shadow-2xs text-left"
                                >
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-200/80 flex items-center justify-center text-purple-800 group-hover:bg-purple-100 transition-colors shrink-0">
                                        <span class="material-symbols-outlined text-[22px]">add_a_photo</span>
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 group-hover:text-purple-950 truncate">Upload Foto Siswa</h4>
                                        <p class="text-[11px] text-slate-500 font-medium truncate mt-0.5">Upload foto siswa massal via NISN</p>
                                    </div>
                                    <span class="material-symbols-outlined text-[18px] text-slate-400 group-hover:text-purple-800 transition-colors shrink-0">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>
            </div>

            <!-- ============================================================ -->
            <!-- TAMPILAN DESKTOP (hidden md:block) SESUAI DATA PEGAWAI       -->
            <!-- ============================================================ -->
            <div class="hidden md:block">
                <!-- Page Header Desktop -->
                <div class="mb-5 sm:mb-6 flex flex-row items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-xl sm:text-2xl md:text-[26px] font-bold text-slate-900 tracking-tight leading-tight">
                            Database Siswa
                        </h1>
                        <p class="text-xs sm:text-[13.5px] text-slate-500 mt-1 sm:mt-1.5 font-medium truncate">
                            <strong class="text-slate-700 font-semibold">{{ unitTabStudents.length }}</strong> siswa
                            <span v-if="activeUnitTab !== 'all'"> di unit <strong class="text-slate-700 font-semibold">{{ activeUnitTabLabel }}</strong></span>
                            <span v-else> di <strong class="text-slate-700 font-semibold">Semua Jenjang Pendidikan</strong></span>
                        </p>
                    </div>

                    <!-- Desktop Action Buttons -->
                    <div class="flex items-center gap-2.5 shrink-0">
                        <!-- Edit Data Dropdown -->
                        <div class="relative">
                            <EditDataButton
                                :is-dropdown="true"
                                :is-open="showEditDropdown"
                                @click="showEditDropdown = !showEditDropdown"
                            />

                            <!-- Overlay -->
                            <div v-if="showEditDropdown" @click="showEditDropdown = false" class="fixed inset-0 z-40"></div>

                            <!-- Dropdown Menu -->
                            <div v-show="showEditDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden py-1.5 animate-in fade-in zoom-in-95 duration-100">
                                <Link
                                    href="/students/inline-edit"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition-colors w-full text-left"
                                    @click="showEditDropdown = false"
                                    title="Edit langsung data siswa secara massal"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">edit_note</span>
                                    <span>Edit Massal</span>
                                </Link>
                                <Link
                                    href="/students/mapping"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition-colors w-full text-left"
                                    @click="showEditDropdown = false"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">route</span>
                                    <span>Petakan Riwayat (Bulking)</span>
                                </Link>
                                <button
                                    type="button"
                                    @click="showPhotoUploadModal = true; showEditDropdown = false"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition-colors w-full text-left cursor-pointer"
                                    title="Upload foto siswa secara batch berdasarkan NISN"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">add_a_photo</span>
                                    <span>Upload Foto</span>
                                </button>
                            </div>
                        </div>

                        <!-- Template Dropdown -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="showTemplateDropdown = !showTemplateDropdown"
                                class="inline-flex items-center justify-between gap-2 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-semibold text-[13px] transition-all shadow-xs cursor-pointer active:scale-95"
                            >
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">description</span>
                                    <span>Template & Import</span>
                                </div>
                                <span class="material-symbols-outlined text-[16px] transition-transform duration-200" :class="{ 'rotate-180': showTemplateDropdown }">
                                    expand_more
                                </span>
                            </button>

                            <!-- Overlay -->
                            <div v-if="showTemplateDropdown" @click="showTemplateDropdown = false" class="fixed inset-0 z-40"></div>

                            <!-- Dropdown Menu -->
                            <div v-show="showTemplateDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden py-1.5 animate-in fade-in zoom-in-95 duration-100">
                                <button
                                    type="button"
                                    @click="downloadTemplate"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition-colors w-full text-left cursor-pointer"
                                    title="Download template XLSX resmi untuk import data siswa"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">download</span>
                                    <span>Download Template</span>
                                </button>
                                <button
                                    type="button"
                                    @click="showImportModal = true; showTemplateDropdown = false"
                                    class="flex items-center gap-2.5 px-4 py-2.5 text-[13px] font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900 transition-colors w-full text-left cursor-pointer"
                                    title="Import data siswa dari file template Excel"
                                >
                                    <span class="material-symbols-outlined text-[18px] text-emerald-700">upload_file</span>
                                    <span>Import Data Siswa</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flash Notifications Desktop -->
                <div
                    v-if="flashSuccess"
                    class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
                >
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-emerald-700 text-2xl shrink-0 mt-0.5">check_circle</span>
                        <div>
                            <h4 class="text-[13.5px] font-bold text-emerald-900">Operasi Berhasil</h4>
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
                    class="mb-5 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
                >
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-rose-600 text-2xl shrink-0 mt-0.5">error</span>
                        <div>
                            <h4 class="text-[13.5px] font-bold text-rose-900">Terjadi Kesalahan</h4>
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

                <!-- Toolbar Desktop -->
                <StudentToolbar
                    :students="unitTabStudents"
                    :total-count="unitTabStudents.length"
                    :filtered-count="totalEntries"
                    :per-page="perPage"
                    :active-status="statusFilter"
                    :active-angkatan="angkatanFilters"
                    :active-jenjang="jenjangFilters"
                    :active-jk="jkFilter"
                    :active-unit="activeUnitTab"
                    :unit-options="unitOptions"
                    @filter-unit="switchUnitTab"
                    @update:per-page="onPerPageChange"
                    @search="onSearch"
                    @filter-status="onFilterStatus"
                    @filter-angkatan="onFilterAngkatan"
                    @filter-jenjang="onFilterJenjang"
                    @filter-jk="onFilterJk"
                    @reset-filters="onResetFilters"
                />

                <!-- Table Desktop -->
                <StudentTable
                    :students="paginatedStudents"
                    :current-page="currentPage"
                    :per-page="perPage"
                    :total-entries="totalEntries"
                    :sort-by="sortBy"
                    :sort-direction="sortDirection"
                    @update:per-page="onPerPageChange"
                    @page-change="onPageChange"
                    @sort-change="onSortChange"
                />
            </div>
        </div>

        <!-- Modals -->
        <StudentImportModal
            :show="showImportModal"
            :unit="activeUnitTab"
            :unit-label="activeUnitTabLabel"
            @close="showImportModal = false"
            @success="onImportSuccess"
        />

        <StudentPhotoUploadModal
            :show="showPhotoUploadModal"
            @close="showPhotoUploadModal = false"
            @success="() => { dismissedFlash = false; }"
        />

        <!-- Loading Overlay for Template Download (Teleport ke body) -->
        <Teleport to="body">
            <div v-if="isDownloadingTemplate" class="fixed inset-0 z-[100] bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl p-6 sm:p-8 max-w-sm w-full shadow-2xl flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-6 relative">
                        <div class="absolute inset-0 rounded-full border-4 border-emerald-300 border-t-emerald-800 animate-spin"></div>
                        <span class="material-symbols-outlined text-emerald-800 text-3xl">download</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-2">Menyiapkan Template...</h3>
                    <p class="text-slate-600 text-sm">Mohon tunggu sebentar, file Excel sedang dibuat. Waktu pembuatan bergantung pada ukuran data.</p>
                </div>
            </div>
        </Teleport>
    </div>
</template>
