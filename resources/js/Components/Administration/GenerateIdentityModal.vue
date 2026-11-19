<script setup>
import { ref, computed, watch } from 'vue';
import { getGenderBadgeClass, getGenderLabel } from '@/Utils/studentBadges';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    allStudents: {
        type: Array,
        default: () => [],
    },
    filteredStudents: {
        type: Array,
        default: () => [],
    },
    preselectedIds: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'preview', 'print-bulk', 'print-single']);

// Target source: 'filtered' | 'checked' | 'manual'
const targetSource = ref('filtered');
// Output mode: 'bulk' (Dicetak Sekaligus) | 'per_name' (Per Nama)
const outputMode = ref('bulk');
// Pages to include
const selectedPages = ref(['cover', 'school_profile', 'identity']);
// Manual search & selection
const manualSearch = ref('');
const manualSelectedIds = ref([]);
// Per-name queue tracking (tracks IDs already clicked to print in this session)
const printedIds = ref([]);
const isPerNameGenerated = ref(false);

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            isPerNameGenerated.value = false;
            manualSearch.value = '';
            printedIds.value = [];

            if (props.preselectedIds.length > 0) {
                targetSource.value = 'checked';
                manualSelectedIds.value = [...props.preselectedIds];
            } else {
                targetSource.value = 'filtered';
                manualSelectedIds.value = [];
            }
        }
    }
);

const manualSearchResults = computed(() => {
    const q = manualSearch.value.trim().toLowerCase();
    if (!q) return props.allStudents;
    return props.allStudents.filter((s) =>
        s.nama?.toLowerCase().includes(q) ||
        s.nisn?.toLowerCase().includes(q) ||
        s.nipd?.toLowerCase().includes(q) ||
        s.kelas?.toLowerCase().includes(q) ||
        s.unit?.toLowerCase().includes(q)
    );
});

const targetStudents = computed(() => {
    if (targetSource.value === 'checked') {
        const idSet = new Set(props.preselectedIds);
        return props.allStudents.filter((s) => idSet.has(s.id));
    }
    if (targetSource.value === 'manual') {
        const idSet = new Set(manualSelectedIds.value);
        return props.allStudents.filter((s) => idSet.has(s.id));
    }
    return props.filteredStudents;
});

function toggleManualStudent(id) {
    const idx = manualSelectedIds.value.indexOf(id);
    if (idx > -1) {
        manualSelectedIds.value.splice(idx, 1);
    } else {
        manualSelectedIds.value.push(id);
    }
}

function selectAllManualResults() {
    const set = new Set(manualSelectedIds.value);
    manualSearchResults.value.forEach((s) => set.add(s.id));
    manualSelectedIds.value = Array.from(set);
}

function clearManualSelection() {
    manualSelectedIds.value = [];
}

function setPagesPreset(preset) {
    if (preset === 'identity_only') {
        selectedPages.value = ['identity'];
    } else {
        selectedPages.value = ['cover', 'school_profile', 'identity'];
    }
}

function handleGenerateAction() {
    if (targetStudents.value.length === 0 || selectedPages.value.length === 0) return;

    if (outputMode.value === 'bulk') {
        emit('print-bulk', {
            students: targetStudents.value,
            pages: [...selectedPages.value],
        });
    } else {
        isPerNameGenerated.value = true;
    }
}

function handlePreviewAll() {
    if (targetStudents.value.length === 0 || selectedPages.value.length === 0) return;
    emit('preview', {
        students: targetStudents.value,
        pages: [...selectedPages.value],
    });
}

function handlePreviewSingle(student) {
    if (selectedPages.value.length === 0) return;
    emit('preview', {
        students: [student],
        pages: [...selectedPages.value],
    });
}

function handlePrintSingle(student) {
    if (selectedPages.value.length === 0) return;
    if (!printedIds.value.includes(student.id)) {
        printedIds.value.push(student.id);
    }
    emit('print-single', {
        student,
        pages: [...selectedPages.value],
    });
}

function isPrinted(id) {
    return printedIds.value.includes(id);
}
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 print:hidden"
        @click.self="$emit('close')"
    >
        <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
            <!-- Header -->
            <div class="px-5 sm:px-6 py-4 bg-emerald-900 text-white flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-yellow-200 text-emerald-950 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">badge</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-base sm:text-lg leading-tight truncate">
                            Generate Identitas Siswa
                        </h3>
                        <p class="text-xs text-emerald-100/90 truncate">
                            Pilih siswa (bulking atau per nama), atur lembar halaman, lalu cetak sekaligus atau per nama.
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
            <div class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-6">
                <!-- View A: Konfigurasi Generate -->
                <template v-if="!isPerNameGenerated">
                    <!-- 1. Pilih Target Siswa -->
                    <div class="space-y-2.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block">
                            1. Pilih Target Siswa (Bulking / Per Nama)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <button
                                type="button"
                                @click="targetSource = 'filtered'"
                                :class="[
                                    'p-3.5 rounded-xl border text-left transition-all cursor-pointer',
                                    targetSource === 'filtered'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="material-symbols-outlined text-[20px]" :class="targetSource === 'filtered' ? 'text-emerald-800' : 'text-slate-500'">groups</span>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="targetSource === 'filtered' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                        {{ filteredStudents.length }} siswa
                                    </span>
                                </div>
                                <div class="font-bold text-xs sm:text-sm text-slate-900">Bulking Sesuai Filter</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">Semua siswa yang tampil di tabel</div>
                            </button>

                            <button
                                type="button"
                                @click="targetSource = 'checked'"
                                :disabled="preselectedIds.length === 0"
                                :class="[
                                    'p-3.5 rounded-xl border text-left transition-all',
                                    preselectedIds.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-50 border-slate-200' : 'cursor-pointer',
                                    targetSource === 'checked'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="material-symbols-outlined text-[20px]" :class="targetSource === 'checked' ? 'text-emerald-800' : 'text-slate-500'">checklist</span>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="targetSource === 'checked' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                        {{ preselectedIds.length }} dipilih
                                    </span>
                                </div>
                                <div class="font-bold text-xs sm:text-sm text-slate-900">Siswa Dicentang</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ preselectedIds.length > 0 ? 'Sesuai centang tabel saat ini' : 'Belum ada centang di tabel' }}
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="targetSource = 'manual'"
                                :class="[
                                    'p-3.5 rounded-xl border text-left transition-all cursor-pointer',
                                    targetSource === 'manual'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="material-symbols-outlined text-[20px]" :class="targetSource === 'manual' ? 'text-emerald-800' : 'text-slate-500'">person_search</span>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="targetSource === 'manual' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                        {{ manualSelectedIds.length }} dipilih
                                    </span>
                                </div>
                                <div class="font-bold text-xs sm:text-sm text-slate-900">Pilih Manual Per Nama</div>
                                <div class="text-[11px] text-slate-500 mt-0.5">Cari & pilih siswa tertentu</div>
                            </button>
                        </div>

                        <!-- Manual Picker Box -->
                        <div v-if="targetSource === 'manual'" class="mt-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="relative flex-1 min-w-[200px]">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                    <input
                                        v-model="manualSearch"
                                        type="text"
                                        placeholder="Ketik nama, NISN, atau kelas..."
                                        class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs sm:text-sm focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                                <button
                                    type="button"
                                    @click="selectAllManualResults"
                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-100 text-xs font-semibold text-slate-700 cursor-pointer"
                                >
                                    Pilih Semua ({{ manualSearchResults.length }})
                                </button>
                                <button
                                    v-if="manualSelectedIds.length > 0"
                                    type="button"
                                    @click="clearManualSelection"
                                    class="px-2.5 py-1.5 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-xs font-semibold text-rose-600 cursor-pointer"
                                >
                                    Kosongkan
                                </button>
                            </div>

                            <div class="max-h-48 overflow-y-auto bg-white rounded-lg border border-slate-200 divide-y divide-slate-100">
                                <label
                                    v-for="s in manualSearchResults"
                                    :key="s.id"
                                    class="flex items-center justify-between gap-2 px-3 py-2 hover:bg-emerald-50/50 cursor-pointer text-xs"
                                >
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <input
                                            type="checkbox"
                                            :checked="manualSelectedIds.includes(s.id)"
                                            @change="toggleManualStudent(s.id)"
                                            class="w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600 cursor-pointer shrink-0"
                                        />
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 truncate">{{ s.nama }}</div>
                                            <div class="text-[11px] text-slate-500 font-mono truncate">
                                                NISN: {{ s.nisn }} • {{ s.kelas || '-' }} ({{ s.unit || '-' }})
                                            </div>
                                        </div>
                                    </div>
                                    <span
                                        v-if="s.jk_raw"
                                        :class="['text-[10px] font-bold px-1.5 py-0.5 rounded border shrink-0', getGenderBadgeClass(s.jk_raw)]"
                                        :title="getGenderLabel(s.jk_raw)"
                                    >
                                        {{ s.jk_raw }}
                                    </span>
                                </label>
                                <div v-if="manualSearchResults.length === 0" class="py-6 text-center text-xs text-slate-400">
                                    Siswa tidak ditemukan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Pilih Lembar Dokumen -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                2. Pilih Lembar yang Di-Generate
                            </label>
                            <div class="flex items-center gap-1.5 text-[11px]">
                                <button
                                    type="button"
                                    @click="setPagesPreset('identity_only')"
                                    class="px-2 py-0.5 rounded-md border border-slate-200 hover:bg-slate-100 font-semibold text-slate-600 cursor-pointer"
                                >
                                    Hanya Identitas
                                </button>
                                <button
                                    type="button"
                                    @click="setPagesPreset('all')"
                                    class="px-2 py-0.5 rounded-md border border-slate-200 hover:bg-slate-100 font-semibold text-slate-600 cursor-pointer"
                                >
                                    Lengkap (3 Halaman)
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" v-model="selectedPages" value="cover" class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 cursor-pointer" />
                                <div>
                                    <div class="text-xs font-bold text-slate-800">1. Cover Depan</div>
                                    <div class="text-[11px] text-slate-500">Halaman sampul rapor</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" v-model="selectedPages" value="school_profile" class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 cursor-pointer" />
                                <div>
                                    <div class="text-xs font-bold text-slate-800">2. Profil Sekolah</div>
                                    <div class="text-[11px] text-slate-500">Data NPSN & alamat unit</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" v-model="selectedPages" value="identity" class="w-4 h-4 rounded text-emerald-700 focus:ring-emerald-600 cursor-pointer" />
                                <div>
                                    <div class="text-xs font-bold text-slate-800">3. Identitas Siswa</div>
                                    <div class="text-[11px] text-slate-500">Biodata & foto 3x4 siswa</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Pilih Mode Cetak (Sekaligus atau Per Nama) -->
                    <div class="space-y-2.5">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block">
                            3. Pilih Mode Cetak
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="outputMode = 'bulk'"
                                :class="[
                                    'p-4 rounded-xl border text-left transition-all cursor-pointer flex items-start gap-3',
                                    outputMode === 'bulk'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="outputMode === 'bulk' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                    <span class="material-symbols-outlined text-[20px]">print</span>
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-slate-900">Cetak Sekaligus (Gabung Semua)</div>
                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                        Semua siswa digabung berurutan dalam satu kali cetak / 1 file PDF gabungan.
                                    </p>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="outputMode = 'per_name'"
                                :class="[
                                    'p-4 rounded-xl border text-left transition-all cursor-pointer flex items-start gap-3',
                                    outputMode === 'per_name'
                                        ? 'bg-emerald-50 border-emerald-600 ring-2 ring-emerald-600/15'
                                        : 'bg-white border-slate-200 hover:border-slate-300'
                                ]"
                            >
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" :class="outputMode === 'per_name' ? 'bg-emerald-900 text-yellow-200' : 'bg-slate-100 text-slate-600'">
                                    <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-slate-900">Cetak Per Nama (Satu per Satu)</div>
                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                        Generate daftar dokumen terpisah per nama siswa agar mudah cetak/simpan PDF per individu.
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- View B: Daftar Hasil Generate Per Nama -->
                <template v-else>
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-200">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">
                                Daftar Berkas Identitas Per Nama ({{ targetStudents.length }} Siswa)
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Klik <strong>Cetak / PDF</strong> pada masing-masing nama. Nama file PDF otomatis menggunakan nama siswa.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="isPerNameGenerated = false"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                            Ubah Pilihan
                        </button>
                    </div>

                    <div class="max-h-[50vh] overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
                        <div
                            v-for="(st, idx) in targetStudents"
                            :key="st.id"
                            class="p-3 flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50/80 transition-colors"
                        >
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <span class="text-xs font-mono font-bold text-slate-400 w-6 text-center shrink-0">{{ idx + 1 }}</span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-sm font-bold text-slate-900 truncate">{{ st.nama }}</span>
                                        <span
                                            v-if="isPrinted(st.id)"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold"
                                        >
                                            <span class="material-symbols-outlined text-[12px]">check</span>
                                            Sudah Dicetak
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        NISN: <span class="font-mono">{{ st.nisn }}</span> • Kelas: {{ st.kelas || '-' }} ({{ st.unit || '-' }})
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    @click="handlePreviewSingle(st)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 cursor-pointer"
                                    title="Preview"
                                >
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Preview</span>
                                </button>
                                <button
                                    type="button"
                                    @click="handlePrintSingle(st)"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer active:scale-95',
                                        isPrinted(st.id)
                                            ? 'bg-emerald-100 text-emerald-900 border border-emerald-300 hover:bg-emerald-200'
                                            : 'bg-emerald-900 text-yellow-200 hover:bg-emerald-800 shadow-xs'
                                    ]"
                                >
                                    <span class="material-symbols-outlined text-[16px]">print</span>
                                    <span>{{ isPrinted(st.id) ? 'Cetak Ulang' : 'Cetak / PDF' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-5 sm:px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="text-xs text-slate-600">
                    <template v-if="targetStudents.length > 0">
                        Siap generate <strong>{{ targetStudents.length }}</strong> siswa × <strong>{{ selectedPages.length }}</strong> halaman
                        (total <strong>{{ targetStudents.length * selectedPages.length }}</strong> lembar A4)
                    </template>
                    <template v-else>
                        <span class="text-rose-600 font-semibold">Pilih minimal 1 siswa untuk di-generate.</span>
                    </template>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-xs sm:text-sm font-semibold text-slate-700 cursor-pointer"
                    >
                        Tutup
                    </button>

                    <template v-if="!isPerNameGenerated">
                        <button
                            v-if="outputMode === 'bulk'"
                            type="button"
                            @click="handlePreviewAll"
                            :disabled="targetStudents.length === 0 || selectedPages.length === 0"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-emerald-700 bg-white hover:bg-emerald-50 text-emerald-900 text-xs sm:text-sm font-bold cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                            <span>Preview Gabungan</span>
                        </button>

                        <button
                            type="button"
                            @click="handleGenerateAction"
                            :disabled="targetStudents.length === 0 || selectedPages.length === 0"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs sm:text-sm font-bold shadow-sm transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-[18px]">
                                {{ outputMode === 'bulk' ? 'print' : 'fact_check' }}
                            </span>
                            <span>
                                {{ outputMode === 'bulk'
                                    ? `Generate & Cetak Sekaligus (${targetStudents.length})`
                                    : `Generate Daftar Per Nama (${targetStudents.length})`
                                }}
                            </span>
                        </button>
                    </template>
                    <template v-else>
                        <button
                            type="button"
                            @click="emit('print-bulk', { students: targetStudents, pages: [...selectedPages] })"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 text-xs sm:text-sm font-bold shadow-sm cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[18px]">print</span>
                            <span>Cetak Semua Sekaligus</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

