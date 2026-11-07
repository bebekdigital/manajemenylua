<script setup>
import { ref, computed, inject } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { formatTanggalIndo } from '../staffHelpers';
import { compressImageToJpeg } from '@/Utils/imageCompressor';

const props = defineProps({
    employee: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const openPreview = inject('openPreview', () => {});
const requestDeleteConfirm = inject('requestDeleteConfirm', () => {});
const showToast = inject('showToast', () => {});

// ==========================================
// 1. DOKUMEN PRIBADI
// ==========================================
const dokumenPribadiList = computed(() => {
    const list = [];
    let no = 1;

    // 1. KTP
    list.push({
        id: 'ktp',
        no: no++,
        nama: 'KTP (Kartu Tanda Penduduk)',
        keterangan: props.employee.nik ? `NIK: ${props.employee.nik}` : 'NIK belum diisi',
        document: props.employee.dokumen_ktp,
        uploadCategory: 'ktp',
        recordType: null,
        recordId: null,
        uploadTitle: `KTP - ${props.employee.nama}`,
        isAdditional: false,
    });

    // 2. KK
    list.push({
        id: 'kk',
        no: no++,
        nama: 'KK (Kartu Keluarga)',
        keterangan: 'Dokumen resmi Kartu Keluarga',
        document: props.employee.dokumen_kk,
        uploadCategory: 'kk',
        recordType: null,
        recordId: null,
        uploadTitle: `KK - ${props.employee.nama}`,
        isAdditional: false,
    });

    return list;
});

// ==========================================
// 2. DOKUMEN RIWAYAT KEPEGAWAIAN
// ==========================================
const dokumenKepegawaianList = computed(() => {
    const list = [];
    let no = 1;

    // 1. SK Pengangkatan Awal
    const tmtAwal = props.employee.formatted_tmt_pengangkatan || formatTanggalIndo(props.employee.tmt_pengangkatan) || '-';
    const tstAwal = props.employee.formatted_tst_pengangkatan || formatTanggalIndo(props.employee.tst_pengangkatan) || 'Tetap';
    list.push({
        id: 'sk_pengangkatan',
        no: no++,
        nama: 'SK Pengangkatan Awal',
        keterangan: `TMT: ${tmtAwal} • TST: ${tstAwal}`,
        document: props.employee.dokumen_sk_pengangkatan,
        uploadCategory: 'sk_pengangkatan',
        recordType: null,
        recordId: null,
        uploadTitle: `SK Pengangkatan - ${props.employee.nama}`,
        isAdditional: false,
    });

    // 2. SK Jenjang Kepegawaian
    if (props.employee.riwayat_kepegawaian && props.employee.riwayat_kepegawaian.length > 0) {
        props.employee.riwayat_kepegawaian.forEach((rec) => {
            const jenjang = rec.jenjang_kepegawaian || '-';
            const tmt = rec.formatted_tmt_kenaikan_jenjang || formatTanggalIndo(rec.tmt_kenaikan_jenjang) || '-';
            const tst = rec.formatted_tst_kenaikan_jenjang || rec.formatted_tst_jenjang || formatTanggalIndo(rec.tst_kenaikan_jenjang || rec.tst_jenjang) || 'Sekarang / Tetap';

            const rincianRec = [];
            if (rec.no_sk) rincianRec.push(`No SK: ${rec.no_sk}`);
            rincianRec.push(`TMT: ${tmt} • TST: ${tst}`);

            list.push({
                id: `rec_${rec.id}`,
                no: no++,
                nama: `SK Jenjang ${jenjang}`,
                keterangan: rincianRec.join(' • '),
                document: rec.document,
                uploadCategory: 'sk_jenjang',
                recordType: 'record',
                recordId: rec.id,
                uploadTitle: `SK Jenjang ${jenjang}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

// ==========================================
// 3. DOKUMEN RIWAYAT PENUGASAN & JABATAN
// ==========================================
const dokumenJabatanList = computed(() => {
    const list = [];
    let no = 1;

    if (props.employee.riwayat_jabatan && props.employee.riwayat_jabatan.length > 0) {
        props.employee.riwayat_jabatan.forEach((pos) => {
            const jabatan = pos.jabatan || '-';
            const unit = pos.unit_kerja ? `Unit: ${pos.unit_kerja}` : '';
            const jenis = pos.jenis_kepegawaian ? `${pos.jenis_kepegawaian}` : '';
            const tmt = pos.formatted_tmt_jabatan || formatTanggalIndo(pos.tmt_jabatan) || '-';
            const tst = pos.formatted_tst_jabatan || formatTanggalIndo(pos.tst_jabatan) || 'Sekarang / Aktif';

            const rincianParts = [];
            if (unit) rincianParts.push(unit);
            if (jenis) rincianParts.push(jenis);
            if (pos.no_sk) rincianParts.push(`No SK: ${pos.no_sk}`);
            rincianParts.push(`TMT: ${tmt} • TST: ${tst}`);

            list.push({
                id: `pos_${pos.id}`,
                no: no++,
                nama: `SK Penugasan: ${jabatan}`,
                keterangan: rincianParts.join(' • '),
                document: pos.document,
                uploadCategory: 'sk_penugasan',
                recordType: 'position',
                recordId: pos.id,
                uploadTitle: `SK Penugasan ${jabatan}${pos.unit_kerja ? ' (' + pos.unit_kerja + ')' : ''}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

// ==========================================
// 4. DOKUMEN RIWAYAT PENDIDIKAN FORMAL
// ==========================================
const dokumenPendidikanList = computed(() => {
    const list = [];
    let no = 1;

    if (props.employee.riwayat_pendidikan && props.employee.riwayat_pendidikan.length > 0) {
        props.employee.riwayat_pendidikan.forEach((edu) => {
            const jenjang = edu.jenjang_pendidikan || 'Pendidikan';
            const jurusan = edu.jurusan ? ` - ${edu.jurusan}` : '';
            const instansi = edu.instansi_pendidikan || '-';
            const tahun = edu.tahun_lulus ? `Lulus ${edu.tahun_lulus}` : '';
            const biaya = edu.pembiayaan ? `Pembiayaan: ${edu.pembiayaan}` : '';

            const rincianParts = [instansi];
            if (tahun) rincianParts.push(tahun);
            if (biaya) rincianParts.push(biaya);

            list.push({
                id: `edu_${edu.id}`,
                no: no++,
                nama: `Ijazah ${jenjang}${jurusan}`,
                keterangan: rincianParts.join(' • '),
                document: edu.document,
                uploadCategory: 'ijazah',
                recordType: 'education',
                recordId: edu.id,
                uploadTitle: `Ijazah ${jenjang}${jurusan}`,
                isAdditional: false,
            });
        });
    }

    if (props.employee.dokumen_ijazah_tambahan && props.employee.dokumen_ijazah_tambahan.length > 0) {
        props.employee.dokumen_ijazah_tambahan.forEach((doc) => {
            list.push({
                id: `doc_${doc.id}`,
                no: no++,
                nama: doc.title || 'Ijazah Tambahan',
                keterangan: 'Dokumen pendidikan tambahan / sertifikat profesi',
                document: doc,
                uploadCategory: 'ijazah',
                recordType: null,
                recordId: null,
                uploadTitle: doc.title,
                isAdditional: true,
            });
        });
    }

    return list;
});

// ==========================================
// 5. DOKUMEN RIWAYAT PELATIHAN DAN SERTIFIKAT
// ==========================================
const dokumenPelatihanList = computed(() => {
    const list = [];
    let no = 1;

    if (props.employee.riwayat_pelatihan && props.employee.riwayat_pelatihan.length > 0) {
        props.employee.riwayat_pelatihan.forEach((tr) => {
            const judul = tr.keahlian || tr.bidang || 'Pelatihan';
            const bidang = tr.bidang ? `Bidang: ${tr.bidang}` : '';
            const instansi = tr.instansi_penyelenggara || '-';
            const tahun = tr.tahun ? `Tahun ${tr.tahun}` : '';
            const biaya = tr.pembiayaan ? `Pembiayaan: ${tr.pembiayaan}` : '';

            const rincianParts = [instansi];
            if (bidang) rincianParts.push(bidang);
            if (tahun) rincianParts.push(tahun);
            if (biaya) rincianParts.push(biaya);

            list.push({
                id: `tr_${tr.id}`,
                no: no++,
                nama: `Sertifikat ${judul}`,
                keterangan: rincianParts.join(' • '),
                document: tr.document,
                uploadCategory: 'sertifikat_pelatihan',
                recordType: 'training',
                recordId: tr.id,
                uploadTitle: `Sertifikat ${judul}`,
                isAdditional: false,
            });
        });
    }

    return list;
});

// ==========================================
// MODAL UPLOAD STATE & ACTIONS
// ==========================================
const showUploadModal = ref(false);
const uploadCategory = ref('');
const uploadRecordType = ref(null);
const uploadRecordId = ref(null);
const uploadTitle = ref('');
const uploadFile = ref(null);
const uploadFileError = ref('');
const isUploading = ref(false);
const isCompressing = ref(false);
const isDragging = ref(false);
const fileInput = ref(null);

function openUploadModal({ category, recordType = null, recordId = null, title = '' }) {
    uploadCategory.value = category;
    uploadRecordType.value = recordType;
    uploadRecordId.value = recordId;
    uploadTitle.value = title;
    uploadFile.value = null;
    uploadFileError.value = '';
    isCompressing.value = false;
    showUploadModal.value = true;
}

function closeUploadModal() {
    if (isUploading.value || isCompressing.value) return;
    showUploadModal.value = false;
    uploadFile.value = null;
    uploadFileError.value = '';
    isCompressing.value = false;
}

function handleFileChange(event) {
    const file = event.target.files?.[0];
    validateAndSetFile(file);
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer.files?.[0];
    validateAndSetFile(file);
}

async function validateAndSetFile(file) {
    if (!file) return;
    const allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    if (!allowed.includes(file.type) && !/\.(pdf|jpe?g|png|webp)$/i.test(file.name)) {
        uploadFileError.value = 'Format berkas tidak didukung. Harap pilih berkas PDF, JPG, PNG, atau WEBP.';
        uploadFile.value = null;
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        uploadFileError.value = 'Ukuran berkas melebihi batas maksimal 10 MB.';
        uploadFile.value = null;
        return;
    }
    uploadFileError.value = '';

    // Jika berkas adalah gambar (KTP, KK, SK, dsb), kompres otomatis ke JPG beresolusi tinggi (maks 1600px, kualitas 82%)
    // Teks di dokumen tetap sangat tajam dan jelas terbaca, namun ukuran menyusut dari MB ke KB
    if (file.type.startsWith('image/') || /\.(jpe?g|png|webp)$/i.test(file.name)) {
        isCompressing.value = true;
        try {
            const compressed = await compressImageToJpeg(file, {
                maxWidth: 1600,
                maxHeight: 1600,
                quality: 0.82,
            });
            uploadFile.value = compressed;
        } catch {
            uploadFile.value = file;
        } finally {
            isCompressing.value = false;
        }
    } else {
        uploadFile.value = file;
    }
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function submitDocument() {
    if (!uploadFile.value) {
        uploadFileError.value = 'Silakan pilih berkas dokumen terlebih dahulu.';
        return;
    }

    const formData = new FormData();
    formData.append('file', uploadFile.value);
    formData.append('category', uploadCategory.value);
    if (uploadRecordType.value) formData.append('record_type', uploadRecordType.value);
    if (uploadRecordId.value) formData.append('record_id', uploadRecordId.value);
    if (uploadTitle.value) formData.append('title', uploadTitle.value);

    isUploading.value = true;
    router.post(`/staff/${props.employee.id}/documents`, formData, {
        forceFormData: true,
        preserveScroll: true,
        only: ['employee', 'flash'],
        onSuccess: () => {
            showUploadModal.value = false;
            uploadFile.value = null;
            isUploading.value = false;
            showToast('success', page.props.flash?.success || 'Dokumen berhasil diunggah.');
        },
        onError: (errors) => {
            isUploading.value = false;
            uploadFileError.value = Object.values(errors).join(' ') || 'Gagal mengunggah dokumen.';
        },
    });
}

function deleteDocument(doc) {
    if (!doc) return;
    requestDeleteConfirm({
        title: 'Hapus Dokumen',
        message: 'Apakah Anda yakin ingin menghapus berkas dokumen ini?',
        itemLabel: doc.title || 'Dokumen Pegawai',
        onConfirm: () => {
            router.delete(`/staff/${props.employee.id}/documents/${doc.id}`, {
                preserveScroll: true,
                only: ['employee', 'flash'],
                onSuccess: () => {
                    showToast('success', page.props.flash?.success || 'Dokumen berhasil dihapus.');
                },
                onError: () => {
                    showToast('error', page.props.flash?.error || 'Gagal menghapus dokumen.');
                }
            });
        }
    });
}
</script>

<template>
    <div class="space-y-6 mb-8">
        <!-- 1. KOTAK SECTION: DOKUMEN PRIBADI -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Dokumen Pribadi
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ dokumenPribadiList.length }} Dokumen
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium mt-1">
                        Berkas identitas resmi seperti Kartu Tanda Penduduk (KTP) dan Kartu Keluarga (KK)
                    </p>
                </div>
            </div>

            <!-- DESKTOP VIEW -->
            <div class="hidden md:block">
                <div v-if="dokumenPribadiList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Nama Dokumen</th>
                                <th class="px-4 py-3 text-center w-36">Status Unggah</th>
                                <th class="px-4 py-3 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in dokumenPribadiList"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="px-4 py-3 text-center text-slate-500 font-mono text-xs">{{ item.no }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    <div>{{ item.nama }}</div>
                                    <div v-if="item.keterangan" class="text-[11px] text-slate-500 font-normal mt-0.5">
                                        {{ item.keterangan }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="item.document"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sudah Diunggah
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Diunggah
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openPreview(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Pratinjau Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            v-if="item.document.download_url"
                                            :href="item.document.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                            <span>Unduh</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unggah Ulang / Ganti Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">sync</span>
                                            <span>Ganti</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Berkas Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-900 hover:bg-emerald-800 text-yellow-200 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                            title="Unggah Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                            <span>Unggah</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE VIEW -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="item in dokumenPribadiList"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[16px]">badge</span>
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">{{ item.nama }}</h5>
                                <span class="text-[10px] text-slate-400 font-mono">No. {{ item.no }}</span>
                            </div>
                        </div>
                        <span
                            v-if="item.document"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Sudah Ada
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Belum Ada
                        </span>
                    </div>

                    <div v-if="item.keterangan" class="bg-slate-50 rounded-xl p-2.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Rincian</span>
                        <p class="text-xs font-semibold text-slate-700 mt-0.5 break-words">{{ item.keterangan }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                        <template v-if="item.document">
                            <button
                                type="button"
                                @click="openPreview(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                <span>Lihat</span>
                            </button>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700"
                            >
                                <span class="material-symbols-outlined text-[15px]">sync</span>
                                <span>Ganti</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteDocument(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-rose-200 text-rose-600"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-900 text-yellow-200 border border-emerald-950/30"
                            >
                                <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                <span>Unggah</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. KOTAK SECTION: DOKUMEN RIWAYAT KEPEGAWAIAN -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Dokumen Riwayat Kepegawaian
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ dokumenKepegawaianList.length }} Dokumen
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium mt-1">
                        Surat Keputusan (SK) Pengangkatan dan kenaikan jenjang kepegawaian
                    </p>
                </div>
            </div>

            <!-- DESKTOP VIEW -->
            <div class="hidden md:block">
                <div v-if="dokumenKepegawaianList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Nama Dokumen</th>
                                <th class="px-4 py-3 text-center w-36">Status Unggah</th>
                                <th class="px-4 py-3 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in dokumenKepegawaianList"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="px-4 py-3 text-center text-slate-500 font-mono text-xs">{{ item.no }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    <div>{{ item.nama }}</div>
                                    <div v-if="item.keterangan" class="text-[11px] text-slate-500 font-normal mt-0.5">
                                        {{ item.keterangan }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="item.document"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sudah Diunggah
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Diunggah
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openPreview(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Pratinjau Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            v-if="item.document.download_url"
                                            :href="item.document.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                            <span>Unduh</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unggah Ulang / Ganti Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">sync</span>
                                            <span>Ganti</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Berkas Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-900 hover:bg-emerald-800 text-yellow-200 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                            title="Unggah Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                            <span>Unggah</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE VIEW -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="item in dokumenKepegawaianList"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[16px]">article</span>
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">{{ item.nama }}</h5>
                                <span class="text-[10px] text-slate-400 font-mono">No. {{ item.no }}</span>
                            </div>
                        </div>
                        <span
                            v-if="item.document"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Sudah Ada
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Belum Ada
                        </span>
                    </div>

                    <div v-if="item.keterangan" class="bg-slate-50 rounded-xl p-2.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Rincian</span>
                        <p class="text-xs font-semibold text-slate-700 mt-0.5 break-words">{{ item.keterangan }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                        <template v-if="item.document">
                            <button
                                type="button"
                                @click="openPreview(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                <span>Lihat</span>
                            </button>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700"
                            >
                                <span class="material-symbols-outlined text-[15px]">sync</span>
                                <span>Ganti</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteDocument(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-rose-200 text-rose-600"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-900 text-yellow-200 border border-emerald-950/30"
                            >
                                <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                <span>Unggah</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. KOTAK SECTION: DOKUMEN RIWAYAT PENUGASAN & JABATAN -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Dokumen Riwayat Penugasan &amp; Jabatan
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ dokumenJabatanList.length }} Dokumen
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium mt-1">
                        Surat Keputusan (SK) Pengangkatan Jabatan Struktural / Fungsional
                    </p>
                </div>
            </div>

            <!-- DESKTOP VIEW -->
            <div class="hidden md:block">
                <div v-if="dokumenJabatanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Nama Dokumen</th>
                                <th class="px-4 py-3 text-center w-36">Status Unggah</th>
                                <th class="px-4 py-3 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in dokumenJabatanList"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="px-4 py-3 text-center text-slate-500 font-mono text-xs">{{ item.no }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    <div>{{ item.nama }}</div>
                                    <div v-if="item.keterangan" class="text-[11px] text-slate-500 font-normal mt-0.5">
                                        {{ item.keterangan }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="item.document"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sudah Diunggah
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Diunggah
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openPreview(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Pratinjau Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            v-if="item.document.download_url"
                                            :href="item.document.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                            <span>Unduh</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unggah Ulang / Ganti Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">sync</span>
                                            <span>Ganti</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Berkas Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-900 hover:bg-emerald-800 text-yellow-200 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                            title="Unggah Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                            <span>Unggah</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE VIEW -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="item in dokumenJabatanList"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[16px]">work</span>
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">{{ item.nama }}</h5>
                                <span class="text-[10px] text-slate-400 font-mono">No. {{ item.no }}</span>
                            </div>
                        </div>
                        <span
                            v-if="item.document"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Sudah Ada
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Belum Ada
                        </span>
                    </div>

                    <div v-if="item.keterangan" class="bg-slate-50 rounded-xl p-2.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Rincian</span>
                        <p class="text-xs font-semibold text-slate-700 mt-0.5 break-words">{{ item.keterangan }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                        <template v-if="item.document">
                            <button
                                type="button"
                                @click="openPreview(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                <span>Lihat</span>
                            </button>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700"
                            >
                                <span class="material-symbols-outlined text-[15px]">sync</span>
                                <span>Ganti</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteDocument(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-rose-200 text-rose-600"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-900 text-yellow-200 border border-emerald-950/30"
                            >
                                <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                <span>Unggah</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. KOTAK SECTION: DOKUMEN RIWAYAT PENDIDIKAN FORMAL -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Dokumen Riwayat Pendidikan Formal
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ dokumenPendidikanList.length }} Dokumen
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium mt-1">
                        Berkas Ijazah resmi dari jenjang pendidikan formal yang telah diselesaikan
                    </p>
                </div>
            </div>

            <!-- DESKTOP VIEW -->
            <div class="hidden md:block">
                <div v-if="dokumenPendidikanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Nama Dokumen</th>
                                <th class="px-4 py-3 text-center w-36">Status Unggah</th>
                                <th class="px-4 py-3 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in dokumenPendidikanList"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="px-4 py-3 text-center text-slate-500 font-mono text-xs">{{ item.no }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    <div>{{ item.nama }}</div>
                                    <div v-if="item.keterangan" class="text-[11px] text-slate-500 font-normal mt-0.5">
                                        {{ item.keterangan }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="item.document"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sudah Diunggah
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Diunggah
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openPreview(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Pratinjau Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            v-if="item.document.download_url"
                                            :href="item.document.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                            <span>Unduh</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unggah Ulang / Ganti Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">sync</span>
                                            <span>Ganti</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Berkas Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-900 hover:bg-emerald-800 text-yellow-200 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                            title="Unggah Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                            <span>Unggah</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE VIEW -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="item in dokumenPendidikanList"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[16px]">school</span>
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">{{ item.nama }}</h5>
                                <span class="text-[10px] text-slate-400 font-mono">No. {{ item.no }}</span>
                            </div>
                        </div>
                        <span
                            v-if="item.document"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Sudah Ada
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Belum Ada
                        </span>
                    </div>

                    <div v-if="item.keterangan" class="bg-slate-50 rounded-xl p-2.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Rincian</span>
                        <p class="text-xs font-semibold text-slate-700 mt-0.5 break-words">{{ item.keterangan }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                        <template v-if="item.document">
                            <button
                                type="button"
                                @click="openPreview(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                <span>Lihat</span>
                            </button>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700"
                            >
                                <span class="material-symbols-outlined text-[15px]">sync</span>
                                <span>Ganti</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteDocument(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-rose-200 text-rose-600"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-900 text-yellow-200 border border-emerald-950/30"
                            >
                                <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                <span>Unggah</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. KOTAK SECTION: DOKUMEN RIWAYAT PELATIHAN DAN SERTIFIKAT -->
        <div class="bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-100">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-wider leading-tight">
                                Dokumen Riwayat Pelatihan &amp; Sertifikat
                            </h4>
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 shrink-0">
                            {{ dokumenPelatihanList.length }} Dokumen
                        </span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium mt-1">
                        Sertifikat kursus, workshop, dan pelatihan nonformal yang pernah diikuti
                    </p>
                </div>
            </div>

            <!-- DESKTOP VIEW -->
            <div class="hidden md:block">
                <div v-if="dokumenPelatihanList.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-2xs">
                    <table class="w-full text-xs sm:text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3 text-left">Nama Dokumen</th>
                                <th class="px-4 py-3 text-center w-36">Status Unggah</th>
                                <th class="px-4 py-3 text-center w-52">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in dokumenPelatihanList"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="px-4 py-3 text-center text-slate-500 font-mono text-xs">{{ item.no }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    <div>{{ item.nama }}</div>
                                    <div v-if="item.keterangan" class="text-[11px] text-slate-500 font-normal mt-0.5">
                                        {{ item.keterangan }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="item.document"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sudah Diunggah
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Diunggah
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div v-if="item.document" class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openPreview(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200 cursor-pointer"
                                            title="Lihat Pratinjau Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">visibility</span>
                                            <span>Lihat</span>
                                        </button>
                                        <a
                                            v-if="item.document.download_url"
                                            :href="item.document.download_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unduh Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">download</span>
                                            <span>Unduh</span>
                                        </a>
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors cursor-pointer"
                                            title="Unggah Ulang / Ganti Berkas"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">sync</span>
                                            <span>Ganti</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteDocument(item.document)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                                            title="Hapus Berkas Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">delete</span>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                    <div v-else class="inline-flex items-center gap-1.5 justify-center flex-wrap">
                                        <button
                                            type="button"
                                            @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-900 hover:bg-emerald-800 text-yellow-200 shadow-xs border border-emerald-950/30 transition-all cursor-pointer active:scale-95"
                                            title="Unggah Dokumen"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                            <span>Unggah</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MOBILE VIEW -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="item in dokumenPelatihanList"
                    :key="item.id"
                    class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs space-y-3"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[16px]">workspace_premium</span>
                            </span>
                            <div>
                                <h5 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">{{ item.nama }}</h5>
                                <span class="text-[10px] text-slate-400 font-mono">No. {{ item.no }}</span>
                            </div>
                        </div>
                        <span
                            v-if="item.document"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Sudah Ada
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            Belum Ada
                        </span>
                    </div>

                    <div v-if="item.keterangan" class="bg-slate-50 rounded-xl p-2.5 border border-slate-100">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Keterangan / Rincian</span>
                        <p class="text-xs font-semibold text-slate-700 mt-0.5 break-words">{{ item.keterangan }}</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2 flex-wrap">
                        <template v-if="item.document">
                            <button
                                type="button"
                                @click="openPreview(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                            >
                                <span class="material-symbols-outlined text-[15px]">visibility</span>
                                <span>Lihat</span>
                            </button>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700"
                            >
                                <span class="material-symbols-outlined text-[15px]">sync</span>
                                <span>Ganti</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteDocument(item.document)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-rose-200 text-rose-600"
                            >
                                <span class="material-symbols-outlined text-[15px]">delete</span>
                                <span>Hapus</span>
                            </button>
                        </template>
                        <template v-else>
                            <button
                                type="button"
                                @click="openUploadModal({ category: item.uploadCategory, recordType: item.recordType, recordId: item.recordId, title: item.uploadTitle })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-900 text-yellow-200 border border-emerald-950/30"
                            >
                                <span class="material-symbols-outlined text-[15px]">upload_file</span>
                                <span>Unggah</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL UPLOAD DOKUMEN -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showUploadModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                    @click.self="closeUploadModal"
                >
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-white">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[20px]">upload_file</span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Unggah Dokumen Pegawai</h3>
                                    <p class="text-xs text-slate-500 truncate max-w-[280px] sm:max-w-xs">{{ uploadTitle || 'Pilih berkas untuk diunggah' }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="closeUploadModal"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </button>
                        </div>

                        <!-- Modal Form -->
                        <form @submit.prevent="submitDocument" class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Judul Dokumen</label>
                                <input
                                    v-model="uploadTitle"
                                    type="text"
                                    placeholder="Contoh: KTP Pegawai, SK Pengangkatan, Ijazah S1"
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    required
                                />
                            </div>

                            <!-- Dropzone -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Berkas Dokumen</label>
                                <div
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleDrop"
                                    :class="[
                                        'border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer relative',
                                        isDragging ? 'border-emerald-600 bg-emerald-50/20' : 'border-slate-300 hover:border-emerald-600/50 bg-white',
                                        uploadFile ? 'border-emerald-500 bg-emerald-50/20' : ''
                                    ]"
                                    @click="fileInput?.click()"
                                >
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept=".pdf,image/jpeg,image/png,image/webp"
                                        class="hidden"
                                        @change="handleFileChange"
                                    />

                                    <div v-if="isCompressing" class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-4xl text-emerald-600 mb-2 animate-spin">sync</span>
                                        <span class="text-xs font-bold text-slate-800">Mengoptimalkan gambar...</span>
                                        <span class="text-[11px] text-slate-500 mt-0.5">Memperkecil ukuran tanpa mengurangi ketajaman dokumen</span>
                                    </div>
                                    <div v-else-if="uploadFile" class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-4xl text-emerald-600 mb-2">task_alt</span>
                                        <span class="text-xs font-bold text-slate-800 max-w-full truncate px-2">{{ uploadFile.name }}</span>
                                        <span class="text-[11px] text-slate-500 mt-0.5">{{ formatBytes(uploadFile.size) }}</span>
                                        <span class="mt-2 text-[11px] text-emerald-700 font-semibold hover:underline">Klik untuk mengganti berkas</span>
                                    </div>
                                    <div v-else class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-4xl text-emerald-700/70 mb-2">cloud_upload</span>
                                        <p class="text-xs font-bold text-slate-800">Klik atau seret berkas ke sini</p>
                                        <p class="text-[11px] text-slate-500 mt-1">Format didukung: PDF, JPG, PNG, WEBP (Maksimal 10 MB)</p>
                                    </div>
                                </div>
                                <p v-if="uploadFileError" class="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">error</span>
                                    {{ uploadFileError }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    @click="closeUploadModal"
                                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-100 border border-slate-300 transition-colors cursor-pointer"
                                    :disabled="isUploading || isCompressing"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isUploading || isCompressing || !uploadFile"
                                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-900 hover:bg-emerald-800 text-yellow-200 font-bold text-xs shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                                >
                                    <span v-if="isUploading || isCompressing" class="material-symbols-outlined text-[16px] animate-spin">sync</span>
                                    <span>{{ isUploading ? 'Mengunggah...' : (isCompressing ? 'Mengompres...' : 'Unggah Dokumen') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

