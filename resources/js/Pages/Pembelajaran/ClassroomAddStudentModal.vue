<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    classId: Number,
    academicYearId: Number,
    students: {
        type: Array,
        default: () => [],
    },
    programs: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'success']);

const filterAngkatan = ref('');
const filterJk = ref('');
const searchQuery = ref('');

// Bulk edit fields
const bulkProgram = ref('Umum');
const bulkStatus = ref('aktif');

const statusOptions = [
    { value: 'aktif', label: 'Aktif' },
    { value: 'mutasi_masuk', label: 'Mutasi Masuk' },
    { value: 'mutasi_keluar', label: 'Mutasi Keluar' },
    { value: 'lulus', label: 'Lulus' },
    { value: 'mengulang', label: 'Mengulang' },
    { value: 'dropout', label: 'Dropout' },
];

const selectedStudentIds = ref([]);

const availableAngkatan = computed(() => {
    const angkatans = new Set(props.students.map(s => s.angkatan).filter(a => a));
    return Array.from(angkatans).sort((a, b) => b - a);
});

const availableStudentsToSelect = computed(() => {
    return props.students.filter(student => {
        if (student.classroom_id == props.classId) return false;

        if (filterAngkatan.value && student.angkatan != filterAngkatan.value) return false;
        if (filterJk.value && student.jk !== filterJk.value) return false;
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            const matchName = student.nama && student.nama.toLowerCase().includes(query);
            const matchNisn = student.nisn && student.nisn.toLowerCase().includes(query);
            if (!matchName && !matchNisn) return false;
        }

        return true;
    });
});

const isAllSelected = computed(() => {
    return availableStudentsToSelect.value.length > 0 &&
           availableStudentsToSelect.value.every(s => selectedStudentIds.value.includes(s.id));
});

function toggleAll() {
    if (isAllSelected.value) {
        selectedStudentIds.value = [];
    } else {
        selectedStudentIds.value = availableStudentsToSelect.value.map(s => s.id);
    }
}

function toggleStudent(id) {
    const index = selectedStudentIds.value.indexOf(id);
    if (index === -1) {
        selectedStudentIds.value.push(id);
    } else {
        selectedStudentIds.value.splice(index, 1);
    }
}

const isSubmitting = ref(false);
const submitError = ref('');

function save() {
    if (selectedStudentIds.value.length === 0) return;
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    submitError.value = '';

    router.post('/pembelajaran/kelas/add-students', {
        student_ids: selectedStudentIds.value,
        target_academic_year_id: props.academicYearId,
        target_classroom_id: props.classId,
        program: bulkProgram.value,
        student_status: bulkStatus.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            selectedStudentIds.value = [];
            closeModal();
            emit('success');
        },
        onError: (errors) => {
            isSubmitting.value = false;
            submitError.value = Object.values(errors).flat().join(', ');
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

function closeModal() {
    emit('close');
}

watch([filterAngkatan, filterJk, searchQuery], () => {
    selectedStudentIds.value = [];
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-surface-variant/80 backdrop-blur-sm">
        <div class="relative bg-surface-container-lowest rounded-2xl shadow-[0px_20px_50px_rgba(0,0,0,0.15)] border border-outline-variant/40 w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="px-6 pt-6 pb-4 border-b border-outline-variant/30 flex items-start justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">group_add</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-on-surface">Tambah Siswa ke Kelas</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">
                            Pilih siswa dari database untuk dimasukkan ke kelas ini
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="closeModal"
                    :disabled="isSubmitting"
                    class="text-outline hover:text-on-surface p-1 rounded-full hover:bg-surface-variant transition-colors disabled:opacity-50"
                >
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Filters -->
            <div class="px-6 py-4 border-b border-outline-variant/30 bg-surface/50 shrink-0">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[18px]">search</span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama atau NISN..."
                            class="w-full pl-9 pr-4 py-2 bg-white border border-outline-variant rounded-lg text-sm focus:ring-primary focus:border-primary shadow-sm"
                        >
                    </div>
                    <div class="flex gap-2 shrink-0 flex-wrap">
                        <select v-model="filterAngkatan" class="py-2 pl-3 pr-8 bg-white border border-outline-variant rounded-lg text-sm focus:ring-primary focus:border-primary shadow-sm">
                            <option value="">Semua Angkatan</option>
                            <option v-for="a in availableAngkatan" :key="a" :value="a">Angkatan {{ a }}</option>
                        </select>
                        <select v-model="filterJk" class="py-2 pl-3 pr-8 bg-white border border-outline-variant rounded-lg text-sm focus:ring-primary focus:border-primary shadow-sm">
                            <option value="">Semua L/P</option>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Bulk Edit Options (Program & Status) -->
            <div class="px-6 py-3 border-b border-outline-variant/30 bg-primary/5 shrink-0">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Setting Bulk:</span>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-on-surface-variant">Program:</label>
                        <select v-model="bulkProgram" class="py-1.5 pl-3 pr-8 bg-white border border-outline-variant rounded-lg text-sm focus:ring-primary focus:border-primary shadow-sm">
                            <option value="Umum">Umum</option>
                            <option v-for="p in programs" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-on-surface-variant">Status:</label>
                        <select v-model="bulkStatus" class="py-1.5 pl-3 pr-8 bg-white border border-outline-variant rounded-lg text-sm focus:ring-primary focus:border-primary shadow-sm">
                            <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table List -->
            <div class="flex-1 overflow-y-auto bg-white relative">
                <table class="w-full text-left text-sm text-on-surface">
                    <thead class="bg-surface-container sticky top-0 z-10 shadow-sm text-on-surface-variant text-xs uppercase font-semibold">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-12 text-center border-b border-outline-variant/50">
                                <input
                                    type="checkbox"
                                    class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                                    :checked="isAllSelected"
                                    @change="toggleAll"
                                    :disabled="availableStudentsToSelect.length === 0"
                                >
                            </th>
                            <th scope="col" class="px-4 py-3 border-b border-outline-variant/50 w-32">NISN</th>
                            <th scope="col" class="px-4 py-3 border-b border-outline-variant/50">Nama Lengkap</th>
                            <th scope="col" class="px-4 py-3 border-b border-outline-variant/50 w-24">J.K</th>
                            <th scope="col" class="px-4 py-3 border-b border-outline-variant/50 w-32">Angkatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="student in availableStudentsToSelect"
                            :key="student.id"
                            class="border-b border-outline-variant/30 hover:bg-surface-container/50 transition-colors cursor-pointer"
                            @click="toggleStudent(student.id)"
                        >
                            <td class="px-4 py-3 text-center">
                                <input
                                    type="checkbox"
                                    class="rounded border-outline-variant text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                                    :checked="selectedStudentIds.includes(student.id)"
                                    @click.stop
                                    @change="toggleStudent(student.id)"
                                >
                            </td>
                            <td class="px-4 py-3 font-medium font-mono">{{ student.nisn || '-' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ student.nama }}</td>
                            <td class="px-4 py-3">
                                <span :class="[
                                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold',
                                    student.jk === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'
                                ]">
                                    {{ student.jk === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ student.angkatan || '-' }}</td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="availableStudentsToSelect.length === 0">
                            <td colspan="5" class="px-4 py-16 text-center text-on-surface-variant bg-surface/30">
                                <span class="material-symbols-outlined text-4xl mb-2 text-outline-variant block">search_off</span>
                                <p class="font-medium">Tidak ada siswa yang sesuai filter</p>
                                <p class="text-xs mt-1">Coba sesuaikan filter pencarian, angkatan, atau jenis kelamin.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Error Banner -->
            <div v-if="submitError" class="px-6 py-3 bg-red-50 border-t border-red-200 text-red-700 text-sm font-medium flex items-center gap-2 shrink-0">
                <span class="material-symbols-outlined text-[18px]">error</span>
                {{ submitError }}
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 border-t border-outline-variant/30 bg-surface-container-lowest shrink-0 flex items-center justify-between">
                <div class="text-sm font-semibold text-primary">
                    {{ selectedStudentIds.length }} Siswa Terpilih
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="closeModal"
                        :disabled="isSubmitting"
                        class="px-5 py-2.5 text-sm font-semibold text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded-xl transition-all"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="save"
                        :disabled="isSubmitting || selectedStudentIds.length === 0"
                        class="px-5 py-2.5 text-sm font-semibold text-on-primary bg-primary rounded-xl hover:bg-primary/90 transition-all shadow-sm active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <span v-if="isSubmitting" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span v-else class="material-symbols-outlined text-[18px]">check_circle</span>
                        <span>Tambahkan ke Kelas</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
