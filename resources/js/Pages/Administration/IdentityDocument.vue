<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StudentDocumentPreviewModal from '@/Components/Administration/StudentDocumentPreviewModal.vue';
import StudentDocumentSheet from '@/Components/Administration/StudentDocumentSheet.vue';
import GenerateIdentityModal from '@/Components/Administration/GenerateIdentityModal.vue';
import StudentToolbar from '@/Components/Students/StudentToolbar.vue';
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

const filteredStudents = computed(() => {
    const filtered = unitTabStudents.value.filter((student) => {
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

    return [...filtered].sort((a, b) => {
        const valA = (a.nama || '').toString().replace(/[\u2018\u2019]/g, "'").toLowerCase();
        const valB = (b.nama || '').toString().replace(/[\u2018\u2019]/g, "'").toLowerCase();
        if (valA < valB) return -1;
        if (valA > valB) return 1;
        return 0;
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
    currentPage.value = 1;
};

// --- Selection (always by student.id) ---
const selectedStudentIds = ref([]);

const selectedStudentsList = computed(() => {
    const idSet = new Set(selectedStudentIds.value);
    return props.students.filter((s) => idSet.has(s.id));
});

const selectedCount = computed(() => selectedStudentsList.value.length);

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
const activePreviewShowPhoto = ref(true);
const isGenerateModalOpen = ref(false);

const openPreview = (student) => {
    activePreviewStudents.value = [student];
    activePreviewPages.value = ['cover', 'school_profile', 'identity'];
    activePreviewShowPhoto.value = true;
    isPreviewModalOpen.value = true;
};

const openGeneratorModal = () => {
    if (selectedCount.value === 0) return;
    isGenerateModalOpen.value = true;
};

const handlePreviewFromGenerator = ({ students, pages, showPhoto }) => {
    activePreviewStudents.value = students;
    activePreviewPages.value = pages;
    activePreviewShowPhoto.value = showPhoto;
    isPreviewModalOpen.value = true;
};

// --- Toast Notice ---
const toastNotice = ref({ show: false, type: 'success', message: '' });
let toastTimer = null;

function showToastNotice(type, message) {
    toastNotice.value = { show: true, type, message };
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastNotice.value.show = false;
    }, 5000);
}

// --- Per-student Foto 3x4 Slider (Default: true/Iya when photo exists) ---
const hiddenPhotoIds = ref([]);

const isStudentPhotoEnabled = (student) => {
    return Boolean(student?.photo_url) && !hiddenPhotoIds.value.includes(student.id);
};

const toggleStudentPhoto = (studentId) => {
    const idx = hiddenPhotoIds.value.indexOf(studentId);
    if (idx > -1) {
        hiddenPhotoIds.value.splice(idx, 1);
    } else {
        hiddenPhotoIds.value.push(studentId);
    }
};

// --- Direct PDF Download ---
const isDownloadingPdf = ref(false);
const downloadPdfProgress = ref('');
const downloadingStudentId = ref(null);

function getXsrfToken() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function fetchAndSavePdf(studentIds, pages, showPhoto, filename, hiddenIds = hiddenPhotoIds.value) {
    const res = await fetch('/administration/identity-document/pdf', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/pdf',
            'X-XSRF-TOKEN': getXsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            student_ids: studentIds,
            pages,
            show_photo: showPhoto,
            hidden_photo_ids: hiddenIds,
            filename,
        }),
    });

    if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
    }

    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => URL.revokeObjectURL(url), 10000);
}

const quickDownloadPdf = async (student) => {
    if (!student || downloadingStudentId.value !== null) return;

    downloadingStudentId.value = student.id;
    const filename = `Identitas - ${student.nama} (${student.nisn || '-'}).pdf`;

    try {
        await fetchAndSavePdf(
            [student.id],
            ['cover', 'school_profile', 'identity'],
            isStudentPhotoEnabled(student),
            filename,
            hiddenPhotoIds.value
        );
        showToastNotice('success', `Berhasil mengunduh PDF ${student.nama}.`);
    } catch (err) {
        console.error('Gagal mengunduh PDF siswa:', err);
        showToastNotice('error', 'Gagal mengunduh berkas PDF siswa. Silakan coba lagi.');
    } finally {
        downloadingStudentId.value = null;
    }
};

const handleDownloadPdfFromGenerator = async ({ students, pages, showPhoto, mode }) => {
    if (!students || students.length === 0) return;

    isDownloadingPdf.value = true;
    downloadPdfProgress.value = '';

    try {
        if (mode === 'per_student') {
            for (let i = 0; i < students.length; i++) {
                const st = students[i];
                downloadPdfProgress.value = `Mengunduh ${i + 1}/${students.length}...`;
                const filename = `Identitas - ${st.nama} (${st.nisn || '-'}).pdf`;
                await fetchAndSavePdf([st.id], pages, showPhoto, filename);
                if (i < students.length - 1) {
                    await new Promise((resolve) => setTimeout(resolve, 350));
                }
            }
            showToastNotice('success', `Berhasil mengunduh ${students.length} file PDF per siswa.`);
        } else {
            downloadPdfProgress.value = 'Menyiapkan PDF...';
            const filename = students.length === 1
                ? `Identitas - ${students[0].nama} (${students[0].nisn || '-'}).pdf`
                : `Identitas Siswa - Gabungan (${students.length} Siswa).pdf`;
            await fetchAndSavePdf(
                students.map((s) => s.id),
                pages,
                showPhoto,
                filename
            );
            showToastNotice('success', `Berhasil mengunduh file PDF (${students.length} siswa).`);
        }
        isGenerateModalOpen.value = false;
    } catch (err) {
        console.error('Gagal mengunduh PDF:', err);
        showToastNotice('error', 'Gagal mengunduh berkas PDF. Silakan coba lagi.');
    } finally {
        isDownloadingPdf.value = false;
        downloadPdfProgress.value = '';
    }
};

// --- Print Queue ---
const batchStudentsToPrint = ref([]);
const batchPagesToPrint = ref(['cover', 'school_profile', 'identity']);
const batchShowPhoto = ref(true);

const executePrint = async (students, pages, showPhoto, customTitle) => {
    if (!students || students.length === 0 || !pages || pages.length === 0) return;

    batchStudentsToPrint.value = students;
    batchPagesToPrint.value = pages;
    batchShowPhoto.value = showPhoto;
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
        : `Identitas Siswa - Gabungan (${targetList.length} Siswa)`;

    executePrint(targetList, pages, activePreviewShowPhoto.value, title);
};

const quickPrint = (student) => {
    executePrint(
        [student],
        ['cover', 'school_profile', 'identity'],
        isStudentPhotoEnabled(student),
        `Identitas - ${student.nama} (${student.nisn || '-'})`
    );
};

const handlePrintBulkFromGenerator = ({ students, pages, showPhoto }) => {
    const title = students.length === 1
        ? `Identitas - ${students[0].nama} (${students[0].nisn || '-'})`
        : `Identitas Siswa - Gabungan (${students.length} Siswa)`;
    executePrint(students, pages, showPhoto, title);
};

const handlePrintSingleFromGenerator = ({ student, pages, showPhoto }) => {
    executePrint(
        [student],
        pages,
        showPhoto,
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

                    <!-- Right: Generate Identitas (disabled until selectedCount > 0) + Pengaturan Template -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button
                            type="button"
                            @click="openGeneratorModal"
                            :disabled="selectedCount === 0"
                            :title="selectedCount === 0 ? 'Pilih minimal 1 siswa di tabel terlebih dahulu' : `Generate identitas untuk ${selectedCount} siswa terpilih`"
                            :class="[
                                'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all',
                                selectedCount > 0
                                    ? 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 shadow-sm hover:shadow-md active:scale-95 cursor-pointer'
                                    : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed'
                            ]"
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

            <!-- Toast Notice -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-if="toastNotice.show"
                    :class="[
                        'fixed bottom-5 right-5 z-[70] max-w-sm px-4 py-3 rounded-2xl shadow-2xl border flex items-center gap-2.5 text-xs font-medium',
                        toastNotice.type === 'error'
                            ? 'bg-rose-900 text-white border-rose-700'
                            : 'bg-emerald-950 text-white border-emerald-700'
                    ]"
                >
                    <span class="material-symbols-outlined text-[20px] shrink-0" :class="toastNotice.type === 'error' ? 'text-rose-200' : 'text-yellow-200'">
                        {{ toastNotice.type === 'error' ? 'error' : 'check_circle' }}
                    </span>
                    <div class="leading-relaxed flex-1">{{ toastNotice.message }}</div>
                    <button
                        type="button"
                        @click="toastNotice.show = false"
                        class="opacity-75 hover:opacity-100 cursor-pointer shrink-0"
                    >
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </button>
                </div>
            </Transition>

            <div class="max-w-7xl mx-auto p-4 md:p-margin-desktop space-y-4">
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
                                    <th class="py-3.5 px-4 text-center text-[11.5px] font-bold uppercase tracking-wider w-56">Aksi Per Nama</th>
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
                                        <div v-if="student.photo_url" class="inline-flex items-center justify-center gap-2">
                                            <button
                                                type="button"
                                                role="switch"
                                                :aria-checked="isStudentPhotoEnabled(student)"
                                                @click="toggleStudentPhoto(student.id)"
                                                :class="[
                                                    'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-1',
                                                    isStudentPhotoEnabled(student) ? 'bg-emerald-700' : 'bg-slate-300'
                                                ]"
                                                :title="isStudentPhotoEnabled(student) ? 'Klik untuk sembunyikan foto siswa ini' : 'Klik untuk tampilkan foto siswa ini'"
                                            >
                                                <span
                                                    :class="[
                                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out',
                                                        isStudentPhotoEnabled(student) ? 'translate-x-4' : 'translate-x-0'
                                                    ]"
                                                />
                                            </button>
                                            <span
                                                :class="[
                                                    'text-[11px] font-bold w-9 text-left',
                                                    isStudentPhotoEnabled(student) ? 'text-emerald-800' : 'text-slate-500'
                                                ]"
                                            >
                                                {{ isStudentPhotoEnabled(student) ? 'Iya' : 'Tidak' }}
                                            </span>
                                        </div>
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
                                                @click="quickDownloadPdf(student)"
                                                :disabled="downloadingStudentId === student.id"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 border border-emerald-700/40 text-emerald-900 hover:bg-emerald-100 transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-wait"
                                                title="Download PDF Langsung"
                                            >
                                                <span v-if="downloadingStudentId === student.id" class="material-symbols-outlined text-[15px] animate-spin">sync</span>
                                                <span v-else class="material-symbols-outlined text-[15px]">picture_as_pdf</span>
                                                <span>{{ downloadingStudentId === student.id ? 'Unduh...' : 'Download' }}</span>
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
                        <div class="flex items-center gap-3 flex-wrap">
                            <span>
                                Menampilkan <strong>{{ startEntry }}</strong>–<strong>{{ Math.min(startEntry + paginatedStudents.length - 1, filteredStudents.length) }}</strong> dari <strong>{{ filteredStudents.length }}</strong> siswa
                            </span>
                            <button
                                v-if="filteredStudents.length > paginatedStudents.length"
                                type="button"
                                @click="selectAllFiltered"
                                class="text-emerald-800 hover:underline font-semibold cursor-pointer"
                            >
                                {{ isAllFilteredSelected ? 'Batal pilih semua hasil filter' : `Pilih semua ${filteredStudents.length} siswa` }}
                            </button>
                            <button
                                v-if="selectedCount > 0"
                                type="button"
                                @click="clearSelection"
                                class="text-rose-600 hover:underline font-semibold cursor-pointer"
                            >
                                Bersihkan pilihan ({{ selectedCount }})
                            </button>
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
                        :show-photo="batchShowPhoto && !hiddenPhotoIds.includes(st.id)"
                    />
                </template>
            </div>
        </Teleport>

        <!-- Preview Modal (Supports single & bulk preview) -->
        <StudentDocumentPreviewModal
            :is-open="isPreviewModalOpen"
            :students="activePreviewStudents"
            :initial-pages="activePreviewPages"
            :show-photo="activePreviewShowPhoto"
            :hidden-photo-ids="hiddenPhotoIds"
            :template="template"
            @close="isPreviewModalOpen = false"
            @print="handlePrintFromPreview"
        />

        <!-- Generate Identitas Siswa Modal -->
        <GenerateIdentityModal
            :is-open="isGenerateModalOpen"
            :selected-students="selectedStudentsList"
            :is-downloading="isDownloadingPdf"
            :download-progress="downloadPdfProgress"
            @close="isGenerateModalOpen = false"
            @preview="handlePreviewFromGenerator"
            @print-bulk="handlePrintBulkFromGenerator"
            @print-single="handlePrintSingleFromGenerator"
            @download-pdf="handleDownloadPdfFromGenerator"
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
