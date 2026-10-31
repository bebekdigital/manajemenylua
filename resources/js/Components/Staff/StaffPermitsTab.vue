<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

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
// Modals State
// -------------------------------------------------------------
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showNoteModal = ref(false);
const showDeleteConfirmModal = ref(false);
const showPreviewModal = ref(false);

const previewData = ref({ url: '', name: '', isPdf: false });
const permitToDelete = ref(null);
const isDeleting = ref(false);

// -------------------------------------------------------------
// Form Create
// -------------------------------------------------------------
const todayStr = new Date().toISOString().split('T')[0];

const formCreate = useForm({
    tanggal: todayStr,
    tanggal_selesai: '',
    jenis: '',
    detail: '',
    keterangan: '',
    status: 'diajukan',
    catatan: '',
    lampiran: null,
});

function openCreateModal() {
    formCreate.reset();
    formCreate.clearErrors();
    formCreate.tanggal = todayStr;
    formCreate.status = 'diajukan';
    formCreate.lampiran = null;
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    formCreate.reset();
    formCreate.clearErrors();
}

function handleCreateFileChange(e) {
    formCreate.lampiran = e.target.files[0] || null;
}

function submitCreate() {
    formCreate.post(route('staff.permits.store', props.employee.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        },
    });
}

// -------------------------------------------------------------
// Form Edit
// -------------------------------------------------------------
const formEdit = useForm({
    id: null,
    tanggal: '',
    tanggal_selesai: '',
    jenis: '',
    detail: '',
    keterangan: '',
    status: 'diajukan',
    catatan: '',
    lampiran: null,
    current_lampiran_nama: '',
    current_preview_url: '',
});

function openEditModal(permit) {
    formEdit.clearErrors();
    formEdit.id = permit.id;
    formEdit.tanggal = permit.tanggal || '';
    formEdit.tanggal_selesai = permit.tanggal_selesai || '';
    formEdit.jenis = permit.jenis || '';
    formEdit.detail = permit.detail || '';
    formEdit.keterangan = permit.keterangan || '';
    formEdit.status = permit.status || 'diajukan';
    formEdit.catatan = permit.catatan || '';
    formEdit.lampiran = null;
    formEdit.current_lampiran_nama = permit.lampiran_nama || '';
    formEdit.current_preview_url = permit.preview_url || '';
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    formEdit.reset();
    formEdit.clearErrors();
}

function handleEditFileChange(e) {
    formEdit.lampiran = e.target.files[0] || null;
}

function submitEdit() {
    router.post(
        route('staff.permits.update', { id: props.employee.id, permitId: formEdit.id }),
        {
            _method: 'PUT',
            tanggal: formEdit.tanggal,
            tanggal_selesai: formEdit.tanggal_selesai,
            jenis: formEdit.jenis,
            detail: formEdit.detail,
            keterangan: formEdit.keterangan,
            status: formEdit.status,
            catatan: formEdit.catatan,
            lampiran: formEdit.lampiran,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
            },
            onError: (err) => {
                formEdit.setError(err);
            },
        }
    );
}

// -------------------------------------------------------------
// Form Note (Catatan & Status)
// -------------------------------------------------------------
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
// Preview
// -------------------------------------------------------------
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
    <div class="space-y-6">
        <!-- Header & Action Row -->
        <div class="bg-white rounded-2xl p-5 border border-outline-variant/60 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]">assignment_turned_in</span>
                    <h3 class="text-base sm:text-lg font-bold text-on-surface">Data Perizinan Pegawai</h3>
                </div>
                <p class="text-xs text-on-surface-variant">
                    Kelola riwayat pengajuan izin, sakit, cuti, dinas luar, lampiran surat, serta catatan persetujuan status.
                </p>
            </div>

            <button
                type="button"
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white text-xs sm:text-sm font-semibold hover:bg-primary/90 transition-all shadow-sm active:scale-[0.98] cursor-pointer shrink-0"
            >
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Ajukan Perizinan</span>
            </button>
        </div>

        <!-- Table Container -->
        <div class="overflow-hidden rounded-2xl border border-outline-variant/60 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-on-surface-variant font-bold text-[11px] sm:text-xs uppercase tracking-wider">
                            <th class="px-4 py-3 text-center w-12 border-r border-outline-variant/40">No</th>
                            <th class="px-4 py-3 text-left w-36 border-r border-outline-variant/40">Tanggal</th>
                            <th class="px-4 py-3 text-left w-36 border-r border-outline-variant/40">Jenis</th>
                            <th class="px-4 py-3 text-left w-48 border-r border-outline-variant/40">Detail</th>
                            <th class="px-4 py-3 text-left border-r border-outline-variant/40">Keterangan</th>
                            <th class="px-4 py-3 text-center w-36 border-r border-outline-variant/40">Lampiran</th>
                            <th class="px-4 py-3 text-center w-32 border-r border-outline-variant/40">Status</th>
                            <th class="px-4 py-3 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        <tr
                            v-for="(item, idx) in permitsList"
                            :key="item.id"
                            class="hover:bg-slate-50/80 transition-colors"
                        >
                            <!-- No -->
                            <td class="px-4 py-3.5 text-center text-on-surface-variant font-mono text-xs border-r border-outline-variant/40">
                                {{ idx + 1 }}
                            </td>

                            <!-- Tanggal -->
                            <td class="px-4 py-3.5 text-on-surface whitespace-nowrap border-r border-outline-variant/40 font-medium">
                                {{ item.formatted_tanggal || item.tanggal || '-' }}
                            </td>

                            <!-- Jenis -->
                            <td class="px-4 py-3.5 font-bold text-on-surface border-r border-outline-variant/40">
                                <span class="px-2.5 py-1 rounded-lg bg-surface-container-high text-on-surface font-semibold text-xs inline-block">
                                    {{ item.jenis || '-' }}
                                </span>
                            </td>

                            <!-- Detail -->
                            <td class="px-4 py-3.5 text-on-surface border-r border-outline-variant/40 font-medium">
                                {{ item.detail || '-' }}
                            </td>

                            <!-- Keterangan -->
                            <td class="px-4 py-3.5 text-on-surface-variant border-r border-outline-variant/40">
                                <div class="max-w-xs xl:max-w-md line-clamp-2 text-xs leading-relaxed" :title="item.keterangan">
                                    {{ item.keterangan || '-' }}
                                </div>
                            </td>

                            <!-- Lampiran -->
                            <td class="px-4 py-3.5 text-center border-r border-outline-variant/40">
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
                            <td class="px-4 py-3.5 text-center border-r border-outline-variant/40">
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
                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1 justify-center">
                                    <!-- Aksi: Catatan -->
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

                                    <!-- Aksi: Edit -->
                                    <button
                                        type="button"
                                        @click="openEditModal(item)"
                                        class="inline-flex items-center justify-center p-1.5 rounded-lg bg-white border border-outline-variant/60 hover:bg-slate-100 text-slate-700 transition-colors cursor-pointer"
                                        title="Edit Data Perizinan"
                                    >
                                        <span class="material-symbols-outlined text-[17px]">edit</span>
                                    </button>

                                    <!-- Aksi: Hapus -->
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
                        </tr>

                        <!-- Empty State Row -->
                        <tr v-if="permitsList.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 text-on-surface-variant max-w-sm mx-auto">
                                    <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center text-outline">
                                        <span class="material-symbols-outlined text-2xl">event_busy</span>
                                    </div>
                                    <h4 class="font-bold text-sm text-on-surface">Belum Ada Data Perizinan</h4>
                                    <p class="text-xs text-on-surface-variant mb-2">
                                        Pegawai ini belum memiliki riwayat pengajuan perizinan.
                                    </p>
                                    <button
                                        type="button"
                                        @click="openCreateModal"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary font-bold text-xs transition-colors cursor-pointer"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                        <span>Ajukan Perizinan Pertama</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- MODAL 1: AJUKAN / TAMBAH PERIZINAN                      -->
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
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto"
                @click.self="closeCreateModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/60 bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface">Ajukan Perizinan Pegawai</h3>
                                <p class="text-xs text-on-surface-variant">{{ employee.nama }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-variant transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitCreate" class="p-5 space-y-4">
                        <!-- Tanggal & Tanggal Selesai -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                    Tanggal Mulai <span class="text-error">*</span>
                                </label>
                                <input
                                    v-model="formCreate.tanggal"
                                    type="date"
                                    required
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                                />
                                <span v-if="formCreate.errors.tanggal" class="text-xs text-error mt-0.5 block">{{ formCreate.errors.tanggal }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                    Tanggal Selesai (Opsional)
                                </label>
                                <input
                                    v-model="formCreate.tanggal_selesai"
                                    type="date"
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                                />
                                <span v-if="formCreate.errors.tanggal_selesai" class="text-xs text-error mt-0.5 block">{{ formCreate.errors.tanggal_selesai }}</span>
                            </div>
                        </div>

                        <!-- Jenis (Format Biasa) -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Jenis Perizinan <span class="text-error">*</span>
                            </label>
                            <input
                                v-model="formCreate.jenis"
                                type="text"
                                required
                                placeholder="Contoh: Izin, Sakit, Cuti, Dinas Luar, dll."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline"
                            />
                            <span v-if="formCreate.errors.jenis" class="text-xs text-error mt-0.5 block">{{ formCreate.errors.jenis }}</span>
                        </div>

                        <!-- Detail (Format Biasa) -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Detail Perizinan
                            </label>
                            <input
                                v-model="formCreate.detail"
                                type="text"
                                placeholder="Contoh: Keperluan keluarga, rawat inap, pelatihan kurikulum, dll."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline"
                            />
                            <span v-if="formCreate.errors.detail" class="text-xs text-error mt-0.5 block">{{ formCreate.errors.detail }}</span>
                        </div>

                        <!-- Keterangan -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Keterangan / Alasan
                            </label>
                            <textarea
                                v-model="formCreate.keterangan"
                                rows="3"
                                placeholder="Tuliskan keterangan detail atau rincian perizinan..."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary placeholder:text-outline resize-none"
                            ></textarea>
                            <span v-if="formCreate.errors.keterangan" class="text-xs text-error mt-0.5 block">{{ formCreate.errors.keterangan }}</span>
                        </div>

                        <!-- Lampiran -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Berkas Lampiran (Opsional)
                            </label>
                            <input
                                type="file"
                                @change="handleCreateFileChange"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                class="w-full text-xs text-on-surface-variant file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer cursor-pointer border border-outline-variant/60 rounded-xl p-1 bg-white"
                            />
                            <p class="text-[11px] text-on-surface-variant mt-1">Format: PDF, JPG, PNG, WEBP (Maksimal 10MB)</p>
                            <span v-if="formCreate.errors.lampiran" class="text-xs text-error mt-0.5 block">{{ formCreate.errors.lampiran }}</span>
                        </div>

                        <!-- Status (Hanya untuk Admin / Pimpinan jika bukan Guru) -->
                        <div v-if="!isTeacher">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Status Awal
                            </label>
                            <select
                                v-model="formCreate.status"
                                class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary cursor-pointer"
                            >
                                <option value="diajukan">Diajukan</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        <!-- Catatan Opsional -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Catatan Tambahan (Opsional)
                            </label>
                            <input
                                v-model="formCreate.catatan"
                                type="text"
                                placeholder="Catatan internal / tindak lanjut..."
                                class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline"
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/60">
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-variant transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="formCreate.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-[0.98] disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="formCreate.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                <span>{{ formCreate.processing ? 'Menyimpan...' : 'Simpan Perizinan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- ======================================================= -->
        <!-- MODAL 2: EDIT PERIZINAN                                 -->
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
                v-if="showEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs overflow-y-auto"
                @click.self="closeEditModal"
            >
                <div class="bg-white rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200 my-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-5 border-b border-outline-variant/60 bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface">Edit Data Perizinan</h3>
                                <p class="text-xs text-on-surface-variant">{{ employee.nama }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="p-1.5 rounded-lg text-outline hover:text-on-surface hover:bg-surface-variant transition-colors cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitEdit" class="p-5 space-y-4">
                        <!-- Tanggal & Tanggal Selesai -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                    Tanggal Mulai <span class="text-error">*</span>
                                </label>
                                <input
                                    v-model="formEdit.tanggal"
                                    type="date"
                                    required
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                                />
                                <span v-if="formEdit.errors.tanggal" class="text-xs text-error mt-0.5 block">{{ formEdit.errors.tanggal }}</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                    Tanggal Selesai (Opsional)
                                </label>
                                <input
                                    v-model="formEdit.tanggal_selesai"
                                    type="date"
                                    class="w-full px-3 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary"
                                />
                                <span v-if="formEdit.errors.tanggal_selesai" class="text-xs text-error mt-0.5 block">{{ formEdit.errors.tanggal_selesai }}</span>
                            </div>
                        </div>

                        <!-- Jenis -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Jenis Perizinan <span class="text-error">*</span>
                            </label>
                            <input
                                v-model="formEdit.jenis"
                                type="text"
                                required
                                placeholder="Contoh: Izin, Sakit, Cuti, Dinas Luar, dll."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline"
                            />
                            <span v-if="formEdit.errors.jenis" class="text-xs text-error mt-0.5 block">{{ formEdit.errors.jenis }}</span>
                        </div>

                        <!-- Detail -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Detail Perizinan
                            </label>
                            <input
                                v-model="formEdit.detail"
                                type="text"
                                placeholder="Contoh: Keperluan keluarga, rawat inap, dll."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline"
                            />
                            <span v-if="formEdit.errors.detail" class="text-xs text-error mt-0.5 block">{{ formEdit.errors.detail }}</span>
                        </div>

                        <!-- Keterangan -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Keterangan / Alasan
                            </label>
                            <textarea
                                v-model="formEdit.keterangan"
                                rows="3"
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline resize-none"
                            ></textarea>
                            <span v-if="formEdit.errors.keterangan" class="text-xs text-error mt-0.5 block">{{ formEdit.errors.keterangan }}</span>
                        </div>

                        <!-- Lampiran & File Terpasang -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Berkas Lampiran
                            </label>

                            <div v-if="formEdit.current_lampiran_nama" class="mb-2 p-2.5 rounded-xl bg-slate-50 border border-outline-variant/40 flex items-center justify-between">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="material-symbols-outlined text-primary text-[18px]">attach_file</span>
                                    <span class="text-xs font-semibold text-on-surface truncate">{{ formEdit.current_lampiran_nama }}</span>
                                </div>
                                <span class="text-[10px] text-on-surface-variant px-2 py-0.5 rounded bg-white border border-outline-variant/40">Tersimpan</span>
                            </div>

                            <input
                                type="file"
                                @change="handleEditFileChange"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                class="w-full text-xs text-on-surface-variant file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 file:cursor-pointer cursor-pointer border border-outline-variant/60 rounded-xl p-1 bg-white"
                            />
                            <p class="text-[11px] text-on-surface-variant mt-1">Pilih berkas baru jika ingin mengganti lampiran saat ini.</p>
                            <span v-if="formEdit.errors.lampiran" class="text-xs text-error mt-0.5 block">{{ formEdit.errors.lampiran }}</span>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Status Perizinan
                            </label>
                            <select
                                v-model="formEdit.status"
                                :disabled="isTeacher"
                                class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary cursor-pointer disabled:bg-slate-100 disabled:cursor-not-allowed"
                            >
                                <option value="diajukan">Diajukan</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Catatan
                            </label>
                            <textarea
                                v-model="formEdit.catatan"
                                rows="2"
                                placeholder="Catatan persetujuan / catatan verifikasi..."
                                class="w-full px-3.5 py-2 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline resize-none"
                            ></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/60">
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-on-surface-variant hover:bg-surface-variant transition-colors cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="formEdit.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm active:scale-[0.98] disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="formEdit.processing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                <span v-else class="material-symbols-outlined text-[16px]">save</span>
                                <span>{{ formEdit.processing ? 'Menyimpan...' : 'Perbarui Perizinan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- ======================================================= -->
        <!-- MODAL 3: CATATAN & STATUS PERIZINAN                     -->
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
                    <!-- Modal Header -->
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

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitNote" class="p-5 space-y-4">
                        <!-- Status Pilihan Cepat -->
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

                        <!-- Catatan Textarea -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">
                                Isi Catatan / Alasan / Tindak Lanjut
                            </label>
                            <textarea
                                v-model="formNote.catatan"
                                rows="4"
                                placeholder="Contoh: Disetujui oleh Kepala Sekolah, atau alasan jika izin ditolak..."
                                class="w-full px-3.5 py-2.5 bg-white border border-outline-variant/60 rounded-xl text-xs sm:text-sm text-on-surface focus:outline-none focus:border-primary placeholder:text-outline resize-none leading-relaxed"
                            ></textarea>
                            <span v-if="formNote.errors.catatan" class="text-xs text-error mt-0.5 block">{{ formNote.errors.catatan }}</span>
                        </div>

                        <!-- Action Buttons -->
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
        <!-- MODAL 4: KONFIRMASI HAPUS PERIZINAN                     -->
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
                        Apakah Anda yakin ingin menghapus data perizinan <strong>{{ permitToDelete.jenis }}</strong> ({{ permitToDelete.formatted_tanggal || permitToDelete.tanggal }})? Tindakan ini tidak dapat dibatalkan.
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
        <!-- MODAL 5: PREVIEW LAMPIRAN DOKUMEN                       -->
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
                    <!-- Modal Header -->
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

                    <!-- Modal Body (Preview Iframe / Image) -->
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
