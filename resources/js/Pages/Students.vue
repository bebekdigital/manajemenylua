<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StudentToolbar from '@/Components/Students/StudentToolbar.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';
import StudentImportModal from '@/Components/Students/StudentImportModal.vue';

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
    let result = props.students;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(s =>
            s.nama.toLowerCase().includes(query) ||
            s.nisn.includes(query) ||
            (s.nipd && s.nipd.includes(query))
        );
    }

    // Unit filter
    if (unitFilter.value) {
        result = result.filter(s => s.unit === unitFilter.value);
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
            <!-- Action Buttons -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap sm:flex-nowrap">
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
            :students="students"
            :total-count="students.length"
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
        @close="showImportModal = false"
        @success="onImportSuccess"
    />
</template>
