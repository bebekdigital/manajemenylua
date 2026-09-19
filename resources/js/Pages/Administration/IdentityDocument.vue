<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import StudentDocumentPreviewModal from '@/Components/Administration/StudentDocumentPreviewModal.vue';
import StudentDocumentSheet from '@/Components/Administration/StudentDocumentSheet.vue';
import StudentToolbar from '@/Components/Students/StudentToolbar.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';

const props = defineProps({
    students: { type: Array, default: () => [] },
    academicYears: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    selectedAcademicYear: { type: Object, default: null },
    template: { type: Object, required: true },
});

// --- Filter & Search ---
const searchQuery = ref('');
const selectedUnit = ref('');
const selectedGrade = ref('');
const selectedGender = ref('');

const handleSearch = (q) => searchQuery.value = q;
const handleFilterUnit = (u) => selectedUnit.value = u;
const handleFilterGrade = (g) => selectedGrade.value = g;
const handleResetFilters = () => {
    searchQuery.value = '';
    selectedUnit.value = '';
    selectedGrade.value = '';
    selectedGender.value = '';
};

// --- Selection ---
const selectedStudentIds = ref([]);

// --- Modals ---
const isPreviewModalOpen = ref(false);
const activePreviewStudent = ref(null);

// --- Print Queue ---
const batchStudentsToPrint = ref([]);
const batchPagesToPrint = ref(['cover', 'school_profile', 'identity']);

// Computed: Filtered students
const filteredStudents = computed(() => {
    return props.students.filter(student => {
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase();
            if (
                !student.nama?.toLowerCase().includes(q) &&
                !student.nisn?.toLowerCase().includes(q) &&
                !student.nis?.toLowerCase().includes(q)
            ) return false;
        }
        if (selectedUnit.value) {
            if (student.unit !== selectedUnit.value) return false;
        }
        if (selectedGrade.value) {
            if (student.kelas !== selectedGrade.value) return false;
        }
        if (selectedGender.value) {
            if (student.jk_raw !== selectedGender.value) return false;
        }
        return true;
    });
});

const selectedCount = computed(() => selectedStudentIds.value.length);

const isAllSelected = computed(() => {
    if (filteredStudents.value.length === 0) return false;
    return filteredStudents.value.every(s => selectedStudentIds.value.includes(s.id));
});

const isIndeterminate = computed(() => {
    const count = filteredStudents.value.filter(s => selectedStudentIds.value.includes(s.id)).length;
    return count > 0 && count < filteredStudents.value.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        const visibleIds = new Set(filteredStudents.value.map(s => s.id));
        selectedStudentIds.value = selectedStudentIds.value.filter(id => !visibleIds.has(id));
    } else {
        const currentIds = new Set(selectedStudentIds.value);
        filteredStudents.value.forEach(s => currentIds.add(s.id));
        selectedStudentIds.value = Array.from(currentIds);
    }
};

const toggleStudent = (id) => {
    const idx = selectedStudentIds.value.indexOf(id);
    if (idx > -1) selectedStudentIds.value.splice(idx, 1);
    else selectedStudentIds.value.push(id);
};

const isSelected = (id) => selectedStudentIds.value.includes(id);

// Academic year switching
const switchAcademicYear = (e) => {
    router.get('/administration/identity-document', { academic_year_id: e.target.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Preview
const openPreview = (student) => {
    activePreviewStudent.value = student;
    isPreviewModalOpen.value = true;
};

// Print helpers
const getPages = () => {
    return ['cover', 'school_profile', 'identity'];
};



const handlePrintStudent = async ({ student, pages }) => {
    batchStudentsToPrint.value = [student];
    batchPagesToPrint.value = pages;
    await nextTick();
    setTimeout(() => window.print(), 300);
};

const quickPrint = async (student) => {
    batchStudentsToPrint.value = [student];
    batchPagesToPrint.value = getPages();
    await nextTick();
    setTimeout(() => window.print(), 300);
};

const startBatchPrint = async () => {
    if (selectedCount.value === 0) return;
    const ids = new Set(selectedStudentIds.value);
    batchStudentsToPrint.value = props.students.filter(s => ids.has(s.id));
    batchPagesToPrint.value = getPages();
    await nextTick();
    setTimeout(() => window.print(), 300);
};

const clearSelection = () => { selectedStudentIds.value = []; };
</script>

<template>
    <div>
        <Head title="Cetak Berkas Identitas Siswa" />

        <!-- ===== SCREEN UI ===== -->
        <div class="print:hidden flex-grow min-h-screen bg-background">

            <!-- Sticky top bar -->
            <div class="sticky top-0 z-20 bg-surface/95 backdrop-blur-md border-b border-outline-variant shadow-xs">
                <div class="max-w-7xl mx-auto px-6 py-3 flex flex-wrap items-center justify-between gap-3">
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
                        <div class="flex items-center space-x-2">
                            <span class="w-7 h-7 bg-primary-container text-on-primary-container rounded-lg flex items-center justify-center">
                                <span class="material-symbols-outlined text-base">contact_page</span>
                            </span>
                            <div>
                                <h1 class="text-sm font-bold text-on-surface leading-tight">Cetak Berkas Identitas</h1>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Pengaturan Template -->
                    <div class="flex items-center space-x-2">
                        <Link
                            href="/administration/identity-document/template"
                            class="flex items-center space-x-1.5 px-3 py-1.5 bg-surface-container-low hover:bg-surface-container border border-outline-variant rounded-xl text-xs font-semibold text-on-surface transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-base text-on-surface-variant">tune</span>
                            <span>Pengaturan Template</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-6 py-6 space-y-5 mt-4">

                <!-- Filter & Action Bar -->
                <StudentToolbar
                    :students="students"
                    :total-count="students.length"
                    :filtered-count="filteredStudents.length"
                    :per-page="0"
                    :hide-per-page="true"
                    @search="handleSearch"
                    @filter-unit="handleFilterUnit"
                    @filter-grade="handleFilterGrade"
                    @reset-filters="handleResetFilters"
                >
                    <template #extra-filters>
                        <!-- Gender Filter -->
                        <div class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[100px] xl:min-w-[90px] flex-grow sm:flex-grow-0">
                            <select
                                v-model="selectedGender"
                                class="w-full pl-2 sm:pl-3 pr-6 sm:pr-8 py-2 sm:py-2 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-medium text-slate-700 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 cursor-pointer transition-colors"
                            >
                                <option value="">Semua JK</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </template>
                </StudentToolbar>


                <!-- Batch Print Action Bar (appears when students are selected) -->
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    leave-active-class="transition-all duration-150"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-if="selectedCount > 0"
                        class="bg-primary rounded-2xl px-5 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-md"
                    >
                        <div class="flex items-center space-x-3 text-on-primary">
                            <span class="material-symbols-outlined text-xl">check_circle</span>
                            <span class="font-semibold text-sm">
                                {{ selectedCount }} siswa dipilih — Format: 
                                <strong>Lengkap (3 Hlm)</strong>
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="clearSelection"
                                class="px-3 py-1.5 rounded-xl text-sm font-medium text-on-primary/80 hover:text-on-primary hover:bg-white/10 transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                @click="startBatchPrint"
                                class="flex items-center space-x-2 px-4 py-1.5 bg-white text-primary rounded-xl text-sm font-bold hover:bg-white/90 active:scale-95 transition-all cursor-pointer shadow-sm"
                            >
                                <span class="material-symbols-outlined text-base">print</span>
                                <span>Cetak {{ selectedCount }} Siswa</span>
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Students Table -->
                <StudentTable
                    :students="filteredStudents"
                    :current-page="1"
                    :per-page="0"
                    :total-entries="filteredStudents.length"
                >
                    <!-- Custom Table Header -->
                    <template #thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant text-xs font-bold text-on-surface-variant uppercase tracking-wide">
                            <th class="p-3 w-12 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    :indeterminate="isIndeterminate"
                                    @change="toggleSelectAll"
                                    class="w-4 h-4 rounded cursor-pointer accent-primary"
                                />
                            </th>
                            <th class="py-3 px-3 w-10">No</th>
                            <th class="py-3 px-3 whitespace-nowrap w-1">Nama Siswa</th>
                            <th class="py-3 px-3 w-28">Kelas</th>
                            <th class="py-3 px-3">Unit</th>
                            <th class="py-3 px-4 text-right w-28">Aksi</th>
                        </tr>
                    </template>

                    <!-- Custom Table Row -->
                    <template #row="{ student, index, startEntry }">
                        <td class="p-3 text-center">
                            <input
                                type="checkbox"
                                :checked="isSelected(student.id)"
                                @change="toggleStudent(student.id)"
                                class="w-4 h-4 rounded cursor-pointer accent-primary"
                            />
                        </td>
                        <td class="py-2.5 px-3 text-xs text-on-surface-variant font-mono">
                            {{ startEntry + index }}
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap">
                            <button @click="openPreview(student)" class="text-left">
                                <div class="font-semibold text-on-surface group-hover:text-primary transition-colors cursor-pointer">
                                    {{ student.nama }}
                                </div>
                            </button>
                        </td>
                        <td class="py-2.5 px-3">
                            <div class="font-medium text-on-surface text-sm">{{ student.kelas || '-' }}</div>
                        </td>
                        <td class="py-2.5 px-3">
                            <div class="text-sm text-on-surface-variant">{{ student.unit || '-' }}</div>
                        </td>
                        <td class="py-2.5 px-4 text-right">
                            <div class="flex items-center justify-end space-x-1.5">
                                <button
                                    @click="openPreview(student)"
                                    class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary-container/30 transition-colors cursor-pointer"
                                    title="Preview Dokumen"
                                >
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                                <button
                                    @click="quickPrint(student)"
                                    class="flex items-center space-x-1 px-2.5 py-1.5 bg-primary/10 hover:bg-primary text-primary hover:text-on-primary rounded-lg text-xs font-semibold transition-all active:scale-95 cursor-pointer"
                                    title="Cetak Langsung"
                                >
                                    <span class="material-symbols-outlined text-sm">print</span>
                                    <span>Cetak</span>
                                </button>
                            </div>
                        </td>
                    </template>
                </StudentTable>

                <!-- Custom Table Footer for selection -->
                <div v-if="filteredStudents.length > 0" class="px-5 py-3 border border-outline-variant/60 rounded-xl bg-surface-container-low/50 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs text-on-surface-variant">
                        Total <strong class="text-on-surface">{{ filteredStudents.length }}</strong> siswa ditampilkan
                    </p>
                    <div class="flex items-center space-x-2">
                        <label class="flex items-center space-x-2 text-xs text-on-surface cursor-pointer select-none">
                            <input
                                type="checkbox"
                                :checked="isAllSelected"
                                @change="toggleSelectAll"
                                class="w-3.5 h-3.5 rounded accent-primary cursor-pointer"
                            />
                            <span>Pilih semua {{ filteredStudents.length }} siswa di halaman ini</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== PRINT CONTAINER (teleported to body so we can hide #app) ===== -->
        <Teleport to="body">
            <div id="print-container" class="font-serif">
                <template v-for="st in batchStudentsToPrint" :key="st.id">
                    <StudentDocumentSheet
                        :student="st"
                        :template="template"
                        :pages-to-print="batchPagesToPrint"
                    />
                </template>
            </div>
        </Teleport>

        <!-- Preview Modal -->
        <StudentDocumentPreviewModal
            :is-open="isPreviewModalOpen"
            :student="activePreviewStudent"
            :template="template"
            @close="isPreviewModalOpen = false"
            @print="handlePrintStudent"
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
