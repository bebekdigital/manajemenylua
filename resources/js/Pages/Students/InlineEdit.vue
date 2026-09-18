<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    students: { type: Array, required: true },
    selectedAcademicYear: { type: Object, default: null },
});

// ============================================================
// Student Data (mutable local copy)
// ============================================================
const students = reactive(props.students.map(s => ({ ...s })));

const isSaving = ref(false);
const saveError = ref(null);

// ============================================================
// Column Groups (collapsible)
// ============================================================
const columnGroups = reactive([
    { key: 'identitas', label: 'Identitas', icon: 'person', expanded: true, color: 'bg-blue-600' },
    { key: 'akademik', label: 'Akademik', icon: 'school', expanded: true, color: 'bg-emerald-600' },
    { key: 'registrasi', label: 'Registrasi', icon: 'how_to_reg', expanded: false, color: 'bg-violet-600' },
    { key: 'alamat', label: 'Alamat', icon: 'home', expanded: false, color: 'bg-orange-600' },
    { key: 'keluarga_ayah', label: 'Ayah', icon: 'man', expanded: false, color: 'bg-cyan-600' },
    { key: 'keluarga_ibu', label: 'Ibu', icon: 'woman', expanded: false, color: 'bg-pink-600' },
    { key: 'keluarga_wali', label: 'Wali', icon: 'supervisor_account', expanded: false, color: 'bg-purple-600' },
    { key: 'bantuan', label: 'Bantuan', icon: 'volunteer_activism', expanded: false, color: 'bg-amber-600' },
]);

const columns = {
    identitas: [
        { key: 'nama', label: 'Nama Lengkap', type: 'text', width: 'w-48' },
        { key: 'nipd', label: 'NIPD', type: 'text', width: 'w-28' },
        { key: 'nik', label: 'NIK', type: 'text', width: 'w-40' },
        { key: 'no_kk', label: 'No KK', type: 'text', width: 'w-40' },
        { key: 'jk', label: 'JK', type: 'select', options: ['L', 'P'], width: 'w-20' },
        { key: 'tempat_lahir', label: 'Tempat Lahir', type: 'text', width: 'w-36' },
        { key: 'tanggal_lahir', label: 'Tgl Lahir', type: 'date', width: 'w-36' },
        { key: 'agama', label: 'Agama', type: 'select', options: ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'], width: 'w-28' },
        { key: 'no_wa', label: 'No WA', type: 'text', width: 'w-32' },
        { key: 'sekolah_asal', label: 'Sekolah Asal', type: 'text', width: 'w-40' },
    ],
    akademik: [
        { key: 'unit', label: 'Unit', type: 'select', options: ['SDIT Ulil Albab Gondangrejo', 'SMPIT Ulil Albab Gondangrejo', 'PPTQ Ulil Albab Gondangrejo'], width: 'w-44' },
        { key: 'program', label: 'Program', type: 'select', options: ['Umum', 'Fullday', 'Boarding'], width: 'w-28' },
        { key: 'jenjang', label: 'Jenjang', type: 'select', options: ['SD', 'SMP', 'SMA'], width: 'w-24' },
        { key: 'tingkat', label: 'Tingkat', type: 'number', width: 'w-20' },
        { key: 'kelas', label: 'Kelas', type: 'text', width: 'w-24' },
        { key: 'student_status', label: 'Status Siswa', type: 'select', options: ['aktif', 'mutasi_masuk', 'mutasi_keluar', 'lulus', 'mengulang', 'dropout'], width: 'w-32' },
    ],
    registrasi: [
        { key: 'status_keluarga', label: 'Status Keluarga', type: 'text', width: 'w-36' },
        { key: 'anak_ke', label: 'Anak Ke', type: 'number', width: 'w-24' },
        { key: 'diterima_di_jenjang', label: 'Diterima di Jenjang', type: 'text', width: 'w-40' },
        { key: 'tanggal_diterima', label: 'Tgl Diterima', type: 'date', width: 'w-36' },
    ],
    alamat: [
        { key: 'jalan', label: 'Jalan', type: 'text', width: 'w-48' },
        { key: 'rt_rw', label: 'RT/RW', type: 'text', width: 'w-24' },
        { key: 'dusun', label: 'Dusun', type: 'text', width: 'w-32' },
        { key: 'desa', label: 'Desa', type: 'text', width: 'w-32' },
        { key: 'kecamatan', label: 'Kecamatan', type: 'text', width: 'w-32' },
        { key: 'kabupaten', label: 'Kabupaten', type: 'text', width: 'w-32' },
        { key: 'provinsi', label: 'Provinsi', type: 'text', width: 'w-32' },
    ],
    keluarga_ayah: [
        { key: 'ayah_nama', label: 'Nama Ayah', type: 'text', width: 'w-40' },
        { key: 'ayah_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'ayah_pendidikan', label: 'Pendidikan', type: 'text', width: 'w-32' },
        { key: 'ayah_pekerjaan', label: 'Pekerjaan', type: 'text', width: 'w-36' },
        { key: 'ayah_penghasilan', label: 'Penghasilan', type: 'text', width: 'w-44' },
        { key: 'ayah_status', label: 'Status', type: 'select', options: ['Hidup', 'Meninggal'], width: 'w-28' },
    ],
    keluarga_ibu: [
        { key: 'ibu_nama', label: 'Nama Ibu', type: 'text', width: 'w-40' },
        { key: 'ibu_tahun_lahir', label: 'Thn Lahir', type: 'number', width: 'w-24' },
        { key: 'ibu_pendidikan', label: 'Pendidikan', type: 'text', width: 'w-32' },
        { key: 'ibu_pekerjaan', label: 'Pekerjaan', type: 'text', width: 'w-36' },
        { key: 'ibu_penghasilan', label: 'Penghasilan', type: 'text', width: 'w-44' },
        { key: 'ibu_status', label: 'Status', type: 'select', options: ['Hidup', 'Meninggal'], width: 'w-28' },
    ],
    keluarga_wali: [
        { key: 'wali_nama', label: 'Nama Wali', type: 'text', width: 'w-40' },
        { key: 'wali_hubungan', label: 'Hubungan', type: 'text', width: 'w-32' },
        { key: 'wali_pekerjaan', label: 'Pekerjaan', type: 'text', width: 'w-36' },
        { key: 'wali_penghasilan', label: 'Penghasilan', type: 'text', width: 'w-44' },
    ],
    bantuan: [
        { key: 'desil', label: 'Desil', type: 'number', width: 'w-20' },
        { key: 'status_pip', label: 'PIP', type: 'checkbox', width: 'w-20' },
        { key: 'pip_keterangan', label: 'Keterangan PIP', type: 'text', width: 'w-44' },
        { key: 'status_kip', label: 'KIP', type: 'checkbox', width: 'w-20' },
        { key: 'no_kip', label: 'No KIP', type: 'text', width: 'w-36' },
    ],
};

// All flat columns (for bulk edit dropdown)
const allColumns = Object.values(columns).flat();

// ============================================================
// Search & Selection
// ============================================================
const searchQuery = ref('');
const selectedRows = ref(new Set());

const filteredStudents = computed(() => {
    if (!searchQuery.value) return students;
    const q = searchQuery.value.toLowerCase().trim();
    return students.filter(s =>
        s.nama?.toLowerCase().includes(q) ||
        s.nisn?.includes(q) ||
        s.nipd?.includes(q)
    );
});

function toggleSelectAll() {
    if (selectedRows.value.size === filteredStudents.value.length) {
        selectedRows.value = new Set();
    } else {
        selectedRows.value = new Set(filteredStudents.value.map(s => s.nisn));
    }
}

function toggleRow(nisn) {
    if (selectedRows.value.has(nisn)) {
        selectedRows.value.delete(nisn);
    } else {
        selectedRows.value.add(nisn);
    }
    selectedRows.value = new Set(selectedRows.value);
}

const allSelected = computed(() =>
    filteredStudents.value.length > 0 && selectedRows.value.size === filteredStudents.value.length
);

const someSelected = computed(() => selectedRows.value.size > 0 && !allSelected.value);

// ============================================================
// Bulk Edit
// ============================================================
const showBulkPanel = ref(false);
const bulkField = ref('');
const bulkValue = ref('');
const bulkMode = ref('selected'); // 'selected' or 'all'
const bulkColumn = computed(() => allColumns.find(c => c.key === bulkField.value));

function applyBulk() {
    if (!bulkField.value) return;
    const targets = bulkMode.value === 'all'
        ? students
        : students.filter(s => selectedRows.value.has(s.nisn));

    const val = bulkColumn.value?.type === 'number' ? Number(bulkValue.value) :
                bulkColumn.value?.type === 'checkbox' ? Boolean(bulkValue.value) :
                bulkValue.value;

    targets.forEach(s => { s[bulkField.value] = val; });

    bulkValue.value = '';
    bulkField.value = '';
    showBulkPanel.value = false;
}

// ============================================================
// Save
// ============================================================
function saveChanges() {
    isSaving.value = true;
    saveError.value = null;

    router.post('/students/inline-update', { students: students }, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
        },
        onError: (errors) => {
            saveError.value = Object.values(errors).join(', ') || 'Terjadi kesalahan saat menyimpan data.';
            isSaving.value = false;
        },
        onFinish: () => {
            isSaving.value = false;
        }
    });
}

// ============================================================
// Helpers
// ============================================================
function toggleGroup(key) {
    const g = columnGroups.find(g => g.key === key);
    if (g) g.expanded = !g.expanded;
}

function visibleCols(groupKey) {
    const g = columnGroups.find(g => g.key === groupKey);
    return g?.expanded ? columns[groupKey] : [];
}

const visibleGroups = computed(() => columnGroups.filter(g => columns[g.key]));

// Column span per group (min 1 for header when collapsed)
function groupSpan(groupKey) {
    const g = columnGroups.find(g => g.key === groupKey);
    return g?.expanded ? (columns[groupKey]?.length ?? 1) : 1;
}
</script>

<template>
    <Head title="Inline Edit Data Siswa" />

    <div class="flex-1 overflow-y-auto p-4 md:p-margin-desktop">
        <!-- ======================================================
             PAGE HEADER
        ====================================================== -->
        <div class="mb-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-on-surface-variant mb-1">
                    <Link href="/students" class="hover:text-primary hover:underline transition-colors">Database Siswa</Link>
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                    <span class="font-medium text-on-surface">Edit Data Massal</span>
                </div>
                <h2 class="text-xl sm:text-2xl text-primary font-bold leading-tight">Edit Data Massal</h2>
                <p class="text-[13px] text-on-surface-variant mt-0.5">
                    Edit langsung di tabel • Klik header grup untuk tampilkan/sembunyikan kolom
                    <span v-if="selectedAcademicYear" class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 bg-primary/10 text-primary rounded-full text-[11px] font-semibold">
                        <span class="material-symbols-outlined text-[13px]">calendar_month</span>
                        {{ selectedAcademicYear.name }}
                    </span>
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                <!-- Bulk Edit Toggle -->
                <button
                    type="button"
                    @click="showBulkPanel = !showBulkPanel"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border font-semibold text-xs transition-all',
                        showBulkPanel
                            ? 'bg-amber-50 border-amber-400 text-amber-700'
                            : 'bg-white border-slate-300 text-slate-700 hover:border-amber-400 hover:text-amber-700'
                    ]"
                >
                    <span class="material-symbols-outlined text-[16px]">edit_attributes</span>
                    Bulk Edit
                    <span v-if="selectedRows.size > 0" class="bg-amber-500 text-white rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none">{{ selectedRows.size }}</span>
                </button>

                <Link
                    href="/students"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-300 text-slate-700 bg-white font-semibold text-xs hover:border-slate-400 transition-all"
                >
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Kembali
                </Link>

                <button
                    type="button"
                    @click="saveChanges"
                    :disabled="isSaving"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-semibold text-xs transition-all shadow-sm hover:shadow-md disabled:opacity-50 cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px]">{{ isSaving ? 'hourglass_empty' : 'save' }}</span>
                    {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
            </div>
        </div>

        <!-- Error Banner -->
        <div
            v-if="saveError"
            class="mb-4 p-3 bg-red-50 border border-red-300 rounded-2xl flex items-start gap-2 text-sm text-red-700"
        >
            <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">error</span>
            <div>
                <strong>Gagal menyimpan:</strong> {{ saveError }}
                <button type="button" @click="saveError = null" class="ml-2 underline text-red-500 hover:text-red-700">Tutup</button>
            </div>
        </div>

        <!-- ======================================================
             BULK EDIT PANEL
        ====================================================== -->
        <transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showBulkPanel" class="mb-4 p-4 bg-amber-50 border border-amber-300 rounded-2xl shadow-sm">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-amber-600 text-[20px]">edit_attributes</span>
                    <h3 class="font-bold text-amber-800 text-sm">Bulk Edit — Terapkan Nilai ke Banyak Baris Sekaligus</h3>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <!-- Target -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Target Siswa</label>
                        <div class="flex rounded-lg overflow-hidden border border-amber-300">
                            <button
                                type="button"
                                @click="bulkMode = 'selected'"
                                :class="['px-3 py-1.5 text-xs font-semibold transition-colors', bulkMode === 'selected' ? 'bg-amber-500 text-white' : 'bg-white text-amber-700 hover:bg-amber-100']"
                            >
                                Terpilih ({{ selectedRows.size }})
                            </button>
                            <button
                                type="button"
                                @click="bulkMode = 'all'"
                                :class="['px-3 py-1.5 text-xs font-semibold transition-colors', bulkMode === 'all' ? 'bg-amber-500 text-white' : 'bg-white text-amber-700 hover:bg-amber-100']"
                            >
                                Semua ({{ form.students.length }})
                            </button>
                        </div>
                    </div>

                    <!-- Field Selector -->
                    <div class="flex flex-col gap-1 min-w-[180px]">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Pilih Kolom</label>
                        <select
                            v-model="bulkField"
                            class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-400"
                        >
                            <option value="">— Pilih kolom —</option>
                            <optgroup v-for="grp in visibleGroups" :key="grp.key" :label="grp.label">
                                <option v-for="col in columns[grp.key]" :key="col.key" :value="col.key">
                                    {{ col.label }}
                                </option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Value Input -->
                    <div v-if="bulkField" class="flex flex-col gap-1 min-w-[160px]">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Nilai Baru</label>
                        <template v-if="bulkColumn?.type === 'select'">
                            <select v-model="bulkValue" class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <option value="">— Pilih —</option>
                                <option v-for="opt in bulkColumn.options" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </template>
                        <template v-else-if="bulkColumn?.type === 'checkbox'">
                            <select v-model="bulkValue" class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <option value="">— Pilih —</option>
                                <option :value="true">Ya</option>
                                <option :value="false">Tidak</option>
                            </select>
                        </template>
                        <template v-else-if="bulkColumn?.type === 'date'">
                            <input v-model="bulkValue" type="date" class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-400" />
                        </template>
                        <template v-else>
                            <input v-model="bulkValue" type="text" placeholder="Masukkan nilai..." class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-400" />
                        </template>
                    </div>

                    <!-- Apply Button -->
                    <button
                        type="button"
                        @click="applyBulk"
                        :disabled="!bulkField || bulkValue === ''"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold disabled:opacity-40 cursor-pointer transition-colors"
                    >
                        <span class="material-symbols-outlined text-[15px]">bolt</span>
                        Terapkan
                    </button>
                </div>
            </div>
        </transition>

        <!-- ======================================================
             TOOLBAR (Search + Stats)
        ====================================================== -->
        <div class="mb-3 flex flex-wrap items-center gap-3">
            <!-- Search -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-[17px]">search</span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama, NISN, NIPD..."
                    class="pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-xl bg-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors w-56"
                />
            </div>

            <!-- Selection info -->
            <div v-if="selectedRows.size > 0" class="text-xs text-on-surface-variant">
                <span class="font-semibold text-primary">{{ selectedRows.size }}</span> baris dipilih
                <button type="button" @click="selectedRows = new Set()" class="ml-2 text-error hover:underline">Batalkan pilihan</button>
            </div>

            <!-- Groups Legend -->
            <div class="flex flex-wrap gap-1.5 ml-auto">
                <button
                    v-for="grp in visibleGroups"
                    :key="grp.key"
                    type="button"
                    @click="toggleGroup(grp.key)"
                    :class="[
                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-all cursor-pointer',
                        columnGroups.find(g => g.key === grp.key)?.expanded
                            ? `${grp.color} text-white border-transparent shadow-sm`
                            : 'bg-white border-slate-300 text-slate-600 hover:border-slate-400'
                    ]"
                    :title="`Klik untuk ${columnGroups.find(g => g.key === grp.key)?.expanded ? 'sembunyikan' : 'tampilkan'} kolom ${grp.label}`"
                >
                    <span class="material-symbols-outlined text-[13px]">{{ grp.icon }}</span>
                    {{ grp.label }}
                </button>
            </div>
        </div>

        <!-- ======================================================
             TABLE
        ====================================================== -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-auto" style="max-height: calc(100vh - 290px);">
                <table class="text-left border-collapse min-w-max w-full">
                    <!-- ============ THEAD ROW 1: Group Headers ============ -->
                    <thead class="sticky top-0 z-30">
                        <tr>
                            <!-- Sticky cols: checkbox + no + nisn + nama -->
                            <th class="p-0 sticky left-0 z-40 bg-slate-800" rowspan="2">
                                <div class="flex items-center justify-center w-10 h-full p-2">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        :indeterminate="someSelected"
                                        @change="toggleSelectAll"
                                        class="w-3.5 h-3.5 rounded accent-primary cursor-pointer"
                                    />
                                </div>
                            </th>
                            <th class="p-2 text-[11px] font-semibold text-white bg-slate-800 sticky left-10 z-40 w-10" rowspan="2">#</th>
                            <th class="p-2 text-[11px] font-semibold text-white uppercase bg-slate-800 sticky left-[80px] z-40 w-32 border-r border-slate-600" rowspan="2">NISN</th>

                            <!-- Dynamic group headers -->
                            <th
                                v-for="grp in visibleGroups"
                                :key="grp.key"
                                :colspan="groupSpan(grp.key)"
                                :class="[grp.color, 'text-center text-[11px] font-bold text-white px-2 py-2 border-r border-white/20 cursor-pointer select-none']"
                                @click="toggleGroup(grp.key)"
                            >
                                <div class="flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">{{ grp.icon }}</span>
                                    <span>{{ grp.label }}</span>
                                    <span class="material-symbols-outlined text-[14px] opacity-70">
                                        {{ columnGroups.find(g => g.key === grp.key)?.expanded ? 'expand_less' : 'expand_more' }}
                                    </span>
                                </div>
                            </th>
                        </tr>

                        <!-- ============ THEAD ROW 2: Column Sub-headers ============ -->
                        <tr>
                            <template v-for="grp in visibleGroups" :key="grp.key">
                                <template v-if="columnGroups.find(g => g.key === grp.key)?.expanded">
                                    <th
                                        v-for="col in columns[grp.key]"
                                        :key="col.key"
                                        :class="[grp.color, 'text-left text-[10px] font-semibold text-white/90 uppercase tracking-wider px-2 py-1.5 border-r border-white/10 whitespace-nowrap', col.width]"
                                    >{{ col.label }}</th>
                                </template>
                                <th v-else :class="[grp.color, 'text-[10px] font-semibold text-white/80 text-center px-2 py-1.5 border-r border-white/10']">
                                    <span class="material-symbols-outlined text-[13px]">more_horiz</span>
                                </th>
                            </template>
                        </tr>
                    </thead>

                    <!-- ============ TBODY ============ -->
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(student, index) in filteredStudents"
                            :key="student.nisn"
                            :class="[
                                'transition-colors group',
                                selectedRows.has(student.nisn) ? 'bg-amber-50 hover:bg-amber-100/70' : 'hover:bg-slate-50'
                            ]"
                        >
                            <!-- Checkbox -->
                            <td class="p-0 sticky left-0 z-20 border-r border-slate-200" :class="selectedRows.has(student.nisn) ? 'bg-amber-50' : 'bg-white group-hover:bg-slate-50'">
                                <div class="flex items-center justify-center w-10 p-2">
                                    <input
                                        type="checkbox"
                                        :checked="selectedRows.has(student.nisn)"
                                        @change="toggleRow(student.nisn)"
                                        class="w-3.5 h-3.5 rounded accent-amber-500 cursor-pointer"
                                    />
                                </div>
                            </td>

                            <!-- No -->
                            <td class="px-2 py-1.5 text-[11px] text-slate-500 sticky left-10 z-20 border-r border-slate-100" :class="selectedRows.has(student.nisn) ? 'bg-amber-50' : 'bg-white group-hover:bg-slate-50'">
                                {{ index + 1 }}
                            </td>

                            <!-- NISN (readonly) -->
                            <td class="px-2 py-1.5 text-[11px] text-slate-600 font-mono sticky left-[80px] z-20 border-r border-slate-200" :class="selectedRows.has(student.nisn) ? 'bg-amber-50' : 'bg-white group-hover:bg-slate-50'">
                                {{ student.nisn }}
                            </td>

                            <!-- Dynamic columns per group -->
                            <template v-for="grp in visibleGroups" :key="grp.key">
                                <template v-if="columnGroups.find(g => g.key === grp.key)?.expanded">
                                    <td
                                        v-for="col in columns[grp.key]"
                                        :key="col.key"
                                        class="p-1 border-r border-slate-100"
                                    >
                                        <!-- Checkbox type -->
                                        <div v-if="col.type === 'checkbox'" class="flex items-center justify-center">
                                            <input
                                                type="checkbox"
                                                v-model="student[col.key]"
                                                class="w-3.5 h-3.5 rounded accent-primary cursor-pointer"
                                            />
                                        </div>
                                        <!-- Select type -->
                                        <select
                                            v-else-if="col.type === 'select'"
                                            v-model="student[col.key]"
                                            :class="['w-full px-1.5 py-1 text-[11px] border border-slate-200 rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-white transition-colors', col.width]"
                                        >
                                            <option value="">-</option>
                                            <option v-for="opt in col.options" :key="opt" :value="opt">{{ opt }}</option>
                                        </select>
                                        <!-- Date type -->
                                        <input
                                            v-else-if="col.type === 'date'"
                                            type="date"
                                            v-model="student[col.key]"
                                            :class="['w-full px-1.5 py-1 text-[11px] border border-slate-200 rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors', col.width]"
                                        />
                                        <!-- Number type -->
                                        <input
                                            v-else-if="col.type === 'number'"
                                            type="number"
                                            v-model="student[col.key]"
                                            :class="['w-full px-1.5 py-1 text-[11px] border border-slate-200 rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors', col.width]"
                                        />
                                        <!-- Text default -->
                                        <input
                                            v-else
                                            type="text"
                                            v-model="student[col.key]"
                                            :class="['w-full px-1.5 py-1 text-[11px] border border-slate-200 rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors', col.width]"
                                        />
                                    </td>
                                </template>
                                <!-- Collapsed group — show icon -->
                                <td v-else class="p-2 border-r border-slate-100 text-center">
                                    <span class="text-[10px] text-slate-400">—</span>
                                </td>
                            </template>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="filteredStudents.length === 0">
                            <td :colspan="6 + visibleGroups.reduce((sum, g) => sum + groupSpan(g.key), 0)" class="py-16 text-center">
                                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">search_off</span>
                                <p class="text-sm text-slate-500">Tidak ada data yang sesuai.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <span class="text-xs text-slate-500">
                    Menampilkan <strong class="text-slate-700">{{ filteredStudents.length }}</strong> dari <strong class="text-slate-700">{{ students.length }}</strong> siswa
                    <span v-if="selectedRows.size > 0"> · <strong class="text-amber-600">{{ selectedRows.size }} dipilih</strong></span>
                </span>
                <button
                    type="button"
                    @click="saveChanges"
                    :disabled="isSaving"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-primary text-on-primary rounded-lg text-xs font-bold disabled:opacity-50 cursor-pointer transition-all hover:shadow-md"
                >
                    <span class="material-symbols-outlined text-[14px]">{{ isSaving ? 'hourglass_empty' : 'save' }}</span>
                    {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
            </div>
        </div>
    </div>
</template>
