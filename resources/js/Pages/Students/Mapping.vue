<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    academicYears: {
        type: Array,
        required: true,
    },
    units: {
        type: Array,
        required: true,
    }
});

// Filters for Source
const sourceAcademicYearId = ref('');
const sourceUnit = ref('');
const sourceClassId = ref('');
const sourceClasses = ref([]);

// Filters for Target
const targetAcademicYearId = ref('');
const targetUnit = ref('');
const targetClassId = ref('');
const targetClasses = ref([]);

const students = ref([]);
const selectedStudents = ref([]);
const isLoadingStudents = ref(false);

const form = useForm({
    student_ids: [],
    target_academic_year_id: '',
    target_classroom_id: '',
    program: '',
    status: 'aktif',
    keterangan: ''
});

// Watch source filters to load classes
watch([sourceAcademicYearId, sourceUnit], async ([taId, unit]) => {
    if (taId && unit) {
        const res = await axios.get('/api/students/mapping/classes', { params: { academic_year_id: taId, unit } });
        sourceClasses.value = res.data.classes;
    } else {
        sourceClasses.value = [];
    }
    sourceClassId.value = '';
    students.value = [];
});

// Watch target filters to load classes
watch([targetAcademicYearId, targetUnit], async ([taId, unit]) => {
    if (taId && unit) {
        const res = await axios.get('/api/students/mapping/classes', { params: { academic_year_id: taId, unit } });
        targetClasses.value = res.data.classes;
    } else {
        targetClasses.value = [];
    }
    targetClassId.value = '';
});

// Watch source class to load students
watch(sourceClassId, async (classId) => {
    if (classId) {
        isLoadingStudents.value = true;
        const res = await axios.get('/api/students/mapping/students', { 
            params: { 
                academic_year_id: sourceAcademicYearId.value, 
                classroom_id: classId 
            } 
        });
        students.value = res.data.students;
        isLoadingStudents.value = false;
        
        // Auto select all active by default
        selectedStudents.value = students.value.filter(s => s.status === 'aktif' || s.status === 'mutasi_masuk').map(s => s.id);
    } else {
        students.value = [];
        selectedStudents.value = [];
    }
});

const isAllSelected = computed(() => {
    return students.value.length > 0 && selectedStudents.value.length === students.value.length;
});

const toggleAll = () => {
    if (isAllSelected.value) {
        selectedStudents.value = [];
    } else {
        selectedStudents.value = students.value.map(s => s.id);
    }
};

const submitMapping = () => {
    if (selectedStudents.value.length === 0) {
        alert('Pilih minimal 1 siswa');
        return;
    }
    if (!targetAcademicYearId.value || !targetClassId.value) {
        alert('Lengkapi Tahun Ajaran dan Kelas tujuan!');
        return;
    }
    
    if (!confirm(`Yakin memetakan ${selectedStudents.value.length} siswa ke kelas baru?`)) return;

    form.student_ids = selectedStudents.value;
    form.target_academic_year_id = targetAcademicYearId.value;
    form.target_classroom_id = targetClassId.value;
    
    form.post('/students/mapping', {
        onSuccess: () => {
            // Success handled by flash message and redirect
        }
    });
};
</script>

<template>
    <Head title="Pemetaan Riwayat Siswa" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop bg-surface-container-lowest">
        <!-- Header -->
        <div class="mb-6 border-b border-outline-variant/30 pb-4">
            <div class="flex items-center gap-3 mb-2">
                <Link href="/students" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-surface-container-low transition-colors text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </Link>
                <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">Pemetaan Riwayat Siswa</h2>
            </div>
            <p class="font-body-md text-sm text-on-surface-variant ml-11">
                Petakan (naik/tinggal kelas) siswa secara masal ke tahun ajaran lain
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <!-- Left Side: Source -->
            <div class="bg-surface-container-low rounded-xl p-5 border border-outline-variant/30">
                <h3 class="text-sm font-bold text-on-surface mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">outbox</span>
                    Dari: Kelas Asal
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Tahun Ajaran</label>
                        <select v-model="sourceAcademicYearId" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm">
                            <option value="">Pilih TA</option>
                            <option v-for="ta in academicYears" :key="ta.id" :value="ta.id">{{ ta.name }} ({{ ta.semester }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Unit</label>
                        <select v-model="sourceUnit" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm">
                            <option value="">Pilih Unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kelas</label>
                        <select v-model="sourceClassId" :disabled="!sourceClasses.length" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm disabled:bg-surface-variant disabled:opacity-50">
                            <option value="">Pilih Kelas</option>
                            <option v-for="c in sourceClasses" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                        </select>
                    </div>
                </div>

                <div v-if="isLoadingStudents" class="py-12 flex justify-center text-primary">
                    <span class="material-symbols-outlined animate-spin text-[32px]">progress_activity</span>
                </div>
                <div v-else-if="students.length > 0" class="border border-outline-variant/30 rounded-lg bg-white overflow-hidden">
                    <div class="bg-surface py-2 px-3 flex justify-between items-center border-b border-outline-variant/30 text-xs font-semibold text-on-surface-variant">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" :checked="isAllSelected" @change="toggleAll" class="rounded text-primary focus:ring-primary/30 border-outline-variant">
                            <span>Pilih Semua ({{ students.length }})</span>
                        </label>
                        <span>{{ selectedStudents.length }} terpilih</span>
                    </div>
                    <div class="max-h-[300px] overflow-y-auto p-2">
                        <label v-for="s in students" :key="s.id" class="flex items-center gap-3 p-2 hover:bg-surface-container-low rounded-lg cursor-pointer transition-colors">
                            <input type="checkbox" :value="s.id" v-model="selectedStudents" class="rounded text-primary focus:ring-primary/30 border-outline-variant">
                            <div>
                                <div class="text-sm font-semibold text-on-surface">{{ s.nama }}</div>
                                <div class="text-[11px] text-on-surface-variant">{{ s.nisn }} • {{ s.status.replace('_', ' ') }}</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Side: Target -->
            <div class="bg-primary/5 rounded-xl p-5 border border-primary/20">
                <h3 class="text-sm font-bold text-primary mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">move_to_inbox</span>
                    Ke: Kelas Tujuan
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Tahun Ajaran</label>
                        <select v-model="targetAcademicYearId" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm">
                            <option value="">Pilih TA</option>
                            <option v-for="ta in academicYears" :key="ta.id" :value="ta.id">{{ ta.name }} ({{ ta.semester }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Unit</label>
                        <select v-model="targetUnit" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm">
                            <option value="">Pilih Unit</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.name">{{ unit.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Kelas</label>
                        <select v-model="targetClassId" :disabled="!targetClasses.length" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm disabled:bg-surface-variant disabled:opacity-50">
                            <option value="">Pilih Kelas</option>
                            <option v-for="c in targetClasses" :key="c.id" :value="c.id">Kelas {{ c.name }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 border-t border-primary/10 pt-4 mt-2">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Pilih Program</label>
                        <select v-model="form.program" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm mb-3">
                            <option value="">-- Tetap (Sesuai Data Sebelumnya) --</option>
                            <option value="Umum">Umum</option>
                            <option value="Tahfidz">Tahfidz</option>
                            <option value="Boarding">Boarding</option>
                            <option value="Fullday">Fullday</option>
                        </select>
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Set Status Baru</label>
                        <select v-model="form.status" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm">
                            <option value="aktif">Aktif</option>
                            <option value="lulus">Lulus</option>
                            <option value="mengulang">Mengulang</option>
                            <option value="mutasi_keluar">Mutasi Keluar</option>
                            <option value="dropout">Drop Out</option>
                        </select>
                    </div>
                    <div v-if="form.status !== 'aktif' && form.status !== 'lulus'" class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface-variant mb-1">Keterangan (Opsional)</label>
                        <input type="text" v-model="form.keterangan" class="w-full text-sm rounded-lg border-outline-variant/50 focus:border-primary focus:ring-primary/20 bg-white shadow-sm" placeholder="Contoh: Pindah ke luar kota">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="submitMapping"
                        :disabled="form.processing || selectedStudents.length === 0 || !targetClassId || !targetAcademicYearId"
                        class="flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-primary-container disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span class="material-symbols-outlined text-[18px]">done_all</span>
                        Proses Pemetaan ({{ selectedStudents.length }} Siswa)
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
