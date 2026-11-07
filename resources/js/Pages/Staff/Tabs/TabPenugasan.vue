<script setup>
import { ref, reactive, computed, inject } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { formatTanggalIndo } from '../staffHelpers';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    departments: {
        type: Array,
        default: () => [],
    },
    schoolUnits: {
        type: Array,
        default: () => [],
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    canViewPenugasan: {
        type: Boolean,
        default: false,
    },
    canCreatePenugasan: {
        type: Boolean,
        default: false,
    },
    canUpdatePenugasan: {
        type: Boolean,
        default: false,
    },
    canDeletePenugasan: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['managingChanged']);

const page = usePage();
const openPreview = inject('openPreview', () => {});
const requestDeleteConfirm = inject('requestDeleteConfirm', () => {});
const showToast = inject('showToast', () => {});

// State & fungsi Kelola Riwayat Penugasan & Jabatan (Tab 5)
const isManagingJabatan = ref(false);
const isJabatanModalOpen = ref(false);
const modalJabatanMode = ref('create'); // 'create' | 'edit'
const isSavingJabatan = ref(false);

const jabatanModalForm = reactive({
    id: null,
    unit_kerja: '',
    jabatan: '',
    jenis_kepegawaian: '',
    no_sk: '',
    tmt_jabatan: '',
    tst_jabatan: '',
    custom_jabatan: false,
});

const currentJabatanList = computed(() => {
    return props.employee.riwayat_jabatan || [];
});

const bidangList = computed(() => {
    return (props.departments || []).map((d) => d.name);
});

const allMasterUnitNames = computed(() => {
    return bidangList.value;
});

function getJabatanOptionsForUnit(unitName) {
    if (!unitName) return [];
    const cleanUnit = unitName.trim().toLowerCase();

    const foundDept = (props.departments || []).find((d) => d.name && d.name.trim().toLowerCase() === cleanUnit);
    if (foundDept && Array.isArray(foundDept.positions) && foundDept.positions.length > 0) {
        return foundDept.positions;
    }

    const foundSchool = (props.schoolUnits || []).find((u) => u.name && u.name.trim().toLowerCase() === cleanUnit);
    if (foundSchool && Array.isArray(foundSchool.positions) && foundSchool.positions.length > 0) {
        return foundSchool.positions;
    }

    return [];
}

function onModalUnitKerjaChange() {
    const available = getJabatanOptionsForUnit(jabatanModalForm.unit_kerja);
    if (jabatanModalForm.jabatan && available.length > 0 && !available.includes(jabatanModalForm.jabatan)) {
        jabatanModalForm.jabatan = '';
    }
}

function startManageJabatan() {
    if (props.isEditing) {
        alert('Anda sedang dalam mode edit Informasi Umum. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum mengelola riwayat jabatan.');
        return;
    }
    isManagingJabatan.value = true;
    emit('managingChanged', true);
}

function finishManageJabatan() {
    isManagingJabatan.value = false;
    emit('managingChanged', false);
}

function openAddJabatanModal() {
    modalJabatanMode.value = 'create';
    jabatanModalForm.id = null;
    jabatanModalForm.unit_kerja = '';
    jabatanModalForm.jabatan = '';
    jabatanModalForm.jenis_kepegawaian = '';
    jabatanModalForm.no_sk = '';
    jabatanModalForm.tmt_jabatan = '';
    jabatanModalForm.tst_jabatan = '';
    jabatanModalForm.custom_jabatan = false;
    isJabatanModalOpen.value = true;
}

function openEditJabatanModal(record) {
    modalJabatanMode.value = 'edit';
    jabatanModalForm.id = record.id || null;
    jabatanModalForm.unit_kerja = record.unit_kerja || '';
    jabatanModalForm.jabatan = record.jabatan || '';
    jabatanModalForm.jenis_kepegawaian = record.jenis_kepegawaian || '';
    jabatanModalForm.no_sk = record.no_sk || '';
    jabatanModalForm.tmt_jabatan = record.tmt_jabatan || '';
    jabatanModalForm.tst_jabatan = record.tst_jabatan || '';
    const options = getJabatanOptionsForUnit(record.unit_kerja);
    jabatanModalForm.custom_jabatan = options.length > 0 && !!record.jabatan && !options.includes(record.jabatan);
    isJabatanModalOpen.value = true;
}

function closeJabatanModal() {
    isJabatanModalOpen.value = false;
}

function submitJabatanModal() {
    if (!jabatanModalForm.unit_kerja && !jabatanModalForm.jabatan) {
        showToast('error', 'Mohon isi Unit Kerja atau Nama Jabatan.', 'Validasi Form');
        return;
    }

    if (!jabatanModalForm.id && !props.canCreatePenugasan) {
        showToast('error', 'Anda tidak memiliki hak akses untuk menambah riwayat penugasan.');
        return;
    }
    if (jabatanModalForm.id && !props.canUpdatePenugasan) {
        showToast('error', 'Anda tidak memiliki hak akses untuk mengubah riwayat penugasan.');
        return;
    }

    isSavingJabatan.value = true;
    router.post(`/staff/${props.employee.id}/positions`, {
        id: jabatanModalForm.id || null,
        unit_kerja: jabatanModalForm.unit_kerja,
        jabatan: jabatanModalForm.jabatan,
        jenis_kepegawaian: jabatanModalForm.jenis_kepegawaian,
        no_sk: jabatanModalForm.no_sk,
        tmt_jabatan: jabatanModalForm.tmt_jabatan,
        tst_jabatan: jabatanModalForm.tst_jabatan,
    }, {
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            isJabatanModalOpen.value = false;
            isSavingJabatan.value = false;
            isManagingJabatan.value = false;
            emit('managingChanged', false);
            showToast('success', page.props.flash?.success || 'Riwayat penugasan & jabatan berhasil disimpan.');
        },
        onError: () => {
            isSavingJabatan.value = false;
            showToast('error', page.props.flash?.error || 'Gagal menyimpan riwayat penugasan & jabatan.');
        }
    });
}

function deleteJabatanRecord(idxOrItem) {
    if (!props.canDeletePenugasan) {
        showToast('error', 'Anda tidak memiliki hak akses untuk menghapus riwayat penugasan.');
        return;
    }

    const list = currentJabatanList.value || [];
    const item = typeof idxOrItem === 'object' ? idxOrItem : list[idxOrItem];
    if (!item) return;

    requestDeleteConfirm({
        title: 'Hapus Riwayat Penugasan & Jabatan',
        message: 'Apakah Anda yakin ingin menghapus catatan riwayat penugasan ini?',
        itemLabel: item.jabatan ? `${item.jabatan} - ${item.unit_kerja || ''}` : item.unit_kerja || '',
        onConfirm: () => {
            if (item.id) {
                isSavingJabatan.value = true;
                router.delete(`/staff/${props.employee.id}/positions/${item.id}`, {
                    preserveScroll: true,
                    only: ['employee', 'flash'],
                    onSuccess: () => {
                        isSavingJabatan.value = false;
                        isManagingJabatan.value = false;
                        emit('managingChanged', false);
                        showToast('success', page.props.flash?.success || 'Riwayat penugasan berhasil dihapus.');
                    },
                    onError: () => {
                        isSavingJabatan.value = false;
                        showToast('error', page.props.flash?.error || 'Gagal menghapus riwayat penugasan.');
                    }
                });
            } else {
                isManagingJabatan.value = false;
                emit('managingChanged', false);
            }
        }
    });
}
</script>

<template>
    <div v-if="canViewPenugasan" class="space-y-6 mb-8">
        <!-- Banner jika user tidak memiliki izin edit penugasan -->
        <div v-if="!canUpdatePenugasan" class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px] text-amber-700 shrink-0">lock</span>
            <div>
                <strong>Mode Lihat Saja:</strong> Anda tidak memiliki hak akses untuk mengubah Data Penugasan. Data pada tab ini terkunci dan hanya dapat diubah oleh role yang memiliki hak akses Ubah Data Penugasan.
            </div>
        </div>

        <!-- WADAH UTAMA: RIWAYAT PENUGASAN & JABATAN -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Riwayat Penugasan &amp; Jabatan
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ currentJabatanList.length }} Jabatan
                        </span>
                    </div>
                    <div v-if="isEditing" class="mt-1">
                        <span class="text-[10px] sm:text-[10.5px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px] sm:text-[13px]">lock</span>
                            <span>Terkunci saat mengedit informasi umum</span>
                        </span>
                    </div>
                </div>

                <!-- Tombol Kelola di Pojok Kanan Atas Section -->
                <div v-if="canUpdatePenugasan || canCreatePenugasan || canDeletePenugasan" class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="!isManagingJabatan"
                        type="button"
                        @click="startManageJabatan"
                        :disabled="isEditing"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                            isEditing
                                ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                        ]"
                        :title="isEditing ? 'Selesaikan atau batalkan mode edit terlebih dahulu untuk mengelola riwayat jabatan' : 'Kelola riwayat penugasan & jabatan'"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                            {{ isEditing ? 'lock' : 'tune' }}
                        </span>
                        <span>Kelola</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="finishManageJabatan"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                        title="Selesai mengelola riwayat jabatan"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">check</span>
                        <span>Selesai Kelola</span>
                    </button>
                </div>
            </div>

            <!-- 1. TAMPILAN DESKTOP (TABLE VIEW) -->
            <div class="hidden md:block">
                <!-- Tombol Tambah Riwayat di Atas Tabel -->
                <div v-if="isManagingJabatan" class="mb-3.5 flex items-center justify-between gap-3">
                    <button
                        v-if="canCreatePenugasan"
                        type="button"
                        @click="openAddJabatanModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat Jabatan</span>
                    </button>
                    <span class="text-xs text-slate-500 font-medium">
                        Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data
                    </span>
                </div>

                <!-- Table Container -->
                <div v-if="currentJabatanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th v-if="isManagingJabatan && canDeletePenugasan" class="px-3 py-3 text-center w-14">Hapus</th>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Unit Kerja</th>
                                <th class="px-4 py-3 text-left">Jabatan</th>
                                <th class="px-4 py-3 text-left">Jenis Kepegawaian</th>
                                <th class="px-4 py-3 text-left">No. SK</th>
                                <th class="px-4 py-3 text-left">TMT Jabatan</th>
                                <th class="px-4 py-3 text-left">TST Jabatan</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-center">Berkas SK</th>
                                <th v-if="isManagingJabatan && canUpdatePenugasan" class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(pos, idx) in currentJabatanList"
                                :key="pos.id || idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td v-if="isManagingJabatan && canDeletePenugasan" class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="deleteJabatanRecord(idx)"
                                        class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer border border-transparent hover:border-rose-200"
                                        title="Hapus riwayat penugasan ini"
                                        :disabled="isSavingJabatan"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>

                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                        {{ pos.unit_kerja || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-slate-900">{{ pos.jabatan || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-700 font-medium">{{ pos.jenis_kepegawaian || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-800 font-medium font-mono text-xs">{{ pos.no_sk || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-700">
                                    {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                </td>
                                <td class="px-4 py-3.5 text-slate-700">
                                    {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        v-if="!pos.tst_jabatan"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Menjabat
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                    >
                                        Selesai
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div v-if="pos.document" class="inline-flex items-center justify-center">
                                        <button
                                            type="button"
                                            @click="openPreview(pos.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer shadow-2xs"
                                            title="Lihat / Preview SK Penugasan"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat SK</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>

                                <td v-if="isManagingJabatan && canUpdatePenugasan" class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditJabatanModal(pos)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                        title="Edit riwayat ini"
                                        :disabled="isSavingJabatan"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                    <p class="text-sm text-slate-500">Belum ada riwayat jabatan untuk pegawai ini</p>
                </div>
            </div>

            <!-- 2. TAMPILAN MOBILE (CARD VIEW) -->
            <div class="md:hidden">
                <!-- Tombol Tambah Riwayat -->
                <div v-if="isManagingJabatan && canCreatePenugasan" class="mb-4">
                    <button
                        type="button"
                        @click="openAddJabatanModal"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat Jabatan</span>
                    </button>
                </div>

                <!-- Kumpulan Card Riwayat Jabatan -->
                <div class="space-y-3">
                    <div
                        v-for="(pos, idx) in currentJabatanList"
                        :key="pos.id || idx"
                        class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                {{ pos.unit_kerja || 'Unit Kerja -' }}
                            </span>
                            <span
                                v-if="!pos.tst_jabatan"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                Menjabat
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                            >
                                Selesai
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Jabatan</label>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">{{ pos.jabatan || '-' }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Jenis Kepegawaian</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ pos.jenis_kepegawaian || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">No. SK</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5 font-mono">{{ pos.no_sk || '-' }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TMT Jabatan</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">
                                        {{ pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TST Jabatan</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">
                                        {{ pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif' }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="pos.document" class="pt-1">
                                <button
                                    type="button"
                                    @click="openPreview(pos.document)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs"
                                >
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Lihat SK Penugasan</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="isManagingJabatan && (canDeletePenugasan || canUpdatePenugasan)" class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button
                                v-if="canDeletePenugasan"
                                type="button"
                                @click="deleteJabatanRecord(idx)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                :disabled="isSavingJabatan"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                <span>Hapus</span>
                            </button>
                            <button
                                v-if="canUpdatePenugasan"
                                type="button"
                                @click="openEditJabatanModal(pos)"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs ml-auto"
                                :disabled="isSavingJabatan"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="currentJabatanList.length === 0" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                        <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                        <p class="text-sm text-slate-500">Belum ada riwayat jabatan untuk pegawai ini</p>
                    </div>
                </div>
            </div>

            <!-- POP-UP MODAL TAMBAH & EDIT RIWAYAT JABATAN -->
            <Teleport to="body">
                <div
                    v-if="isJabatanModalOpen"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                    @click.self="closeJabatanModal"
                >
                    <div class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-200">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ modalJabatanMode === 'create' ? 'add_circle' : 'edit' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">
                                        {{ modalJabatanMode === 'create' ? 'Tambah Riwayat Jabatan' : 'Edit Riwayat Jabatan' }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ modalJabatanMode === 'create' ? 'Tambahkan riwayat penugasan atau jabatan baru' : 'Perbarui data catatan penugasan & jabatan' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeJabatanModal"
                                class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitJabatanModal" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Unit Kerja <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="jabatanModalForm.unit_kerja"
                                    @change="onModalUnitKerjaChange"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option value="" disabled>-- Pilih Unit Kerja --</option>
                                    <option v-for="dept in bidangList" :key="'modal_dept_' + dept" :value="dept">
                                        {{ dept }}
                                    </option>
                                    <option
                                        v-if="jabatanModalForm.unit_kerja && !allMasterUnitNames.includes(jabatanModalForm.unit_kerja)"
                                        :value="jabatanModalForm.unit_kerja"
                                    >
                                        {{ jabatanModalForm.unit_kerja }} (Lainnya)
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jabatan <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <select
                                        v-if="!jabatanModalForm.custom_jabatan && getJabatanOptionsForUnit(jabatanModalForm.unit_kerja).length > 0"
                                        v-model="jabatanModalForm.jabatan"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    >
                                        <option value="" disabled>-- Pilih Jabatan --</option>
                                        <option
                                            v-for="jbt in getJabatanOptionsForUnit(jabatanModalForm.unit_kerja)"
                                            :key="'modal_jbt_' + jbt"
                                            :value="jbt"
                                        >
                                            {{ jbt }}
                                        </option>
                                        <option
                                            v-if="jabatanModalForm.jabatan && !getJabatanOptionsForUnit(jabatanModalForm.unit_kerja).includes(jabatanModalForm.jabatan)"
                                            :value="jabatanModalForm.jabatan"
                                        >
                                            {{ jabatanModalForm.jabatan }} (Lainnya)
                                        </option>
                                    </select>

                                    <input
                                        v-else
                                        v-model="jabatanModalForm.jabatan"
                                        type="text"
                                        required
                                        :placeholder="jabatanModalForm.unit_kerja ? 'Ketik nama jabatan' : 'Pilih unit kerja dahulu'"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />

                                    <button
                                        v-if="getJabatanOptionsForUnit(jabatanModalForm.unit_kerja).length > 0"
                                        type="button"
                                        @click="jabatanModalForm.custom_jabatan = !jabatanModalForm.custom_jabatan"
                                        class="p-2.5 rounded-xl text-slate-600 hover:text-emerald-800 hover:bg-emerald-50 border border-slate-200 transition-colors shrink-0 cursor-pointer"
                                        :title="jabatanModalForm.custom_jabatan ? 'Kembali ke pilihan dropdown jabatan' : 'Ketik nama jabatan secara manual'"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">
                                            {{ jabatanModalForm.custom_jabatan ? 'list' : 'edit' }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Jenis Kepegawaian
                                    </label>
                                    <input
                                        v-model="jabatanModalForm.jenis_kepegawaian"
                                        type="text"
                                        placeholder="Contoh: Tenaga Pendidik"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Nomor SK
                                    </label>
                                    <input
                                        v-model="jabatanModalForm.no_sk"
                                        type="text"
                                        placeholder="Contoh: 012/SK-DIR/VII/2023"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 font-mono"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        TMT Jabatan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="jabatanModalForm.tmt_jabatan"
                                        type="date"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        TST Jabatan
                                    </label>
                                    <input
                                        v-model="jabatanModalForm.tst_jabatan"
                                        type="date"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <span class="text-[10px] text-slate-400 mt-1 block">Kosongkan jika masih aktif / menjabat</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-6">
                                <button
                                    type="button"
                                    @click="closeJabatanModal"
                                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="isSavingJabatan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="isSavingJabatan"
                                >
                                    <span v-if="isSavingJabatan" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span>{{ isSavingJabatan ? 'Menyimpan...' : (modalJabatanMode === 'create' ? 'Simpan Riwayat' : 'Simpan Perubahan') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>

