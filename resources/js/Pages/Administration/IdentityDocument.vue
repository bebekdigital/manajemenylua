<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import StudentDocumentPreviewModal from '@/Components/Administration/StudentDocumentPreviewModal.vue';
import StudentDocumentSheet from '@/Components/Administration/StudentDocumentSheet.vue';
import GenerateIdentityModal from '@/Components/Administration/GenerateIdentityModal.vue';
import StudentToolbar from '@/Components/Students/StudentToolbar.vue';
import AppSelect from '@/Components/Shared/AppSelect.vue';
import {
    getGenderBadgeClass,
    getGenderLabel,
    getUnitBadgeClass,
} from '@/Utils/studentBadges';

const props = defineProps({
    students: { type: Array, default: () => [] },
    academicYears: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    selectedAcademicYear: { type: Object, default: null },
    template: { type: Object, required: true },
});

// --- Filter, Search & Pagination ---
const searchQuery = ref('');
const statusFilter = ref('aktif');
const activeUnit = ref('all');
const angkatanFilters = ref([]);
const jenjangFilters = ref([]);
const jkFilter = ref('');
const selectedKelas = ref('');
const currentPage = ref(1);
const perPage = ref(25);

function matchesStudentUnit(s, unitId) {
    if (unitId === 'all') return true;
    const u = (s.unit || '').toUpperCase();
    const up = (s.unit_pesantren || '').toUpperCase();
    const p = (s.program || '').toUpperCase();
    if (unitId === 'SDIT') return u.includes('SDIT') || u.includes('SD IT') || u === 'SD';
    if (unitId === 'SMPIT') return u.includes('SMPIT') || u.includes('SMP IT') || u === 'SMP';
    if (unitId === 'SMAIT') return u.includes('SMAIT') || u.includes('SMA IT') || u === 'SMA';
    if (unitId === 'PPTQ') return u.includes('PPTQ') || up.includes('PPTQ') || p.includes('BOARDING');
    return false;
}

const unitTabStudents = computed(() => {
    if (activeUnit.value === 'all') return props.students;
    return props.students.filter((s) => matchesStudentUnit(s, activeUnit.value));
});

const unitOptions = computed(() => {
    const count = (id) => (id === 'all' ? props.students.length : props.students.filter((s) => matchesStudentUnit(s, id)).length);
    return [
        { value: 'all', label: `Semua Jenjang Pendidikan (${count('all')})` },
        { value: 'SDIT', label: `SDIT (${count('SDIT')})` },
        { value: 'SMPIT', label: `SMPIT (${count('SMPIT')})` },
        { value: 'SMAIT', label: `SMAIT (${count('SMAIT')})` },
        { value: 'PPTQ', label: `PPTQ (${count('PPTQ')})` },
    ];
});

const availableClasses = computed(() => {
    const set = new Set();
    unitTabStudents.value.forEach((s) => {
        if (s.kelas && s.kelas !== '-') set.add(s.kelas);
    });
    return Array.from(set).sort();
});

const kelasOptions = computed(() => [
    { value: '', label: 'Semua Kelas' },
    ...availableClasses.value.map((k) => ({ value: k, label: `Kelas ${k}` })),
]);

const filteredStudents = computed(() => {
    return unitTabStudents.value.filter((student) => {
        // Status keaktifan
        if (statusFilter.value && statusFilter.value !== 'semua') {
            const raw = (student.status_keaktifan || 'Aktif').toLowerCase().trim();
            if (statusFilter.value === 'lulus' && raw !== 'lulus') return false;
            if (statusFilter.value === 'tidak_aktif' && raw !== 'tidak aktif' && raw !== 'tidak_aktif' && !raw.includes('tidak')) return false;
            if (statusFilter.value === 'aktif' && (raw === 'lulus' || raw === 'tidak aktif' || raw === 'tidak_aktif' || raw.includes('tidak'))) return false;
        }

        // Angkatan
        if (angkatanFilters.value.length > 0 && !angkatanFilters.value.includes(student.angkatan)) {
            return false;
        }

        // Jenjang
        if (jenjangFilters.value.length > 0 && !jenjangFilters.value.includes(student.jenjang)) {
            return false;
        }

        // Jenis Kelamin
        if (jkFilter.value && student.jk_raw !== jkFilter.value) {
            return false;
        }

        // Kelas
        if (selectedKelas.value && student.kelas !== selectedKelas.value) {
            return false;
        }

        // Pencarian
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim();
            const match =
                student.nama?.toLowerCase().includes(q) ||
                student.nisn?.toLowerCase().includes(q) ||
                student.nis?.toLowerCase().includes(q) ||
                student.nipd?.toLowerCase().includes(q) ||
                student.kelas?.toLowerCase().includes(q) ||
                String(student.angkatan || '').toLowerCase().includes(q);
            if (!match) return false;
        }

        return true;
    });
});

const totalPages = computed(() => {
    if (perPage.value === 0) return 1;
    return Math.max(1, Math.ceil(filteredStudents.value.length / perPage.value));
});

const paginatedStudents = computed(() => {
    if (perPage.value === 0) return filteredStudents.value;
    const start = (currentPage.value - 1) * perPage.value;
    return filteredStudents.value.slice(start, start + perPage.value);
});

const startEntry = computed(() => {
    if (filteredStudents.value.length === 0) return 0;
    if (perPage.value === 0) return 1;
    return (currentPage.value - 1) * perPage.value + 1;
});

const handleSearch = (q) => {
    searchQuery.value = q;
    currentPage.value = 1;
};
const handleFilterStatus = (val) => {
    statusFilter.value = val;
    currentPage.value = 1;
};
const handleFilterUnit = (u) => {
    activeUnit.value = u;
    selectedKelas.value = '';
    currentPage.value = 1;
};
const handleFilterAngkatan = (val) => {
    angkatanFilters.value = val;
    currentPage.value = 1;
};
const handleFilterJenjang = (val) => {
    jenjangFilters.value = val;
    currentPage.value = 1;
};
const handleFilterJk = (val) => {
    jkFilter.value = val;
    currentPage.value = 1;
};
const handlePerPageChange = (val) => {
    perPage.value = Number(val);
    currentPage.value = 1;
};
const handleResetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'aktif';
    activeUnit.value = 'all';
    angkatanFilters.value = [];
    jenjangFilters.value = [];
    jkFilter.value = '';
    selectedKelas.value = '';
    currentPage.value = 1;
};

// Academic year switching
const switchAcademicYear = (e) => {
    router.get(
        '/administration/identity-document',
        { academic_year_id: e.target.value },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

// --- Selection (always by student.id) ---
const selectedStudentIds = ref([]);

const selectedCount = computed(() => selectedStudentIds.value.length);

const isAllVisibleSelected = computed(() => {
    if (paginatedStudents.value.length === 0) return false;
    return paginatedStudents.value.every((s) => selectedStudentIds.value.includes(s.id));
});

const isAllFilteredSelected = computed(() => {
    if (filteredStudents.value.length === 0) return false;
    return filteredStudents.value.every((s) => selectedStudentIds.value.includes(s.id));
});

const isIndeterminate = computed(() => {
    const count = paginatedStudents.value.filter((s) => selectedStudentIds.value.includes(s.id)).length;
    return count > 0 && count < paginatedStudents.value.length;
});

const toggleSelectVisible = () => {
    if (isAllVisibleSelected.value) {
        const visibleIds = new Set(paginatedStudents.value.map((s) => s.id));
        selectedStudentIds.value = selectedStudentIds.value.filter((id) => !visibleIds.has(id));
    } else {
        const currentIds = new Set(selectedStudentIds.value);
        paginatedStudents.value.forEach((s) => currentIds.add(s.id));
        selectedStudentIds.value = Array.from(currentIds);
    }
};

const selectAllFiltered = () => {
    if (isAllFilteredSelected.value) {
        const filteredSet = new Set(filteredStudents.value.map((s) => s.id));
        selectedStudentIds.value = selectedStudentIds.value.filter((id) => !filteredSet.has(id));
    } else {
        const currentIds = new Set(selectedStudentIds.value);
        filteredStudents.value.forEach((s) => currentIds.add(s.id));
        selectedStudentIds.value = Array.from(currentIds);
    }
};

const toggleStudent = (id) => {
    const idx = selectedStudentIds.value.indexOf(id);
    if (idx > -1) selectedStudentIds.value.splice(idx, 1);
    else selectedStudentIds.value.push(id);
};

const isSelected = (id) => selectedStudentIds.value.includes(id);

const clearSelection = () => {
    selectedStudentIds.value = [];
};

// --- Modals ---
const isPreviewModalOpen = ref(false);
const activePreviewStudents = ref([]);
const activePreviewPages = ref(['cover', 'school_profile', 'identity']);
const isGenerateModalOpen = ref(false);

const openPreview = (student) => {
    activePreviewStudents.value = [student];
    activePreviewPages.value = ['cover', 'school_profile', 'identity'];
    isPreviewModalOpen.value = true;
};

const openGeneratorModal = () => {
    isGenerateModalOpen.value = true;
};

const handlePreviewFromGenerator = ({ students, pages }) => {
    activePreviewStudents.value = students;
    activePreviewPages.value = pages;
    isPreviewModalOpen.value = true;
};

// --- Print Queue ---
const batchStudentsToPrint = ref([]);
const batchPagesToPrint = ref(['cover', 'school_profile', 'identity']);
const selectedBatchPages = ref(['cover', 'school_profile', 'identity']);

const executePrint = async (students, pages, customTitle) => {
    if (!students || students.length === 0 || !pages || pages.length === 0) return;

    batchStudentsToPrint.value = students;
    batchPagesToPrint.value = pages;
    await nextTick();

    const originalTitle = document.title;
    if (customTitle) {
        document.title = customTitle;
    }

    const restoreTitle = () => {
        document.title = originalTitle;
        window.removeEventListener('afterprint', restoreTitle);
    };
    window.addEventListener('afterprint', restoreTitle);

    setTimeout(() => {
        window.print();
        setTimeout(() => {
            if (document.title !== originalTitle) {
                document.title = originalTitle;
            }
        }, 1500);
    }, 300);
};

const handlePrintFromPreview = ({ student, students, pages }) => {
    const targetList = students && students.length > 0 ? students : (student ? [student] : []);
    if (targetList.length === 0) return;

    const title = targetList.length === 1
        ? `Identitas - ${targetList[0].nama} (${targetList[0].nisn || '-'})`
        : `Identitas Siswa - Sekaligus (${targetList.length} Siswa)`;

    executePrint(targetList, pages, title);
};

const quickPrint = (student) => {
    executePrint(
        [student],
        ['cover', 'school_profile', 'identity'],
        `Identitas - ${student.nama} (${student.nisn || '-'})`
    );
};

const startBatchPrint = () => {
    if (selectedCount.value === 0 || selectedBatchPages.value.length === 0) return;
    const ids = new Set(selectedStudentIds.value);
    const list = props.students.filter((s) => ids.has(s.id));
    const title = list.length === 1
        ? `Identitas - ${list[0].nama} (${list[0].nisn || '-'})`
        : `Identitas Siswa - Sekaligus (${list.length} Siswa)`;
    executePrint(list, [...selectedBatchPages.value], title);
};

const handlePrintBulkFromGenerator = ({ students, pages }) => {
    const title = students.length === 1
        ? `Identitas - ${students[0].nama} (${students[0].nisn || '-'})`
        : `Identitas Siswa - Sekaligus (${students.length} Siswa)`;
    executePrint(students, pages, title);
};

const handlePrintSingleFromGenerator = ({ student, pages }) => {
    executePrint(
        [student],
        pages,
        `Identitas - ${student.nama} (${student.nisn || '-'})`
    );
};
</script>

<template>
    <div>
        <Head title="Cetak Berkas Identitas Siswa" />

        <!-- ===== SCREEN UI ===== -->
        <div class="print:hidden flex-grow min-h-screen bg-white pb-20">
            <!-- Sticky top bar -->
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-2xs">
                <div class="max-w-7xl mx-auto px-4 md:px-margin-desktop py-3 flex flex-wrap items-center justify-between gap-3">
                    <!-- Left: Back + Title -->
                    <div class="flex items-center space-x-3">
                        <Link
                            href="/administration"
                            class="flex items-center justify-center w-8 h-8 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors"
                            title="Kembali ke Menu Administrasi"
                        >
                            <span class="material-symbols-outlined text-lg">arrow_back</span>
                        </Link>
                        <div class="w-px h-5 bg-outline-variant"></div>
                        <div class="flex items-center space-x-2.5">
                            <span class="w-8 h-8 bg-emerald-100 text-emerald-900 rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-lg">contact_page</span>
                            </span>
                            <div>
                                <h1 class="text-sm sm:text-base font-bold text-slate-900 leading-tight">Cetak Berkas Identitas Siswa</h1>
                                <p class="text-[11px] text-slate-500 hidden sm:block">Cover Rapor, Profil Sekolah & Lembar Identitas Peserta Didik</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Generate Identitas + Pengaturan Template -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="openGeneratorModal"
                            :disabled="students.length === 0"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 rounded-xl text-xs sm:text-sm font-bold shadow-sm hover:shadow-md transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-[18px]">auto_awesome</span>
                            <span>Generate Identitas Siswa</span>
                            <span
                                v-if="selectedCount > 0"
                                class="px-1.5 py-0.5 rounded-full bg-yellow-200 text-emerald-950 text-[10px] font-extrabold leading-none"
                            >
                                {{ selectedCount }}
                            </span>
                        </button>

                        <Link
                            href="/administration/identity-document/template"
                            class="flex items-center space-x-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-base text-slate-500">tune</span>
                            <span class="hidden sm:inline">Pengaturan Template</span>
                            <span class="sm:hidden">Template</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto p-4 md:p-margin-desktop space-y-4">
                <!-- Quick Filter Bar (Tahun Ajaran + Kelas + Shortcut Bulk) -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50/90 border border-slate-200 rounded-2xl p-3 sm:px-4">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Filter Tahun Ajaran -->
                        <div v-if="academicYears.length > 0" class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500">Tahun Ajaran:</span>
                            <select
                                :value="selectedAcademicYear?.id || ''"
                                @change="switchAcademicYear"
                                class="pl-3 pr-8 py-1.5 rounded-xl border border-slate-300 bg-white text-xs sm:text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                            >
                                <option v-for="ay in academicYears" :key="ay.id" :value="ay.id">
                                    {{ ay.name }} ({{ ay.semester }})
                                </option>
                            </select>
                        </div>

                        <!-- Filter Kelas -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500">Kelas:</span>
                            <AppSelect
                                v-model="selectedKelas"
                                :options="kelasOptions"
                                icon="meeting_room"
                                title="Filter Kelas"
                                placement="left"
                                @change="currentPage = 1"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Menampilkan <strong class="text-slate-900">{{ filteredStudents.length }}</strong> dari {{ students.length }} siswa</span>
                        <button
                            v-if="filteredStudents.length > 0"
                            type="button"
                            @click="selectAllFiltered"
                            class="px-2.5 py-1 rounded-lg border border-emerald-700/30 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 font-semibold transition-colors cursor-pointer"
                        >
                            {{ isAllFilteredSelected ? 'Batal Centang Semua' : `Centang Semua (${filteredStudents.length})` }}
                        </button>
                    </div>
                </div>

                <!-- Filter & Action Toolbar (Reusable StudentToolbar) -->
                <StudentToolbar
                    :students="unitTabStudents"
                    :total-count="unitTabStudents.length"
                    :filtered-count="filteredStudents.length"
                    :per-page="perPage"
                    :active-status="statusFilter"
                    :active-angkatan="angkatanFilters"
                    :active-jenjang="jenjangFilters"
                    :active-jk="jkFilter"
                    :active-unit="activeUnit"
                    :unit-options="unitOptions"
                    @search="handleSearch"
                    @filter-status="handleFilterStatus"
                    @filter-unit="handleFilterUnit"
                    @filter-angkatan="handleFilterAngkatan"
                    @filter-jenjang="handleFilterJenjang"
                    @filter-jk="handleFilterJk"
                    @update:per-page="handlePerPageChange"
                    @reset-filters="handleResetFilters"
                />

                <!-- Batch Print Action Bar (appears when students are checked) -->
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    leave-active-class="transition-all duration-150"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-if="selectedCount > 0"
                        class="bg-emerald-900 rounded-2xl px-4 sm:px-5 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-lg border border-emerald-950"
                    >
                        <div class="flex items-center flex-wrap gap-3 text-white">
                            <span class="material-symbols-outlined text-xl text-yellow-200">check_circle</span>
                            <span class="font-bold text-sm">
                                {{ selectedCount }} siswa dipilih
                            </span>
                            <div class="flex items-center space-x-3 bg-white/10 px-3 py-1.5 rounded-xl border border-white/15">
                                <span class="text-xs font-medium text-emerald-100">Halaman:</span>
                                <label class="flex items-center space-x-1.5 cursor-pointer group">
                                    <input type="checkbox" v-model="selectedBatchPages" value="cover" class="w-3.5 h-3.5 rounded border-white/40 bg-white/20 text-yellow-300 focus:ring-yellow-200 cursor-pointer">
                                    <span class="text-xs font-medium text-white">Cover</span>
                                </label>
                                <label class="flex items-center space-x-1.5 cursor-pointer group">
                                    <input type="checkbox" v-model="selectedBatchPages" value="school_profile" class="w-3.5 h-3.5 rounded border-white/40 bg-white/20 text-yellow-300 focus:ring-yellow-200 cursor-pointer">
                                    <span class="text-xs font-medium text-white">Profil</span>
                                </label>
                                <label class="flex items-center space-x-1.5 cursor-pointer group">
                                    <input type="checkbox" v-model="selectedBatchPages" value="identity" class="w-3.5 h-3.5 rounded border-white/40 bg-white/20 text-yellow-300 focus:ring-yellow-200 cursor-pointer">
                                    <span class="text-xs font-medium text-white">Identitas</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center flex-wrap gap-2">
                            <button
                                type="button"
                                @click="clearSelection"
                                class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium text-emerald-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="openGeneratorModal"
                                class="flex items-center gap-1.5 px-3.5 py-1.5 bg-white/15 hover:bg-white/25 text-white border border-white/25 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[17px]">tune</span>
                                <span>Pilih Mode (Sekaligus / Per Nama)</span>
                            </button>
                            <button
                                type="button"
                                @click="startBatchPrint"
                                :disabled="selectedBatchPages.length === 0"
                                class="flex items-center space-x-1.5 px-4 py-1.5 bg-yellow-200 text-emerald-950 rounded-xl text-xs sm:text-sm font-bold hover:bg-yellow-100 active:scale-95 transition-all cursor-pointer shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="material-symbols-outlined text-base">print</span>
                                <span>Cetak Sekaligus ({{ selectedCount }})</span>
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Students Table -->
                <div class="bg-white rounded-xl border border-emerald-800 shadow-[0_4px_25px_rgba(0,40,20,0.08)] overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar-x">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-emerald-800 text-white border-b border-emerald-900">
                                    <th class="py-3.5 px-3 w-12 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isAllVisibleSelected"
                                            :indeterminate="isIndeterminate"
                                            @change="toggleSelectVisible"
                                            class="w-4 h-4 rounded cursor-pointer accent-yellow-300"
                                            title="Pilih semua siswa di halaman ini"
                                        />
                                    </th>
                                    <th class="py-3.5 px-2.5 w-12 text-center text-[11.5px] font-bold uppercase tracking-wider">No</th>
                                    <th class="py-3.5 px-4 min-w-[240px] text-[11.5px] font-bold uppercase tracking-wider">Nama Siswa</th>
                                    <th class="py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider">Kelas & Jenjang</th>
                                    <th class="py-3.5 px-3 text-[11.5px] font-bold uppercase tracking-wider">Unit & Angkatan</th>
                                    <th class="py-3.5 px-3 text-center text-[11.5px] font-bold uppercase tracking-wider">Foto 3x4</th>
                                    <th class="py-3.5 px-4 text-center text-[11.5px] font-bold uppercase tracking-wider w-44">Aksi Per Nama</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr
                                    v-for="(student, index) in paginatedStudents"
                                    :key="student.id"
                                    class="hover:bg-slate-50/90 transition-colors group"
                                    :class="{ 'bg-emerald-50/60': isSelected(student.id) }"
                                >
                                    <td class="py-3 px-3 text-center align-middle">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected(student.id)"
                                            @change="toggleStudent(student.id)"
                                            class="w-4 h-4 rounded cursor-pointer accent-emerald-800"
                                        />
                                    </td>
                                    <td class="py-3 px-2.5 text-xs text-slate-500 font-mono text-center align-middle">
                                        {{ startEntry + index }}
                                    </td>
                                    <td class="py-3 px-4 align-middle">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <button
                                                    type="button"
                                                    @click="openPreview(student)"
                                                    class="text-[13.5px] font-bold text-slate-900 hover:text-emerald-800 text-left transition-colors cursor-pointer"
                                                >
                                                    {{ student.nama }}
                                                </button>
                                                <span
                                                    v-if="student.jk_raw"
                                                    :class="['text-[10px] font-bold px-1.5 py-0.5 rounded border leading-none', getGenderBadgeClass(student.jk_raw)]"
                                                    :title="getGenderLabel(student.jk_raw)"
                                                >
                                                    {{ student.jk_raw }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-500 font-mono mt-0.5">
                                                NISN: {{ student.nisn || '-' }} <span v-if="student.nipd && student.nipd !== '-'">• NIPD: {{ student.nipd }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 align-middle whitespace-nowrap">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span v-if="student.jenjang" class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                                {{ student.jenjang }}
                                            </span>
                                            <span v-if="student.kelas && student.kelas !== '-'" class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-900 border border-emerald-200">
                                                {{ student.kelas }}
                                            </span>
                                            <span v-else class="text-xs text-slate-400">-</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 align-middle whitespace-nowrap">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span v-if="student.unit" :class="['px-2 py-0.5 rounded-md text-[11px] font-semibold border', getUnitBadgeClass('formal')]">
                                                {{ student.unit }}
                                            </span>
                                            <span v-if="student.angkatan" class="px-2 py-0.5 rounded-md text-[11px] font-mono bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ student.angkatan }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center align-middle whitespace-nowrap">
                                        <span
                                            v-if="student.photo_url"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Ada Foto
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200"
                                        >
                                            Belum Ada
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center align-middle whitespace-nowrap">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openPreview(student)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-emerald-800 transition-colors cursor-pointer"
                                                title="Preview Dokumen"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                                <span>Preview</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="quickPrint(student)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-900 hover:bg-emerald-800 text-yellow-200 rounded-lg text-xs font-bold transition-all active:scale-95 cursor-pointer shadow-2xs"
                                                title="Generate & Cetak Identitas Siswa Ini"
                                            >
                                                <span class="material-symbols-outlined text-[15px]">print</span>
                                                <span>Cetak</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="paginatedStudents.length === 0">
                                    <td colspan="7" class="py-12 px-4 text-center text-sm text-slate-400">
                                        <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">search_off</span>
                                        Tidak ada siswa yang sesuai dengan filter saat ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div
                        v-if="filteredStudents.length > 0"
                        class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600"
                    >
                        <div>
                            Menampilkan <strong>{{ startEntry }}</strong>–<strong>{{ Math.min(startEntry + paginatedStudents.length - 1, filteredStudents.length) }}</strong> dari <strong>{{ filteredStudents.length }}</strong> siswa
                        </div>
                        <div v-if="perPage > 0 && totalPages > 1" class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage <= 1"
                                class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer font-semibold"
                            >
                                Sebelumnya
                            </button>
                            <span class="px-2.5 font-semibold text-slate-800">
                                Hal {{ currentPage }} / {{ totalPages }}
                            </span>
                            <button
                                type="button"
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage >= totalPages"
                                class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer font-semibold"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PRINT CONTAINER (teleported to body so we can hide #app) ===== -->
        <Teleport to="body">
            <div id="print-container" class="font-serif">
                <template v-for="(st, idx) in batchStudentsToPrint" :key="st.id || idx">
                    <StudentDocumentSheet
                        :student="st"
                        :template="template"
                        :pages-to-print="batchPagesToPrint"
                        :is-last-in-batch="idx === batchStudentsToPrint.length - 1"
                    />
                </template>
            </div>
        </Teleport>

        <!-- Preview Modal (Supports single & bulk preview) -->
        <StudentDocumentPreviewModal
            :is-open="isPreviewModalOpen"
            :students="activePreviewStudents"
            :initial-pages="activePreviewPages"
            :template="template"
            @close="isPreviewModalOpen = false"
            @print="handlePrintFromPreview"
        />

        <!-- Generate Identitas Siswa Modal (Bulking & Per Nama) -->
        <GenerateIdentityModal
            :is-open="isGenerateModalOpen"
            :all-students="students"
            :filtered-students="filteredStudents"
            :preselected-ids="selectedStudentIds"
            @close="isGenerateModalOpen = false"
            @preview="handlePreviewFromGenerator"
            @print-bulk="handlePrintBulkFromGenerator"
            @print-single="handlePrintSingleFromGenerator"
        />
    </div>
</template>

<style>
#print-container {
    display: none;
}

@media print {
    /* Hide the entire Vue app layout (sidebar, header, spacing, etc) */
    #app {
        display: none !important;
    }

    /* Show only the teleported print container */
    #print-container {
        display: block !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
}
</style>
