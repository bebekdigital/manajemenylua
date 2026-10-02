<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
});

import StudentTable from '@/Components/Students/StudentTable.vue';
import ClassroomAddStudentModal from './ClassroomAddStudentModal.vue';

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

// Filter classes based on selected Unit/Jenjang and Academic Year
const availableClasses = computed(() => {
    return props.classrooms.filter(c => 
        (c.jenjang === 'SD' && activeTab.value === 'SDIT' || c.jenjang === 'SMP' && activeTab.value === 'SMPIT' || c.unit === activeTab.value) 
        && c.academic_year_id === selectedAcademicYearId.value
    );
});

// Auto-select first class when available classes change
watch(availableClasses, (newClasses) => {
    if (newClasses.length > 0 && (!selectedClassId.value || !newClasses.find(c => c.id === selectedClassId.value))) {
        selectedClassId.value = newClasses[0].id;
    } else if (newClasses.length === 0) {
        selectedClassId.value = null;
    }
}, { immediate: true });

const showAddStudentModal = ref(false);

const classStudents = computed(() => {
    if (!selectedClassId.value) return [];
    return props.students.filter(s => s.classroom_id === selectedClassId.value);
});

// Pagination states for the StudentTable
const currentPage = ref(1);
const perPage = ref(10);

const totalEntries = computed(() => classStudents.value.length);
const paginatedStudents = computed(() => {
    if (perPage.value === 0) return classStudents.value;
    const start = (currentPage.value - 1) * perPage.value;
    return classStudents.value.slice(start, start + perPage.value);
});

function onPerPageChange(val) {
    perPage.value = Number(val);
    currentPage.value = 1;
}

function onPageChange(page) {
    currentPage.value = page;
}

watch(selectedClassId, () => {
    currentPage.value = 1;
});

function switchTab(tabId) {
    activeTab.value = tabId;
}
</script>

<template>
    <Head title="Daftar Kelas - Pembelajaran" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop font-sans">
        <div class="max-w-7xl mx-auto w-full">
            
            <!-- Flash Messages -->
            <div v-if="flashSuccess && !dismissedFlash" class="mb-6 p-4 bg-primary/10 border border-primary/20 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-primary shrink-0 mt-0.5">check_circle</span>
                <div class="flex-1 text-sm text-primary font-medium">
                    {{ flashSuccess }}
                </div>
                <button @click="dismissedFlash = true" class="text-primary/70 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>
            <div v-if="flashError && !dismissedFlash" class="mb-6 p-4 bg-error/10 border border-error/20 rounded-xl flex items-start gap-3">
                <span class="material-symbols-outlined text-error shrink-0 mt-0.5">error</span>
                <div class="flex-1 text-sm text-error font-medium">
                    {{ flashError }}
                </div>
                <button @click="dismissedFlash = true" class="text-error/70 hover:text-error transition-colors">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Page Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-outline-variant/30">
                <div class="flex items-end gap-6 pb-2">
                    <div class="shrink-0">
                        <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">
                            Daftar Kelas
                        </h2>
                        <p class="font-body-md text-[13px] sm:text-sm text-on-surface-variant mt-1">
                            Kelola siswa per kelas
                        </p>
                    </div>

                    <!-- Unit Tabs -->
                    <div class="flex space-x-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            @click="switchTab(tab.id)"
                            :class="[
                                'px-4 py-2 text-sm font-semibold rounded-t-xl transition-colors border-b-2',
                                activeTab === tab.id
                                    ? 'text-emerald-700 border-emerald-600 bg-emerald-50/50'
                                    : 'text-on-surface-variant border-transparent hover:bg-surface-container hover:text-on-surface'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Class Tabs and TA Filter -->
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div v-if="availableClasses.length > 0" class="flex gap-2 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="kelas in availableClasses"
                        :key="kelas.id"
                        @click="selectedClassId = kelas.id"
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-semibold transition-all whitespace-nowrap border',
                            selectedClassId === kelas.id
                                ? 'bg-primary text-on-primary border-primary shadow-sm'
                                : 'bg-white text-on-surface-variant border-outline-variant/60 hover:border-primary hover:text-primary'
                        ]"
                    >
                        {{ kelas.name }}
                    </button>
                </div>
                <div v-else class="text-sm text-on-surface-variant italic">Belum ada kelas</div>

                <!-- Filter Tahun Ajaran -->
                <div class="flex items-center space-x-2 shrink-0">
                    <span class="text-sm text-on-surface-variant font-medium">T.A:</span>
                    <select
                        v-model="selectedAcademicYearId"
                        class="text-sm font-medium bg-white border border-outline-variant rounded-lg px-3 py-1.5 focus:ring-primary focus:border-primary shadow-sm"
                    >
                        <option v-for="ta in academicYears" :key="ta.id" :value="ta.id">
                            {{ ta.name }} - {{ ta.semester }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Content Area (Table & Tambah Siswa) -->
            <div v-if="selectedClassId" class="bg-white rounded-2xl border border-outline-variant overflow-hidden">
                <!-- Toolbar -->
                <div class="p-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container/30">
                    <h3 class="font-bold text-on-surface text-lg">Daftar Siswa</h3>
                    <button 
                        @click="showAddStudentModal = true"
                        class="flex items-center gap-1.5 px-3 py-2 bg-primary text-on-primary rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Tambah Siswa</span>
                    </button>
                </div>
                
                <!-- Table (Empty State) -->
                <!-- Table -->
                <StudentTable
                    :students="paginatedStudents"
                    :current-page="currentPage"
                    :per-page="perPage"
                    :total-entries="totalEntries"
                    @update:per-page="onPerPageChange"
                    @page-change="onPageChange"
                />
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
        @close="showAddStudentModal = false"
    />
</template>
