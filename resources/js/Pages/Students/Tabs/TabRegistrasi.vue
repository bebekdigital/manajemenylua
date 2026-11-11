<script setup>
import { ref, reactive, watch, inject } from 'vue';
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

    const isNonActive = (props.student.status_keaktifan || props.student.student_status || '').toLowerCase().includes('tidak')
        || (props.student.student_status || '').toLowerCase() === 'tidak_aktif'
        || (props.student.student_status || '').toLowerCase() === 'dropout';

    form.status_keaktifan = isNonActive ? 'Tidak Aktif' : 'Aktif';
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
                        :class="(student.status_keaktifan === 'Tidak Aktif' || student.student_status === 'tidak_aktif') ? 'bg-rose-500' : 'bg-emerald-600'"
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
                                v-if="student.keterangan_tidak_aktif || student.ket_tidak_aktif"
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
                            <span v-if="student.tanggal_tidak_aktif" class="inline-flex items-center gap-1.5">
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
                <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 inline-block"></span>
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Riwayat Keaktifan Siswa</h4>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-2xs relative">
                    <table class="w-full text-left text-xs sm:text-sm border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50">
                                <th class="p-3.5 font-bold text-slate-700 min-w-[160px] sticky left-0 z-10 border-r border-slate-200 bg-slate-50">Unit</th>
                                <th v-for="header in timelineHeaders" :key="header.key" class="p-3.5 font-bold text-slate-700 text-center">
                                    {{ header.ta }} <br/>
                                    <span class="text-xs text-slate-500 font-medium">{{ header.semester }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in timelineRows" :key="row.unit" class="hover:bg-slate-50/60">
                                <td class="p-3.5 text-slate-800 font-bold bg-white sticky left-0 z-10 border-r border-slate-100">
                                    {{ row.unit }}
                                </td>
                                <td v-for="header in timelineHeaders" :key="header.key" class="p-3.5 text-center min-w-[160px]">
                                    <template v-if="row.data[header.key]">
                                        <span v-if="row.data[header.key].status === 'aktif'" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-950 border border-emerald-300 shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Kelas {{ row.data[header.key].kelas || 'Aktif' }}
                                        </span>
                                        <div v-else class="flex flex-col items-center justify-center">
                                            <span class="text-rose-700 font-bold capitalize text-xs bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full">
                                                {{ row.data[header.key].status.replace('_', ' ') }}
                                            </span>
                                            <span v-if="row.data[header.key].keterangan" class="text-[10px] text-rose-600 mt-1 max-w-[140px] truncate" :title="row.data[header.key].keterangan">
                                                ({{ row.data[header.key].keterangan }})
                                            </span>
                                        </div>
                                    </template>
                                    <span v-else class="text-slate-300 text-xs">-</span>
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
                    <!-- Toggle Status Keaktifan (Aktif vs Tidak Aktif) -->
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
                                <option value="Lulus">Lulus</option>
                                <option value="Mutasi / Pindah Sekolah">Mutasi / Pindah Sekolah</option>
                                <option value="Dikeluarkan">Dikeluarkan</option>
                                <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                                <option value="Meninggal Dunia">Meninggal Dunia</option>
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

