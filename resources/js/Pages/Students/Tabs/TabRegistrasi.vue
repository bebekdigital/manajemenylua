<script setup>
import { ref, reactive, computed, watch, inject } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    student: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
    allUnits: {
        type: Array,
        default: () => [],
    },
    allLevels: {
        type: Array,
        default: () => [],
    },
    timelineHeaders: {
        type: Array,
        default: () => [],
    },
    timelineRows: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['startEdit', 'cancelEdit', 'editingChange']);

const page = usePage();
const showToast = inject('showToast', null);
const isEditing = ref(false);

const form = useForm({
    _tab: 'registrasi',
    status_registrasi: '',
    sekolah_asal: '',
    diterima_di_jenjang: '',
    tanggal_diterima: '',
    info_psb: '',
    status_keaktifan: 'Aktif',
    keterangan_tidak_aktif: '',
    tanggal_tidak_aktif: '',
});

function initForm() {
    form.status_registrasi = props.student.status_registrasi || 'Siswa Baru';
    form.sekolah_asal = props.student.sekolah_asal || '';
    form.diterima_di_jenjang = props.student.diterima_di_jenjang || '';
    form.tanggal_diterima = props.student.tanggal_diterima || '';
    form.info_psb = props.student.info_psb || '';

    const rawStatus = (props.student.status_keaktifan || '').trim();
    if (rawStatus.toLowerCase() === 'lulus') {
        form.status_keaktifan = 'Lulus';
    } else if (
        rawStatus.toLowerCase().includes('tidak')
        || (props.student.student_status || '').toLowerCase() === 'tidak_aktif'
        || (props.student.student_status || '').toLowerCase() === 'dropout'
    ) {
        form.status_keaktifan = 'Tidak Aktif';
    } else {
        form.status_keaktifan = 'Aktif';
    }

    form.keterangan_tidak_aktif = props.student.keterangan_tidak_aktif || props.student.ket_tidak_aktif || '';
    form.tanggal_tidak_aktif = props.student.tanggal_tidak_aktif || '';
}

initForm();

watch(
    () => props.student,
    () => {
        if (!isEditing.value) {
            initForm();
        }
    },
    { deep: true }
);

function startEdit() {
    if (!props.canEdit) return;
    initForm();
    form.clearErrors();
    isEditing.value = true;
    emit('editingChange', true);
    emit('startEdit');
}

function cancelEdit() {
    initForm();
    form.clearErrors();
    isEditing.value = false;
    emit('editingChange', false);
    emit('cancelEdit');
}

function saveChanges() {
    form.put(`/students/${props.student.id || props.student.nisn}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
            emit('editingChange', false);
            initForm();
            if (showToast) {
                showToast('success', page.props.flash?.success || 'Data registrasi siswa berhasil diperbarui.');
            }
        },
        onError: () => {
            if (showToast) {
                showToast('error', 'Mohon periksa kembali isian form registrasi yang belum valid.', 'Gagal Menyimpan');
            }
        },
    });
}

// ============================================================
// Riwayat Keaktifan Siswa Matrix (TA di Kolom 1, Unit di Baris Atas)
// ============================================================

// 1. Daftar baris Tahun Ajaran & Semester (Kolom Pertama)
const academicYearRows = computed(() => {
    if (!props.student.riwayat_keaktifan || props.student.riwayat_keaktifan.length === 0) return [];

    const map = new Map();
    props.student.riwayat_keaktifan.forEach(r => {
        if (!r.academic_year) return;
        const key = `${r.academic_year}__${r.semester || ''}`;
        if (!map.has(key)) {
            map.set(key, {
                key,
                ta: r.academic_year,
                semester: r.semester || '',
            });
        }
    });
    return Array.from(map.values());
});

// 2. Daftar kolom Unit (Baris Atas)
const timelineUnits = computed(() => {
    const list = [];
    const seen = new Set();

    const addUnit = (name, type) => {
        if (!name || seen.has(name)) return;
        seen.add(name);
        const upper = name.toUpperCase();
        const isPesantren = type === 'pesantren' || upper.includes('PPTQ') || upper.includes('PESANTREN') || upper.includes('PONDOK');
        list.push({
            name,
            type: isPesantren ? 'pesantren' : 'formal',
            badgeText: isPesantren ? 'Unit Asrama / Pesantren' : 'Unit Sekolah Formal',
            badgeColor: isPesantren ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200',
        });
    };

    // Formal unit aktif
    if (props.student.unit) addUnit(props.student.unit, 'formal');

    // Formal units dari riwayat
    if (props.student.riwayat_keaktifan) {
        props.student.riwayat_keaktifan.forEach(r => {
            if (r.unit) addUnit(r.unit, 'formal');
        });
    }

    // Pesantren unit aktif
    if (props.student.unit_pesantren) addUnit(props.student.unit_pesantren, 'pesantren');

    // Pesantren units dari riwayat
    if (props.student.riwayat_keaktifan) {
        props.student.riwayat_keaktifan.forEach(r => {
            if (r.unit_pesantren) addUnit(r.unit_pesantren, 'pesantren');
        });
    }

    return list;
});

function getCellData(row, unit) {
    if (!props.student.riwayat_keaktifan) return null;

    const record = props.student.riwayat_keaktifan.find(r => 
        r.academic_year === row.ta && (r.semester || '') === (row.semester || '')
    );
    if (!record) return null;

    // Catatan tidak aktif (cth: Mutasi Keluar, Drop Out, Dikeluarkan, Lulus)
    const hasInactiveNote = Boolean(record.keterangan && record.keterangan.trim());

    // Status tampilan: prioritaskan ket_tidak_aktif jika ada (Mutasi Keluar, Drop Out, dll)
    // Jika tidak ada, gunakan status semester (Naik Kelas / Tinggal Kelas)
    let displayStatus = 'Naik Kelas';
    let sub = null;

    if (hasInactiveNote) {
        displayStatus = record.keterangan;
    } else if (record.status) {
        const sLower = record.status.toLowerCase().trim();
        if (sLower === 'tinggal kelas' || sLower === 'mengulang') {
            displayStatus = 'Tinggal Kelas';
        } else if (sLower === 'lulus') {
            displayStatus = 'Lulus';
        } else {
            displayStatus = 'Naik Kelas';
        }
    }

    if (unit.type === 'formal') {
        if (record.unit && record.unit !== unit.name) return null;
        sub = record.kelas ? `Kelas ${record.kelas}` : (record.tingkat ? `Tingkat ${record.tingkat}` : null);
    } else {
        if (!record.unit_pesantren || record.unit_pesantren !== unit.name) return null;
        sub = record.program || 'Santri';
    }

    return {
        status: displayStatus,
        subtext: sub,
        isInactive: hasInactiveNote,
    };
}

function getStatusBadgeClass(status) {
    const s = (status || '').toLowerCase().trim();
    if (s === 'naik kelas' || s === 'aktif') {
        return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    }
    if (s === 'tinggal kelas' || s === 'mengulang') {
        return 'bg-amber-50 text-amber-800 border-amber-200';
    }
    if (s === 'lulus') {
        return 'bg-blue-50 text-blue-800 border-blue-200';
    }
    if (s.includes('mutasi')) {
        return 'bg-amber-50 text-amber-800 border-amber-200';
    }
    if (s.includes('drop') || s.includes('tidak') || s.includes('keluar') || s.includes('undur') || s.includes('meninggal')) {
        return 'bg-rose-50 text-rose-800 border-rose-200';
    }
    return 'bg-slate-50 text-slate-700 border-slate-200';
}

function getStatusDotClass(status) {
    const s = (status || '').toLowerCase().trim();
    if (s === 'naik kelas' || s === 'aktif') return 'bg-emerald-600';
    if (s === 'tinggal kelas' || s === 'mengulang') return 'bg-amber-500';
    if (s === 'lulus') return 'bg-blue-600';
    if (s.includes('mutasi')) return 'bg-amber-500';
    return 'bg-rose-600';
}

function formatStatus(status) {
    if (!status) return 'Naik Kelas';
    const map = {
        'naik kelas': 'Naik Kelas',
        'tinggal kelas': 'Tinggal Kelas',
        'aktif': 'Naik Kelas',
        'mengulang': 'Tinggal Kelas',
        'mutasi_keluar': 'Mutasi Keluar',
        'mutasi_masuk': 'Mutasi Masuk',
        'dropout': 'Drop Out',
        'tidak_aktif': 'Tidak Aktif',
        'lulus': 'Lulus',
        'mengundurkan_diri': 'Mengundurkan Diri',
    };
    const s = status.toLowerCase().trim();
    if (map[s]) return map[s];
    const formatted = s.replace(/_/g, ' ');
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Section Header Data Pendaftaran Siswa -->
        <div class="flex items-center justify-between pb-3 mb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider">
                    {{ isEditing ? 'Edit Data Pendaftaran & Registrasi Siswa' : 'Data Pendaftaran Siswa' }}
                </h4>
            </div>

            <!-- Tombol Aksi di Pojok Kanan Atas -->
            <div v-if="canEdit" class="flex items-center gap-2">
                <!-- Saat View Mode: Tombol Edit Data -->
                <button
                    v-if="!isEditing"
                    type="button"
                    @click="startEdit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    title="Edit data registrasi siswa"
                >
                    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">edit_note</span>
                    <span>Edit Data</span>
                </button>

                <!-- Saat Edit Mode: Tombol Batal & Simpan -->
                <template v-else>
                    <button
                        type="button"
                        @click="cancelEdit"
                        class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                        :disabled="form.processing"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveChanges"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                        <span v-else class="material-symbols-outlined text-[16px]">save</span>
                        <span>Simpan</span>
                    </button>
                </template>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- VIEW MODE                                                    -->
        <!-- ============================================================ -->
        <div v-if="!isEditing" class="space-y-6">
            <!-- Informasi Pendaftaran -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Registrasi</label>
                    <p class="mt-1">
                        <span
                            v-if="student.status_registrasi"
                            :class="[
                                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border',
                                student.status_registrasi === 'Siswa Baru'
                                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200'
                                    : (student.status_registrasi === 'Mutasi Masuk'
                                        ? 'bg-amber-50 text-amber-800 border-amber-200'
                                        : 'bg-slate-100 text-slate-700 border-slate-200')
                            ]"
                        >
                            {{ student.status_registrasi }}
                        </span>
                        <span v-else class="text-xs sm:text-sm font-semibold text-slate-900">-</span>
                    </p>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sekolah Asal</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.sekolah_asal || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Diterima di Jenjang / Kelas</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.diterima_di_jenjang || '-' }}</p>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Diterima</label>
                    <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">{{ student.formatted_tanggal_diterima || student.tanggal_diterima || '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sumber Informasi PSB</label>
                    <p class="mt-1">
                        <span
                            v-if="student.info_psb"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200"
                        >
                            <span class="material-symbols-outlined text-[14px]">campaign</span>
                            {{ student.info_psb }}
                        </span>
                        <span v-else class="text-xs sm:text-sm font-semibold text-slate-900">-</span>
                    </p>
                </div>
            </div>

            <!-- Status Keaktifan Siswa -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span
                        class="w-2.5 h-2.5 rounded-full inline-block"
                        :class="student.status_keaktifan === 'Tidak Aktif' ? 'bg-rose-500' : (student.status_keaktifan === 'Lulus' ? 'bg-blue-600' : 'bg-emerald-600')"
                    ></span>
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Status Keaktifan Siswa</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-4">
                    <div>
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status Keaktifan</label>
                        <p class="mt-1">
                            <span
                                v-if="student.status_keaktifan === 'Tidak Aktif' || student.student_status === 'tidak_aktif'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Tidak Aktif
                            </span>
                            <span
                                v-else-if="student.status_keaktifan === 'Lulus'"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                Lulus
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                Aktif
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Keterangan Tidak Aktif</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                            <span
                                v-if="(student.status_keaktifan === 'Tidak Aktif' || student.student_status === 'tidak_aktif') && (student.keterangan_tidak_aktif || student.ket_tidak_aktif)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200"
                            >
                                <span class="material-symbols-outlined text-[14px] text-amber-600">info</span>
                                {{ student.keterangan_tidak_aktif || student.ket_tidak_aktif }}
                            </span>
                            <span v-else class="text-slate-400 font-normal">-</span>
                        </p>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Tidak Aktif</label>
                        <p class="text-xs sm:text-sm font-semibold text-slate-900 mt-1">
                            <span v-if="student.status_keaktifan === 'Tidak Aktif' && student.tanggal_tidak_aktif" class="inline-flex items-center gap-1.5">
                                <span class="font-mono text-slate-800">{{ student.tanggal_tidak_aktif }}</span>
                                <span v-if="student.formatted_tanggal_tidak_aktif" class="text-xs font-normal text-slate-500">({{ student.formatted_tanggal_tidak_aktif }})</span>
                            </span>
                            <span v-else class="text-slate-400 font-normal">-</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Riwayat Keaktifan Siswa Timeline -->
            <div class="mt-6 pt-2" v-if="student.riwayat_keaktifan && student.riwayat_keaktifan.length > 0">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Riwayat Keaktifan Siswa</h4>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full">
                        {{ academicYearRows.length }} Semester Tercatat
                    </span>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs relative">
                    <table class="w-full text-left text-xs sm:text-sm border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80">
                                <th class="p-3.5 font-bold text-slate-700 min-w-[200px] sticky left-0 z-10 border-r border-slate-200 bg-slate-50/90">
                                    Tahun Ajaran / Semester
                                </th>
                                <th
                                    v-for="u in timelineUnits"
                                    :key="u.name"
                                    class="p-3.5 font-bold text-slate-800 text-center min-w-[220px]"
                                >
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-bold text-slate-900 leading-tight">{{ u.name }}</span>
                                        <span
                                            class="inline-block text-[10px] font-semibold px-2 py-0.5 rounded-md border"
                                            :class="u.badgeColor"
                                        >
                                            {{ u.badgeText }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in academicYearRows" :key="row.key" class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-3.5 bg-white sticky left-0 z-10 border-r border-slate-100">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-900 font-mono text-xs sm:text-sm">{{ row.ta }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded-md text-[11px] font-bold"
                                            :class="row.semester === 'Ganjil' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200'"
                                        >
                                            {{ row.semester }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    v-for="u in timelineUnits"
                                    :key="u.name"
                                    class="p-3.5 text-center min-w-[220px]"
                                >
                                    <template v-if="getCellData(row, u)">
                                        <div class="inline-flex flex-col items-center gap-1">
                                            <span
                                                class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs"
                                                :class="getStatusBadgeClass(getCellData(row, u).status)"
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full"
                                                    :class="getStatusDotClass(getCellData(row, u).status)"
                                                ></span>
                                                {{ formatStatus(getCellData(row, u).status) }}
                                            </span>
                                            <span
                                                v-if="getCellData(row, u).subtext"
                                                class="text-[11px] font-semibold text-slate-600"
                                            >
                                                {{ getCellData(row, u).subtext }}
                                            </span>
                                        </div>
                                    </template>
                                    <span v-else class="text-slate-300 text-xs font-normal">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- EDIT MODE                                                    -->
        <!-- ============================================================ -->
        <div v-else class="space-y-6 animate-in fade-in duration-150">
            <!-- 1. Data Pendaftaran -->
            <div>
                <h5 class="text-xs font-bold text-emerald-900 uppercase tracking-wider mb-4 pb-2 border-b border-emerald-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[17px] text-emerald-700">app_registration</span>
                    <span>Informasi Pendaftaran</span>
                </h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    <!-- Status Registrasi -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Status Registrasi
                        </label>
                        <select
                            v-model="form.status_registrasi"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-800 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                        >
                            <option value="Siswa Baru">Siswa Baru</option>
                            <option value="Mutasi Masuk">Mutasi Masuk</option>
                            <option
                                v-if="form.status_registrasi && !['Siswa Baru', 'Mutasi Masuk'].includes(form.status_registrasi)"
                                :value="form.status_registrasi"
                            >
                                {{ form.status_registrasi }}
                            </option>
                        </select>
                    </div>

                    <!-- Sekolah Asal -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Sekolah Asal
                        </label>
                        <input
                            v-model="form.sekolah_asal"
                            type="text"
                            placeholder="cth. TK Islam Terpadu Al-Azhar"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-800 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        />
                    </div>

                    <!-- Diterima di Jenjang / Kelas -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Diterima di Jenjang / Kelas
                        </label>
                        <input
                            v-model="form.diterima_di_jenjang"
                            type="text"
                            placeholder="cth. SDIT / Kelas 1"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-800 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        />
                    </div>

                    <!-- Tanggal Diterima -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Tanggal Diterima
                        </label>
                        <input
                            v-model="form.tanggal_diterima"
                            type="date"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-800 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer"
                        />
                    </div>

                    <!-- Sumber Informasi PSB -->
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Sumber Informasi PSB
                        </label>
                        <input
                            v-model="form.info_psb"
                            type="text"
                            placeholder="cth. Brosur, Rekomendasi Teman/Keluarga, Instagram, dll."
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200/90 text-xs sm:text-sm font-semibold text-slate-800 placeholder-slate-400 bg-white shadow-2xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        />
                    </div>
                </div>
            </div>

            <!-- 2. Status Keaktifan Siswa -->
            <div class="pt-4 border-t border-slate-100">
                <h5 class="text-xs font-bold text-emerald-900 uppercase tracking-wider mb-4 pb-2 border-b border-emerald-100 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[17px] text-emerald-700">verified_user</span>
                    <span>Status Keaktifan Siswa</span>
                </h5>

                <div class="space-y-4">
                    <!-- Toggle Status Keaktifan (Aktif vs Tidak Aktif vs Lulus) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Pilih Status Keaktifan
                        </label>
                        <div class="inline-flex p-1 rounded-2xl bg-slate-100 border border-slate-200/90 gap-1">
                            <button
                                type="button"
                                @click="form.status_keaktifan = 'Aktif'"
                                :class="[
                                    'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                    form.status_keaktifan === 'Aktif'
                                        ? 'bg-emerald-900 text-yellow-200 shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>Aktif</span>
                            </button>
                            <button
                                type="button"
                                @click="form.status_keaktifan = 'Tidak Aktif'"
                                :class="[
                                    'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                    form.status_keaktifan === 'Tidak Aktif'
                                        ? 'bg-rose-700 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="w-2 h-2 rounded-full bg-rose-300"></span>
                                <span>Tidak Aktif</span>
                            </button>
                            <button
                                type="button"
                                @click="form.status_keaktifan = 'Lulus'"
                                :class="[
                                    'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer',
                                    form.status_keaktifan === 'Lulus'
                                        ? 'bg-blue-700 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span class="w-2 h-2 rounded-full bg-blue-300"></span>
                                <span>Lulus</span>
                            </button>
                        </div>
                    </div>

                    <!-- Keterangan & Tanggal Tidak Aktif jika Tidak Aktif dipilih -->
                    <div
                        v-if="form.status_keaktifan === 'Tidak Aktif'"
                        class="p-4 rounded-2xl bg-rose-50/60 border border-rose-200/80 grid grid-cols-1 md:grid-cols-2 gap-4 animate-in fade-in"
                    >
                        <div>
                            <label class="block text-[11px] font-bold text-rose-900 uppercase tracking-wider mb-1">
                                Keterangan Tidak Aktif
                            </label>
                            <select
                                v-model="form.keterangan_tidak_aktif"
                                class="w-full px-3.5 py-2 rounded-xl border border-rose-200 text-xs sm:text-sm font-semibold text-rose-950 bg-white shadow-2xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                            >
                                <option value="">-- Pilih Keterangan --</option>
                                <option value="Mutasi Keluar">Mutasi Keluar</option>
                                <option value="Dikeluarkan">Dikeluarkan</option>
                                <option value="Putus Sekolah">Putus Sekolah</option>
                                <option value="Meninggal">Meninggal</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-rose-900 uppercase tracking-wider mb-1">
                                Tanggal Tidak Aktif
                            </label>
                            <input
                                v-model="form.tanggal_tidak_aktif"
                                type="date"
                                class="w-full px-3.5 py-2 rounded-xl border border-rose-200 text-xs sm:text-sm font-semibold text-rose-950 bg-white shadow-2xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons di Bawah Form -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    @click="cancelEdit"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                    :disabled="form.processing"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="saveChanges"
                    class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>
    </div>
</template>

