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
    { id: 'all', label: 'Semua', icon: 'groups', color: 'slate' },
    { id: 'SDIT', label: 'SDIT', icon: 'school', color: 'emerald' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'domain', color: 'blue' },
];

const activeUnitTab = ref('all');

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
    statusFilter.value = '';
    currentPage.value = 1;
}

// ============================================================
// Search & Filter State
// ============================================================
const searchQuery = ref('');
const gradeFilter = ref('');
const unitFilter = ref('');
const statusFilter = ref('');
const currentPage = ref(1);
const perPage = ref(10);

// Import modal state
const showImportModal = ref(false);
const showPhotoUploadModal = ref(false);
const dismissedFlash = ref(false);

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
    if (statusFilter.value) {
        result = result.filter(s => s.student_status === statusFilter.value);
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
    statusFilter.value = '';
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
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                    Database Siswa
                </h2>
                <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                    Kelola dan tinjau seluruh data peserta didik sesuai Tahun Ajaran aktif
                </p>
            </div>
            
            <!-- Unit Tabs (Moved to top right) -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
                <button
                    v-for="tab in unitTabs"
                    :key="tab.id"
                    type="button"
                    @click="switchUnitTab(tab.id)"
                    :class="[
                        'group relative inline-flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl font-semibold text-xs transition-all duration-200 cursor-pointer border',
                        activeUnitTab === tab.id
                            ? tab.color === 'emerald'
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-500/25'
                                : tab.color === 'slate'
                                    ? 'bg-slate-700 text-white border-slate-700 shadow-md shadow-slate-500/25'
                                    : 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/25'
                            : tab.color === 'emerald'
                                ? 'bg-white text-emerald-700 border-emerald-200 hover:bg-emerald-50 hover:border-emerald-300'
                                : tab.color === 'slate'
                                    ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300'
                                    : 'bg-white text-blue-700 border-blue-200 hover:bg-blue-50 hover:border-blue-300'
                    ]"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">{{ tab.icon }}</span>
                    <span>{{ tab.label }}</span>
                    <span :class="[
                        'inline-flex items-center justify-center min-w-[20px] h-[20px] px-1.5 rounded-full text-[10px] font-bold leading-none',
                        activeUnitTab === tab.id
                            ? 'bg-white/25 text-white'
                            : tab.color === 'emerald'
                                ? 'bg-emerald-100 text-emerald-700'
                                : tab.color === 'slate'
                                    ? 'bg-slate-100 text-slate-700'
                                    : 'bg-blue-100 text-blue-700'
                    ]">
                        {{ unitStudentCount(tab.id) }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Action Buttons (Moved to below header) -->
        <div class="mb-5 flex items-center gap-2 flex-wrap sm:flex-nowrap">
            <Link
                href="/students/inline-edit"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2.5 rounded-lg sm:rounded-xl border border-primary text-primary bg-primary/10 hover:bg-primary/20 active:bg-primary/30 font-semibold text-[11px] sm:text-sm transition-all shadow-2xs hover:shadow-sm"
                title="Edit langsung data siswa secara massal"
            >
                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                <span>Edit Data</span>
            </Link>

            <button
                type="button"
                @click="showPhotoUploadModal = true"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2.5 rounded-lg sm:rounded-xl border border-tertiary/50 text-tertiary bg-tertiary/10 hover:bg-tertiary/20 active:bg-tertiary/30 font-semibold text-[11px] sm:text-sm transition-all shadow-2xs hover:shadow-sm cursor-pointer"
                title="Upload foto siswa secara batch berdasarkan NISN"
            >
                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">add_a_photo</span>
                <span>Upload Foto</span>
            </button>

            <a
                href="/students/template"
                download
                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2.5 rounded-lg sm:rounded-xl border border-yellow-300 text-yellow-800 bg-yellow-100 hover:bg-yellow-200 active:bg-yellow-300 font-semibold text-[11px] sm:text-sm transition-all shadow-2xs hover:shadow-sm"
                title="Download template XLSX resmi untuk import data siswa"
            >
                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">download</span>
                <span>Download Template</span>
            </a>

            <button
                type="button"
                @click="showImportModal = true"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2.5 rounded-lg sm:rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-[11px] sm:text-sm transition-all shadow-xs hover:shadow-md cursor-pointer"
                title="Import data siswa dari file template Excel"
            >
                <span class="material-symbols-outlined text-[16px] sm:text-[18px]">upload_file</span>
                <span>Import Data Siswa</span>
            </button>
        </div>

        <!-- Flash Notification Banners -->
        <div
            v-if="flashSuccess"
            class="mb-5 p-4 bg-primary/10 border border-primary/30 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
        >
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-primary text-2xl shrink-0 mt-0.5">check_circle</span>
                <div>
                    <h4 class="text-sm font-bold text-primary">Import Berhasil</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">{{ flashSuccess }}</p>
                </div>
            </div>
            <button
                type="button"
                @click="dismissedFlash = true"
                class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-primary/15 transition-colors cursor-pointer"
                title="Tutup notifikasi"
            >
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <div
            v-if="flashError"
            class="mb-5 p-4 bg-error/10 border border-error/30 rounded-2xl flex items-start justify-between gap-3 animate-in fade-in duration-200"
        >
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-error text-2xl shrink-0 mt-0.5">error</span>
                <div>
                    <h4 class="text-sm font-bold text-error">Import Gagal</h4>
                    <p class="text-xs text-on-surface-variant mt-0.5 leading-relaxed">{{ flashError }}</p>
                </div>
            </div>
            <button
                type="button"
                @click="dismissedFlash = true"
                class="text-on-surface-variant hover:text-on-surface p-1 rounded-full hover:bg-error/15 transition-colors cursor-pointer"
                title="Tutup notifikasi"
            >
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <!-- Search & Filters Toolbar -->
        <StudentToolbar
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
</template>
