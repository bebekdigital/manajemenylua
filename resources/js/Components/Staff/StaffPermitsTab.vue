<script setup>
import { ref, watch, nextTick } from 'vue';
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

const todayStr = new Date().toISOString().split('T')[0];

// Array of rows currently displayed in the table
const rows = ref([]);

function initializeRows() {
    const list = props.employee?.perizinan || [];
    rows.value = list.map((item) => ({
        id: item.id,
        isNew: false,
        isSaving: false,
        isDirty: false,
        tanggal: item.tanggal || '',
        tanggal_selesai: item.tanggal_selesai || '',
        jenis: item.jenis || '',
        detail: item.detail || '',
        keterangan: item.keterangan || '',
        status: item.status || 'diajukan',
        catatan: item.catatan || '',
        lampiran: null,
        lampiran_nama: item.lampiran_nama || '',
        preview_url: item.preview_url || '',
        download_url: item.download_url || '',
    }));
}

// Watch for changes in props.employee.perizinan
watch(
    () => props.employee?.perizinan,
    () => {
        initializeRows();
    },
    { immediate: true, deep: true }
);

function markDirty(row) {
    row.isDirty = true;
}

function onFileChange(row, e) {
    row.lampiran = e.target.files[0] || null;
    row.isDirty = true;
}

// Add a new row to the table (Identical to addRiwayatJabatanRow / addEducationRow)
function addNewPermitRow() {
    const newRow = {
        id: null,
        isNew: true,
        isSaving: false,
        isDirty: true,
        tanggal: todayStr,
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
    };
    rows.value.push(newRow);

    nextTick(() => {
        // Scroll to the bottom of the table smoothly
        const inputs = document.querySelectorAll('input[data-permit-jenis]');
        if (inputs.length > 0) {
            inputs[inputs.length - 1].focus();
        }
    });
}

// Save a row (either new or existing)
function saveRow(row) {
    if (!row.jenis || !row.jenis.trim()) {
        alert('Jenis perizinan wajib diisi.');
        return;
    }

    row.isSaving = true;

    if (row.isNew) {
        // Create new permit
        router.post(
            route('staff.permits.store', props.employee.id),
            {
                tanggal: row.tanggal || todayStr,
                tanggal_selesai: row.tanggal_selesai || null,
                jenis: row.jenis,
                detail: row.detail || null,
                keterangan: row.keterangan || null,
                status: props.isTeacher ? 'diajukan' : row.status,
                lampiran: row.lampiran,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    row.isDirty = false;
                },
                onFinish: () => {
                    row.isSaving = false;
                },
            }
        );
    } else {
        // Update existing permit
        router.post(
            route('staff.permits.update', { id: props.employee.id, permitId: row.id }),
            {
                _method: 'PUT',
                tanggal: row.tanggal,
                tanggal_selesai: row.tanggal_selesai || null,
                jenis: row.jenis,
                detail: row.detail || null,
                keterangan: row.keterangan || null,
                status: props.isTeacher ? undefined : row.status,
                lampiran: row.lampiran,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    row.isDirty = false;
                },
                onFinish: () => {
                    row.isSaving = false;
                },
            }
        );
    }
}

// Delete Confirmation
const showDeleteConfirmModal = ref(false);
const rowToDelete = ref(null);
const indexToDelete = ref(null);
const isDeleting = ref(false);

function confirmDeleteRow(row, idx) {
    if (row.isNew) {
        // If not saved to database yet, simply remove from array
        rows.value.splice(idx, 1);
        return;
    }

    rowToDelete.value = row;
    indexToDelete.value = idx;
    showDeleteConfirmModal.value = true;
}

function closeDeleteConfirmModal() {
    showDeleteConfirmModal.value = false;
    rowToDelete.value = null;
    indexToDelete.value = null;
}

function submitDelete() {
    if (!rowToDelete.value) return;
    isDeleting.value = true;
    router.delete(
        route('staff.permits.destroy', { id: props.employee.id, permitId: rowToDelete.value.id }),
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                closeDeleteConfirmModal();
            },
        }
    );
}

// Modal Catatan
const showNoteModal = ref(false);
const formNote = useForm({
    id: null,
    jenis: '',
    detail: '',
    status: 'diajukan',
    catatan: '',
});

function openNoteModal(row) {
    if (row.isNew) {
        alert('Silakan simpan baris perizinan ini terlebih dahulu sebelum menambahkan catatan.');
        return;
    }
    formNote.clearErrors();
    formNote.id = row.id;
    formNote.jenis = row.jenis;
    formNote.detail = row.detail;
    formNote.status = row.status || 'diajukan';
    formNote.catatan = row.catatan || '';
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

// Modal Preview
const showPreviewModal = ref(false);
const previewData = ref({ url: '', name: '', isPdf: false });

function openPreview(row) {
    if (!row.preview_url) return;
    const isPdf = (row.lampiran_nama || '').toLowerCase().endsWith('.pdf');
    previewData.value = {
        url: row.preview_url,
        name: row.lampiran_nama || 'Lampiran Perizinan',
        isPdf,
    };
    showPreviewModal.value = true;
}

function closePreviewModal() {
    showPreviewModal.value = false;
    previewData.value = { url: '', name: '', isPdf: false };
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <!-- Header Section (Sama persis seperti Riwayat Jabatan & Riwayat Pendidikan) -->
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div>
                    <h4 class="text-xs sm:text-sm font-bold text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">assignment_turned_in</span>
                        Riwayat Perizinan
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Edit data langsung di baris tabel atau klik tombol di baris paling bawah untuk menambah riwayat perizinan baru
                    </p>
                </div>
                <span class="text-xs text-outline font-medium">
                    {{ rows.length }} Perizinan
                </span>
            </div>

            <!-- Tabel Perizinan (Format sama persis seperti tabel Riwayat Jabatan di Tab Penugasan) -->
            <div class="overflow-x-auto rounded-xl border border-primary/30 bg-white shadow-2xs">
                <table class="w-full text-xs sm:text-sm border-collapse border border-outline-variant/60 min-w-[1050px]">
                    <thead>
                        <tr class="bg-primary/5 text-primary font-bold text-[10px] sm:text-xs uppercase tracking-wider">
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-center w-12">NO</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-left min-w-[145px]">TANGGAL MULAI</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-left min-w-[145px]">TANGGAL AKHIR</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-left min-w-[140px]">JENIS</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-left min-w-[160px]">DETAIL</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-left min-w-[180px]">KETERANGAN</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-center min-w-[140px]">LAMPIRAN</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-center min-w-[130px]">STATUS</th>
                            <th class="border border-outline-variant/60 px-3 py-2.5 text-center w-28">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Empty State jika belum ada baris -->
                        <tr v-if="rows.length === 0">
                            <td colspan="9" class="border border-outline-variant/60 p-6 text-center text-xs text-on-surface-variant italic">
                                <span class="material-symbols-outlined text-2xl text-outline/60 block mb-1">event_busy</span>
                                Belum ada riwayat perizinan. Klik tombol di bawah untuk menambah riwayat perizinan baru.
                            </td>
                        </tr>

                        <!-- Baris Perizinan (Editable Langsung di Tabel) -->
                        <tr
                            v-for="(row, idx) in rows"
                            :key="row.id || 'new_' + idx"
                            :class="[
                                'transition-colors',
                                row.isNew ? 'bg-primary/5' : 'hover:bg-slate-50/70'
                            ]"
                        >
                            <!-- 1. NO -->
                            <td class="border border-outline-variant/60 px-3 py-2.5 text-center text-on-surface-variant font-mono font-bold">
                                {{ idx + 1 }}
                            </td>

                            <!-- 2. TANGGAL MULAI -->
                            <td class="border border-outline-variant/60 px-3 py-2.5">
                                <input
                                    v-model="row.tanggal"
                                    type="date"
                                    @change="markDirty(row)"
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                            </td>

                            <!-- 3. TANGGAL AKHIR -->
                            <td class="border border-outline-variant/60 px-3 py-2.5">
                                <input
                                    v-model="row.tanggal_selesai"
                                    type="date"
                                    @change="markDirty(row)"
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 cursor-pointer"
                                />
                                <span class="text-[10px] text-outline mt-0.5 block">Kosongkan jika 1 hari</span>
                            </td>

                            <!-- 4. JENIS (Format biasa, bukan dropdown) -->
                            <td class="border border-outline-variant/60 px-3 py-2.5">
                                <input
                                    v-model="row.jenis"
                                    type="text"
                                    data-permit-jenis
                                    @input="markDirty(row)"
                                    placeholder="Izin, Sakit, Cuti..."
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface font-semibold focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </td>

                            <!-- 5. DETAIL (Format biasa, bukan dropdown) -->
                            <td class="border border-outline-variant/60 px-3 py-2.5">
                                <input
                                    v-model="row.detail"
                                    type="text"
                                    @input="markDirty(row)"
                                    placeholder="Detail perizinan..."
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </td>

                            <!-- 6. KETERANGAN -->
                            <td class="border border-outline-variant/60 px-3 py-2.5">
                                <input
                                    v-model="row.keterangan"
                                    type="text"
                                    @input="markDirty(row)"
                                    placeholder="Keterangan / alasan..."
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />
                            </td>

                            <!-- 7. LAMPIRAN -->
                            <td class="border border-outline-variant/60 px-3 py-2.5 text-center">
                                <div class="flex flex-col items-center gap-1.5 justify-center">
                                    <div v-if="row.preview_url" class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="openPreview(row)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            :href="row.download_url"
                                            class="inline-flex items-center justify-center p-1 rounded-lg text-xs font-semibold bg-white border border-outline-variant/60 hover:bg-slate-100 text-on-surface transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                        </a>
                                    </div>
                                    <input
                                        type="file"
                                        @change="e => onFileChange(row, e)"
                                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                                        class="w-full max-w-[140px] text-[10px] text-on-surface-variant file:mr-1 file:py-0.5 file:px-1.5 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-primary/10 file:text-primary cursor-pointer border border-outline-variant/40 rounded-lg p-0.5 bg-white"
                                        :title="row.lampiran_nama ? 'Ganti berkas: ' + row.lampiran_nama : 'Pilih berkas lampiran'"
                                    />
                                </div>
                            </td>

                            <!-- 8. STATUS (diajukan, disetujui, ditolak) -->
                            <td class="border border-outline-variant/60 px-3 py-2.5 text-center">
                                <!-- Mode Tambah Baru (isNew): Tanpa dropdown, selalu Diajukan -->
                                <span
                                    v-if="row.isNew"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Diajukan
                                </span>

                                <!-- Mode Baris Sudah Tersimpan: Dropdown untuk Admin, Badge untuk Guru -->
                                <template v-else>
                                    <select
                                        v-if="!isTeacher"
                                        v-model="row.status"
                                        @change="markDirty(row)"
                                        :class="[
                                            'w-full px-2.5 py-2 border rounded-xl text-xs sm:text-sm font-bold cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-primary/20',
                                            row.status === 'disetujui' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' :
                                            row.status === 'ditolak' ? 'bg-rose-50 border-rose-300 text-rose-800' :
                                            'bg-amber-50 border-amber-300 text-amber-800'
                                        ]"
                                    >
                                        <option value="diajukan">Diajukan</option>
                                        <option value="disetujui">Disetujui</option>
                                        <option value="ditolak">Ditolak</option>
                                    </select>
                                    <span
                                        v-else
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border',
                                            row.status === 'disetujui' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                                            row.status === 'ditolak' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-amber-50 text-amber-700 border-amber-200'
                                        ]"
                                    >
                                        {{ row.status === 'disetujui' ? 'Disetujui' : row.status === 'ditolak' ? 'Ditolak' : 'Diajukan' }}
                                    </span>
                                </template>
                            </td>

                            <!-- 9. AKSI -->
                            <td class="border border-outline-variant/60 px-3 py-2.5 text-center">
                                <!-- Mode Tambah Baru (isNew): HANYA Tampilkan Simpan dan Hapus -->
                                <div v-if="row.isNew" class="inline-flex items-center gap-1.5 justify-center">
                                    <!-- Simpan -->
                                    <button
                                        type="button"
                                        @click="saveRow(row)"
                                        :disabled="row.isSaving"
                                        class="inline-flex items-center justify-center p-2 rounded-xl bg-primary text-white hover:bg-primary/90 transition-all shadow-2xs active:scale-95 disabled:opacity-50 cursor-pointer"
                                        title="Simpan baris perizinan baru ini"
                                    >
                                        <span v-if="row.isSaving" class="material-symbols-outlined text-[17px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[17px]">save</span>
                                    </button>

                                    <!-- Hapus -->
                                    <button
                                        type="button"
                                        @click="confirmDeleteRow(row, idx)"
                                        class="w-8 h-8 rounded-xl text-error hover:bg-error/10 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Batalkan / hapus baris ini"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>

                                <!-- Mode Baris Sudah Tersimpan: Tampilkan Catatan, Simpan, dan Hapus -->
                                <div v-else class="inline-flex items-center gap-1.5 justify-center">
                                    <!-- Catatan -->
                                    <button
                                        type="button"
                                        @click="openNoteModal(row)"
                                        :class="[
                                            'relative w-8 h-8 rounded-xl border flex items-center justify-center transition-colors cursor-pointer',
                                            row.catatan
                                                ? 'bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100'
                                                : 'bg-white border-outline-variant/60 text-slate-500 hover:bg-slate-100'
                                        ]"
                                        :title="row.catatan ? 'Catatan: ' + row.catatan : 'Beri Catatan'"
                                    >
                                        <span class="material-symbols-outlined text-[17px]">note_alt</span>
                                        <span
                                            v-if="row.catatan"
                                            class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-amber-500 border border-white"
                                        ></span>
                                    </button>

                                    <!-- Simpan (Perubahan) -->
                                    <button
                                        type="button"
                                        @click="saveRow(row)"
                                        :disabled="row.isSaving"
                                        class="inline-flex items-center justify-center p-2 rounded-xl bg-primary text-white hover:bg-primary/90 transition-all shadow-2xs active:scale-95 disabled:opacity-50 cursor-pointer"
                                        title="Simpan perubahan baris ini"
                                    >
                                        <span v-if="row.isSaving" class="material-symbols-outlined text-[17px] animate-spin">sync</span>
                                        <span v-else class="material-symbols-outlined text-[17px]">save</span>
                                    </button>

                                    <!-- Hapus -->
                                    <button
                                        type="button"
                                        @click="confirmDeleteRow(row, idx)"
                                        class="w-8 h-8 rounded-xl text-error hover:bg-error/10 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Hapus baris perizinan ini"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- BARIS PALING BAWAH: TOMBOL TAMBAH RIWAYAT PERIZINAN (PERSIS SEPERTI DI SCREENSHOT) -->
                        <tr class="bg-slate-50/70 hover:bg-slate-100/70 transition-colors">
                            <td colspan="9" class="border border-outline-variant/60 p-2.5 text-center">
                                <button
                                    type="button"
                                    @click="addNewPermitRow"
                                    class="w-full py-2.5 px-4 rounded-xl border border-dashed border-primary/50 hover:border-primary bg-primary/5 hover:bg-primary/10 text-primary font-bold text-xs sm:text-sm flex items-center justify-center gap-2 transition-all cursor-pointer active:scale-[0.99]"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                    <span>Tambah Riwayat Perizinan</span>
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
                v-if="showDeleteConfirmModal && rowToDelete"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="closeDeleteConfirmModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-sm overflow-hidden animate-in fade-in zoom-in-95 duration-200 p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>

                    <h3 class="text-base font-bold text-on-surface mb-1">Hapus Data Perizinan?</h3>
                    <p class="text-xs text-on-surface-variant mb-5">
                        Apakah Anda yakin ingin menghapus perizinan <strong>{{ rowToDelete.jenis }}</strong> ({{ rowToDelete.tanggal }})?
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
