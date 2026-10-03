<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClassroomAddStudentModal from './ClassroomAddStudentModal.vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    classrooms: {
        type: Array,
        default: () => [],
    },
    academicYears: {
        type: Array,
        default: () => [],
    },
    initialAcademicYearId: {
        type: Number,
        default: null,
    },
    students: {
        type: Array,
        default: () => [],
    },
    programs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const dismissedFlash = ref(false);

watch([flashSuccess, flashError], () => {
    dismissedFlash.value = false;
});

const activeTab = ref('SDIT');
const tabs = [
    { id: 'SDIT', label: 'SDIT', icon: 'school' },
    { id: 'SMPIT', label: 'SMPIT', icon: 'school' },
];

const selectedAcademicYearId = ref(props.initialAcademicYearId);
const selectedClassId = ref(null);

const availableClasses = computed(() => {
    return props.classrooms.filter(c =>
        (c.jenjang === 'SD' && activeTab.value === 'SDIT' || c.jenjang === 'SMP' && activeTab.value === 'SMPIT' || c.unit === activeTab.value)
        && c.academic_year_id === selectedAcademicYearId.value
    );
});

watch(availableClasses, (newClasses) => {
    // No longer auto-selecting class for student list
}, { immediate: true });

const showAddStudentModal = ref(false);
const viewMode = ref('classes'); // 'classes' or 'students'

const classStudents = computed(() => {
    if (!selectedClassId.value) return [];
    return props.students.filter(s => s.classroom_id == selectedClassId.value);
});

const selectedClassName = computed(() => {
    const kelas = availableClasses.value.find(c => c.id === selectedClassId.value);
    return kelas ? kelas.name : '';
});

function switchTab(tabId) {
    activeTab.value = tabId;
}

function statusLabel(status) {
    const map = {
        aktif: 'Aktif',
        mutasi_masuk: 'Mutasi Masuk',
        mutasi_keluar: 'Mutasi Keluar',
        lulus: 'Lulus',
        mengulang: 'Mengulang',
        dropout: 'Dropout',
    };
    return map[status] || status || '-';
}

function statusColor(status) {
    const map = {
        aktif: 'bg-emerald-100 text-emerald-800',
        mutasi_masuk: 'bg-blue-100 text-blue-800',
        mutasi_keluar: 'bg-amber-100 text-amber-800',
        lulus: 'bg-indigo-100 text-indigo-800',
        mengulang: 'bg-orange-100 text-orange-800',
        dropout: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

const showTADropdown = ref(false);
const showClassDropdown = ref(false);

const selectedTaName = computed(() => {
    const ta = props.academicYears.find(t => t.id === selectedAcademicYearId.value);
    return ta ? `${ta.name} - ${ta.semester}` : 'Pilih T.A';
});

const selectedClassLabel = computed(() => {
    const cls = availableClasses.value.find(c => c.id === selectedClassId.value);
    return cls ? `Kelas ${cls.name}` : 'Pilih Kelas';
});

function getClassStudentCount(classId) {
    return props.students.filter(s => s.classroom_id === classId).length;
}

function openAddStudent(kelas) {
    selectedClassId.value = kelas.id;
    showAddStudentModal.value = true;
}

function viewStudents(kelas) {
    selectedClassId.value = kelas.id;
    viewMode.value = 'students';
}

function deleteClass(kelas) {
    const count = getClassStudentCount(kelas.id);
    if (count > 0) {
        alert('Gagal! Tidak dapat menghapus kelas ini karena masih ada ' + count + ' siswa yang terdata di dalamnya. Silakan kosongkan kelas terlebih dahulu.');
        return;
    }
    // Wali kelas validasi (dummy)
    if (true) { // assuming wali kelas is always assigned for dummy
        // alert('Gagal! Masih ada Wali Kelas yang ditugaskan di kelas ini.');
        // return;
    }

    if (confirm(`Yakin ingin menghapus kelas ${kelas.name}?`)) {
        router.delete(`/pembelajaran/kelas/${kelas.id}`, { preserveScroll: true });
    }
}

function changeTA(id) {
    selectedAcademicYearId.value = id;
    showTADropdown.value = false;
    router.get('/pembelajaran/kelas', { ta: id }, { preserveState: true, preserveScroll: true });
}

function unitStudentCount(unitId) {
    return props.students.filter(s => {
        if (!s.unit) return false;
        const u = s.unit.toUpperCase();
        if (unitId === 'SDIT') return u.includes('SDIT') || u.includes('SD IT');
        if (unitId === 'SMPIT') return u.includes('SMPIT') || u.includes('SMP IT');
        return false;
    }).length;
}

// Bulk selection for delete
const selectedIds = ref([]);
const isDeleting = ref(false);

const isAllClassSelected = computed(() => {
    return classStudents.value.length > 0 && classStudents.value.every(s => selectedIds.value.includes(s.nisn));
});

function toggleSelectAll() {
    if (isAllClassSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = classStudents.value.map(s => s.nisn);
    }
}

function toggleSelectStudent(id) {
    const idx = selectedIds.value.indexOf(id);
    if (idx === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(idx, 1);
    }
}

watch(selectedClassId, () => {
    selectedIds.value = [];
});

function removeSelected() {
    if (selectedIds.value.length === 0) return;
    if (!confirm(`Hapus ${selectedIds.value.length} siswa dari kelas ini?`)) return;

    isDeleting.value = true;
    router.post('/pembelajaran/kelas/remove-students', {
        student_ids: selectedIds.value,
        academic_year_id: selectedAcademicYearId.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            isDeleting.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Daftar Kelas - Pembelajaran" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop font-sans">
        <div class="max-w-7xl mx-auto w-full">

            <!-- Flash Messages -->
            <div v-if="flashSuccess && !dismissedFlash" class="mb-6 p-4 bg-primary/10 border border-primary/20 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-primary shrink-0 mt-0.5">check_circle</span>
                <div class="flex-1 text-sm text-primary font-medium">{{ flashSuccess }}</div>
                <button @click="dismissedFlash = true" class="text-primary/70 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>
            <div v-if="flashError && !dismissedFlash" class="mb-6 p-4 bg-error/10 border border-error/20 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-error shrink-0 mt-0.5">error</span>
                <div class="flex-1 text-sm text-error font-medium">{{ flashError }}</div>
                <button @click="dismissedFlash = true" class="text-error/70 hover:text-error transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

        <!-- Page Header -->
        <div class="mb-6 border-b border-outline-variant/30 pb-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-start gap-4 sm:gap-12">
                <div class="shrink-0">
                    <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">Daftar Kelas</h2>
                    <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">Kelola siswa per kelas</p>
                </div>
                <!-- Unit Tabs (Flat Style) -->
                <div class="flex overflow-x-auto sm:ml-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        @click="switchTab(tab.id)"
                        :class="[
                            'flex items-center gap-2 px-5 sm:px-8 py-2.5 sm:py-3 text-xs sm:text-sm font-bold transition-all duration-300 border-b-2 rounded-t-lg',
                            activeTab === tab.id
                                ? 'text-emerald-600 border-emerald-600 bg-gradient-to-t from-emerald-600/20 to-transparent'
                                : 'text-on-surface-variant border-transparent hover:text-emerald-600 hover:border-emerald-600/50 hover:bg-emerald-50/50'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px] transition-all" :class="{ 'filled-icon': activeTab === tab.id }">{{ tab.icon }}</span>
                        <span>{{ tab.label }}</span>
                    </button>
                </div>
            </div>
        </div>

            <!-- Action Buttons & Filters Row -->
            <div class="mb-5 flex flex-col lg:flex-row gap-4 lg:items-center">
                <div class="flex items-center gap-3 shrink-0 relative z-30 w-full">
                    <!-- Filter Label -->
                    <div class="hidden sm:flex items-center gap-1.5 text-on-surface-variant mr-1 shrink-0">
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-primary">tune</span>
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider">Filter:</span>
                    </div>

                    <!-- Tahun Ajaran Dropdown -->
                    <div class="relative w-[calc(50%-4px)] sm:w-auto sm:min-w-[150px] shrink-0">
                        <button
                            type="button"
                            @click="showTADropdown = !showTADropdown"
                            class="flex items-center justify-between w-full px-3 py-2 rounded-lg sm:rounded-xl text-[12px] sm:text-sm font-semibold border transition-all cursor-pointer bg-emerald-600 border-emerald-700 text-white hover:bg-emerald-700 shadow-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full bg-white/90"></span>
                                {{ selectedTaName }}
                            </div>
                            <span class="material-symbols-outlined text-[16px] sm:text-[18px] transition-transform duration-200" :class="{ 'rotate-180': showTADropdown }">expand_more</span>
                        </button>
                        
                        <div v-if="showTADropdown" @click="showTADropdown = false" class="fixed inset-0 z-40"></div>
                        
                        <div v-show="showTADropdown" class="absolute z-50 top-full left-0 mt-1 w-full min-w-[200px] bg-white border border-slate-200 rounded-xl shadow-lg py-1 overflow-hidden">
                            <button
                                v-for="ta in academicYears" :key="ta.id"
                                type="button"
                                @click="changeTA(ta.id)"
                                class="flex items-center gap-2 w-full px-3 py-2 text-[12px] sm:text-sm text-left hover:bg-slate-50 transition-colors font-medium text-slate-700"
                            >
                                <span class="w-2 h-2 sm:w-2.5 sm:h-2.5 rounded-full" :class="ta.id === selectedAcademicYearId ? 'bg-emerald-500' : 'bg-transparent'"></span>
                                {{ ta.name }} - {{ ta.semester }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Content Area - Classes Table -->
            <div v-if="viewMode === 'classes'" class="bg-white rounded-2xl border border-outline-variant overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-emerald-700 bg-emerald-600">
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider w-12 text-center">No</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Tingkat</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Kelas</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Wali Kelas</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Ruangan</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider text-center">Siswa</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider text-center w-48">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr
                                v-for="(kelas, index) in availableClasses"
                                :key="kelas.id"
                                class="hover:bg-surface-container-high/50 transition-colors"
                            >
                                <td class="p-3 text-center text-on-surface-variant">{{ index + 1 }}</td>
                                <td class="p-3 text-on-surface-variant">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 bg-primary/10 text-primary rounded-md text-xs font-semibold">
                                        {{ kelas.grade || '-' }}
                                    </span>
                                </td>
                                <td class="p-3 font-semibold text-on-surface">{{ kelas.name }}</td>
                                <td class="p-3 text-on-surface-variant">Ustadz Fulan, S.Pd</td>
                                <td class="p-3 text-on-surface-variant">Gedung A - R.101</td>
                                <td class="p-3 text-center">
                                    <span class="font-semibold text-on-surface">{{ getClassStudentCount(kelas.id) }}</span>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="viewStudents(kelas)"
                                            class="p-2 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors cursor-pointer"
                                            title="Lihat Siswa"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">group</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openAddStudent(kelas)"
                                            class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors cursor-pointer"
                                            title="Tambah Siswa"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">person_add</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="p-2 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer"
                                            title="Edit Kelas"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteClass(kelas)"
                                            class="p-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                                            title="Hapus Kelas"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="availableClasses.length === 0">
                                <td colspan="7" class="p-12 text-center">
                                    <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">meeting_room</span>
                                    <p class="font-medium text-on-surface-variant">Belum ada kelas untuk tingkat/jenjang ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Content Area - Students Table -->
            <div v-else-if="viewMode === 'students'" class="bg-white rounded-2xl border border-outline-variant overflow-hidden">
                <!-- Toolbar -->
                <div class="p-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container/30">
                    <div>
                        <h3 class="font-bold text-on-surface text-base">Daftar Siswa — {{ selectedClassName }}</h3>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-on-surface-variant">
                            <span><strong class="font-medium text-on-surface">Jumlah Siswa:</strong> {{ classStudents.length }}</span>
                            <span><strong class="font-medium text-on-surface">Wali Kelas:</strong> Ustadz Fulan, S.Pd</span>
                            <span><strong class="font-medium text-on-surface">Ruangan:</strong> Gedung B - Lt. 2</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="viewMode = 'classes'"
                            class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-high text-on-surface rounded-lg text-sm font-semibold hover:bg-surface-container-highest transition-colors shadow-sm"
                        >
                            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                            <span>Kembali</span>
                        </button>
                        <button
                            @click="removeSelected"
                            :disabled="isDeleting || selectedIds.length === 0"
                            class="flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isDeleting" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                            <span v-else class="material-symbols-outlined text-[18px]">delete</span>
                            <span>Hapus{{ selectedIds.length > 0 ? ` (${selectedIds.length})` : '' }}</span>
                        </button>
                        <button
                            @click="showAddStudentModal = true"
                            class="flex items-center gap-1.5 px-3 py-2 bg-primary text-on-primary rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors shadow-sm"
                        >
                            <span class="material-symbols-outlined text-[18px]">add</span>
                            <span>Tambah Siswa</span>
                        </button>
                    </div>
                </div>

                <!-- Custom Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-emerald-700 bg-emerald-600">
                                <th class="p-3 w-12 text-center">
                                    <input
                                        type="checkbox"
                                        class="rounded border-white/60 text-emerald-600 focus:ring-emerald-400 w-4 h-4 cursor-pointer bg-white/90"
                                        :checked="isAllClassSelected"
                                        @change="toggleSelectAll"
                                        :disabled="classStudents.length === 0"
                                    >
                                </th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider w-12">No</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">NISN</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Nama Lengkap</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">NIPD</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider w-20">Tingkat</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Kelas</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Program</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Keterangan</th>
                                <th class="p-3 text-xs font-semibold text-white uppercase tracking-wider">Tgl Tidak Aktif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr
                                v-for="(student, index) in classStudents"
                                :key="student.nisn"
                                :class="[
                                    'hover:bg-surface-container-high/50 transition-colors cursor-pointer',
                                    selectedIds.includes(student.nisn) ? 'bg-primary/5' : ''
                                ]"
                                @click="toggleSelectStudent(student.nisn)"
                            >
                                <td class="p-3 text-center" @click.stop>
                                    <input
                                        type="checkbox"
                                        class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                                        :checked="selectedIds.includes(student.nisn)"
                                        @change="toggleSelectStudent(student.nisn)"
                                    >
                                </td>
                                <td class="p-3 text-on-surface-variant">{{ index + 1 }}</td>
                                <td class="p-3 text-on-surface-variant font-mono">{{ student.nisn }}</td>
                                <td class="p-3 font-semibold text-on-surface whitespace-nowrap">{{ student.nama }}</td>
                                <td class="p-3 text-on-surface-variant font-mono">{{ student.nipd || '-' }}</td>
                                <td class="p-3 text-center">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 bg-primary/10 text-primary rounded-md text-xs font-semibold">
                                        {{ student.tingkat || '-' }}
                                    </span>
                                </td>
                                <td class="p-3 text-on-surface-variant whitespace-nowrap">{{ student.kelas || '-' }}</td>
                                <td class="p-3 text-on-surface-variant">{{ student.program || '-' }}</td>
                                <td class="p-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold', statusColor(student.student_status)]">
                                        {{ statusLabel(student.student_status) }}
                                    </span>
                                </td>
                                <td class="p-3 text-on-surface-variant text-xs">{{ student.ket_tidak_aktif || '-' }}</td>
                                <td class="p-3 text-on-surface-variant text-xs whitespace-nowrap">{{ student.tanggal_tidak_aktif || '-' }}</td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="classStudents.length === 0">
                                <td colspan="11" class="p-12 text-center">
                                    <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">group_off</span>
                                    <p class="font-medium text-on-surface-variant">Belum ada siswa di kelas ini.</p>
                                    <p class="text-xs text-outline mt-1">Klik tombol "Tambah Siswa" untuk menambahkan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="academicYears.length > 0" class="bg-white rounded-2xl border border-outline-variant p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-outline-variant mb-4 block">meeting_room</span>
                <p class="font-body-lg text-body-lg text-on-surface-variant">Belum ada data kelas untuk Tahun Ajaran ini.</p>
                <p class="font-body-sm text-body-sm text-outline mt-1">Sinkronkan kelas dari pengaturan tahun ajaran.</p>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Siswa -->
    <ClassroomAddStudentModal
        v-if="showAddStudentModal"
        :show="showAddStudentModal"
        :class-id="selectedClassId"
        :academic-year-id="selectedAcademicYearId"
        :students="students"
        :programs="programs"
        @close="showAddStudentModal = false"
    />
</template>
