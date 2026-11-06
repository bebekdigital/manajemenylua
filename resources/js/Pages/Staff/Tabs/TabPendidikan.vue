<script setup>
import { ref, reactive, computed, inject } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
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

// ==========================================
// 1. PENDIDIKAN FORMAL
// ==========================================
const isManagingPendidikan = ref(false);
const isPendidikanModalOpen = ref(false);
const modalPendidikanMode = ref('create'); // 'create' | 'edit'
const isSavingPendidikan = ref(false);

const pendidikanModalForm = reactive({
    id: null,
    jenjang_pendidikan: '',
    jurusan: '',
    instansi_pendidikan: '',
    tahun_lulus: '',
    pembiayaan: '',
});

const currentPendidikanList = computed(() => {
    return props.employee.riwayat_pendidikan || [];
});

function startManagePendidikan() {
    if (props.isEditing) {
        alert('Anda sedang dalam mode edit Informasi Umum. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum mengelola riwayat pendidikan.');
        return;
    }
    isManagingPendidikan.value = true;
    emit('managingChanged', true);
}

function finishManagePendidikan() {
    isManagingPendidikan.value = false;
    emit('managingChanged', false);
}

function openAddPendidikanModal() {
    modalPendidikanMode.value = 'create';
    pendidikanModalForm.id = null;
    pendidikanModalForm.jenjang_pendidikan = '';
    pendidikanModalForm.jurusan = '';
    pendidikanModalForm.instansi_pendidikan = '';
    pendidikanModalForm.tahun_lulus = '';
    pendidikanModalForm.pembiayaan = '';
    isPendidikanModalOpen.value = true;
}

function openEditPendidikanModal(record) {
    modalPendidikanMode.value = 'edit';
    pendidikanModalForm.id = record.id || null;
    pendidikanModalForm.jenjang_pendidikan = record.jenjang_pendidikan || '';
    pendidikanModalForm.jurusan = record.jurusan || '';
    pendidikanModalForm.instansi_pendidikan = record.instansi_pendidikan || '';
    pendidikanModalForm.tahun_lulus = record.tahun_lulus || '';
    pendidikanModalForm.pembiayaan = record.pembiayaan || '';
    isPendidikanModalOpen.value = true;
}

function closePendidikanModal() {
    isPendidikanModalOpen.value = false;
}

function submitPendidikanModal() {
    if (!pendidikanModalForm.jenjang_pendidikan && !pendidikanModalForm.instansi_pendidikan) {
        showToast('error', 'Mohon pilih Jenjang atau isi Instansi Pendidikan.', 'Validasi Form');
        return;
    }

    isSavingPendidikan.value = true;
    router.post(`/staff/${props.employee.id}/education`, {
        id: pendidikanModalForm.id || null,
        jenjang_pendidikan: pendidikanModalForm.jenjang_pendidikan,
        jurusan: pendidikanModalForm.jurusan,
        instansi_pendidikan: pendidikanModalForm.instansi_pendidikan,
        tahun_lulus: pendidikanModalForm.tahun_lulus,
        pembiayaan: pendidikanModalForm.pembiayaan,
    }, {
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            isPendidikanModalOpen.value = false;
            isSavingPendidikan.value = false;
            isManagingPendidikan.value = false;
            emit('managingChanged', false);
            showToast('success', page.props.flash?.success || 'Riwayat pendidikan berhasil disimpan.');
        },
        onError: () => {
            isSavingPendidikan.value = false;
            showToast('error', page.props.flash?.error || 'Gagal menyimpan riwayat pendidikan.');
        }
    });
}

function deletePendidikanRecord(idxOrItem) {
    const list = currentPendidikanList.value;
    const item = typeof idxOrItem === 'object' ? idxOrItem : list[idxOrItem];
    if (!item) return;

    requestDeleteConfirm({
        title: 'Hapus Riwayat Pendidikan',
        message: 'Apakah Anda yakin ingin menghapus catatan riwayat pendidikan ini?',
        itemLabel: item.jenjang_pendidikan ? `${item.jenjang_pendidikan} - ${item.instansi_pendidikan || ''}` : item.instansi_pendidikan || '',
        onConfirm: () => {
            if (item.id) {
                isSavingPendidikan.value = true;
                router.delete(`/staff/${props.employee.id}/education/${item.id}`, {
                    preserveScroll: true,
                    only: ['employee', 'flash'],
                    onSuccess: () => {
                        isSavingPendidikan.value = false;
                        isManagingPendidikan.value = false;
                        emit('managingChanged', false);
                        showToast('success', page.props.flash?.success || 'Riwayat pendidikan berhasil dihapus.');
                    },
                    onError: () => {
                        isSavingPendidikan.value = false;
                        showToast('error', page.props.flash?.error || 'Gagal menghapus riwayat pendidikan.');
                    }
                });
            } else {
                isManagingPendidikan.value = false;
                emit('managingChanged', false);
            }
        }
    });
}

// ==========================================
// 2. PELATIHAN & NONFORMAL
// ==========================================
const isManagingPelatihan = ref(false);
const isPelatihanModalOpen = ref(false);
const modalPelatihanMode = ref('create'); // 'create' | 'edit'
const isSavingPelatihan = ref(false);

const pelatihanModalForm = reactive({
    id: null,
    bidang: '',
    keahlian: '',
    instansi_penyelenggara: '',
    tahun: '',
    pembiayaan: '',
});

const currentPelatihanList = computed(() => {
    return props.employee.riwayat_pelatihan || [];
});

function startManagePelatihan() {
    if (props.isEditing) {
        alert('Anda sedang dalam mode edit Informasi Umum. Selesaikan (Simpan) atau batalkan (Batal) perubahan terlebih dahulu sebelum mengelola riwayat pelatihan.');
        return;
    }
    isManagingPelatihan.value = true;
    emit('managingChanged', true);
}

function finishManagePelatihan() {
    isManagingPelatihan.value = false;
    emit('managingChanged', false);
}

function openAddPelatihanModal() {
    modalPelatihanMode.value = 'create';
    pelatihanModalForm.id = null;
    pelatihanModalForm.bidang = '';
    pelatihanModalForm.keahlian = '';
    pelatihanModalForm.instansi_penyelenggara = '';
    pelatihanModalForm.tahun = '';
    pelatihanModalForm.pembiayaan = '';
    isPelatihanModalOpen.value = true;
}

function openEditPelatihanModal(record) {
    modalPelatihanMode.value = 'edit';
    pelatihanModalForm.id = record.id || null;
    pelatihanModalForm.bidang = record.bidang || '';
    pelatihanModalForm.keahlian = record.keahlian || '';
    pelatihanModalForm.instansi_penyelenggara = record.instansi_penyelenggara || '';
    pelatihanModalForm.tahun = record.tahun || '';
    pelatihanModalForm.pembiayaan = record.pembiayaan || '';
    isPelatihanModalOpen.value = true;
}

function closePelatihanModal() {
    isPelatihanModalOpen.value = false;
}

function submitPelatihanModal() {
    if (!pelatihanModalForm.bidang && !pelatihanModalForm.keahlian && !pelatihanModalForm.instansi_penyelenggara) {
        showToast('error', 'Mohon isi Bidang, Keahlian, atau Instansi Penyelenggara.', 'Validasi Form');
        return;
    }

    isSavingPelatihan.value = true;
    router.post(`/staff/${props.employee.id}/training`, {
        id: pelatihanModalForm.id || null,
        bidang: pelatihanModalForm.bidang,
        keahlian: pelatihanModalForm.keahlian,
        instansi_penyelenggara: pelatihanModalForm.instansi_penyelenggara,
        tahun: pelatihanModalForm.tahun,
        pembiayaan: pelatihanModalForm.pembiayaan,
    }, {
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            isPelatihanModalOpen.value = false;
            isSavingPelatihan.value = false;
            isManagingPelatihan.value = false;
            emit('managingChanged', false);
            showToast('success', page.props.flash?.success || 'Riwayat pelatihan & nonformal berhasil disimpan.');
        },
        onError: () => {
            isSavingPelatihan.value = false;
            showToast('error', page.props.flash?.error || 'Gagal menyimpan riwayat pelatihan.');
        }
    });
}

function deletePelatihanRecord(idxOrItem) {
    const list = currentPelatihanList.value;
    const item = typeof idxOrItem === 'object' ? idxOrItem : list[idxOrItem];
    if (!item) return;

    requestDeleteConfirm({
        title: 'Hapus Riwayat Pelatihan & Sertifikasi',
        message: 'Apakah Anda yakin ingin menghapus catatan riwayat pelatihan ini?',
        itemLabel: item.bidang ? `${item.bidang} (${item.instansi_penyelenggara || ''})` : item.keahlian || '',
        onConfirm: () => {
            if (item.id) {
                isSavingPelatihan.value = true;
                router.delete(`/staff/${props.employee.id}/training/${item.id}`, {
                    preserveScroll: true,
                    only: ['employee', 'flash'],
                    onSuccess: () => {
                        isSavingPelatihan.value = false;
                        isManagingPelatihan.value = false;
                        emit('managingChanged', false);
                        showToast('success', page.props.flash?.success || 'Riwayat pelatihan berhasil dihapus.');
                    },
                    onError: () => {
                        isSavingPelatihan.value = false;
                        showToast('error', page.props.flash?.error || 'Gagal menghapus riwayat pelatihan.');
                    }
                });
            } else {
                isManagingPelatihan.value = false;
                emit('managingChanged', false);
            }
        }
    });
}
</script>

<template>
    <div class="space-y-6 mb-8">
        <!-- 1. KOTAK SECTION: RIWAYAT PENDIDIKAN FORMAL -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Riwayat Pendidikan Formal
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ currentPendidikanList.length }} Riwayat
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
                <div v-if="canEdit" class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="!isManagingPendidikan"
                        type="button"
                        @click="startManagePendidikan"
                        :disabled="isEditing"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                            isEditing
                                ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                        ]"
                        :title="isEditing ? 'Selesaikan atau batalkan mode edit terlebih dahulu untuk mengelola riwayat pendidikan' : 'Kelola riwayat pendidikan formal'"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                            {{ isEditing ? 'lock' : 'tune' }}
                        </span>
                        <span>Kelola</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="finishManagePendidikan"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                        title="Selesai mengelola riwayat pendidikan formal"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">check</span>
                        <span>Selesai Kelola</span>
                    </button>
                </div>
            </div>

            <!-- 1.A. DESKTOP VIEW: TABEL PENDIDIKAN FORMAL -->
            <div class="hidden md:block">
                <!-- Tombol Tambah Riwayat di Atas Tabel -->
                <div v-if="isManagingPendidikan" class="mb-3.5 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="openAddPendidikanModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat Pendidikan Formal</span>
                    </button>
                    <span class="text-xs text-slate-500 font-medium">
                        Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data
                    </span>
                </div>

                <!-- Table Container -->
                <div v-if="currentPendidikanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th v-if="isManagingPendidikan" class="px-3 py-3 text-center w-14">Hapus</th>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Jenjang</th>
                                <th class="px-4 py-3 text-left">Jurusan</th>
                                <th class="px-4 py-3 text-left">Instansi Pendidikan</th>
                                <th class="px-4 py-3 text-center">Tahun Lulus</th>
                                <th class="px-4 py-3 text-left">Pembiayaan</th>
                                <th class="px-4 py-3 text-center">Berkas Ijazah</th>
                                <th v-if="isManagingPendidikan" class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(edu, idx) in currentPendidikanList"
                                :key="edu.id || idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td v-if="isManagingPendidikan" class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="deletePendidikanRecord(idx)"
                                        class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer border border-transparent hover:border-rose-200"
                                        title="Hapus riwayat pendidikan ini"
                                        :disabled="isSavingPendidikan"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>

                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs">
                                        {{ edu.jenjang_pendidikan || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-800 font-medium">{{ edu.jurusan || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-800 font-semibold">{{ edu.instansi_pendidikan || '-' }}</td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-900 font-mono">{{ edu.tahun_lulus || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-600 text-xs">{{ edu.pembiayaan || '-' }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <div v-if="edu.document" class="inline-flex items-center justify-center">
                                        <button
                                            type="button"
                                            @click="openPreview(edu.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer shadow-2xs"
                                            title="Lihat Berkas Ijazah"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat Ijazah</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>

                                <td v-if="isManagingPendidikan" class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditPendidikanModal(edu)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                        title="Edit riwayat ini"
                                        :disabled="isSavingPendidikan"
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
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">school</span>
                    <p class="text-sm font-semibold text-slate-700">Belum ada riwayat pendidikan formal</p>
                </div>
            </div>

            <!-- 1.B. MOBILE CARD VIEW: PENDIDIKAN FORMAL -->
            <div class="md:hidden">
                <div v-if="isManagingPendidikan" class="mb-4">
                    <button
                        type="button"
                        @click="openAddPendidikanModal"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat Pendidikan Formal</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(edu, idx) in currentPendidikanList"
                        :key="edu.id || idx"
                        class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs">
                                {{ edu.jenjang_pendidikan || '-' }}
                            </span>
                            <span class="text-xs font-bold text-slate-700 font-mono bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200">
                                Lulus: {{ edu.tahun_lulus || '-' }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Instansi / Universitas</label>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">{{ edu.instansi_pendidikan || '-' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Jurusan</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ edu.jurusan || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pembiayaan</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ edu.pembiayaan || '-' }}</p>
                                </div>
                            </div>
                            <div v-if="edu.document" class="pt-1">
                                <button
                                    type="button"
                                    @click="openPreview(edu.document)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer shadow-2xs"
                                >
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Lihat Ijazah</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="isManagingPendidikan" class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="deletePendidikanRecord(idx)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                :disabled="isSavingPendidikan"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                <span>Hapus</span>
                            </button>
                            <button
                                type="button"
                                @click="openEditPendidikanModal(edu)"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                :disabled="isSavingPendidikan"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="currentPendidikanList.length === 0" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                        <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">school</span>
                        <p class="text-xs font-semibold text-slate-600">Belum ada riwayat pendidikan formal</p>
                    </div>
                </div>
            </div>

            <!-- POP-UP MODAL TAMBAH & EDIT PENDIDIKAN FORMAL -->
            <Teleport to="body">
                <div
                    v-if="isPendidikanModalOpen"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                    @click.self="closePendidikanModal"
                >
                    <div class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-200">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ modalPendidikanMode === 'create' ? 'add_circle' : 'edit' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">
                                        {{ modalPendidikanMode === 'create' ? 'Tambah Pendidikan Formal' : 'Edit Pendidikan Formal' }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ modalPendidikanMode === 'create' ? 'Tambahkan riwayat jenjang pendidikan formal baru' : 'Perbarui data catatan pendidikan formal' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closePendidikanModal"
                                class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitPendidikanModal" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Jenjang Pendidikan <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="pendidikanModalForm.jenjang_pendidikan"
                                        required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 cursor-pointer"
                                    >
                                        <option value="">-- Pilih Jenjang --</option>
                                        <option value="SD">SD / Sederajat</option>
                                        <option value="SMP">SMP / MTs</option>
                                        <option value="SMA">SMA / SMK / MA</option>
                                        <option value="D1">D1</option>
                                        <option value="D2">D2</option>
                                        <option value="D3">D3</option>
                                        <option value="D4">D4</option>
                                        <option value="S1">S1 (Sarjana)</option>
                                        <option value="S2">S2 (Magister)</option>
                                        <option value="S3">S3 (Doktor)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tahun Lulus
                                    </label>
                                    <input
                                        v-model="pendidikanModalForm.tahun_lulus"
                                        type="text"
                                        placeholder="Contoh: 2018"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 font-mono focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Instansi / Universitas <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="pendidikanModalForm.instansi_pendidikan"
                                    type="text"
                                    required
                                    placeholder="Contoh: Universitas Sebelas Maret (UNS)"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Jurusan / Program Studi
                                    </label>
                                    <input
                                        v-model="pendidikanModalForm.jurusan"
                                        type="text"
                                        placeholder="Contoh: Pendidikan Bahasa Arab"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Pembiayaan
                                    </label>
                                    <input
                                        v-model="pendidikanModalForm.pembiayaan"
                                        type="text"
                                        placeholder="Contoh: Mandiri / Beasiswa"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-6">
                                <button
                                    type="button"
                                    @click="closePendidikanModal"
                                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="isSavingPendidikan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="isSavingPendidikan"
                                >
                                    <span v-if="isSavingPendidikan" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span>{{ isSavingPendidikan ? 'Menyimpan...' : (modalPendidikanMode === 'create' ? 'Simpan Riwayat' : 'Simpan Perubahan') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>

        <!-- 2. KOTAK SECTION: RIWAYAT PELATIHAN DAN PENDIDIKAN NONFORMAL -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Riwayat Pelatihan &amp; Pendidikan Nonformal
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ currentPelatihanList.length }} Catatan
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
                <div v-if="canEdit" class="flex items-center gap-2 shrink-0">
                    <button
                        v-if="!isManagingPelatihan"
                        type="button"
                        @click="startManagePelatihan"
                        :disabled="isEditing"
                        :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold shadow-xs transition-all whitespace-nowrap shrink-0',
                            isEditing
                                ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                                : 'bg-emerald-900 hover:bg-emerald-800 text-yellow-200 border border-emerald-950/30 cursor-pointer active:scale-95'
                        ]"
                        :title="isEditing ? 'Selesaikan atau batalkan mode edit terlebih dahulu untuk mengelola riwayat pelatihan' : 'Kelola riwayat pelatihan & nonformal'"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">
                            {{ isEditing ? 'lock' : 'tune' }}
                        </span>
                        <span>Kelola</span>
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="finishManagePelatihan"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                        title="Selesai mengelola riwayat pelatihan & nonformal"
                    >
                        <span class="material-symbols-outlined text-[16px] sm:text-[18px]">check</span>
                        <span>Selesai Kelola</span>
                    </button>
                </div>
            </div>

            <!-- 2.A. DESKTOP VIEW: TABEL PELATIHAN -->
            <div class="hidden md:block">
                <div v-if="isManagingPelatihan" class="mb-3.5 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="openAddPelatihanModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat Pelatihan &amp; Nonformal</span>
                    </button>
                    <span class="text-xs text-slate-500 font-medium">
                        Gunakan tombol hapus di kolom pertama atau tombol edit untuk mengubah data
                    </span>
                </div>

                <div v-if="currentPelatihanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th v-if="isManagingPelatihan" class="px-3 py-3 text-center w-14">Hapus</th>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Bidang</th>
                                <th class="px-4 py-3 text-left">Keahlian</th>
                                <th class="px-4 py-3 text-left">Instansi Penyelenggara</th>
                                <th class="px-4 py-3 text-center">Tahun</th>
                                <th class="px-4 py-3 text-left">Pembiayaan</th>
                                <th class="px-4 py-3 text-center">Berkas Sertifikat</th>
                                <th v-if="isManagingPelatihan" class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(tr, idx) in currentPelatihanList"
                                :key="tr.id || idx"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td v-if="isManagingPelatihan" class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="deletePelatihanRecord(idx)"
                                        class="w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 flex items-center justify-center mx-auto transition-colors cursor-pointer border border-transparent hover:border-rose-200"
                                        title="Hapus riwayat pelatihan ini"
                                        :disabled="isSavingPelatihan"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>

                                <td class="px-4 py-3.5 text-center text-slate-400 font-mono">{{ idx + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/80 shadow-2xs">
                                        {{ tr.bidang || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-800 font-bold">{{ tr.keahlian || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-700 font-medium">{{ tr.instansi_penyelenggara || '-' }}</td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-900 font-mono">{{ tr.tahun || '-' }}</td>
                                <td class="px-4 py-3.5 text-slate-600 text-xs">{{ tr.pembiayaan || '-' }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <div v-if="tr.document" class="inline-flex items-center justify-center">
                                        <button
                                            type="button"
                                            @click="openPreview(tr.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer shadow-2xs"
                                            title="Lihat Berkas Sertifikat"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat Sertifikat</span>
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">-</span>
                                </td>

                                <td v-if="isManagingPelatihan" class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="openEditPelatihanModal(tr)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                        title="Edit riwayat ini"
                                        :disabled="isSavingPelatihan"
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
                    <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">workspace_premium</span>
                    <p class="text-sm font-semibold text-slate-700">Belum ada riwayat pelatihan dan nonformal</p>
                </div>
            </div>

            <!-- 2.B. MOBILE CARD VIEW: PELATIHAN -->
            <div class="md:hidden">
                <div v-if="isManagingPelatihan" class="mb-4">
                    <button
                        type="button"
                        @click="openAddPelatihanModal"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Tambah Riwayat Pelatihan &amp; Nonformal</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(tr, idx) in currentPelatihanList"
                        :key="tr.id || idx"
                        class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200/80 shadow-2xs">
                                {{ tr.bidang || '-' }}
                            </span>
                            <span class="text-xs font-bold text-slate-700 font-mono bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200">
                                Tahun: {{ tr.tahun || '-' }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Keahlian / Materi</label>
                                <p class="text-sm font-bold text-slate-900 mt-0.5">{{ tr.keahlian || '-' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Penyelenggara</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ tr.instansi_penyelenggara || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Pembiayaan</label>
                                    <p class="text-xs sm:text-sm font-semibold text-slate-800 mt-0.5">{{ tr.pembiayaan || '-' }}</p>
                                </div>
                            </div>
                            <div v-if="tr.document" class="pt-1">
                                <button
                                    type="button"
                                    @click="openPreview(tr.document)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer shadow-2xs"
                                >
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    <span>Lihat Sertifikat</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="isManagingPelatihan" class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="deletePelatihanRecord(idx)"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors cursor-pointer"
                                :disabled="isSavingPelatihan"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                <span>Hapus</span>
                            </button>
                            <button
                                type="button"
                                @click="openEditPelatihanModal(tr)"
                                class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200 transition-colors cursor-pointer shadow-2xs"
                                :disabled="isSavingPelatihan"
                            >
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                <span>Edit</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="currentPelatihanList.length === 0" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                        <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">workspace_premium</span>
                        <p class="text-xs font-semibold text-slate-600">Belum ada riwayat pelatihan dan nonformal</p>
                    </div>
                </div>
            </div>

            <!-- POP-UP MODAL TAMBAH & EDIT PELATIHAN -->
            <Teleport to="body">
                <div
                    v-if="isPelatihanModalOpen"
                    class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-in fade-in duration-200"
                    @click.self="closePelatihanModal"
                >
                    <div class="w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl border border-slate-200 animate-in zoom-in-95 duration-200">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ modalPelatihanMode === 'create' ? 'add_circle' : 'edit' }}
                                    </span>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900">
                                        {{ modalPelatihanMode === 'create' ? 'Tambah Pelatihan & Nonformal' : 'Edit Pelatihan & Nonformal' }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ modalPelatihanMode === 'create' ? 'Tambahkan riwayat kursus, sertifikasi, atau pelatihan baru' : 'Perbarui data catatan pelatihan & pendidikan nonformal' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closePelatihanModal"
                                class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <form @submit.prevent="submitPelatihanModal" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Bidang Pelatihan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="pelatihanModalForm.bidang"
                                        type="text"
                                        required
                                        placeholder="Contoh: IT / Pedagogik / Bahasa"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tahun
                                    </label>
                                    <input
                                        v-model="pelatihanModalForm.tahun"
                                        type="text"
                                        placeholder="Contoh: 2024"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 font-mono focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Keahlian / Materi <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="pelatihanModalForm.keahlian"
                                    type="text"
                                    required
                                    placeholder="Contoh: Kurikulum Merdeka / Manajemen Berbasis Sekolah"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 font-semibold focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Instansi Penyelenggara
                                    </label>
                                    <input
                                        v-model="pelatihanModalForm.instansi_penyelenggara"
                                        type="text"
                                        placeholder="Contoh: Balai Diklat / Kemdikbud"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Pembiayaan
                                    </label>
                                    <input
                                        v-model="pelatihanModalForm.pembiayaan"
                                        type="text"
                                        placeholder="Contoh: Mandiri / Yayasan"
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100 mt-6">
                                <button
                                    type="button"
                                    @click="closePelatihanModal"
                                    class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-all cursor-pointer shadow-2xs"
                                    :disabled="isSavingPelatihan"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs sm:text-sm shadow-xs border border-emerald-950/30 active:scale-95 cursor-pointer disabled:opacity-60 transition-all"
                                    :disabled="isSavingPelatihan"
                                >
                                    <span v-if="isSavingPelatihan" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span>{{ isSavingPelatihan ? 'Menyimpan...' : (modalPelatihanMode === 'create' ? 'Simpan Riwayat' : 'Simpan Perubahan') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>

