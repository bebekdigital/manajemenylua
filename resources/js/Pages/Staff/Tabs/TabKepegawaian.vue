<script setup>
import { ref, reactive, computed, inject } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    formatTanggalIndo,
    hitungMasaKerja,
    getJenjangBadgeClass,
    getStatusStyle,
} from '../staffHelpers';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    isAnyManaging: {
        type: Boolean,
        default: false,
    },
    canViewKepegawaian: {
        type: Boolean,
        default: false,
    },
    canCreateKepegawaian: {
        type: Boolean,
        default: false,
    },
    canUpdateKepegawaian: {
        type: Boolean,
        default: false,
    },
    canDeleteKepegawaian: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['startEdit', 'cancelEdit', 'saveChanges', 'managingChanged']);

const page = usePage();
const openPreview = inject('openPreview', () => {});
const requestDeleteConfirm = inject('requestDeleteConfirm', () => {});
const showToast = inject('showToast', () => {});

// State Kelola Riwayat Jenjang Kepegawaian (Wadah 2)
const isManagingRiwayat = ref(false);
const isRiwayatModalOpen = ref(false);
const modalRiwayatMode = ref('create');
const isSavingRiwayat = ref(false);

const riwayatModalForm = reactive({
    id: null,
    jenjang_kepegawaian: '',
    no_sk: '',
    tmt_kenaikan_jenjang: '',
    tst_kenaikan_jenjang: '',
});

const currentRiwayatList = computed(() => {
    return props.employee.riwayat_kepegawaian || [];
});

function startManageRiwayat() {
    if (props.isEditing) {
        alert('Anda sedang dalam mode edit Informasi Umum. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum mengelola riwayat jenjang.');
        return;
    }
    isManagingRiwayat.value = true;
    emit('managingChanged', true);
}

function finishManageRiwayat() {
    isManagingRiwayat.value = false;
    emit('managingChanged', false);
}

function openAddRiwayatModal() {
    modalRiwayatMode.value = 'create';
    riwayatModalForm.id = null;
    riwayatModalForm.jenjang_kepegawaian = '';
    riwayatModalForm.no_sk = '';
    riwayatModalForm.tmt_kenaikan_jenjang = '';
    riwayatModalForm.tst_kenaikan_jenjang = '';
    isRiwayatModalOpen.value = true;
}

function openEditRiwayatModal(record) {
    modalRiwayatMode.value = 'edit';
    riwayatModalForm.id = record.id || null;
    riwayatModalForm.jenjang_kepegawaian = record.jenjang_kepegawaian || '';
    riwayatModalForm.no_sk = record.no_sk || '';
    riwayatModalForm.tmt_kenaikan_jenjang = record.tmt_kenaikan_jenjang || '';
    riwayatModalForm.tst_kenaikan_jenjang = record.tst_kenaikan_jenjang || record.tst_jenjang || '';
    isRiwayatModalOpen.value = true;
}

function closeRiwayatModal() {
    isRiwayatModalOpen.value = false;
}

function submitRiwayatModal() {
    if (!riwayatModalForm.jenjang_kepegawaian && !riwayatModalForm.tmt_kenaikan_jenjang) {
        showToast('error', 'Mohon pilih Jenjang Kepegawaian atau isi TMT Kenaikan Jenjang.', 'Validasi Form');
        return;
    }

    if (!riwayatModalForm.id && !props.canCreateKepegawaian) {
        showToast('error', 'Anda tidak memiliki hak akses untuk menambah riwayat jenjang kepegawaian.');
        return;
    }
    if (riwayatModalForm.id && !props.canUpdateKepegawaian) {
        showToast('error', 'Anda tidak memiliki hak akses untuk mengubah riwayat jenjang kepegawaian.');
        return;
    }

    isSavingRiwayat.value = true;
    router.post(`/staff/${props.employee.id}/records`, {
        id: riwayatModalForm.id || null,
        jenjang_kepegawaian: riwayatModalForm.jenjang_kepegawaian,
        no_sk: riwayatModalForm.no_sk,
        tmt_kenaikan_jenjang: riwayatModalForm.tmt_kenaikan_jenjang,
        tst_kenaikan_jenjang: riwayatModalForm.tst_kenaikan_jenjang,
    }, {
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            isRiwayatModalOpen.value = false;
            isSavingRiwayat.value = false;
            isManagingRiwayat.value = false;
            emit('managingChanged', false);
            showToast('success', page.props.flash?.success || 'Riwayat jenjang kepegawaian berhasil disimpan.');
        },
        onError: () => {
            isSavingRiwayat.value = false;
            showToast('error', page.props.flash?.error || 'Gagal menyimpan riwayat jenjang kepegawaian.');
        }
    });
}

function deleteRiwayatRecord(idxOrItem) {
    if (!props.canDeleteKepegawaian) {
        showToast('error', 'Anda tidak memiliki hak akses untuk menghapus riwayat jenjang kepegawaian.');
        return;
    }

    const list = currentRiwayatList.value;
    const item = typeof idxOrItem === 'object' ? idxOrItem : list[idxOrItem];
    if (!item) return;

    requestDeleteConfirm({
        title: 'Hapus Riwayat Jenjang Kepegawaian',
        message: 'Apakah Anda yakin ingin menghapus catatan riwayat jenjang kepegawaian ini?',
        itemLabel: item.jenjang_kepegawaian ? `Jenjang: ${item.jenjang_kepegawaian}${item.no_sk ? ' (No. SK: ' + item.no_sk + ')' : ''}` : '',
        onConfirm: () => {
            if (item.id) {
                isSavingRiwayat.value = true;
                router.delete(`/staff/${props.employee.id}/records/${item.id}`, {
                    preserveScroll: true,
                    only: ['employee', 'flash'],
                    onSuccess: () => {
                        isSavingRiwayat.value = false;
                        isManagingRiwayat.value = false;
                        emit('managingChanged', false);
                        showToast('success', page.props.flash?.success || 'Riwayat jenjang kepegawaian berhasil dihapus.');
                    },
                    onError: () => {
                        isSavingRiwayat.value = false;
                        showToast('error', page.props.flash?.error || 'Gagal menghapus riwayat jenjang kepegawaian.');
                    }
                });
            } else {
                isManagingRiwayat.value = false;
                emit('managingChanged', false);
            }
        }
    });
}
</script>

<template>
    <div v-if="canViewKepegawaian" class="space-y-6 mb-8">
        <!-- Banner jika user tidak memiliki izin edit kepegawaian -->
        <div v-if="!canUpdateKepegawaian" class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[18px] text-amber-700 shrink-0">lock</span>
            <div>
                <strong>Mode Lihat Saja:</strong> Anda tidak memiliki hak akses untuk mengubah Data Kepegawaian. Data pada tab ini terkunci dan hanya dapat diubah oleh role yang memiliki hak akses Ubah Data Kepegawaian.
            </div>
        </div>

        <!-- WADAH 1: INFORMASI UMUM KEPEGAWAIAN -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                            Informasi Umum Kepegawaian
                        </h4>
                    </div>
                    <div v-if="isAnyManaging" class="mt-1">
                        <span class="text-[10px] sm:text-[10.5px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px] sm:text-[13px]">lock</span>
                            <span>Terkunci saat mengelola riwayat</span>
                        </span>
                    </div>
                </div>

                <!-- Tombol Edit / Simpan di Pojok Kanan Atas Section -->
                <div v-if="canUpdateKepegawaian" class="flex items-center gap-2 shrink-0">
                    <!-- Saat View Mode: Tombol Edit Data -->
                    <button
                        v-if="!isEditing"
                        type="button"
                        @click="$emit('startEdit')"
                        :disabled="isAnyManaging"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                            isAnyManaging
                                ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                        ]"
                        :title="isAnyManaging ? 'Selesaikan kelola riwayat terlebih dahulu untuk mengedit informasi umum' : 'Edit informasi umum kepegawaian'"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                            {{ isAnyManaging ? 'lock' : 'edit_note' }}
                        </span>
                        <span>Edit Data</span>
                    </button>

                    <!-- Saat Edit Mode: Tombol Batal & Simpan -->
                    <template v-else>
                        <button
                            type="button"
                            @click="$emit('cancelEdit')"
                            class="px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs whitespace-nowrap shrink-0"
                            :disabled="form.processing"
                            title="Batalkan perubahan"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="$emit('saveChanges')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-5 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all whitespace-nowrap shrink-0"
                            :disabled="form.processing"
                            title="Simpan perubahan informasi umum kepegawaian"
                        >
                            <span v-if="form.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                            <span v-else class="material-symbols-outlined text-[16px]">save</span>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- View Mode Data Statis -->
            <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <!-- TMT Pengangkatan -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TMT Pengangkatan</label>
                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                        {{ employee.formatted_tmt_pengangkatan || formatTanggalIndo(employee.tmt_pengangkatan) || '-' }}
                    </p>
                </div>

                <!-- TST Pengangkatan -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">TST Pengangkatan</label>
                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                        {{ employee.formatted_tst_pengangkatan || formatTanggalIndo(employee.tst_pengangkatan) || 'Sekarang / Tetap' }}
                    </p>
                </div>

                <!-- Masa Kerja -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Masa Kerja</label>
                    <p class="text-sm sm:text-base font-semibold text-emerald-800 mt-1">
                        {{ hitungMasaKerja(employee.tmt_pengangkatan, employee.tst_pengangkatan) || employee.masa_kerja || '-' }}
                    </p>
                </div>

                <!-- Status Keaktifan -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Status Keaktifan</label>
                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs shadow-2xs',
                                getStatusStyle(employee.status_keaktifan).wrapper
                            ]"
                        >
                            <span
                                :class="[
                                    'w-2 h-2 rounded-full shrink-0',
                                    getStatusStyle(employee.status_keaktifan).dot
                                ]"
                            ></span>
                            <span>{{ employee.status_keaktifan || 'Aktif' }}</span>
                        </span>
                        <span v-if="employee.keterangan_tidak_aktif" class="text-xs text-slate-500 font-medium">
                            ({{ employee.keterangan_tidak_aktif }})
                        </span>
                    </div>
                </div>

                <!-- Keaktifan Dapodik -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Keaktifan Dapodik</label>
                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                        {{ employee.keaktifan_dapodik || '-' }}
                    </p>
                </div>

                <!-- Unit Keaktifan Dapodik -->
                <div>
                    <label class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Keaktifan Dapodik</label>
                    <p class="text-sm sm:text-base font-semibold text-slate-900 mt-1">
                        {{ employee.unit_keaktifan_dapodik || '-' }}
                    </p>
                </div>
            </div>

            <!-- Edit Mode Data Statis -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">TMT Pengangkatan</label>
                    <input
                        v-model="form.tmt_pengangkatan"
                        type="date"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">TST Pengangkatan</label>
                    <input
                        v-model="form.tst_pengangkatan"
                        type="date"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                    />
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan jika aktif / tetap bekerja</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Keaktifan</label>
                    <select
                        v-model="form.status_keaktifan"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                    >
                        <option value="Aktif">Aktif</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                        <option value="Pensiun">Pensiun</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keterangan Tidak Aktif</label>
                    <input
                        v-model="form.keterangan_tidak_aktif"
                        type="text"
                        placeholder="Keterangan jika non-aktif / pensiun"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Keaktifan Dapodik</label>
                    <select
                        v-model="form.keaktifan_dapodik"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                    >
                        <option value="">-- Pilih Status Dapodik --</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Unit Keaktifan Dapodik</label>
                    <input
                        v-model="form.unit_keaktifan_dapodik"
                        type="text"
                        placeholder="Unit yang terdaftar pada Dapodik"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                    />
                </div>
            </div>
        </div>

        <!-- WADAH 2: RIWAYAT JENJANG KEPEGAWAIAN -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Riwayat Jenjang Kepegawaian
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ currentRiwayatList.length }} Catatan
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
                <div v-if="canUpdateKepegawaian || canCreateKepegawaian || canDeleteKepegawaian" class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="!isManagingRiwayat"
                        type="button"
                        @click="startManageRiwayat"
                        :disabled="isEditing"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                            isEditing
                                ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                        ]"
                        :title="isEditing ? 'Selesaikan atau batalkan mode edit terlebih dahulu untuk mengelola riwayat jenjang' : 'Kelola riwayat jenjang kepegawaian'"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                            {{ isEditing ? 'lock' : 'tune' }}
                        </span>
                        <span>Kelola</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="finishManageRiwayat"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                        title="Selesai mengelola riwayat jenjang"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">check</span>
                        <span>Selesai Kelola</span>
                    </button>
                </div>
            </div>

            <!-- TAMPILAN DESKTOP (TABLE VIEW) -->
            <div class="hidden md:block">
                <!-- Tombol Tambah Riwayat di Atas Tabel -->
                <div v-if="isManagingRiwayat" class="mb-3.5 flex items-center justify-between gap-3">
                    <button
                        v-if="canCreateKepegawaian"
                        type="button"
                        @click="openAddRiwayatModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat</span>
                    </button>
                    <span class="text-xs text-slate-500 font-medium">
                        Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data
                    </span>
                </div>

                <!-- Table Container -->
                <div v-if="currentRiwayatList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th v-if="isManagingRiwayat && canDeleteKepegawaian" class="px-3 py-3 text-center w-14">Hapus</th>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Jenjang Kepegawaian</th>
                                <th class="px-4 py-3 text-left">No. SK</th>
                                <th class="px-4 py-3 text-left">TMT Kenaikan Jenjang</th>
                                <th class="px-4 py-3 text-left">TST Kenaikan Jenjang</th>
                                <th class="px-4 py-3 text-left">Status Masa Berlaku</th>
                                <th class="px-4 py-3 text-center">Berkas SK</th>
                                <th v-if="isManagingRiwayat && canUpdateKepegawaian" class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(record, idx) in currentRiwayatList"
                                :key="record.id || idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td v-if="isManagingRiwayat && canDeleteKepegawaian" class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="deleteRiwayatRecord(idx)"
                                        class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer border border-transparent hover:border-rose-200"
                                        title="Hapus riwayat jenjang ini"
                                        :disabled="isSavingRiwayat"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>

                                <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs shadow-2xs', getJenjangBadgeClass(record.jenjang_kepegawaian)]">
                                        {{ record.jenjang_kepegawaian || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-800 font-medium font-mono text-xs">
                                    {{ record.no_sk || '-' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="!record.tst_kenaikan_jenjang && !record.tst_jenjang"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Aktif Berlaku
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                                    >
                                        Selesai
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="record.document" class="inline-flex items-center justify-center">
                                        <button
                                            type="button"
                                            @click="openPreview(record.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat / Preview SK Jenjang"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat SK</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>

                                <td v-if="isManagingRiwayat && canUpdateKepegawaian" class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditRiwayatModal(record)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                        title="Edit riwayat ini"
                                        :disabled="isSavingRiwayat"
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
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">work_history</span>
                    <p class="text-sm text-slate-500">Belum ada riwayat jenjang kepegawaian</p>
                </div>
            </div>

            <!-- TAMPILAN MOBILE (CARD VIEW) -->
            <div class="md:hidden">
                <!-- Tombol Tambah Riwayat -->
                <div v-if="isManagingRiwayat && canCreateKepegawaian" class="mb-4">
                    <button
                        type="button"
                        @click="openAddRiwayatModal"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat</span>
                    </button>
                </div>

                <!-- Kumpulan Card Riwayat Jenjang -->
                <div class="space-y-3">
                    <div
                        v-for="(record, idx) in currentRiwayatList"
                        :key="record.id || idx"
                        class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold shadow-2xs', getJenjangBadgeClass(record.jenjang_kepegawaian)]">
                                {{ record.jenjang_kepegawaian || 'Jenjang -' }}
                            </span>
                            <span
                                v-if="!record.tst_kenaikan_jenjang && !record.tst_jenjang"
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                Aktif Berlaku
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200"
                            >
                                Selesai
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">No. SK</label>
                                <p class="text-sm font-semibold text-slate-900 mt-0.5 font-mono">{{ record.no_sk || '-' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TMT Kenaikan Jenjang</label>
                                    <p class="text-sm font-semibold text-slate-900 mt-0.5">
                                        {{ record.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(record.tmt_kenaikan_jenjang) || '-' }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">TST Kenaikan Jenjang</label>
                                    <p class="text-sm font-semibold text-slate-900 mt-0.5">
                                        {{ record.formatted_tst_kenaikan_jenjang || record.formatted_tst_jenjang || formatTanggalIndo(record.tst_kenaikan_jenjang || record.tst_jenjang) || 'Sekarang / Tetap' }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="record.document" class="pt-1">
                                <button
                                    type="button"
                                    @click="openPreview(record.document)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                >
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Lihat SK</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="isManagingRiwayat && (canDeleteKepegawaian || canUpdateKepegawaian)" class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button
                                v-if="canDeleteKepegawaian"
                                type="button"
                                @click="deleteRiwayatRecord(idx)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                :disabled="isSavingRiwayat"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                <span>Hapus</span>
                            </button>
                            <button
                                v-if="canUpdateKepegawaian"
                                type="button"
                                @click="openEditRiwayatModal(record)"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs ml-auto"
                                :disabled="isSavingRiwayat"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="currentRiwayatList.length === 0" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                        <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">work_history</span>
                        <p class="text-sm text-slate-500">Belum ada riwayat jenjang kepegawaian</p>
                    </div>
                </div>
            </div>

            <!-- POP-UP MODAL TAMBAH & EDIT RIWAYAT JENJANG -->
            <Teleport to="body">
                <div
                    v-if="isRiwayatModalOpen"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                    @click.self="closeRiwayatModal"
                >
                    <div class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-200">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ modalRiwayatMode === 'create' ? 'add_circle' : 'edit' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">
                                        {{ modalRiwayatMode === 'create' ? 'Tambah Riwayat Jenjang' : 'Edit Riwayat Jenjang' }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ modalRiwayatMode === 'create' ? 'Tambahkan catatan kenaikan jenjang kepegawaian baru' : 'Perbarui data catatan jenjang kepegawaian' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeRiwayatModal"
                                class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitRiwayatModal" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Jenjang Kepegawaian <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="riwayatModalForm.jenjang_kepegawaian"
                                    required
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option value="">-- Pilih Jenjang --</option>
                                    <option value="GTY">GTY (Guru Tetap Yayasan)</option>
                                    <option value="GTT">GTT (Guru Tidak Tetap)</option>
                                    <option value="PTY">PTY (Pegawai Tetap Yayasan)</option>
                                    <option value="PTT">PTT (Pegawai Tidak Tetap)</option>
                                    <option value="PJT">PJT</option>
                                    <option value="PTTY">PTTY</option>
                                    <option
                                        v-if="riwayatModalForm.jenjang_kepegawaian && !['GTY', 'GTT', 'PTY', 'PTT', 'PJT', 'PTTY'].includes(riwayatModalForm.jenjang_kepegawaian)"
                                        :value="riwayatModalForm.jenjang_kepegawaian"
                                    >
                                        {{ riwayatModalForm.jenjang_kepegawaian }} (Lainnya)
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nomor SK
                                </label>
                                <input
                                    v-model="riwayatModalForm.no_sk"
                                    type="text"
                                    placeholder="Contoh: 045/SK-YUA/VII/2023"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        TMT Kenaikan Jenjang <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="riwayatModalForm.tmt_kenaikan_jenjang"
                                        type="date"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        TST Kenaikan Jenjang
                                    </label>
                                    <input
                                        v-model="riwayatModalForm.tst_kenaikan_jenjang"
                                        type="date"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <span class="text-[10px] text-slate-400 mt-1 block">Kosongkan jika aktif / tetap</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-6">
                                <button
                                    type="button"
                                    @click="closeRiwayatModal"
                                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="isSavingRiwayat"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="isSavingRiwayat"
                                >
                                    <span v-if="isSavingRiwayat" class="material-symbols-outlined text-[18px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[18px]">save</span>
                                    <span>{{ isSavingRiwayat ? 'Menyimpan...' : 'Simpan Riwayat' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>

