<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, inject } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { formatTanggalIndo } from '../staffHelpers';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    permissions: {
        type: Object,
        default: () => ({}),
    },
    canApprovePermit: {
        type: Boolean,
        default: false,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['managingChanged']);

const page = usePage();
const openPreview = inject('openPreview', () => {});
const requestDeleteConfirm = inject('requestDeleteConfirm', () => {});
const showToast = inject('showToast', () => {});

const perizinanList = ref([...(props.employee?.perizinan || [])]);
const isManagingPerizinan = ref(false);
const isPerizinanModalOpen = ref(false);
const modalPerizinanMode = ref('create'); // 'create' | 'edit'
const editingPerizinanIndex = ref(null);
const isSavingPerizinan = ref(false);

const perizinanModalForm = reactive({
    id: null,
    tanggal: new Date().toISOString().split('T')[0],
    tanggal_selesai: '',
    jenis: '',
    detail: '',
    keterangan: '',
    status: 'diajukan',
    catatan: '',
    lampiran: null,
    lampiran_nama: '',
    preview_url: '',
    download_url: '',
});

function canModifyPermit(item) {
    if (!item) return false;
    if (props.canApprovePermit) return true;
    if (!item.id) return true;
    if (item.status === 'disetujui' || item.status !== 'diajukan') {
        return false;
    }
    return true;
}

function startManagePerizinan() {
    if (props.isEditing) {
        alert('Anda sedang dalam mode edit Informasi Umum. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum mengelola riwayat perizinan.');
        return;
    }
    isManagingPerizinan.value = true;
    emit('managingChanged', true);
}

function finishManagePerizinan() {
    isManagingPerizinan.value = false;
    emit('managingChanged', false);
}

function openAddPerizinanModal() {
    modalPerizinanMode.value = 'create';
    editingPerizinanIndex.value = null;
    perizinanModalForm.id = null;
    perizinanModalForm.tanggal = new Date().toISOString().split('T')[0];
    perizinanModalForm.tanggal_selesai = '';
    perizinanModalForm.jenis = '';
    perizinanModalForm.detail = '';
    perizinanModalForm.keterangan = '';
    perizinanModalForm.status = 'diajukan';
    perizinanModalForm.catatan = '';
    perizinanModalForm.lampiran = null;
    perizinanModalForm.lampiran_nama = '';
    perizinanModalForm.preview_url = '';
    perizinanModalForm.download_url = '';
    isPerizinanModalOpen.value = true;
}

function openEditPerizinanModal(record, index) {
    if (!canModifyPermit(record)) {
        alert('Perizinan yang sudah disetujui tidak dapat diubah oleh pemohon. Hanya Atasan Langsung dan Superadmin yang dapat mengubahnya.');
        return;
    }
    modalPerizinanMode.value = 'edit';
    editingPerizinanIndex.value = index;
    perizinanModalForm.id = record.id || null;
    perizinanModalForm.tanggal = record.tanggal || '';
    perizinanModalForm.tanggal_selesai = record.tanggal_selesai || '';
    perizinanModalForm.jenis = record.jenis || '';
    perizinanModalForm.detail = record.detail || '';
    perizinanModalForm.keterangan = record.keterangan || '';
    perizinanModalForm.status = record.status || 'diajukan';
    perizinanModalForm.catatan = record.catatan || '';
    perizinanModalForm.lampiran = null;
    perizinanModalForm.lampiran_nama = record.lampiran_nama || '';
    perizinanModalForm.preview_url = record.preview_url || '';
    perizinanModalForm.download_url = record.download_url || '';
    isPerizinanModalOpen.value = true;
}

function closePerizinanModal() {
    isPerizinanModalOpen.value = false;
}

function onModalPermitFileChange(event) {
    const file = event.target.files?.[0] || null;
    perizinanModalForm.lampiran = file;
}

function submitPerizinanModal() {
    if (!perizinanModalForm.jenis) {
        alert('Mohon pilih atau isi Jenis Perizinan.');
        return;
    }
    if (!perizinanModalForm.tanggal) {
        alert('Mohon tentukan Tanggal Mulai.');
        return;
    }
    if (perizinanModalForm.tanggal_selesai && perizinanModalForm.tanggal_selesai < perizinanModalForm.tanggal) {
        alert('Tanggal Selesai harus sama atau setelah Tanggal Mulai.');
        return;
    }

    isSavingPerizinan.value = true;

    const formData = new FormData();
    formData.append('tanggal', perizinanModalForm.tanggal || '');
    if (perizinanModalForm.tanggal_selesai) {
        formData.append('tanggal_selesai', perizinanModalForm.tanggal_selesai);
    }
    formData.append('jenis', perizinanModalForm.jenis || '');
    if (perizinanModalForm.detail) {
        formData.append('detail', perizinanModalForm.detail);
    }
    if (perizinanModalForm.keterangan) {
        formData.append('keterangan', perizinanModalForm.keterangan);
    }
    if (props.canApprovePermit) {
        formData.append('status', perizinanModalForm.status || 'diajukan');
        if (perizinanModalForm.catatan) {
            formData.append('catatan', perizinanModalForm.catatan);
        }
    }
    if (perizinanModalForm.lampiran instanceof File) {
        formData.append('lampiran', perizinanModalForm.lampiran);
    }

    if (modalPerizinanMode.value === 'edit' && perizinanModalForm.id) {
        formData.append('_method', 'put');
        router.post(`/staff/${props.employee.id}/permits/${perizinanModalForm.id}`, formData, {
            preserveScroll: true,
            forceFormData: true,
            only: ['employee', 'flash'],
            onSuccess: () => {
                isSavingPerizinan.value = false;
                isPerizinanModalOpen.value = false;
                isManagingPerizinan.value = false;
                emit('managingChanged', false);
                fetchPermitsRealtime();
                showToast('success', page.props.flash?.success || 'Catatan perizinan berhasil diperbarui.');
            },
            onError: (errors) => {
                isSavingPerizinan.value = false;
                const errMsg = Object.values(errors).join(' ');
                showToast('error', errMsg || 'Gagal memperbarui perizinan.');
            },
        });
    } else {
        router.post(`/staff/${props.employee.id}/permits`, formData, {
            preserveScroll: true,
            forceFormData: true,
            only: ['employee', 'flash'],
            onSuccess: () => {
                isSavingPerizinan.value = false;
                isPerizinanModalOpen.value = false;
                isManagingPerizinan.value = false;
                emit('managingChanged', false);
                fetchPermitsRealtime();
                showToast('success', page.props.flash?.success || 'Catatan perizinan berhasil diajukan.');
            },
            onError: (errors) => {
                isSavingPerizinan.value = false;
                const errMsg = Object.values(errors).join(' ');
                showToast('error', errMsg || 'Gagal mengajukan perizinan.');
            },
        });
    }
}

function deletePerizinanRecord(item, index) {
    if (!canModifyPermit(item)) {
        showToast('error', 'Perizinan yang sudah disetujui tidak dapat dihapus oleh pemohon. Hanya Atasan Langsung dan Superadmin yang dapat menghapusnya.', 'Akses Ditolak');
        return;
    }

    requestDeleteConfirm({
        title: 'Hapus Catatan Perizinan',
        message: 'Apakah Anda yakin ingin menghapus catatan perizinan ini?',
        itemLabel: item.jenis ? `${item.jenis} (${formatTanggalIndo(item.tanggal) || item.tanggal})` : '',
        onConfirm: () => {
            if (item && item.id) {
                isSavingPerizinan.value = true;
                router.delete(`/staff/${props.employee.id}/permits/${item.id}`, {
                    preserveScroll: true,
                    only: ['employee', 'flash'],
                    onSuccess: () => {
                        isSavingPerizinan.value = false;
                        isManagingPerizinan.value = false;
                        emit('managingChanged', false);
                        fetchPermitsRealtime();
                        showToast('success', page.props.flash?.success || 'Catatan perizinan berhasil dihapus.');
                    },
                    onError: (errors) => {
                        isSavingPerizinan.value = false;
                        const errMsg = Object.values(errors).join(' ');
                        showToast('error', errMsg || 'Gagal menghapus perizinan.');
                    }
                });
            } else {
                perizinanList.value.splice(index, 1);
                isManagingPerizinan.value = false;
                emit('managingChanged', false);
            }
        }
    });
}

function openPermitPreview(item) {
    if (!item?.preview_url) return;
    const isPdf = (item.lampiran_nama || '').toLowerCase().endsWith('.pdf');
    openPreview({
        title: item.lampiran_nama || `Lampiran Perizinan - ${item.jenis}`,
        file_name: item.lampiran_nama || 'lampiran',
        formatted_file_size: '',
        mime_type: isPdf ? 'application/pdf' : 'image/jpeg',
        preview_url: item.preview_url,
        download_url: item.download_url,
    });
}

// Modal Catatan / Persetujuan Perizinan
const showPermitNoteModal = ref(false);
const permitNoteForm = useForm({
    id: null,
    jenis: '',
    detail: '',
    status: 'diajukan',
    catatan: '',
    updater_name: '',
    updater_role: '',
    formatted_tanggal: '',
});

function openPermitNoteModal(item) {
    if (!item?.id) return;
    permitNoteForm.id = item.id;
    permitNoteForm.jenis = item.jenis || '';
    permitNoteForm.detail = item.detail || '';
    permitNoteForm.status = item.status || 'diajukan';
    permitNoteForm.catatan = item.catatan || '';
    permitNoteForm.updater_name = item.updater_name || '';
    permitNoteForm.updater_role = item.updater_role || '';
    permitNoteForm.formatted_tanggal = item.formatted_tanggal || formatTanggalIndo(item.tanggal) || '';
    showPermitNoteModal.value = true;
}

function closePermitNoteModal() {
    showPermitNoteModal.value = false;
    permitNoteForm.reset();
}

function submitPermitNote() {
    if (!permitNoteForm.id) return;
    permitNoteForm.patch(`/staff/${props.employee.id}/permits/${permitNoteForm.id}/note`, {
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            closePermitNoteModal();
            fetchPermitsRealtime();
        },
    });
}

// ==========================================
// REALTIME PERMIT POLLING (3 detik)
// ==========================================
let permitPollInterval = null;

async function fetchPermitsRealtime() {
    if (props.isEditing || isPerizinanModalOpen.value) return;
    if (showPermitNoteModal.value && props.canApprovePermit) return;

    try {
        const response = await axios.get(`/staff/${props.employee.id}/permits/realtime`);
        if (response.data && Array.isArray(response.data.permits)) {
            perizinanList.value = response.data.permits;
            if (props.employee) {
                props.employee.perizinan = response.data.permits;
            }

            if (showPermitNoteModal.value && permitNoteForm.id && !props.canApprovePermit) {
                const item = response.data.permits.find(p => p.id === permitNoteForm.id);
                if (item) {
                    permitNoteForm.status = item.status || 'diajukan';
                    permitNoteForm.catatan = item.catatan || '';
                    permitNoteForm.updater_name = item.updater_name || '';
                    permitNoteForm.updater_role = item.updater_role || '';
                }
            }
        }
    } catch {
        // Abaikan error jaringan sesaat
    }
}

function startPermitPolling() {
    stopPermitPolling();
    fetchPermitsRealtime();
    permitPollInterval = setInterval(() => {
        if (!props.isEditing && !isPerizinanModalOpen.value && !(showPermitNoteModal.value && props.canApprovePermit)) {
            fetchPermitsRealtime();
        }
    }, 3000);
}

function stopPermitPolling() {
    if (permitPollInterval) {
        clearInterval(permitPollInterval);
        permitPollInterval = null;
    }
}

function onWindowFocus() {
    if (!props.isEditing && !isPerizinanModalOpen.value && !(showPermitNoteModal.value && props.canApprovePermit)) {
        fetchPermitsRealtime();
    }
}

onMounted(() => {
    startPermitPolling();
    window.addEventListener('focus', onWindowFocus);
});

onUnmounted(() => {
    stopPermitPolling();
    window.removeEventListener('focus', onWindowFocus);
});
</script>

<template>
    <div class="space-y-6 mb-8">
        <!-- KOTAK SECTION UTAMA: RIWAYAT PERIZINAN -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100 flex-wrap">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Riwayat Perizinan &amp; Ketidakhadiran
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ perizinanList?.length || 0 }} Perizinan
                        </span>
                    </div>
                    <p v-if="isManagingPerizinan" class="text-xs text-slate-500 mt-1">
                        Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data perizinan
                    </p>
                    <p v-else-if="permissions?.permit_supervisor_position" class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[15px] text-emerald-600">account_tree</span>
                        <span>Approval: <strong class="text-slate-800 font-semibold">{{ permissions.permit_supervisor_position }}</strong> ({{ permissions.permit_supervisor_dept || 'Unit' }}) &amp; Superadmin</span>
                    </p>
                </div>

                <!-- Tombol Kelola / Selesai Kelola di Pojok Kanan Atas Section -->
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="!isManagingPerizinan"
                        type="button"
                        @click="startManagePerizinan"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 cursor-pointer active:scale-95"
                        title="Kelola data perizinan"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px] text-slate-600">tune</span>
                        <span>Kelola</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="finishManagePerizinan"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-emerald-950 hover:bg-emerald-900 text-emerald-300 font-bold text-xs sm:text-sm shadow-xs border border-emerald-800 transition-all cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                        title="Selesai kelola data perizinan"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">check</span>
                        <span>Selesai Kelola</span>
                    </button>
                </div>
            </div>

            <!-- 1. TAMPILAN DESKTOP (TABLE VIEW) -->
            <div class="hidden md:block">
                <!-- Tombol Tambah Riwayat di Atas Tabel -->
                <div v-if="isManagingPerizinan" class="mb-3.5 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="openAddPerizinanModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Ajukan / Tambah Perizinan</span>
                    </button>
                    <span class="text-xs text-slate-500 font-medium">
                        Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data
                    </span>
                </div>

                <!-- Table Container -->
                <div v-if="perizinanList && perizinanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th v-if="isManagingPerizinan" class="px-3 py-3 text-center w-14">Hapus</th>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Tanggal</th>
                                <th class="px-4 py-3 text-left">Jenis</th>
                                <th class="px-4 py-3 text-left">Detail</th>
                                <th class="px-4 py-3 text-left">Keterangan</th>
                                <th class="px-4 py-3 text-center">Lampiran</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Persetujuan</th>
                                <th v-if="isManagingPerizinan" class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(item, idx) in perizinanList"
                                :key="item.id || idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td v-if="isManagingPerizinan" class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="deletePerizinanRecord(item, idx)"
                                        :class="[
                                            'w-8 h-8 rounded-lg flex items-center justify-center mx-auto transition-colors',
                                            canModifyPermit(item)
                                                ? 'text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 cursor-pointer active:scale-95'
                                                : 'text-slate-300 border border-transparent cursor-not-allowed opacity-40'
                                        ]"
                                        :title="canModifyPermit(item) ? 'Hapus catatan perizinan ini' : 'Perizinan yang sudah disetujui tidak dapat dihapus oleh pemohon'"
                                        :disabled="!canModifyPermit(item) || isSavingPerizinan"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>

                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3.5 text-slate-900 font-semibold whitespace-nowrap">
                                    {{ item.formatted_tanggal || formatTanggalIndo(item.tanggal) || '-' }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                        {{ item.jenis || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-800 font-medium">{{ item.detail || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-600 max-w-[220px] truncate" :title="item.keterangan || ''">
                                    {{ item.keterangan || '-' }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div v-if="item.preview_url" class="inline-flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            @click="openPermitPreview(item)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer shadow-2xs"
                                            title="Lihat Berkas Lampiran"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="item.download_url"
                                            class="inline-flex items-center justify-center p-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer shadow-2xs"
                                            title="Unduh Berkas Lampiran"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                        </a>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-2xs',
                                            item.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            item.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-amber-50 text-amber-700 border-amber-200'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full',
                                                item.status === 'disetujui' ? 'bg-emerald-500' :
                                                item.status === 'ditolak' ? 'bg-rose-500' :
                                                'bg-amber-500'
                                            ]"
                                        ></span>
                                        {{ item.status === 'disetujui' ? 'Disetujui' : item.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <button
                                        type="button"
                                        @click="openPermitNoteModal(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200/80 transition-colors cursor-pointer shadow-2xs"
                                        :title="item.catatan ? 'Lihat Catatan: ' + item.catatan : 'Buka Persetujuan Perizinan'"
                                    >
                                        <span class="material-symbols-outlined text-[16px] text-slate-600">sticky_note_2</span>
                                        <span>Persetujuan</span>
                                        <span v-if="item.catatan" class="w-1.5 h-1.5 rounded-full bg-emerald-600" title="Ada catatan"></span>
                                    </button>
                                </td>

                                <td v-if="isManagingPerizinan" class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditPerizinanModal(item, idx)"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors shadow-2xs',
                                            canModifyPermit(item)
                                                ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 cursor-pointer'
                                                : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-50'
                                        ]"
                                        :title="canModifyPermit(item) ? 'Edit data perizinan ini' : 'Perizinan yang sudah disetujui tidak dapat diubah oleh pemohon'"
                                        :disabled="!canModifyPermit(item) || isSavingPerizinan"
                                    >
                                        <span class="material-symbols-outlined text-[15px]">edit</span>
                                        <span>Edit</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                    <p class="text-sm font-semibold text-slate-700">Belum ada riwayat perizinan untuk pegawai ini</p>
                    <p class="text-xs text-slate-400 mt-0.5">Klik tombol Kelola di atas untuk mengajukan perizinan</p>
                </div>
            </div>

            <!-- 2. TAMPILAN MOBILE (CARD VIEW) -->
            <div class="md:hidden">
                <div v-if="isManagingPerizinan" class="mb-4">
                    <button
                        type="button"
                        @click="openAddPerizinanModal"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Ajukan / Tambah Perizinan</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(item, idx) in perizinanList"
                        :key="item.id || idx"
                        class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                {{ item.jenis || '-' }}
                            </span>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border shadow-2xs',
                                    item.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                    item.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                    'bg-amber-50 text-amber-700 border-amber-200'
                                ]"
                            >
                                <span
                                    :class="[
                                        'w-1.5 h-1.5 rounded-full',
                                        item.status === 'disetujui' ? 'bg-emerald-500' :
                                        item.status === 'ditolak' ? 'bg-rose-500' :
                                        'bg-amber-500'
                                    ]"
                                ></span>
                                {{ item.status === 'disetujui' ? 'Disetujui' : item.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Tanggal Izin</label>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">{{ item.formatted_tanggal || formatTanggalIndo(item.tanggal) || '-' }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Detail</label>
                                <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ item.detail || '-' }}</p>
                            </div>
                            <div v-if="item.keterangan">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Alasan</label>
                                <p class="text-xs text-slate-600 mt-0.5">{{ item.keterangan }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-2 pt-1 flex-wrap">
                                <div v-if="item.preview_url">
                                    <button
                                        type="button"
                                        @click="openPermitPreview(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        <span>Lampiran</span>
                                    </button>
                                </div>
                                <span v-else class="text-[11px] text-slate-400 italic">Tanpa lampiran</span>

                                <button
                                    type="button"
                                    @click="openPermitNoteModal(item)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200/80 transition-colors shadow-2xs"
                                >
                                    <span class="material-symbols-outlined text-[16px] text-slate-600">sticky_note_2</span>
                                    <span>Persetujuan</span>
                                    <span v-if="item.catatan" class="w-1.5 h-1.5 rounded-full bg-emerald-600" title="Ada catatan"></span>
                                </button>
                            </div>
                        </div>

                        <div v-if="isManagingPerizinan" class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="deletePerizinanRecord(item, idx)"
                                :class="[
                                    'inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer',
                                    canModifyPermit(item)
                                        ? 'text-rose-600 hover:bg-rose-50 border border-rose-200'
                                        : 'text-slate-300 border border-slate-200 cursor-not-allowed opacity-40'
                                ]"
                                :disabled="!canModifyPermit(item) || isSavingPerizinan"
                                :title="canModifyPermit(item) ? 'Hapus catatan perizinan ini' : 'Perizinan yang sudah disetujui tidak dapat dihapus oleh pemohon'"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                <span>Hapus</span>
                            </button>
                            <button
                                type="button"
                                @click="openEditPerizinanModal(item, idx)"
                                :class="[
                                    'inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer shadow-2xs',
                                    canModifyPermit(item)
                                        ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200'
                                        : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-50'
                                ]"
                                :disabled="!canModifyPermit(item) || isSavingPerizinan"
                                :title="canModifyPermit(item) ? 'Edit data perizinan ini' : 'Perizinan yang sudah disetujui tidak dapat diubah oleh pemohon'"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="!perizinanList || perizinanList.length === 0" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                        <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">assignment_late</span>
                        <p class="text-xs font-semibold text-slate-600">Belum ada riwayat perizinan</p>
                    </div>
                </div>
            </div>

            <!-- POP-UP MODAL TAMBAH & EDIT RIWAYAT PERIZINAN -->
            <Teleport to="body">
                <div
                    v-if="isPerizinanModalOpen"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                    @click.self="closePerizinanModal"
                >
                    <div class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ modalPerizinanMode === 'create' ? 'add_circle' : 'edit' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">
                                        {{ modalPerizinanMode === 'create' ? 'Ajukan / Tambah Perizinan' : 'Edit Data Perizinan' }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ modalPerizinanMode === 'create' ? 'Ajukan izin ketidakhadiran atau cuti pegawai' : 'Perbarui data catatan perizinan pegawai' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closePerizinanModal"
                                class="w-8 h-8 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitPerizinanModal" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Tanggal Mulai <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="perizinanModalForm.tanggal"
                                        type="date"
                                        required
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Tanggal Selesai
                                    </label>
                                    <input
                                        v-model="perizinanModalForm.tanggal_selesai"
                                        type="date"
                                        :min="perizinanModalForm.tanggal"
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    />
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">Kosongkan bila hanya 1 hari</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Jenis Perizinan <span class="text-rose-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <button
                                            v-for="opt in ['Izin', 'Sakit', 'Cuti Tahunan', 'Cuti Melahirkan', 'Dinas Luar']"
                                            :key="opt"
                                            type="button"
                                            @click="perizinanModalForm.jenis = opt"
                                            :class="[
                                                'px-2.5 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer',
                                                perizinanModalForm.jenis === opt
                                                    ? 'bg-emerald-900 text-yellow-200 border border-emerald-950 font-bold shadow-2xs'
                                                    : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'
                                            ]"
                                        >
                                            {{ opt }}
                                        </button>
                                    </div>
                                    <input
                                        v-model="perizinanModalForm.jenis"
                                        type="text"
                                        placeholder="Contoh: Izin, Sakit, Cuti Tahunan, dll."
                                        required
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Detail / Keperluan
                                </label>
                                <input
                                    v-model="perizinanModalForm.detail"
                                    type="text"
                                    placeholder="Contoh: Acara keluarga, medical check-up, dll."
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Keterangan / Alasan
                                </label>
                                <textarea
                                    v-model="perizinanModalForm.keterangan"
                                    rows="3"
                                    placeholder="Tuliskan keterangan atau alasan lebih rinci..."
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 resize-none"
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Berkas Lampiran <span class="text-[10px] text-slate-400 font-normal">(Surat dokter, undangan, bukti dinas - Opsional)</span>
                                </label>
                                <div v-if="perizinanModalForm.preview_url" class="mb-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="material-symbols-outlined text-emerald-600 text-[18px] shrink-0">attach_file</span>
                                        <span class="text-xs text-slate-700 font-medium truncate">{{ perizinanModalForm.lampiran_nama || 'Berkas Lampiran Tersimpan' }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="openPermitPreview(perizinanModalForm)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors shrink-0"
                                    >
                                        <span class="material-symbols-outlined text-[14px]">visibility</span>
                                        <span>Lihat</span>
                                    </button>
                                </div>
                                <input
                                    type="file"
                                    @change="onModalPermitFileChange"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 border border-slate-200 rounded-xl p-1 bg-white cursor-pointer"
                                />
                                <p class="text-[10px] text-slate-400 mt-1">Format: PDF, JPG, PNG, WEBP (Maksimal 10MB)</p>
                            </div>

                            <div v-if="canApprovePermit" class="pt-3 border-t border-slate-100 space-y-3 bg-amber-50/50 p-3 rounded-2xl border border-amber-200/60">
                                <div>
                                    <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                                        Status Pengajuan
                                    </label>
                                    <select
                                        v-model="perizinanModalForm.status"
                                        class="w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-xs sm:text-sm font-semibold text-slate-800 focus:outline-none focus:border-amber-500"
                                    >
                                        <option value="diajukan">Diajukan (Menunggu Persetujuan)</option>
                                        <option value="disetujui">Disetujui</option>
                                        <option value="ditolak">Ditolak</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                                        Catatan Atasan / Sekolah
                                    </label>
                                    <textarea
                                        v-model="perizinanModalForm.catatan"
                                        rows="2"
                                        placeholder="Catatan dari atasan atau reviewer..."
                                        class="w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-amber-500 resize-none"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="closePerizinanModal"
                                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition-colors cursor-pointer"
                                    :disabled="isSavingPerizinan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer disabled:opacity-60 active:scale-95"
                                    :disabled="isSavingPerizinan"
                                >
                                    <span v-if="isSavingPerizinan" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                    <span>{{ isSavingPerizinan ? 'Menyimpan...' : 'Simpan' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- MODAL CATATAN / PERSETUJUAN PERIZINAN -->
            <Teleport to="body">
                <div
                    v-if="showPermitNoteModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                    @click.self="closePermitNoteModal"
                >
                    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                            <div class="flex items-center gap-2 text-emerald-900 font-bold text-sm sm:text-base">
                                <span class="material-symbols-outlined text-[20px]">sticky_note_2</span>
                                <span>Persetujuan Perizinan</span>
                            </div>
                            <button
                                type="button"
                                @click="closePermitNoteModal"
                                class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitPermitNote" class="p-6 space-y-4">
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-2">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="text-xs text-slate-600">
                                        <span class="font-bold text-slate-900">Jenis:</span> {{ permitNoteForm.jenis || '-' }}
                                    </div>
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="text-[11px] text-slate-400 font-semibold">Status:</span>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold border',
                                                permitNoteForm.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                                permitNoteForm.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                'bg-amber-50 text-amber-700 border-amber-200'
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'w-1.5 h-1.5 rounded-full',
                                                    permitNoteForm.status === 'disetujui' ? 'bg-emerald-500' :
                                                    permitNoteForm.status === 'ditolak' ? 'bg-rose-500' :
                                                    'bg-amber-500'
                                                ]"
                                            ></span>
                                            {{ permitNoteForm.status === 'disetujui' ? 'Disetujui' : permitNoteForm.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="permitNoteForm.detail" class="text-xs text-slate-600">
                                    <span class="font-bold text-slate-900">Detail:</span> {{ permitNoteForm.detail }}
                                </div>
                                <div v-if="permitNoteForm.formatted_tanggal" class="text-xs text-slate-600">
                                    <span class="font-bold text-slate-900">Tanggal:</span> {{ permitNoteForm.formatted_tanggal }}
                                </div>
                            </div>

                            <div v-if="canApprovePermit" class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-900">Ubah Status Pengajuan</label>
                                <select
                                    v-model="permitNoteForm.status"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                >
                                    <option value="diajukan">Diajukan</option>
                                    <option value="disetujui">Disetujui</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-900">
                                    {{ canApprovePermit ? 'Catatan / Alasan Persetujuan / Penolakan' : 'Catatan dari Pihak Sekolah / Atasan Langsung' }}
                                </label>
                                <textarea
                                    v-if="canApprovePermit"
                                    v-model="permitNoteForm.catatan"
                                    rows="4"
                                    placeholder="Tuliskan catatan, alasan, atau keterangan tambahan..."
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-900 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 resize-y"
                                ></textarea>
                                <div v-else class="space-y-2">
                                    <div
                                        class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs sm:text-sm text-slate-800 min-h-[80px] whitespace-pre-wrap leading-relaxed"
                                    >
                                        {{ permitNoteForm.catatan || 'Belum ada catatan yang diberikan oleh pihak sekolah / atasan untuk perizinan ini.' }}
                                    </div>
                                    <div v-if="permitNoteForm.updater_name" class="flex items-center gap-1.5 text-[11px] text-slate-600 bg-emerald-50/50 px-2.5 py-1.5 rounded-lg border border-emerald-100">
                                        <span class="material-symbols-outlined text-[14px] text-emerald-700">verified_user</span>
                                        <span>Diberikan oleh: <strong class="text-slate-900">{{ permitNoteForm.updater_name }}</strong> ({{ permitNoteForm.updater_role || 'Pemberi Approval' }})</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="closePermitNoteModal"
                                    class="px-4 py-2 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
                                >
                                    {{ canApprovePermit ? 'Batal' : 'Tutup' }}
                                </button>
                                <button
                                    v-if="canApprovePermit"
                                    type="submit"
                                    :disabled="permitNoteForm.processing"
                                    class="px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs transition-colors cursor-pointer disabled:opacity-60"
                                >
                                    Simpan Status &amp; Catatan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>

