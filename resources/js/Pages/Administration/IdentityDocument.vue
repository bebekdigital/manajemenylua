<script setup>
import { ref, computed, nextTick } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import StudentDocumentPreviewModal from '@/Components/Administration/StudentDocumentPreviewModal.vue';
import StudentDocumentSheet from '@/Components/Administration/StudentDocumentSheet.vue';

const props = defineProps({
    students: { type: Array, default: () => [] },
    academicYears: { type: Array, default: () => [] },
    classrooms: { type: Array, default: () => [] },
    selectedAcademicYear: { type: Object, default: null },
    template: { type: Object, required: true },
});

// --- Filter & Search ---
const searchQuery = ref('');
const selectedClassroom = ref('all');
const selectedGender = ref('all');
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
        if (selectedClassroom.value !== 'all') {
            if (student.classroom_id !== Number(selectedClassroom.value)) return false;
        }
        if (selectedGender.value !== 'all') {
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
                <div class="bg-surface rounded-2xl border border-outline-variant shadow-xs p-4">
                    <div class="flex flex-wrap items-end gap-3">
                        <!-- Search -->
                        <div class="flex-1 min-w-48">
                            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Cari Siswa</label>
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari nama, NISN, atau NIS..."
                                    class="w-full bg-surface-container-low border border-outline-variant rounded-xl px-3 pr-8 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                                />
                                <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface cursor-pointer">
                                    <span class="material-symbols-outlined" style="font-size:16px;">close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Classroom Filter -->
                        <div class="min-w-36">
                            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Kelas / Rombel</label>
                            <div class="relative">
                                <select v-model="selectedClassroom" class="w-full appearance-none bg-surface-container-low border border-outline-variant rounded-xl pl-3 pr-8 py-2 text-sm text-on-surface focus:border-primary outline-none cursor-pointer">
                                    <option value="all">Semua Kelas</option>
                                    <option v-for="c in classrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none leading-none" style="font-size:18px; line-height:1;">expand_more</span>
                            </div>
                        </div>

                        <!-- Gender Filter -->
                        <div class="min-w-28">
                            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">Jenis Kelamin</label>
                            <div class="relative">
                                <select v-model="selectedGender" class="w-full appearance-none bg-surface-container-low border border-outline-variant rounded-xl pl-3 pr-8 py-2 text-sm text-on-surface focus:border-primary outline-none cursor-pointer">
                                    <option value="all">Semua</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none leading-none" style="font-size:18px; line-height:1;">expand_more</span>
                            </div>
                        </div>

                    </div>

                    <!-- Stats row -->
                    <div class="mt-3 pt-3 border-t border-outline-variant/60 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center space-x-4 text-sm">
                            <span class="text-on-surface-variant">
                                Menampilkan <strong class="text-on-surface">{{ filteredStudents.length }}</strong> dari {{ students.length }} siswa
                            </span>
                        </div>
                        <div v-if="selectedCount > 0" class="flex items-center space-x-2">
                            <span class="text-xs text-on-surface-variant">
                                <strong class="text-primary">{{ selectedCount }}</strong> siswa dipilih
                            </span>
                            <button @click="clearSelection" class="text-xs text-on-surface-variant hover:text-on-surface underline cursor-pointer">Batal Pilih</button>
                        </div>
                    </div>
                </div>

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
                <div class="bg-surface rounded-2xl border border-outline-variant overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-surface-container-low border-b border-outline-variant">
                                <tr class="text-xs font-bold text-on-surface-variant uppercase tracking-wide">
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
                                    <th class="py-3 px-3">Nama Siswa</th>
                                    <th class="py-3 px-3 w-28">Kelas</th>
                                    <th class="py-3 px-3 w-32">Unit</th>
                                    <th class="py-3 px-4 text-right w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60">
                                <tr
                                    v-for="(student, idx) in filteredStudents"
                                    :key="student.id"
                                    :class="[
                                        'transition-colors group divide-x divide-outline-variant/30',
                                        isSelected(student.id) ? 'bg-primary/5' : 'hover:bg-surface-container/50'
                                    ]"
                                >
                                    <td class="p-3 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isSelected(student.id)"
                                            @change="toggleStudent(student.id)"
                                            class="w-4 h-4 rounded cursor-pointer accent-primary"
                                        />
                                    </td>
                                    <td class="py-2.5 px-3 text-xs text-on-surface-variant font-mono">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3">
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
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filteredStudents.length === 0">
                                    <td colspan="8" class="text-center py-16">
                                        <div class="w-14 h-14 bg-surface-container rounded-full flex items-center justify-center mx-auto mb-3">
                                            <span class="material-symbols-outlined text-3xl text-on-surface-variant/50">person_search</span>
                                        </div>
                                        <p class="font-semibold text-on-surface">Tidak ada siswa yang cocok</p>
                                        <p class="text-sm text-on-surface-variant mt-1">Coba ubah filter pencarian di atas</p>
                                        <button v-if="searchQuery || selectedClassroom !== 'all' || selectedGender !== 'all'" @click="searchQuery = ''; selectedClassroom = 'all'; selectedGender = 'all'" class="mt-3 text-sm text-primary hover:underline cursor-pointer">
                                            Reset semua filter
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer -->
                    <div v-if="filteredStudents.length > 0" class="px-5 py-3 border-t border-outline-variant/60 bg-surface-container-low/50 flex flex-wrap items-center justify-between gap-2">
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
