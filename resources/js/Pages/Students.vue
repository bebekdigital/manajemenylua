<script setup>
import { ref, computed, watch } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StudentToolbar from '@/Components/Students/StudentToolbar.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';
import StudentImportModal from '@/Components/Students/StudentImportModal.vue';
import StudentPhotoUploadModal from '@/Components/Students/StudentPhotoUploadModal.vue';

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

const activeUnitTab = ref('SDIT');

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
    gradeFilter.value = '';
    unitFilter.value = '';
    statusFilter.value = 'aktif';
    currentPage.value = 1;
}

// ============================================================
// Search & Filter State
// ============================================================
const searchQuery = ref('');
const gradeFilter = ref('');
const unitFilter = ref('');
const statusFilter = ref('aktif');
const currentPage = ref(1);
const perPage = ref(10);

// Import modal state
const showImportModal = ref(false);
const showPhotoUploadModal = ref(false);
const dismissedFlash = ref(false);

// Dropdown states
const showEditDropdown = ref(false);
const showTemplateDropdown = ref(false);
const isDownloadingTemplate = ref(false);

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

const flashSuccess = computed(() => {
    return !dismissedFlash.value ? page.props.flash?.success : null;
});

const flashError = computed(() => {
    return !dismissedFlash.value ? page.props.flash?.error : null;
});

function onImportSuccess() {
    dismissedFlash.value = false;
}

const filteredStudents = computed(() => {
    let result = unitTabStudents.value;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(s =>
            s.nama.toLowerCase().includes(query) ||
            s.nisn.includes(query) ||
            (s.nipd && s.nipd.includes(query))
        );
    }

    // Grade filter
    if (gradeFilter.value) {
        result = result.filter(s => s.kelas && s.kelas === gradeFilter.value);
    }

    // Status filter
    if (statusFilter.value && statusFilter.value !== 'semua') {
        if (statusFilter.value === 'aktif') {
            result = result.filter(s => !s.student_status || s.student_status.toLowerCase() === 'aktif');
        } else if (statusFilter.value === 'tidak_aktif') {
            result = result.filter(s => s.student_status && s.student_status.toLowerCase() !== 'aktif');
        } else {
            result = result.filter(s => s.student_status === statusFilter.value);
        }
    }

    return result;
});

const totalEntries = computed(() => filteredStudents.value.length);

const paginatedStudents = computed(() => {
    if (perPage.value === 0) {
        return filteredStudents.value;
    }
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

function onFilterUnit(unit) {
    unitFilter.value = unit;
    currentPage.value = 1;
}

function onFilterGrade(grade) {
    gradeFilter.value = grade;
    currentPage.value = 1;
}

function onFilterStatus(status) {
    statusFilter.value = status;
    currentPage.value = 1;
}

function onResetFilters() {
    searchQuery.value = '';
    unitFilter.value = '';
    gradeFilter.value = '';
    statusFilter.value = 'aktif';
    currentPage.value = 1;
}

function onPageChange(page) {
    currentPage.value = page;
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
</script>

<template>
    <Head title="Student Directory - Foundation Data Center" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop">
        <!-- Page Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-start gap-4 sm:gap-12 border-b border-outline-variant/30 pb-0">
            <div class="pb-4 sm:pb-3 shrink-0">
                <h2 class="text-xl sm:text-2xl text-emerald-600 font-bold leading-tight">
                    Database Siswa
                </h2>
                <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                    Kelola dan tinjau seluruh data peserta didik sesuai Tahun Ajaran aktif
                </p>
            </div>
            
            <!-- Unit Tabs (Tab Kotak2 Style) -->
            <div class="flex overflow-x-auto -mb-px">
                <button
                    v-for="tab in unitTabs"
                    :key="tab.id"
                    type="button"
                    @click="switchUnitTab(tab.id)"
                    :class="[
                        'flex items-center gap-2 px-3 sm:px-5 py-2.5 sm:py-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap border-b-2 rounded-t-lg',
                        activeUnitTab === tab.id
                            ? 'text-emerald-600 border-emerald-600 bg-gradient-to-t from-emerald-600/20 to-transparent'
                            : 'text-on-surface-variant border-transparent hover:text-on-surface hover:border-outline-variant'
                    ]"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">{{ tab.icon }}</span>
                    <span>{{ tab.label }}</span>
                    <span :class="[
                        'inline-flex items-center justify-center min-w-[20px] h-[20px] px-1.5 rounded-full text-[10px] font-bold leading-none',
                        activeUnitTab === tab.id
                            ? 'bg-primary/10 text-primary'
                            : 'bg-outline-variant/20 text-on-surface-variant'
                    ]">
                        {{ unitStudentCount(tab.id) }}
                    </span>
                </button>
            </div>
        </div>


        <!-- Flash Messages -->
        <div v-if="flashSuccess" class="mb-6 p-4 bg-primary/10 border border-primary/20 rounded-xl flex items-start gap-3">
            <span class="material-symbols-outlined text-primary shrink-0 mt-0.5">check_circle</span>
            <div class="flex-1 text-sm text-primary font-medium">
                {{ flashSuccess }}
            </div>
            <button @click="dismissedFlash = true" class="text-primary/70 hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
        <div v-if="flashError" class="mb-6 p-4 bg-error/10 border border-error/20 rounded-xl flex items-start gap-3">
            <span class="material-symbols-outlined text-error shrink-0 mt-0.5">error</span>
            <div class="flex-1 text-sm text-error font-medium">
                {{ flashError }}
            </div>
            <button @click="dismissedFlash = true" class="text-error/70 hover:text-error transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Action Buttons & Filters Row -->
        <div class="mb-5 flex flex-col lg:flex-row gap-4 lg:items-center">
            
            <!-- Action Buttons (Dropdowns) -->
            <div class="flex items-center gap-3 shrink-0 relative z-30">
                <!-- Edit Data Dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        @click="showEditDropdown = !showEditDropdown"
                        class="inline-flex items-center justify-between gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl border border-amber-500 text-amber-700 bg-amber-50 hover:bg-amber-100 active:bg-amber-200 font-semibold text-[11px] sm:text-sm transition-all shadow-2xs hover:shadow-sm"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit</span>
                            <span>Edit Data</span>
                        </div>
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px] transition-transform duration-200" :class="{ 'rotate-180': showEditDropdown }">expand_more</span>
                    </button>

                    <!-- Overlay -->
                    <div v-if="showEditDropdown" @click="showEditDropdown = false" class="fixed inset-0 z-40"></div>

                    <!-- Dropdown Menu -->
                    <div v-show="showEditDropdown" class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden py-1">
                        <Link
                            href="/students/inline-edit"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-primary/10 hover:text-primary transition-colors w-full text-left"
                            @click="showEditDropdown = false"
                            title="Edit langsung data siswa secara massal"
                        >
                            <span class="material-symbols-outlined text-[18px]">edit_note</span>
                            Edit Massal
                        </Link>
                        <button
                            type="button"
                            @click="showPhotoUploadModal = true; showEditDropdown = false"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-primary/10 hover:text-primary transition-colors w-full text-left"
                            title="Upload foto siswa secara batch berdasarkan NISN"
                        >
                            <span class="material-symbols-outlined text-[18px]">add_a_photo</span>
                            Upload Foto
                        </button>
                    </div>
                </div>

                <!-- Template Dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        @click="showTemplateDropdown = !showTemplateDropdown"
                        class="inline-flex items-center justify-between gap-2 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-[11px] sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] sm:text-[18px]">description</span>
                            <span>Template</span>
                        </div>
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px] transition-transform duration-200" :class="{ 'rotate-180': showTemplateDropdown }">expand_more</span>
                    </button>

                    <!-- Overlay -->
                    <div v-if="showTemplateDropdown" @click="showTemplateDropdown = false" class="fixed inset-0 z-40"></div>

                    <!-- Dropdown Menu -->
                    <div v-show="showTemplateDropdown" class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden py-1">
                        <button
                            type="button"
                            @click="downloadTemplate"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-primary/10 hover:text-primary transition-colors w-full text-left"
                            title="Download template XLSX resmi untuk import data siswa"
                        >
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            Download Template
                        </button>
                        <button
                            type="button"
                            @click="showImportModal = true; showTemplateDropdown = false"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-primary/10 hover:text-primary transition-colors w-full text-left"
                            title="Import data siswa dari file template Excel"
                        >
                            <span class="material-symbols-outlined text-[18px]">upload_file</span>
                            Import Data Siswa
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="flex-grow w-full relative z-20">
                <StudentToolbar
                    class="!mb-0"
                    :students="unitTabStudents"
                    :total-count="unitTabStudents.length"
                    :filtered-count="totalEntries"
                    :per-page="perPage"
                    @update:per-page="onPerPageChange"
                    @search="onSearch"
                    @filter-unit="onFilterUnit"
                    @filter-grade="onFilterGrade"
                    @filter-status="onFilterStatus"
                    @reset-filters="onResetFilters"
                    @open-import="showImportModal = true"
                />
            </div>
        </div>

        <!-- Student Data Table -->
        <StudentTable
            :students="paginatedStudents"
            :current-page="currentPage"
            :per-page="perPage"
            :total-entries="totalEntries"
            @update:per-page="onPerPageChange"
            @page-change="onPageChange"
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
            <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-6 relative">
                <div class="absolute inset-0 rounded-full border-4 border-primary/30 border-t-primary animate-spin"></div>
                <span class="material-symbols-outlined text-primary text-3xl">download</span>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Menyiapkan Template...</h3>
            <p class="text-slate-600 text-sm">Mohon tunggu sebentar, file Excel sedang dibuat. Waktu pembuatan bergantung pada ukuran data.</p>
        </div>
    </div>
</template>
