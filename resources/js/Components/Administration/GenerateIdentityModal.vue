<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    selectedStudents: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'preview', 'print-bulk', 'download-pdf-bulk', 'print-single', 'download-pdf-single']);

// 1. Checklist halaman
const selectedPages = ref(['cover', 'school_profile', 'identity']);
// 2. Slider tampilkan foto / tidak
const showPhoto = ref(true);
// 3. Pilihan gabung file ('merged') atau per siswa ('per_student')
const fileMode = ref('merged');

// Per-student queue state when fileMode === 'per_student' & > 1 student
const showPerStudentQueue = ref(false);
const queueActionType = ref('print'); // 'print' | 'pdf'
const processedIds = ref([]);

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            showPerStudentQueue.value = false;
            processedIds.value = [];
        }
    }
);

const totalSheets = computed(() => props.selectedStudents.length * selectedPages.value.length);
const isValid = computed(() => props.selectedStudents.length > 0 && selectedPages.value.length > 0);

function setPagesPreset(preset) {
    if (preset === 'identity_only') {
        selectedPages.value = ['identity'];
    } else {
        selectedPages.value = ['cover', 'school_profile', 'identity'];
    }
}

function handleDirectPrint() {
    if (!isValid.value) return;

    if (fileMode.value === 'merged') {
        emit('print-bulk', {
            students: props.selectedStudents,
            pages: [...selectedPages.value],
            showPhoto: showPhoto.value,
        });
    } else if (props.selectedStudents.length === 1) {
        emit('print-single', {
            student: props.selectedStudents[0],
            pages: [...selectedPages.value],
            showPhoto: showPhoto.value,
        });
    } else {
        queueActionType.value = 'print';
        showPerStudentQueue.value = true;
    }
}

function handleDownloadPdf() {
    if (!isValid.value) return;

    if (fileMode.value === 'merged') {
        emit('download-pdf-bulk', {
            students: props.selectedStudents,
            pages: [...selectedPages.value],
            showPhoto: showPhoto.value,
        });
    } else if (props.selectedStudents.length === 1) {
        emit('download-pdf-single', {
            student: props.selectedStudents[0],
            pages: [...selectedPages.value],
            showPhoto: showPhoto.value,
        });
    } else {
        queueActionType.value = 'pdf';
        showPerStudentQueue.value = true;
    }
}

function handlePreview() {
    if (!isValid.value) return;
    emit('preview', {
        students: props.selectedStudents,
        pages: [...selectedPages.value],
        showPhoto: showPhoto.value,
    });
}

function processSingleStudent(student, actionType) {
    if (!processedIds.value.includes(student.id)) {
        processedIds.value.push(student.id);
    }

    const payload = {
        student,
        pages: [...selectedPages.value],
        showPhoto: showPhoto.value,
    };

    if (actionType === 'pdf') {
        emit('download-pdf-single', payload);
    } else {
        emit('print-single', payload);
    }
}

function isProcessed(id) {
    return processedIds.value.includes(id);
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 print:hidden"
        @click.self="$emit('close')"
    >
        <div class="bg-white rounded-2xl w-full max-w-xl max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
            <!-- Header -->
            <div class="px-5 sm:px-6 py-4 bg-emerald-900 text-white flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-yellow-200 text-emerald-950 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">contact_page</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-base sm:text-lg leading-tight truncate">
                            Generate Identitas Siswa
                        </h3>
                        <p class="text-xs text-emerald-100/90 truncate">
                            {{ selectedStudents.length }} siswa terpilih siap diproses
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1.5 rounded-xl text-emerald-100 hover:text-white hover:bg-white/10 transition-colors cursor-pointer shrink-0"
                    title="Tutup"
                >
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-5">
                <template v-if="!showPerStudentQueue">
                    <!-- 1. Checklist Halaman yang Mau Dicetak -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                1. Pilih Halaman yang Dicetak
                            </label>
                            <div class="flex items-center gap-1.5 text-[11px]">
                                <button
                                    type="button"
                                    @click="setPagesPreset('identity_only')"
                                    class="px-2 py-0.5 rounded-md border border-slate-200 hover:bg-slate-100 font-semibold text-slate-600 cursor-pointer"
                                >
                                    Identitas Saja
                                </button>
                                <button
                                    type="button"
                                    @click="setPagesPreset('all')"
                                    class="px-2 py-0.5 rounded-md border border-slate-200 hover:bg-slate-100 font-semibold text-slate-600 cursor-pointer"
                                >
                                    Semua (3)
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <label
                                :class="[
                                    'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                    selectedPages.includes('cover')
                                        ? 'bg-emerald-50/70 border-emerald-500'
                                        : 'bg-white border-slate-200 hover:bg-slate-50'
                                ]"
                            >
                                <input type="checkbox" v-model="selectedPages" value="cover" class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 cursor-pointer" />
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Cover</div>
                                    <div class="text-[11px] text-slate-500">Sampul depan</div>
                                </div>
                            </label>

                            <label
                                :class="[
                                    'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                    selectedPages.includes('school_profile')
                                        ? 'bg-emerald-50/70 border-emerald-500'
                                        : 'bg-white border-slate-200 hover:bg-slate-50'
                                ]"
                            >
                                <input type="checkbox" v-model="selectedPages" value="school_profile" class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 cursor-pointer" />
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Profil Sekolah</div>
                                    <div class="text-[11px] text-slate-500">Identitas unit</div>
                                </div>
                            </label>

                            <label
                                :class="[
                                    'flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all',
                                    selectedPages.includes('identity')
                                        ? 'bg-emerald-50/70 border-emerald-500'
                                        : 'bg-white border-slate-200 hover:bg-slate-50'
                                ]"
                            >
                                <input type="checkbox" v-model="selectedPages" value="identity" class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 cursor-pointer" />
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Identitas Siswa</div>
                                    <div class="text-[11px] text-slate-500">Biodata & foto</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Pilihan Tampilkan Foto / Tidak (Slider) -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block">
                            2. Pengaturan Pas Foto (3x4)
                        </label>
                        <div class="flex items-center justify-between gap-4 p-3.5 rounded-xl border border-slate-200 bg-slate-50/60">
                            <div class="min-w-0">
                                <div class="text-xs sm:text-sm font-bold text-slate-800">
                                    Tampilkan Foto Siswa
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ showPhoto
                                        ? 'Menampilkan pas foto digital siswa pada kotak 3x4 (jika tersedia).'
                                        : 'Foto disembunyikan; hanya mencetak kotak kosong 3x4 untuk tempel foto fisik.'
                                    }}
                                </p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="showPhoto"
                                @click="showPhoto = !showPhoto"
                                :class="[
                                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2',
                                    showPhoto ? 'bg-emerald-700' : 'bg-slate-300'
                                ]"
                                title="Aktifkan/Nonaktifkan Pas Foto Siswa"
                            >
                                <span
                                    :class="[
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out',
                                        showPhoto ? 'translate-x-5' : 'translate-x-0'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- 3. Pilihan Gabung File atau Per Siswa -->
                    <div class="space-y-2.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block">
                            3. Mode Output Berkas
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="fileMode = 'merged'"
                                :class="[
                                    'p-3.5 rounded-xl border text-left transition-all cursor-pointer flex items-start gap-3',
                                    fileMode === 'merged'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="fileMode === 'merged' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                    <span class="material-symbols-outlined text-[20px]">library_books</span>
                                </div>
                                <div>
                                    <div class="font-bold text-xs sm:text-sm text-slate-900">Gabung File</div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        Semua siswa digabung berurutan dalam 1 dokumen siap cetak / 1 PDF.
                                    </p>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="fileMode = 'per_student'"
                                :class="[
                                    'p-3.5 rounded-xl border text-left transition-all cursor-pointer flex items-start gap-3',
                                    fileMode === 'per_student'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="fileMode === 'per_student' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                    <span class="material-symbols-outlined text-[20px]">person</span>
                                </div>
                                <div>
                                    <div class="font-bold text-xs sm:text-sm text-slate-900">Per Siswa</div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                        Dipisah per nama siswa (nama file PDF otomatis sesuai nama siswa).
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Queue View saat mode Per Siswa & siswa > 1 -->
                <template v-else>
                    <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-200">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">
                                Daftar {{ queueActionType === 'pdf' ? 'Download PDF' : 'Cetak' }} Per Siswa ({{ selectedStudents.length }})
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Klik tombol pada setiap nama siswa. Nama file otomatis menggunakan nama siswa.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="showPerStudentQueue = false"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 cursor-pointer shrink-0"
                        >
                            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                            Kembali
                        </button>
                    </div>

                    <div class="max-h-[45vh] overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
                        <div
                            v-for="(st, idx) in selectedStudents"
                            :key="st.id"
                            class="p-3 flex flex-wrap items-center justify-between gap-2 hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <span class="text-xs font-mono font-bold text-slate-400 w-6 text-center shrink-0">{{ idx + 1 }}</span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs sm:text-sm font-bold text-slate-900 truncate">{{ st.nama }}</span>
                                        <span
                                            v-if="isProcessed(st.id)"
                                            class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold"
                                        >
                                            <span class="material-symbols-outlined text-[12px]">check</span>
                                            Diproses
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        NISN: {{ st.nisn }} • {{ st.kelas || '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="processSingleStudent(st, 'print')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700 cursor-pointer"
                                    title="Cetak Langsung Siswa Ini"
                                >
                                    <span class="material-symbols-outlined text-[15px]">print</span>
                                    <span>Cetak</span>
                                </button>
                                <button
                                    type="button"
                                    @click="processSingleStudent(st, 'pdf')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs font-bold cursor-pointer"
                                    title="Download PDF Siswa Ini"
                                >
                                    <span class="material-symbols-outlined text-[15px]">picture_as_pdf</span>
                                    <span>PDF</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span v-if="selectedPages.length === 0" class="text-rose-600 font-semibold">
                        Pilih minimal 1 halaman.
                    </span>
                    <span v-else>
                        Total: <strong>{{ totalSheets }}</strong> lembar A4
                    </span>

                    <button
                        v-if="!showPerStudentQueue && isValid"
                        type="button"
                        @click="handlePreview"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-[11px] font-semibold text-slate-700 cursor-pointer ml-1"
                        title="Lihat pratinjau sebelum cetak"
                    >
                        <span class="material-symbols-outlined text-[14px]">visibility</span>
                        <span>Preview</span>
                    </button>
                </div>

                <!-- Tombol Cetak Langsung dan Download PDF -->
                <div v-if="!showPerStudentQueue" class="flex items-center gap-2 ml-auto">
                    <button
                        type="button"
                        @click="handleDirectPrint"
                        :disabled="!isValid"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-emerald-800 bg-white hover:bg-emerald-50 text-emerald-900 text-xs sm:text-sm font-bold shadow-2xs transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-[18px]">print</span>
                        <span>Cetak Langsung</span>
                    </button>
                    <button
                        type="button"
                        @click="handleDownloadPdf"
                        :disabled="!isValid"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs sm:text-sm font-bold shadow-sm transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                        <span>Download PDF</span>
                    </button>
                </div>

                <div v-else class="flex items-center gap-2 ml-auto">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-xs sm:text-sm font-semibold text-slate-700 cursor-pointer"
                    >
                        Selesai & Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
