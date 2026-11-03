<script setup>
import { ref, computed, watch } from 'vue';
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
// Unit Tabs
// ============================================================
const unitTabs = [
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain' },
];

const activeUnitTab = ref(localStorage.getItem('activeUnitTab') || 'SDIT');

watch(activeUnitTab, (newVal) => {
    localStorage.setItem('activeUnitTab', newVal);
});

const unitTabStudents = computed(() => {
    if (activeUnitTab.value === 'all') return props.students;

    return props.students.filter(s => {
        if (!s.unit) return false;
        const u = s.unit.toUpperCase();
        if (activeUnitTab.value === 'SDIT') {
            return u.includes('SDIT') || u.includes('SD IT');
        }
        if (activeUnitTab.value === 'SMPIT') {
            return u.includes('SMPIT') || u.includes('SMP IT');
        }
        return false;
    });
});

const activeUnitTabLabel = computed(() => {
    return unitTabs.find(t => t.id === activeUnitTab.value)?.label ?? '';
});

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

// Modal & dropdown states
const showImportModal = ref(false);
const showPhotoUploadModal = ref(false);
const dismissedFlash = ref(false);
const showEditDropdown = ref(false);
const showTemplateDropdown = ref(false);
const isDownloadingTemplate = ref(false);

const flashSuccess = computed(() => !dismissedFlash.value ? page.props.flash?.success : null);
const flashError = computed(() => !dismissedFlash.value ? page.props.flash?.error : null);

function onImportSuccess() {
    dismissedFlash.value = false;
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
    currentPage.value = p;
}

function unitStudentCount(unitId) {
    if (unitId === 'all') return props.students.length;

    return props.students.filter(s => {
        if (!s.unit) return false;
        const u = s.unit.toUpperCase();
        if (unitId === 'SDIT') {
            return u.includes('SDIT') || u.includes('SD IT');
        }
        if (unitId === 'SMPIT') {
            return u.includes('SMPIT') || u.includes('SMP IT');
        }
        return false;
    }).length;
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
    <div class="flex-1 overflow-y-auto px-4 py-6 md:px-8 md:py-7 bg-white min-h-screen">
        <Head title="Database Siswa - Foundation Data Center" />

        <div class="max-w-[1440px] mx-auto">
            <!-- Page Header: Judul, Unit Tabs & Action Buttons -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                    <div>
                        <h1 class="text-2xl sm:text-[26px] font-bold text-slate-900 tracking-tight leading-none">
                            Database Siswa
                        </h1>
                        <p class="text-[13.5px] text-slate-500 mt-2 font-medium">
                            <strong class="text-slate-700 font-semibold">{{ unitTabStudents.length }}</strong> siswa di unit <strong class="text-slate-700 font-semibold">{{ activeUnitTabLabel }}</strong>
                        </p>
                    </div>

                    <!-- Unit Segmented Capsule Tabs (SDIT / SMPIT) -->
                    <div class="bg-slate-100 p-1 rounded-full inline-flex items-center gap-1 border border-slate-200/90 shrink-0">
                        <button
                            v-for="tab in unitTabs"
                            :key="tab.id"
                            type="button"
                            @click="switchUnitTab(tab.id)"
                            :class="[
                                'inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-[13px] font-bold transition-all duration-150 cursor-pointer shrink-0',
                                activeUnitTab === tab.id
                                    ? 'bg-emerald-900 text-yellow-200 shadow-xs'
                                    : 'text-slate-600 hover:text-slate-900 font-semibold'
                            ]"
                        >
                            <span class="material-symbols-outlined text-[16px]">{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[11px] leading-none transition-colors font-bold',
                                    activeUnitTab === tab.id
                                        ? 'bg-emerald-800 text-yellow-100'
                                        : 'bg-slate-300/80 text-slate-700'
                                ]"
                            >
                                {{ unitStudentCount(tab.id) }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons: Edit Data Dropdown & Template Dropdown -->
                <div class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap relative z-30">
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

            <!-- Flash Notifications -->
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

            <!-- Toolbar: Status Tabs, Search Bar, Multi-select Filter Dropdown & Active Chips -->
            <StudentToolbar
                :students="unitTabStudents"
                :total-count="unitTabStudents.length"
                :filtered-count="totalEntries"
                :per-page="perPage"
                :active-status="statusFilter"
                :active-angkatan="angkatanFilters"
                :active-jenjang="jenjangFilters"
                :active-jk="jkFilter"
                @update:per-page="onPerPageChange"
                @search="onSearch"
                @filter-status="onFilterStatus"
                @filter-angkatan="onFilterAngkatan"
                @filter-jenjang="onFilterJenjang"
                @filter-jk="onFilterJk"
                @reset-filters="onResetFilters"
            />

            <!-- Table: Modern design dengan Header Hijau, Garis Kisi Tebal, Border Emerald -->
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

        <!-- Import Modal -->
    <StudentImportModal
        :show="showImportModal"
        :unit="activeUnitTab"
        :unit-label="activeUnitTabLabel"
        @close="showImportModal = false"
        @success="onImportSuccess"
    />

    <!-- Photo Upload Modal -->
    <StudentPhotoUploadModal
        :show="showPhotoUploadModal"
        @close="showPhotoUploadModal = false"
        @success="() => { dismissedFlash = false; }"
    />

    <!-- Loading Overlay for Template Download -->
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
    </div>
</template>
