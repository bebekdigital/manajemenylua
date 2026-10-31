<script setup>
import { ref, computed, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
    isTeacher: {
        type: Boolean,
        default: false,
    },
    canEdit: {
        type: Boolean,
        default: true,
    },
});

const permitsList = computed(() => props.employee?.perizinan || []);

// -------------------------------------------------------------
// Inline Add Row State
// -------------------------------------------------------------
const todayStr = new Date().toISOString().split('T')[0];
const isAddingNew = ref(false);
const isSavingNew = ref(false);
const newJenisInputRef = ref(null);

const newPermit = ref({
    tanggal: todayStr,
    tanggal_selesai: '',
    jenis: '',
    detail: '',
    keterangan: '',
    status: 'diajukan',
    lampiran: null,
});

function addNewPermitRow() {
    isAddingNew.value = true;
    newPermit.value = {
        tanggal: todayStr,
        tanggal_selesai: '',
        jenis: '',
        detail: '',
        keterangan: '',
        status: 'diajukan',
        lampiran: null,
    };
    nextTick(() => {
        if (newJenisInputRef.value) {
            newJenisInputRef.value.focus();
        }
    });
}

function cancelNewPermit() {
    isAddingNew.value = false;
    newPermit.value = {
        tanggal: todayStr,
        tanggal_selesai: '',
        jenis: '',
        detail: '',
        keterangan: '',
        status: 'diajukan',
        lampiran: null,
    };
}

function handleNewFileChange(e) {
    newPermit.value.lampiran = e.target.files[0] || null;
}

function saveNewPermit() {
    if (!newPermit.value.jenis || !newPermit.value.jenis.trim()) {
        alert('Jenis perizinan wajib diisi.');
        return;
    }

    isSavingNew.value = true;
    router.post(
        route('staff.permits.store', props.employee.id),
        {
            tanggal: newPermit.value.tanggal,
            tanggal_selesai: newPermit.value.tanggal_selesai || null,
            jenis: newPermit.value.jenis,
            detail: newPermit.value.detail || null,
            keterangan: newPermit.value.keterangan || null,
            status: props.isTeacher ? 'diajukan' : newPermit.value.status,
            lampiran: newPermit.value.lampiran,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                isAddingNew.value = false;
                newPermit.value = {
                    tanggal: todayStr,
                    tanggal_selesai: '',
                    jenis: '',
                    detail: '',
                    keterangan: '',
                    status: 'diajukan',
                    lampiran: null,
                };
            },
            onFinish: () => {
                isSavingNew.value = false;
            },
        }
    );
}

// -------------------------------------------------------------
// Inline Edit Row State
// -------------------------------------------------------------
const editingId = ref(null);
const isSavingEdit = ref(false);
const editingPermit = ref({
    id: null,
    tanggal: '',
    tanggal_selesai: '',
    jenis: '',
    detail: '',
    keterangan: '',
    status: 'diajukan',
    lampiran: null,
    current_lampiran_nama: '',
    current_preview_url: '',
});

function startEditRow(permit) {
    editingId.value = permit.id;
    editingPermit.value = {
        id: permit.id,
        tanggal: permit.tanggal || '',
        tanggal_selesai: permit.tanggal_selesai || '',
        jenis: permit.jenis || '',
        detail: permit.detail || '',
        keterangan: permit.keterangan || '',
        status: permit.status || 'diajukan',
        lampiran: null,
        current_lampiran_nama: permit.lampiran_nama || '',
        current_preview_url: permit.preview_url || '',
    };
}

function cancelEditRow() {
    editingId.value = null;
    editingPermit.value = {
        id: null,
        tanggal: '',
        tanggal_selesai: '',
        jenis: '',
        detail: '',
        keterangan: '',
        status: 'diajukan',
        lampiran: null,
        current_lampiran_nama: '',
        current_preview_url: '',
    };
}

function handleEditFileChange(e) {
    editingPermit.value.lampiran = e.target.files[0] || null;
}

function saveEditRow() {
    if (!editingPermit.value.jenis || !editingPermit.value.jenis.trim()) {
        alert('Jenis perizinan wajib diisi.');
        return;
    }

    isSavingEdit.value = true;
    router.post(
        route('staff.permits.update', { id: props.employee.id, permitId: editingPermit.value.id }),
        {
            _method: 'PUT',
            tanggal: editingPermit.value.tanggal,
            tanggal_selesai: editingPermit.value.tanggal_selesai || null,
            jenis: editingPermit.value.jenis,
            detail: editingPermit.value.detail || null,
            keterangan: editingPermit.value.keterangan || null,
            status: props.isTeacher ? undefined : editingPermit.value.status,
            lampiran: editingPermit.value.lampiran,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                editingId.value = null;
            },
            onFinish: () => {
                isSavingEdit.value = false;
            },
        }
    );
}

// -------------------------------------------------------------
// Modal Catatan State
// -------------------------------------------------------------
const showNoteModal = ref(false);
const formNote = useForm({
    id: null,
    jenis: '',
    detail: '',
    status: 'diajukan',
    catatan: '',
});

function openNoteModal(permit) {
    formNote.clearErrors();
    formNote.id = permit.id;
    formNote.jenis = permit.jenis;
    formNote.detail = permit.detail;
    formNote.status = permit.status || 'diajukan';
    formNote.catatan = permit.catatan || '';
    showNoteModal.value = true;
}

function closeNoteModal() {
    showNoteModal.value = false;
    formNote.reset();
    formNote.clearErrors();
}

function submitNote() {
    formNote.patch(route('staff.permits.note', { id: props.employee.id, permitId: formNote.id }), {
        preserveScroll: true,
        onSuccess: () => {
            closeNoteModal();
        },
    });
}

// -------------------------------------------------------------
// Delete Confirmation
// -------------------------------------------------------------
const showDeleteConfirmModal = ref(false);
const permitToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(permit) {
    permitToDelete.value = permit;
    showDeleteConfirmModal.value = true;
}

function closeDeleteConfirmModal() {
    showDeleteConfirmModal.value = false;
    permitToDelete.value = null;
}

function submitDelete() {
    if (!permitToDelete.value) return;
    isDeleting.value = true;
    router.delete(
        route('staff.permits.destroy', { id: props.employee.id, permitId: permitToDelete.value.id }),
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                closeDeleteConfirmModal();
            },
        }
    );
}

// -------------------------------------------------------------
// Preview State
// -------------------------------------------------------------
const showPreviewModal = ref(false);
const previewData = ref({ url: '', name: '', isPdf: false });

function openPreview(permit) {
    if (!permit.preview_url) return;
    const isPdf = (permit.lampiran_nama || '').toLowerCase().endsWith('.pdf');
    previewData.value = {
        url: permit.preview_url,
        name: permit.lampiran_nama || 'Lampiran Perizinan',
        isPdf,
    };
    showPreviewModal.value = true;
}

function closePreviewModal() {
    showPreviewModal.value = false;
    previewData.value = { url: '', name: '', isPdf: false };
}

// -------------------------------------------------------------
// Helpers: Status Styling
// -------------------------------------------------------------
function getStatusBadge(status) {
    const s = (status || 'diajukan').toLowerCase();
    if (s === 'disetujui') {
        return {
            label: 'Disetujui',
            dotClass: 'bg-emerald-500',
            badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            icon: 'check_circle',
        };
    }
    if (s === 'ditolak') {
        return {
            label: 'Ditolak',
            dotClass: 'bg-rose-500',
            badgeClass: 'bg-rose-50 text-rose-700 border-rose-200',
            icon: 'cancel',
        };
    }
    return {
        label: 'Diajukan',
        dotClass: 'bg-amber-500 animate-pulse',
        badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
        icon: 'schedule',
    };
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header Row -->
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                    Riwayat dan Pengajuan Perizinan Pegawai
                </h4>
                <p class="text-xs text-on-surface-variant mt-0.5">
                    Kelola data izin, sakit, cuti, dinas luar, lampiran berkas, dan status persetujuan langsung pada tabel di bawah
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-outline font-medium mr-1">
                    {{ permitsList.length }} Catatan
                </span>
                <button
                    v-if="!isAddingNew"
                    type="button"
                    @click="addNewPermitRow"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-[0.98] cursor-pointer"
                >
                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                    <span>Ajukan Perizinan</span>
                </button>
            </div>
        </div>

        <!-- Tabel Perizinan -->
        <div class="overflow-hidden rounded-xl border border-outline-variant/60 bg-white shadow-2xs">
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60">
                    <thead>
                        <tr class="bg-white text-on-surface-variant font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                            <th class="border border-outline-variant/60 px-3 py-3 text-center w-12">No</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-left w-36">Tanggal</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-left w-36">Jenis</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-left w-48">Detail</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-left">Keterangan</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-center w-36">Lampiran</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-center w-32">Status</th>
                            <th class="border border-outline-variant/60 px-3 py-3 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- BARIS FORM INLINE UNTUK AJUKAN PERIZINAN BARU -->
                        <tr
                            v-if="isAddingNew"
                            class="bg-primary/5 border-2 border-primary/40 animate-in fade-in duration-150"
                        >
                            <!-- No -->
                            <td class="border border-outline-variant/60 px-3 py-2 text-center text-primary font-bold">
                                +
                            </td>

                            <!-- Tanggal -->
                            <td class="border border-outline-variant/60 px-2 py-2">
                                <input
                                    v-model="newPermit.tanggal"
                                    type="date"
                                    required
                                    class="w-full px-2 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                />
                            </td>

                            <!-- Jenis (Format Biasa) -->
                            <td class="border border-outline-variant/60 px-2 py-2">
                                <input
                                    ref="newJenisInputRef"
                                    v-model="newPermit.jenis"
                                    type="text"
                                    required
                                    placeholder="Izin, Sakit, Cuti..."
                                    class="w-full px-2.5 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs font-semibold text-on-surface focus:outline-none focus:border-primary placeholder:text-outline"
                                />
                            </td>

                            <!-- Detail (Format Biasa) -->
                            <td class="border border-outline-variant/60 px-2 py-2">
                                <input
                                    v-model="newPermit.detail"
                                    type="text"
                                    placeholder="Detail perizinan..."
                                    class="w-full px-2.5 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary placeholder:text-outline"
                                />
                            </td>

                            <!-- Keterangan -->
                            <td class="border border-outline-variant/60 px-2 py-2">
                                <input
                                    v-model="newPermit.keterangan"
                                    type="text"
                                    placeholder="Keterangan / alasan..."
                                    class="w-full px-2.5 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary placeholder:text-outline"
                                />
                            </td>

                            <!-- Lampiran File -->
                            <td class="border border-outline-variant/60 px-2 py-2 text-center">
                                <input
                                    type="file"
                                    @change="handleNewFileChange"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    class="w-full text-[11px] text-on-surface-variant file:mr-1.5 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-primary/10 file:text-primary cursor-pointer border border-outline-variant/60 rounded-lg p-0.5 bg-white"
                                />
                            </td>

                            <!-- Status -->
                            <td class="border border-outline-variant/60 px-2 py-2 text-center">
                                <select
                                    v-if="!isTeacher"
                                    v-model="newPermit.status"
                                    class="w-full px-2 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs font-bold text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                                >
                                    <option value="diajukan">Diajukan</option>
                                    <option value="disetujui">Disetujui</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    Diajukan
                                </span>
                            </td>

                            <!-- Aksi Simpan / Batal -->
                            <td class="border border-outline-variant/60 px-2 py-2 text-center">
                                <div class="inline-flex items-center gap-1.5 justify-center">
                                    <button
                                        type="button"
                                        @click="saveNewPermit"
                                        :disabled="isSavingNew"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
                                        title="Simpan Perizinan Baru"
                                    >
                                        <span v-if="isSavingNew" class="material-symbols-outlined text-[15px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[15px]">check</span>
                                        <span>Simpan</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelNewPermit"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-on-surface hover:bg-slate-200/60 transition-colors cursor-pointer"
                                        title="Batalkan"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- LIST EXISTING PERIZINAN ROWS -->
                        <tr
                            v-for="(item, idx) in permitsList"
                            :key="item.id"
                            :class="[
                                'transition-colors',
                                editingId === item.id ? 'bg-amber-50/50' : 'hover:bg-slate-50'
                            ]"
                        >
                            <!-- 1. MODE INLINE EDIT -->
                            <template v-if="editingId === item.id">
                                <td class="border border-outline-variant/60 px-3 py-2 text-center font-mono text-xs">
                                    {{ idx + 1 }}
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2">
                                    <input
                                        v-model="editingPermit.tanggal"
                                        type="date"
                                        required
                                        class="w-full px-2 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                    />
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2">
                                    <input
                                        v-model="editingPermit.jenis"
                                        type="text"
                                        required
                                        placeholder="Jenis..."
                                        class="w-full px-2.5 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs font-semibold text-on-surface focus:outline-none focus:border-primary"
                                    />
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2">
                                    <input
                                        v-model="editingPermit.detail"
                                        type="text"
                                        placeholder="Detail..."
                                        class="w-full px-2.5 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                    />
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2">
                                    <input
                                        v-model="editingPermit.keterangan"
                                        type="text"
                                        placeholder="Keterangan..."
                                        class="w-full px-2.5 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary"
                                    />
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2 text-center">
                                    <div v-if="editingPermit.current_lampiran_nama" class="text-[10px] text-on-surface-variant truncate max-w-[130px] mb-1">
                                        {{ editingPermit.current_lampiran_nama }}
                                    </div>
                                    <input
                                        type="file"
                                        @change="handleEditFileChange"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        class="w-full text-[11px] text-on-surface-variant file:mr-1.5 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-primary/10 file:text-primary cursor-pointer border border-outline-variant/60 rounded-lg p-0.5 bg-white"
                                    />
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2 text-center">
                                    <select
                                        v-if="!isTeacher"
                                        v-model="editingPermit.status"
                                        class="w-full px-2 py-1.5 bg-white border border-outline-variant/60 rounded-lg text-xs font-bold text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                                    >
                                        <option value="diajukan">Diajukan</option>
                                        <option value="disetujui">Disetujui</option>
                                        <option value="ditolak">Ditolak</option>
                                    </select>
                                    <span
                                        v-else
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border',
                                            getStatusBadge(editingPermit.status).badgeClass
                                        ]"
                                    >
                                        {{ getStatusBadge(editingPermit.status).label }}
                                    </span>
                                </td>
                                <td class="border border-outline-variant/60 px-2 py-2 text-center">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <button
                                            type="button"
                                            @click="saveEditRow"
                                            :disabled="isSavingEdit"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
                                            title="Simpan Perubahan"
                                        >
                                            <span v-if="isSavingEdit" class="material-symbols-outlined text-[15px] animate-spin">sync</span>
                                            <span v-else class="material-symbols-outlined text-[15px]">check</span>
                                            <span>Simpan</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelEditRow"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-on-surface hover:bg-slate-200/60 transition-colors cursor-pointer"
                                            title="Batal"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                        </button>
                                    </div>
                                </td>
                            </template>

                            <!-- 2. MODE NORMAL DISPLAY -->
                            <template v-else>
                                <!-- No -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-center text-on-surface-variant font-mono text-xs">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Tanggal -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-on-surface whitespace-nowrap font-medium">
                                    {{ item.formatted_tanggal || item.tanggal || '-' }}
                                </td>

                                <!-- Jenis -->
                                <td class="border border-outline-variant/60 px-4 py-3 font-bold text-on-surface">
                                    <span class="px-2.5 py-1 rounded-lg bg-surface-container-high text-on-surface font-semibold text-xs inline-block">
                                        {{ item.jenis || '-' }}
                                    </span>
                                </td>

                                <!-- Detail -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-on-surface font-medium">
                                    {{ item.detail || '-' }}
                                </td>

                                <!-- Keterangan -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-on-surface-variant">
                                    <div class="max-w-xs xl:max-w-md line-clamp-2 text-xs leading-relaxed" :title="item.keterangan">
                                        {{ item.keterangan || '-' }}
                                    </div>
                                </td>

                                <!-- Lampiran -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                    <div v-if="item.lampiran_path" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openPreview(item)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Pratinjau Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="item.download_url"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                        </a>
                                    </div>
                                    <span v-else class="text-xs text-outline italic">
                                        Tidak ada
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border transition-colors',
                                            getStatusBadge(item.status).badgeClass
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusBadge(item.status).dotClass"></span>
                                        <span>{{ getStatusBadge(item.status).label }}</span>
                                    </span>
                                </td>

                                <!-- Aksi (Catatan, Edit, Hapus) -->
                                <td class="border border-outline-variant/60 px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <!-- Catatan -->
                                        <button
                                            type="button"
                                            @click="openNoteModal(item)"
                                            :class="[
                                                'relative inline-flex items-center justify-center p-1.5 rounded-lg border transition-all cursor-pointer',
                                                item.catatan
                                                    ? 'bg-amber-50 text-amber-700 border-amber-300 hover:bg-amber-100'
                                                    : 'bg-white text-slate-600 border-outline-variant/60 hover:bg-slate-100'
                                            ]"
                                            :title="item.catatan ? 'Lihat/Ubah Catatan: ' + item.catatan : 'Beri Catatan / Tindak Lanjut'"
                                        >
                                            <span class="material-symbols-outlined text-[17px]">note_alt</span>
                                            <span
                                                v-if="item.catatan"
                                                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-500 rounded-full border-2 border-white"
                                            ></span>
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="startEditRow(item)"
                                            class="inline-flex items-center justify-center p-1.5 rounded-lg bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                            title="Edit Data Perizinan Langsung di Baris Ini"
                                        >
                                            <span class="material-symbols-outlined text-[17px]">edit</span>
                                        </button>

                                        <!-- Hapus -->
                                        <button
                                            type="button"
                                            @click="confirmDelete(item)"
                                            class="inline-flex items-center justify-center p-1.5 rounded-lg bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Perizinan"
                                        >
                                            <span class="material-symbols-outlined text-[17px]">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </template>
                        </tr>

                        <!-- Empty State Row (Jika belum ada data dan tidak sedang menambah baris) -->
                        <tr v-if="permitsList.length === 0 && !isAddingNew">
                            <td colspan="8" class="border border-outline-variant/60 px-4 py-8 text-center text-xs text-on-surface-variant">
                                Belum ada data riwayat perizinan untuk pegawai ini.
                            </td>
                        </tr>

                        <!-- BARIS PALING BAWAH: TOMBOL TAMBAH PERIZINAN (MIRIP RIWAYAT PENDIDIKAN) -->
                        <tr class="bg-slate-50/70 hover:bg-slate-100/70 transition-colors">
                            <td colspan="8" class="border border-outline-variant/60 p-2.5 text-center">
                                <button
                                    type="button"
                                    @click="addNewPermitRow"
                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-primary/50 hover:border-primary bg-primary/5 hover:bg-primary/10 text-primary font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Ajukan / Tambah Perizinan</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL CATATAN & STATUS PERIZINAN                        -->
        <!-- ======================================================= -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showNoteModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="closeNoteModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/60 bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">note_alt</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface">Catatan Perizinan</h3>
                                <p class="text-xs text-on-surface-variant font-medium">
                                    {{ formNote.jenis }} <span v-if="formNote.detail">• {{ formNote.detail }}</span>
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeNoteModal"
                            class="p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-variant transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitNote" class="p-5 space-y-4">
                        <div v-if="!isTeacher">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">
                                Status Perizinan
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="formNote.status = 'diajukan'"
                                    :class="[
                                        'py-2 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                                        formNote.status === 'diajukan'
                                            ? 'bg-amber-100 border-amber-400 text-amber-800 shadow-xs'
                                            : 'bg-white border-outline-variant/60 text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Diajukan</span>
                                </button>

                                <button
                                    type="button"
                                    @click="formNote.status = 'disetujui'"
                                    :class="[
                                        'py-2 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                                        formNote.status === 'disetujui'
                                            ? 'bg-emerald-100 border-emerald-400 text-emerald-800 shadow-xs'
                                            : 'bg-white border-outline-variant/60 text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Disetujui</span>
                                </button>

                                <button
                                    type="button"
                                    @click="formNote.status = 'ditolak'"
                                    :class="[
                                        'py-2 px-3 rounded-xl border text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer',
                                        formNote.status === 'ditolak'
                                            ? 'bg-rose-100 border-rose-400 text-rose-800 shadow-xs'
                                            : 'bg-white border-outline-variant/60 text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    <span>Ditolak</span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Isi Catatan / Tindak Lanjut
                            </label>
                            <textarea
                                v-model="formNote.catatan"
                                rows="4"
                                placeholder="Tuliskan catatan verifikasi, persetujuan, atau alasan penolakan..."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline resize-none leading-relaxed"
                            ></textarea>
                            <span v-if="formNote.errors.catatan" class="text-xs text-error mt-0.5 block">{{ formNote.errors.catatan }}</span>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/60">
                            <button
                                type="button"
                                @click="closeNoteModal"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-variant transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                            <button
                                type="submit"
                                :disabled="formNote.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-[0.98] disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="formNote.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                <span>{{ formNote.processing ? 'Menyimpan...' : 'Simpan Catatan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- ======================================================= -->
        <!-- MODAL KONFIRMASI HAPUS PERIZINAN                        -->
        <!-- ======================================================= -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDeleteConfirmModal && permitToDelete"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="closeDeleteConfirmModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200 p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>

                    <h3 class="text-base font-bold text-on-surface mb-1">Hapus Data Perizinan?</h3>
                    <p class="text-xs text-on-surface-variant mb-5">
                        Apakah Anda yakin ingin menghapus perizinan <strong>{{ permitToDelete.jenis }}</strong> ({{ permitToDelete.formatted_tanggal || permitToDelete.tanggal }})?
                    </p>

                    <div class="flex items-center justify-center gap-3">
                        <button
                            type="button"
                            @click="closeDeleteConfirmModal"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitDelete"
                            :disabled="isDeleting"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition-all shadow-sm active:scale-[0.98] disabled:opacity-50 cursor-pointer"
                        >
                            <span v-if="isDeleting" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                            <span v-else class="material-symbols-outlined text-[16px]">delete</span>
                            <span>{{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ======================================================= -->
        <!-- MODAL PREVIEW LAMPIRAN DOKUMEN                          -->
        <!-- ======================================================= -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showPreviewModal && previewData.url"
                class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/75 backdrop-blur-md"
                @click.self="closePreviewModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-4xl h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="flex items-center justify-between p-4 border-b border-outline-variant/60 bg-white shrink-0">
                        <div class="flex items-center gap-3 truncate mr-2">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[18px]">
                                    {{ previewData.isPdf ? 'picture_as_pdf' : 'image' }}
                                </span>
                            </div>
                            <div class="truncate">
                                <h3 class="text-xs sm:text-sm font-bold text-on-surface truncate">{{ previewData.name }}</h3>
                                <p class="text-[11px] text-on-surface-variant">Pratinjau Berkas Lampiran</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a
                                :href="previewData.url"
                                target="_blank"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary/10 hover:bg-primary/20 text-primary transition-colors cursor-pointer"
                                title="Buka di Tab Baru"
                            >
                                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                <span class="hidden sm:inline">Tab Baru</span>
                            </a>
                            <button
                                type="button"
                                @click="closePreviewModal"
                                class="p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-variant transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 bg-surface-container-low/40 p-2 sm:p-4 overflow-auto flex items-center justify-center">
                        <iframe
                            v-if="previewData.isPdf"
                            :src="previewData.url"
                            class="w-full h-full rounded-xl border border-outline-variant/60 bg-white shadow-inner"
                        ></iframe>
                        <img
                            v-else
                            :src="previewData.url"
                            :alt="previewData.name"
                            class="max-w-full max-h-full object-contain rounded-xl shadow-md"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
